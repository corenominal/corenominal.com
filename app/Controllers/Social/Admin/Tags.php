<?php

namespace App\Controllers\Social\Admin;

use App\Models\SocialVerificationTagModel;
use Ramsey\Uuid\Uuid;

class Tags extends BaseController
{
    public function index()
    {
        $data['title']            = 'Social Admin — Verification Tags';
        $data['js']               = ['social/admin/tags'];
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'social/admin/sidebar-menu';
        return view('social/admin/tags', $data);
    }

    public function getData()
    {
        $model = new SocialVerificationTagModel();

        $page    = max(1, (int)($this->request->getGet('page') ?? 1));
        $perPage = in_array((int)($this->request->getGet('per_page') ?? 20), [10, 20, 50, 100])
                   ? (int)$this->request->getGet('per_page')
                   : 20;
        $search  = trim((string)($this->request->getGet('search') ?? ''));
        $sortCol = $this->request->getGet('sort') ?? 'id';
        $sortDir = strtolower((string)($this->request->getGet('dir') ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';

        $allowed = ['id', 'name', 'url', 'created_at'];
        if (!in_array($sortCol, $allowed)) {
            $sortCol = 'id';
        }

        if ($search !== '') {
            $model->groupStart()
                  ->like('name', $search)
                  ->orLike('url', $search)
                  ->groupEnd();
        }

        $total  = $model->countAllResults(false);
        $offset = ($page - 1) * $perPage;

        $tags = $model->select('id, uuid, name, url, created_at')
                      ->orderBy($sortCol, $sortDir)
                      ->findAll($perPage, $offset);

        return $this->response->setJSON([
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => $total > 0 ? (int)ceil($total / $perPage) : 0,
            'tags'     => $tags,
        ]);
    }

    public function getTag(int $id)
    {
        $model = new SocialVerificationTagModel();
        $tag   = $model->select('id, uuid, name, url, created_at')->find($id);

        if (!$tag) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tag not found.']);
        }

        return $this->response->setJSON($tag);
    }

    public function createTag()
    {
        $name = trim((string)($this->request->getPost('name') ?? ''));
        $url  = trim((string)($this->request->getPost('url') ?? ''));

        if ($name === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Name is required.']);
        }

        if ($url === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'URL is required.']);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'URL is not valid.']);
        }

        $model = new SocialVerificationTagModel();

        $model->insert([
            'uuid' => Uuid::uuid4()->toString(),
            'name' => $name,
            'url'  => $url,
        ]);

        logit("Admin created social verification tag \"{$name}\".");

        return $this->response->setJSON(['success' => true]);
    }

    public function updateTag(int $id)
    {
        $model = new SocialVerificationTagModel();

        if (!$model->find($id)) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tag not found.']);
        }

        $name = trim((string)($this->request->getPost('name') ?? ''));
        $url  = trim((string)($this->request->getPost('url') ?? ''));

        if ($name === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Name is required.']);
        }

        if ($url === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'URL is required.']);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'URL is not valid.']);
        }

        $model->update($id, [
            'name' => $name,
            'url'  => $url,
        ]);

        logit("Admin updated social verification tag ID {$id} (\"{$name}\").");

        return $this->response->setJSON(['success' => true]);
    }

    public function deleteTag(int $id)
    {
        $model = new SocialVerificationTagModel();

        $tag = $model->select('id, name')->find($id);
        if (!$tag) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Tag not found.']);
        }

        $model->delete($id);

        logit("Admin deleted social verification tag ID {$id} (\"{$tag['name']}\").", 1);

        return $this->response->setJSON(['success' => true]);
    }

    public function bulkDelete()
    {
        $ids = $this->request->getPost('ids');

        if (!is_array($ids) || empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No IDs provided.']);
        }

        $ids = array_filter(array_map('intval', $ids));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No valid IDs provided.']);
        }

        $model = new SocialVerificationTagModel();
        $model->delete($ids);

        logit('Admin bulk deleted ' . count($ids) . ' social verification tag(s): IDs ' . implode(', ', $ids) . '.', 1);

        return $this->response->setJSON(['success' => true, 'deleted' => count($ids)]);
    }
}
