<?php

namespace App\Controllers;

class Unauthorised extends BaseController
{
    /**
     * Handles the unauthorized access page.
     *
     * @return \CodeIgniter\HTTP\Response|string The rendered view for unauthorized access.
     */
    public function index()
    {
        $data['title'] = 'Access Denied';
        // Return login form view
        return $this->response->setStatusCode(403)->setBody(view('unauthorised', $data));
    }
}
