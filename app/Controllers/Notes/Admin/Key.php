<?php

namespace App\Controllers\Notes\Admin;

class Key extends BaseController
{
    public function index(): mixed
    {
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu'] = 'notes/admin/sidebar-menu';
        $data['js']    = ['notes/admin/key'];
        $data['title'] = 'Notes — Set Key';

        return view('notes/admin/key', $data);
    }
}
