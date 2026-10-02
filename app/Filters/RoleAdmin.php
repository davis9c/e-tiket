<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleAdmin implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $roleAdmin = getenv('ROLE_ADMIN');

        if (session()->get('kd_jabatan') === $roleAdmin) {
            return;
        }

        // Jangan redirect saat request fetch, agar tidak dapat HTML.
        if ($this->isJson($request)) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 'error',
                    'message' => 'Akses ditolak!',
                ]);
        }

        return redirect()->to('/dashboard')
            ->with('error', 'Akses ditolak!');
    }

    private function isJson(RequestInterface $request): bool
    {
        return $request->isAJAX()
            || str_contains((string) $request->getHeaderLine('Accept'), 'application/json');
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}