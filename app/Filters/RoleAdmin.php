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
        if (session()->get('kd_jabatan') !== $roleAdmin) {
            return redirect()->to('/dashboard')
                ->with('error', 'Akses ditolak!');
        }
    }
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        //
    }
}