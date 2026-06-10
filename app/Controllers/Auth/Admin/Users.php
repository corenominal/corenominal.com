<?php
namespace App\Controllers\Auth\Admin;

class Users extends BaseController
{
    public function index()
    {
        $data['title'] = 'Auth Admin Users';
        $data['js'] = ['auth/admin/users'];
        $data['templateMaxWidth'] = '96%';
        $data['templateMenu'] = 'auth/admin/sidebar-menu';
        return view('auth/admin/users', $data);
    }
}
