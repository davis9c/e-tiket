<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/** A V2 login without a jabatan is valid, but cannot perform unit actions. */
class RequireJabatan implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (session('auth_version') === 2 && session('kd_jabatan')) {
            return null;
        }

        $message = 'Akun Anda belum memiliki jabatan untuk fitur ini.';
        if ($request->isAJAX() || str_contains($request->getHeaderLine('Accept'), 'application/json')) {
            return service('response')->setStatusCode(403)->setJSON([
                'status' => 'error', 'message' => $message,
            ]);
        }

        return redirect()->to(base_url('index'))->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}
