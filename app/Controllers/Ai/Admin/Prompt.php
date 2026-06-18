<?php

namespace App\Controllers\Ai\Admin;

use App\Models\SystemPromptModel;

class Prompt extends BaseController
{
    public function index(): string
    {
        $model = new SystemPromptModel();

        $data['revisions']        = $model->orderBy('id', 'DESC')->findAll();
        $data['current']          = $data['revisions'][0] ?? null;
        $data['js']               = ['ai/admin/prompt'];
        $data['css']              = ['ai/admin/prompt'];
        $data['title']            = 'Default Prompt';
        // $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'ai/admin/sidebar-menu';

        return view('ai/admin/prompt', $data);
    }

    public function update(): \CodeIgniter\HTTP\RedirectResponse
    {
        $content = $this->request->getPost('content') ?? '';
        $model   = new SystemPromptModel();
        $current = $model->getActive();

        if ($content === ($current['content'] ?? null)) {
            session()->setFlashdata('info', 'No changes detected — prompt not saved.');
            return redirect()->to('/admin/ai/prompt');
        }

        $model->insert(['content' => $content]);
        session()->setFlashdata('success', 'Prompt saved as a new revision.');

        return redirect()->to('/admin/ai/prompt');
    }

    public function revert(int $id): \CodeIgniter\HTTP\RedirectResponse
    {
        $model    = new SystemPromptModel();
        $revision = $model->find($id);

        if (!$revision) {
            session()->setFlashdata('error', 'Revision not found.');
            return redirect()->to('/admin/ai/prompt');
        }

        $current = $model->getActive();

        if ($revision['content'] === ($current['content'] ?? null)) {
            session()->setFlashdata('info', 'That revision is already the active prompt.');
            return redirect()->to('/admin/ai/prompt');
        }

        $model->insert(['content' => $revision['content']]);
        session()->setFlashdata('success', 'Prompt reverted successfully.');

        return redirect()->to('/admin/ai/prompt');
    }
}
