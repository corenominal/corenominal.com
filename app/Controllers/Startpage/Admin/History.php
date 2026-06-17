<?php

namespace App\Controllers\Startpage\Admin;

use App\Controllers\BaseController;

class History extends BaseController
{
    public function index(): string
    {
        $data['history']          = model('StartHistoryModel')->orderBy('updated_at', 'DESC')->findAll();
        $data['js']               = ['startpage/admin/history'];
        $data['css']              = [];
        $data['title']            = 'Start Page — History';
        $data['templateMenu']     = 'admin/sidebar-menu';
        $data['templateMaxWidth'] = '100%';

        return view('startpage/admin/history', $data);
    }

    public function delete(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $ids  = $json['ids'] ?? [];

        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No valid IDs provided.',
            ]);
        }

        model('StartHistoryModel')->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
