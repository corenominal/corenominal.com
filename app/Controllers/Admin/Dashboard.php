<?php

namespace App\Controllers\Admin;

use CodeIgniter\Controller;

class Dashboard extends Controller
{
    public function index()
    {
        $data['title'] = 'Admin';
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu'] = 'admin/sidebar-menu';
        return view('admin/dashboard', $data);
    }
}
