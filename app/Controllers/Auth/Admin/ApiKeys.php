<?php
namespace App\Controllers\Auth\Admin;

class ApiKeys extends BaseController
{
    public function index()
    {
        $data['title'] = 'Auth Admin API Keys';
        $data['js'] = ['auth/admin/apikeys'];
        $data['templateMaxWidth'] = '96%';
        $data['templateMenu'] = 'auth/admin/sidebar-menu';
        return view('auth/admin/apikeys', $data);
    }
}
