<?php

namespace App\Controllers\Bookmarks\Admin;

use App\Controllers\BaseController;
use App\Models\BookmarkModel;

class BookmarkForm extends BaseController
{
    public function create(): string
    {
        return view('bookmarks/admin/bookmark_form', [
            'title'            => 'Add Bookmark',
            'js'               => ['bookmarks/admin/bookmark-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'create',
            'bookmark'         => null,
        ]);
    }

    public function edit(string $uuid): mixed
    {
        $bookmark = (new BookmarkModel())->where('uuid', $uuid)->first();

        if (! $bookmark) {
            return redirect()->to(site_url('admin/bookmarks'))->with('error', 'Bookmark not found.');
        }

        return view('bookmarks/admin/bookmark_form', [
            'title'            => 'Edit Bookmark',
            'js'               => ['bookmarks/admin/bookmark-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'edit',
            'bookmark'         => $bookmark,
        ]);
    }
}
