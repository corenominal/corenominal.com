<?php

namespace App\Controllers\Status\Admin;

use App\Controllers\BaseController;

class Generator extends BaseController
{
    public function index(): string
    {
        $db = \Config\Database::connect();

        $rows = $db->query(
            "SELECT content FROM statuses
             WHERE deleted_at IS NULL
               AND (in_reply_to_id IS NULL OR in_reply_to_id = '')
               AND content != ''
               AND CHAR_LENGTH(content) > 60
             ORDER BY RAND()
             LIMIT 25"
        )->getResultArray();

        $voiceSamples = array_map('strip_tags', array_column($rows, 'content'));

        return view('status/admin/generator', [
            'title'            => 'Status Generator',
            'js'               => ['status/admin/generator'],
            'css'              => ['status/admin/generator'],
            'templateMenu'     => 'admin/sidebar-menu',
            'templateMaxWidth' => '860px',
            'voiceSamples'     => $voiceSamples,
        ]);
    }
}
