<?php

namespace App\Controllers\Todo\Admin;

class Home extends BaseController
{
    public function index(): string
    {
        $data['js']    = ['todo/admin/home'];
        $data['css']   = ['todo/admin/home'];
        $data['title'] = 'Todo';
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'admin/sidebar-menu';

        return view('todo/admin/home', $data);
    }
}
