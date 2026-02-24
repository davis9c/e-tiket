<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;


abstract class BaseController extends Controller
{

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        // $this->session = service('session');
    }
    protected function checkToken()
{
    if (!session()->get('token')) {
        header('Location: ' . base_url('login'));
        exit;
    }

    if (strtotime(session()->get('expires')) < time()) {
        header('Location: ' . base_url('logout'));
        exit;
    }
}
}
