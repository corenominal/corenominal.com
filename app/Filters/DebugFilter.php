<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class DebugFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (!user_in_group('debug')) {
            return redirect()->to('/unauthorised');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
    }
}
