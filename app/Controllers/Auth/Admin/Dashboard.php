<?php
namespace App\Controllers\Auth\Admin;

class Dashboard extends BaseController
{
    public function index()
    {
        $data['title'] = 'Auth Admin Dashboard';
        $data['js'] = ['auth/admin/dashboard'];
        $data['templateMaxWidth'] = '96%';
        $data['templateMenu'] = 'auth/admin/sidebar-menu';
        return view('auth/admin/dashboard', $data);
    }
}