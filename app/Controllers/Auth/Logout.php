<?php
namespace App\Controllers\Auth;

class Logout extends BaseController
{
    public function index()
    {
        // Clear the session and cookies to log the user out
        $this->session->destroy();
        helper('cookie');
        delete_cookie('user_uuid');
        delete_cookie('username');
        delete_cookie('email');
        delete_cookie('apikey');

        $data['title'] = 'Logout';
        return view('auth/logout', $data);
    }
}