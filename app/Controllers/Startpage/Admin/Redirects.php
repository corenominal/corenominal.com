<?php

namespace App\Controllers\Startpage\Admin;

use App\Controllers\BaseController;
use App\Models\StartRedirectsModel;

class Redirects extends BaseController
{
    public function index(): string
    {
        $model = new StartRedirectsModel();

        $data['redirects']        = $model->orderBy('phrase', 'ASC')->findAll();
        $data['js']               = ['startpage/admin/redirects'];
        $data['css']              = [];
        $data['title']            = 'Start Page — Redirects';
        $data['templateMenu']     = 'admin/sidebar-menu';
        $data['templateMaxWidth'] = '100%';

        return view('startpage/admin/redirects', $data);
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

        $model = new StartRedirectsModel();

        if ($model->where('phrase', $phrase)->first()) {
            return $this->response->setStatusCode(409)->setJSON([
                'status'  => 'error',
                'message' => 'A redirect with that phrase already exists.',
            ]);
        }

        $model->insert([
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

        $model = new StartRedirectsModel();
        $model->update($id, [
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

        $ids = array_values(array_filter(array_map('intval', $ids), fn($id) => $id > 0));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No valid IDs provided.',
            ]);
        }

        (new StartRedirectsModel())->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
