<?php

namespace App\Services;

use CodeIgniter\HTTP\CURLRequest;
use Config\Services;

/** Server-only HTTP client for the read-only KanzaBridge V2 API. */
class KanzaBridgeClient
{
    private CURLRequest $http;
    private string $baseUrl;
    private string $apiKey;

    public function __construct(?CURLRequest $http = null, ?string $baseUrl = null, ?string $apiKey = null)
    {
        $this->http = $http ?? Services::curlrequest();
        $this->baseUrl = rtrim(trim($baseUrl ?? (string) env('KANZABRIDGE_BASE_URL')), '/') . '/';
        $this->apiKey = trim($apiKey ?? (string) env('KANZABRIDGE_API_KEY'));
    }

    public function get(string $endpoint): array
    {
        return $this->request('get', $endpoint);
    }

    public function post(string $endpoint, ?array $payload = null): array
    {
        return $this->request('post', $endpoint, $payload);
    }

    /** @return array<string, mixed> Response body, including data and message. */
    private function request(string $method, string $endpoint, ?array $payload = null): array
    {
        $parts = parse_url($this->baseUrl);
        if ($this->apiKey === '' || $parts === false
            || !in_array($parts['scheme'] ?? '', ['http', 'https'], true)
            || !str_ends_with($parts['path'] ?? '', '/api/v2/')
            || (ENVIRONMENT === 'production' && $parts['scheme'] !== 'https')) {
            throw new KanzaBridgeException('Konfigurasi KanzaBridge V2 tidak valid.');
        }
        if ($endpoint === '' || str_contains($endpoint, '..') || str_contains($endpoint, '://') || str_starts_with($endpoint, '/')) {
            throw new KanzaBridgeException('Path KanzaBridge V2 tidak valid.');
        }

        $options = [
            'headers' => [
                'X-API-Key' => $this->apiKey,
                'Accept'    => 'application/json',
            ],
            'timeout'     => 10,
            'http_errors' => false,
        ];
        if ($payload !== null) {
            $options['json'] = $payload;
        }

        try {
            $response = $this->http->{$method}($this->baseUrl . $endpoint, $options);
        } catch (\Throwable $e) {
            // Never log the request/options (they contain the API key and may contain a password).
            throw new KanzaBridgeException('KanzaBridge tidak dapat dihubungi.', previous: $e);
        }

        $status = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);
        if (!is_array($body)) {
            throw new KanzaBridgeException('Respons KanzaBridge bukan JSON yang valid.', $status);
        }

        if ($status !== 200) {
            $message = (string) ($body['message'] ?? '');
            $invalidCredentials = $endpoint === 'auth/login' && $status === 401
                && $message === 'User ID atau password salah';
            $scope = $status === 403 && is_string($body['required_scope'] ?? null)
                ? $body['required_scope'] : null;
            $retry = $status === 429 ? $response->getHeaderLine('Retry-After') : '';
            throw new KanzaBridgeException(
                'KanzaBridge menolak permintaan (HTTP ' . $status . ').',
                $status,
                $scope,
                ctype_digit($retry) ? (int) $retry : null,
                $invalidCredentials,
            );
        }

        if (isset($body['status']) && (int) $body['status'] !== 200) {
            throw new KanzaBridgeException('Status respons KanzaBridge tidak sesuai.', $status);
        }

        return $body;
    }
}
