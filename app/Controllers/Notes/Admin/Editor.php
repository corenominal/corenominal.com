<?php

namespace App\Controllers\Notes\Admin;

class Editor extends BaseController
{
    public function new(): mixed
    {
        helper('cookie');
        if (get_cookie('noteskey') === null) {
            return redirect()->to('/admin/notes/key');
        }

        $data['js']           = ['vendor/marked.min', 'notes/admin/editor', 'notes/admin/markdown-expanders'];
        $data['css']          = ['notes/admin/editor'];
        $data['title']        = 'New Note';
        $data['templateMaxWidth'] = '100%';
        $data['note_id']      = null;
        $data['masterKey']    = config('ApiKeys')->masterKey;
        $data['templateMenu'] = 'notes/admin/sidebar-menu';

        return view('notes/admin/editor', $data);
    }

    public function edit(int $id): mixed
    {
        helper('cookie');
        if (get_cookie('noteskey') === null) {
            return redirect()->to('/admin/notes/key');
        }

        $data['js']           = ['vendor/marked.min', 'notes/admin/editor', 'notes/admin/markdown-expanders'];
        $data['css']          = ['notes/admin/editor'];
        $data['title']        = 'Edit Note';
        $data['templateMaxWidth'] = '100%';
        $data['note_id']      = $id;
        $data['masterKey']    = config('ApiKeys')->masterKey;
        $data['templateMenu'] = 'notes/admin/sidebar-menu';

        return view('notes/admin/editor', $data);
    }
}
