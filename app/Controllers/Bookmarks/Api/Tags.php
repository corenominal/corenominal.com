<?php

namespace App\Controllers\Bookmarks\Api;

use App\Controllers\BaseController;
use App\Models\BookmarkTagModel;

class Tags extends BaseController
{
    /**
     * GET /api/bookmarks/tags
     */
    public function index()
    {
        $rows = (new BookmarkTagModel())
            ->select('tag')
            ->distinct()
            ->orderBy('tag', 'ASC')
            ->findAll();

        $tags = array_column($rows, 'tag');

        return $this->response->setJSON([
            'status' => 'ok',
            'tags'   => $tags,
        ]);
    }
}
