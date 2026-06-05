<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $data['title'] = 'Under Construction';
        return view('under-construction', $data);
    }
}
