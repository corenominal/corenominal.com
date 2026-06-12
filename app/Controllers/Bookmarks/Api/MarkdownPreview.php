<?php

namespace App\Controllers\Bookmarks\Api;

use App\Controllers\BaseController;
use App\Libraries\Markdown;

class MarkdownPreview extends BaseController
{
    /**
     * POST /api/bookmarks/markdown/preview
     */
    public function convert()
    {
        $json     = $this->request->getJSON(true);
        $markdown = trim($json['markdown'] ?? '');

        if ($markdown === '') {
            return $this->response->setJSON(['html' => '']);
        }

        try {
            $lib = new Markdown();
            $lib->setMarkdown($markdown);
            $html = $lib->convert();

            return $this->response->setJSON(['html' => $html]);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Markdown conversion failed.',
            ]);
        }
    }
}
