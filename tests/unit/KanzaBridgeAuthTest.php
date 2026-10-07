<?php

namespace Tests\Unit;

use App\Controllers\Auth;
use App\Filters\AuthFilter;
use App\Filters\RequireJabatan;
use CodeIgniter\HTTP\CURLRequest;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Test\CIUnitTestCase;
use Config\Services;

final class AuthV2FakeRequest extends CURLRequest
{
    public int $status = 200;
    public array $responseBody = [];
    public string $url = '';
    public array $options = [];

    public function __construct() {}

    public function post(string $url, array $options = []): ResponseInterface
    {
        $this->url = $url;
        $this->options = $options;
        return service('response')->setStatusCode($this->status)
            ->setBody(json_encode($this->responseBody));
    }
}

final class KanzaBridgeAuthTest extends CIUnitTestCase
{
    private AuthV2FakeRequest $http;

    protected function setUp(): void
    {
        parent::setUp();
        $this->http = new AuthV2FakeRequest();
        Services::injectMock('curlrequest', $this->http);
        session()->remove(['logged_in', 'auth_version', 'token', 'expires', 'kd_jabatan']);
    }

    protected function tearDown(): void
    {
        session()->remove(['logged_in', 'auth_version', 'token', 'expires', 'kd_jabatan']);
        Services::injectMock('curlrequest', null);
        parent::tearDown();
    }

    private function attemptLogin(): array
    {
        $method = (new \ReflectionClass(Auth::class))->getMethod('loginApi');
        $method->setAccessible(true);
        return $method->invoke(new Auth(), '123456', 'sandi');
    }

    public function testV2LoginReturnsProfileWithoutUpstreamToken(): void
    {
        $this->http->responseBody = ['status' => 200, 'message' => 'Login berhasil, tetapi akun Anda belum memiliki jabatan',
            'data' => ['pegawai_id' => '123', 'nik' => '123456', 'nama' => 'Pengguna',
                'kd_jabatan' => null, 'jabatan' => null]];
        $result = $this->attemptLogin();

        $this->assertTrue($result['success']);
        $this->assertSame('https://kanzabridge.invalid/api/v2/auth/login', $this->http->url);
        $this->assertSame(['user_id' => '123456', 'password' => 'sandi'], $this->http->options['json']);
        $this->assertArrayNotHasKey('token', $result['data']);

        // A null jabatan is a successful login, but may not grant unit permissions.
        $auth = new Auth();
        $method = (new \ReflectionClass($auth))->getMethod('setUserSession');
        $method->setAccessible(true);
        $method->invoke($auth, $result['data']);
        $this->assertTrue(session('logged_in'));
        $this->assertSame(2, session('auth_version'));
        $this->assertNull(session('kd_jabatan'));
        $this->assertNull(session('token'));
        $this->assertNull(session('expires'));
    }

    public function testLoginErrorDistinguishesPasswordFromInvalidApiKey(): void
    {
        $this->http->status = 401;
        $this->http->responseBody = ['status' => 401, 'message' => 'User ID atau password salah'];
        $this->assertSame('User ID atau password salah', $this->attemptLogin()['message']);

        $this->http->responseBody = ['status' => 401, 'message' => 'API key tidak valid.'];
        $this->assertSame('Layanan login sedang bermasalah.', $this->attemptLogin()['message']);
    }

    public function testLoginRejectsScopeErrorAndIncompleteProfile(): void
    {
        $this->http->status = 403;
        $this->http->responseBody = ['status' => 403, 'required_scope' => 'auth.login'];
        $this->assertSame('Layanan login sedang bermasalah.', $this->attemptLogin()['message']);

        $this->http->status = 200;
        $this->http->responseBody = ['status' => 200, 'data' => ['pegawai_id' => '123']];
        $this->assertSame('Layanan login sedang bermasalah.', $this->attemptLogin()['message']);
    }

    public function testOldV1SessionIsNotAccepted(): void
    {
        session()->set(['logged_in' => true, 'token' => 'legacy-token']);
        $response = (new AuthFilter())->before(service('request'));
        $this->assertNotNull($response);

        session()->set(['auth_version' => 2]);
        $this->assertNull((new AuthFilter())->before(service('request')));
    }

    public function testUserWithoutJabatanCannotUseUnitActions(): void
    {
        session()->set(['logged_in' => true, 'auth_version' => 2, 'kd_jabatan' => null]);
        $this->assertNotNull((new RequireJabatan())->before(service('request')));

        session()->set('kd_jabatan', 'J002');
        $this->assertNull((new RequireJabatan())->before(service('request')));
    }
}
