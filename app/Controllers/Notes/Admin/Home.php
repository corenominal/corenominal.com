<?php

namespace App\Controllers\Notes\Admin;

class Home extends BaseController
{
    public function index(): mixed
    {
        helper('cookie');
        if (get_cookie('noteskey') === null) {
            return redirect()->to('/admin/notes/key');
        }

        $data['js']           = ['notes/admin/home'];
        $data['css']          = ['notes/admin/home'];
        $data['title']        = 'Notes';
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu'] = 'notes/admin/sidebar-menu';

        return view('notes/admin/home', $data);
    }
}
