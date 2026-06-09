<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

// Configured in Config/Filters.php
class DebugFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Test if user is authenticated as an admin. If not, redirect to the unauthorised page. This filter is used on all debug routes, so only admins can access those routes and see the debug info.
        $session = session();
        $auth = $session->get('is_admin');
        if (!$auth) {
            // Redirect to unauthorised page
            return redirect()->to('/unauthorised');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }
}
