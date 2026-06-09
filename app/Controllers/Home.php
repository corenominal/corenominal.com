<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index()
    {
        // Check if there are any users in the database, and if not, redirect to the login page to encourage setup.
        $userModel = model('UserModel');
        if ($userModel->countAllResults() === 0) {
            return redirect()->to('/auth/register');
        }
    
        $data['title'] = 'Under Construction';
        return view('under-construction', $data);
    }
}
