<?php

namespace App\Controllers;

class About extends BaseController
{
    public function index()
    {
        $data['title'] = 'About';
        return view('about', $data);
    }
}
