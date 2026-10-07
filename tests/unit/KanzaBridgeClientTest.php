<?php

namespace Tests\Unit;

use App\Services\KanzaBridgeClient;
use App\Services\KanzaBridgeException;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use RuntimeException;

/** No parent constructor or HTTP transport: every request is captured locally. */
final class KanzaBridgeFakeCurlRequest extends CURLRequest
{
    /** @var list<array{method: string, url: string, options: array}> */
    public array $calls = [];

    /** @var list<ResponseInterface|\Throwable> */
    private array $replies = [];

    public function __construct()
    {
    }

    public function enqueue(ResponseInterface|\Throwable $reply): void
    {
        $this->replies[] = $reply;
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->sendFake('get', $url, $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->sendFake('post', $url, $options);
    }

    private function sendFake(string $method, string $url, array $options): ResponseInterface
    {
        $this->calls[] = compact('method', 'url', 'options');
        $reply = array_shift($this->replies);
        if ($reply === null) {
            throw new RuntimeException('Unexpected HTTP request in unit test');
        }
        if ($reply instanceof \Throwable) {
            throw $reply;
        }

        return $reply;
    }
}

/**
 * @internal
 */
final class KanzaBridgeClientTest extends CIUnitTestCase
{
    private const BASE_URL = 'https://bridge.example.test/api/v2/';
    private const API_KEY = 'unit-test-server-key';

    private function client(KanzaBridgeFakeCurlRequest $http, ?string $baseUrl = self::BASE_URL, ?string $apiKey = self::API_KEY): KanzaBridgeClient
    {
        return new KanzaBridgeClient($http, $baseUrl, $apiKey);
    }

    private function response(int $status, string $body, array $headers = []): ResponseInterface
    {
        $response = new Response(new App());
        $response->setStatusCode($status)->setBody($body);
        foreach ($headers as $name => $value) {
            $response->setHeader($name, $value);
        }

        return $response;
    }

    private function failure(callable $request): KanzaBridgeException
    {
        try {
            $request();
        } catch (KanzaBridgeException $exception) {
            return $exception;
        }

        $this->fail('Expected KanzaBridgeException');
    }

    public function testGetUsesV2UrlAndServerOnlyApiKeyHeaderWithoutJsonBody(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $body = ['status' => 200, 'data' => [['id' => 7]], 'message' => 'OK'];
        $http->enqueue($this->response(200, (string) json_encode($body)));

        $this->assertSame($body, $this->client($http, '  ' . self::BASE_URL . '//  ')->get('profile?user_id=7'));
        $this->assertCount(1, $http->calls);
        $call = $http->calls[0];
        $this->assertSame('get', $call['method']);
        $this->assertSame(self::BASE_URL . 'profile?user_id=7', $call['url']);
        $this->assertSame(self::API_KEY, $call['options']['headers']['X-API-Key']);
        $this->assertSame('application/json', $call['options']['headers']['Accept']);
        $this->assertSame(10, $call['options']['timeout']);
        $this->assertFalse($call['options']['http_errors']);
        $this->assertArrayNotHasKey('json', $call['options']);
        $this->assertStringNotContainsString(self::API_KEY, $call['url']);
    }

    public function testPostLoginSendsJsonAndReturnsDecodedProfileUnchanged(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $payload = ['user_id' => 'tester', 'password' => 'unit-test-password'];
        $body = ['status' => 200, 'message' => 'Berhasil', 'data' => ['profile' => ['user_id' => 'tester', 'scope' => ['profile:read']]]];
        $http->enqueue($this->response(200, (string) json_encode($body)));

        $this->assertSame($body, $this->client($http)->post('auth/login', $payload));
        $this->assertSame('post', $http->calls[0]['method']);
        $this->assertSame(self::BASE_URL . 'auth/login', $http->calls[0]['url']);
        $this->assertSame($payload, $http->calls[0]['options']['json']);
        $this->assertSame(self::API_KEY, $http->calls[0]['options']['headers']['X-API-Key']);
        $this->assertStringNotContainsString($payload['password'], $http->calls[0]['url']);
    }

    public function testPostWithoutPayloadOmitsJsonOption(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $http->enqueue($this->response(200, '{"data":[]}'));

        $this->assertSame(['data' => []], $this->client($http)->post('auth/logout'));
        $this->assertSame('post', $http->calls[0]['method']);
        $this->assertArrayNotHasKey('json', $http->calls[0]['options']);
    }

    public function testOnlyExactLoginCredentialErrorIsMarkedInvalidCredentials(): void
    {
        foreach ([
            ['auth/login', 'User ID atau password salah', true],
            ['auth/login', 'Invalid API key', false],
            ['profile', 'User ID atau password salah', false],
        ] as [$endpoint, $message, $invalidCredentials]) {
            $http = new KanzaBridgeFakeCurlRequest();
            $http->enqueue($this->response(401, (string) json_encode(['message' => $message])));

            $error = $this->failure(fn () => $this->client($http)->post($endpoint, []));
            $this->assertSame(401, $error->status);
            $this->assertSame($invalidCredentials, $error->invalidCredentials);
            $this->assertNull($error->requiredScope);
            $this->assertNull($error->retryAfter);
            $this->assertStringNotContainsString($message, $error->getMessage());
        }
    }

    public function testForbiddenResponseExposesRequiredScopeWithoutLeakingUpstreamMessage(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $http->enqueue($this->response(403, '{"message":"private upstream detail","required_scope":"tickets:read"}'));

        $error = $this->failure(fn () => $this->client($http)->get('tickets'));
        $this->assertSame(403, $error->status);
        $this->assertSame('tickets:read', $error->requiredScope);
        $this->assertFalse($error->invalidCredentials);
        $this->assertStringNotContainsString('private upstream detail', $error->getMessage());
    }

    public function testRateLimitReadsNumericRetryAfterHeader(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $http->enqueue($this->response(429, '{"message":"Too many requests"}', ['Retry-After' => '45']));

        $error = $this->failure(fn () => $this->client($http)->get('profile'));
        $this->assertSame(429, $error->status);
        $this->assertSame(45, $error->retryAfter);
        $this->assertNull($error->requiredScope);
    }

    public function testNonNumericRetryAfterIsNotTreatedAsSeconds(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $http->enqueue($this->response(429, '{}', ['Retry-After' => 'Wed, 21 Oct 2030 07:28:00 GMT']));

        $this->assertNull($this->failure(fn () => $this->client($http)->get('profile'))->retryAfter);
    }

    public function testMalformedJsonFailsClosedAndPreservesHttpStatus(): void
    {
        foreach ([200, 502] as $status) {
            $http = new KanzaBridgeFakeCurlRequest();
            $http->enqueue($this->response($status, '<html>not JSON</html>'));

            $error = $this->failure(fn () => $this->client($http)->get('profile'));
            $this->assertSame($status, $error->status);
            $this->assertFalse($error->invalidCredentials);
        }
    }

    public function testConflictingStatusInsideSuccessfulJsonIsRejected(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $http->enqueue($this->response(200, '{"status":401,"message":"not authorized"}'));

        $error = $this->failure(fn () => $this->client($http)->get('profile'));
        $this->assertSame(200, $error->status);
        $this->assertFalse($error->invalidCredentials);
    }

    public function testMissingBaseUrlOrApiKeyFailsBeforeAnyHttpCall(): void
    {
        foreach ([['', self::API_KEY], [self::BASE_URL, ''], ['', '']] as [$baseUrl, $apiKey]) {
            $http = new KanzaBridgeFakeCurlRequest();
            $this->failure(fn () => $this->client($http, $baseUrl, $apiKey)->get('profile'));
            $this->assertSame([], $http->calls);
        }
    }

    public function testV1BasePathAndUntrustedEndpointsFailBeforeAnyHttpCall(): void
    {
        foreach (['https://bridge.example.test/api/v1/', 'https://bridge.example.test/api/v2/../v1/'] as $baseUrl) {
            $http = new KanzaBridgeFakeCurlRequest();
            $this->failure(fn () => $this->client($http, $baseUrl)->get('profile'));
            $this->assertSame([], $http->calls);
        }
        foreach (['', '../api/v1/profile', '/profile', 'https://elsewhere.example.test/profile'] as $endpoint) {
            $http = new KanzaBridgeFakeCurlRequest();
            $this->failure(fn () => $this->client($http)->get($endpoint));
            $this->assertSame([], $http->calls);
        }
    }

    public function testTransportFailureIsWrappedWithoutLeakingSecrets(): void
    {
        $http = new KanzaBridgeFakeCurlRequest();
        $http->enqueue(new RuntimeException('connection failed: ' . self::API_KEY));

        $error = $this->failure(fn () => $this->client($http)->get('profile'));
        $this->assertSame(0, $error->status);
        $this->assertStringNotContainsString(self::API_KEY, $error->getMessage());
        $this->assertInstanceOf(RuntimeException::class, $error->getPrevious());
    }
}
