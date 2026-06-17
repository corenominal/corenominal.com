<?php

namespace App\Controllers\Startpage\Admin;

use App\Controllers\BaseController;
use App\Models\StartSearchModel;

class Search extends BaseController
{
    public function index(): string
    {
        $model = new StartSearchModel();

        $data['search_engines']   = $model->orderBy('phrase', 'ASC')->findAll();
        $data['js']               = ['startpage/admin/search'];
        $data['css']              = [];
        $data['title']            = 'Start Page — Search Engines';
        $data['templateMenu']     = 'admin/sidebar-menu';
        $data['templateMaxWidth'] = '100%';

        return view('startpage/admin/search', $data);
    }

    public function add(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);

        $phrase   = trim($json['phrase'] ?? '');
        $url      = trim($json['url'] ?? '');
        $comments = trim($json['comments'] ?? '');

        if (empty($phrase) || empty($url)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Phrase and URL are required.',
            ]);
        }

        (new StartSearchModel())->insert([
            'phrase'   => $phrase,
            'url'      => $url,
            'comments' => $comments,
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function edit(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);

        $id       = (int) ($json['id'] ?? 0);
        $phrase   = trim($json['phrase'] ?? '');
        $url      = trim($json['url'] ?? '');
        $comments = trim($json['comments'] ?? '');

        if ($id <= 0 || empty($phrase) || empty($url)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ID, phrase, and URL are required.',
            ]);
        }

        (new StartSearchModel())->update($id, [
            'phrase'   => $phrase,
            'url'      => $url,
            'comments' => $comments,
        ]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function delete(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $ids  = $json['ids'] ?? [];

        $ids = array_values(array_filter(array_map('intval', $ids), fn ($id) => $id > 0));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No valid IDs provided.',
            ]);
        }

        (new StartSearchModel())->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
