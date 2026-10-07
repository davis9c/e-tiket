<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // V1 login sessions must not survive the switch to application keys.
        if (session()->get('logged_in') && session()->get('auth_version') === 2) {
            return;
        }

        // Sesi habis saat request fetch: balas JSON, jangan redirect HTML.
        if ($this->isJson($request)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Sesi habis, silakan login kembali.',
                    'redirect' => base_url('login'),
                ]);
        }

        return redirect()->to(base_url('login'));
    }

    private function isJson(RequestInterface $request): bool
    {
        return $request->isAJAX()
            || str_contains((string) $request->getHeaderLine('Accept'), 'application/json');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}