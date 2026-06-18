<?php

namespace App\Controllers\Ai\Admin;

class Chat extends BaseController
{
    public function index(): string
    {
        return $this->renderChat(null);
    }

    public function session(string $uuid): string
    {
        return $this->renderChat($uuid);
    }

    private function renderChat(?string $uuid): string
    {
        $data['title']            = 'Chat';
        $data['uuid']             = $uuid;
        $data['js']               = ['vendor/marked.min', 'vendor/highlight.min', 'ai/admin/chat'];
        $data['css']              = ['vendor/highlight-dracula.min', 'ai/admin/chat'];
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'ai/admin/sidebar-menu';
        $data['icon16'] = '/assets/img/icons/ai/icon-16x16.png';
        $data['icon32'] = '/assets/img/icons/ai/icon-32x32.png';
        $data['manifest'] = '/assets/img/icons/ai/manifest.json';

        return view('ai/admin/chat', $data);
    }
}
