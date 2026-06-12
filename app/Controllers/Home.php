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
        // If logged in show home page, otherwise show under construction page
        if(is_logged_in()) {
            // Get the latest status post
            $model  = model('StatusModel');
            $status = $model->orderBy('created_at', 'DESC')->first();

            $data['status']          = $status !== null ? status_with_media($status) : null;
            $data['mastodonHandle']  = config('Mastodon')->account;
            $data['mastodonProfile'] = config('Mastodon')->profile;
            $data['js']              = ['home'];
            $data['css']             = ['status/timeline'];
            $data['title']           = 'Tech Enthusiast and Web Developer';
            return view('home', $data);
        } else {
            $data['title'] = 'Under Construction';
            return view('under-construction', $data);
        }
    }
}
