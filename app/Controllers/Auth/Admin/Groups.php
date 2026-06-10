<?php
namespace App\Controllers\Auth\Admin;

class Groups extends BaseController
{
    public function index()
    {
        $data['title'] = 'Auth Admin Groups';
        $data['js'] = ['auth/admin/groups'];
        $data['templateMaxWidth'] = '96%';
        $data['templateMenu'] = 'auth/admin/sidebar-menu';
        return view('auth/admin/groups', $data);
    }
}
