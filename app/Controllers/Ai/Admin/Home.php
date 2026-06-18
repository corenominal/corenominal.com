<?php

namespace App\Controllers\Ai\Admin;

use App\Models\ChatSessionModel;
use App\Models\ChatMessageModel;

class Home extends BaseController
{
    public function index(): string
    {
        $sessions = new ChatSessionModel();
        $messages = new ChatMessageModel();

        $data['stats'] = [
            'sessions' => $sessions->countAll(),
            'messages' => $messages->countAll(),
            'pinned'   => $sessions->where('pinned', 1)->countAllResults(),
            'avg'      => $sessions->countAll() > 0
                ? round($messages->countAll() / $sessions->countAll(), 1)
                : 0,
        ];

        $data['recent_sessions'] = $sessions->orderBy('created_at', 'DESC')->limit(8)->find();

        $data['title']            = 'AI Chat';
        // $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'ai/admin/sidebar-menu';

        return view('ai/admin/home', $data);
    }
}
