<?php

namespace App\Controllers;

class About extends BaseController
{
    public function index()
    {
        // $data['js']    = [];
        // $data['css']   = [];
        $data['title'] = 'About';
        return view('about', $data);
    }
}
