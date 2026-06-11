<?php

namespace App\Controllers\Status\Api;

use App\Models\StatusDraftModel;
use App\Models\StatusMediaModel;
use CodeIgniter\HTTP\ResponseInterface;
use Ramsey\Uuid\Uuid;

class Drafts extends BaseController
{
    public function index(): ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden.']);
        }

        $draftModel = new StatusDraftModel();
        $drafts     = $draftModel->orderBy('created_at', 'DESC')->findAll();
        $mediaModel = new StatusMediaModel();

        foreach ($drafts as &$draft) {
            $draft['media'] = $this->hydrateMedia($draft['media_ids'] ?? [], $mediaModel);
        }

        unset($draft);

        return $this->response->setJSON(['status' => 'success', 'data' => $drafts]);
    }

    public function create(): ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden.']);
        }

        $content  = trim((string) ($this->request->getPost('content') ?? ''));
        $mediaIds = $this->parseMediaIds($this->request->getPost('media_ids'));

        $draftModel = new StatusDraftModel();
        $draftModel->insert([
            'uuid'      => Uuid::uuid4()->toString(),
            'content'   => $content,
            'media_ids' => $mediaIds,
        ]);

        $draft          = $draftModel->find($draftModel->getInsertID());
        $mediaModel     = new StatusMediaModel();
        $draft['media'] = $this->hydrateMedia($draft['media_ids'] ?? [], $mediaModel);

        return $this->response->setStatusCode(201)->setJSON(['status' => 'success', 'data' => $draft]);
    }

    public function update(int $id): ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden.']);
        }

        $draftModel = new StatusDraftModel();
        $draft      = $draftModel->find($id);

        if (! $draft) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Draft not found.']);
        }

        $body  = $this->request->getJSON(true) ?: [];
        $post  = $this->request->getPost() ?: [];
        $input = array_merge($post, $body);

        $update = [];

        if (array_key_exists('content', $input)) {
            $update['content'] = trim((string) $input['content']);
        }

        if (array_key_exists('media_ids', $input)) {
            $update['media_ids'] = $this->parseMediaIds($input['media_ids']);
        }

        if (! empty($update)) {
            $draftModel->update($id, $update);
        }

        $draft          = $draftModel->find($id);
        $mediaModel     = new StatusMediaModel();
        $draft['media'] = $this->hydrateMedia($draft['media_ids'] ?? [], $mediaModel);

        return $this->response->setJSON(['status' => 'success', 'data' => $draft]);
    }

    public function delete(int $id): ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'Forbidden.']);
        }

        $draftModel = new StatusDraftModel();
        $draft      = $draftModel->find($id);

        if (! $draft) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Draft not found.']);
        }

        $draftModel->delete($id);

        return $this->response->setJSON(['status' => 'success']);
    }

    private function parseMediaIds(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = $raw !== null ? [$raw] : [];
        }

        return array_values(array_filter(array_map('intval', $raw), fn ($id) => $id > 0));
    }

    private function hydrateMedia(array $mediaIds, StatusMediaModel $mediaModel): array
    {
        if (empty($mediaIds)) {
            return [];
        }

        $ids  = array_values(array_filter(array_map('intval', $mediaIds), fn ($id) => $id > 0));
        $rows = $mediaModel->whereIn('id', $ids)->findAll();
        $byId = [];

        foreach ($rows as $row) {
            $byId[(int) $row['id']] = [
                'id'          => (int) $row['id'],
                'description' => (string) ($row['description'] ?? ''),
                'url'         => '/uploads/status/media/' . ($row['file_name'] ?? ''),
                'mime_type'   => (string) ($row['mime_type'] ?? ''),
                'width'       => (int) ($row['width'] ?? 0),
                'height'      => (int) ($row['height'] ?? 0),
            ];
        }

        $media = [];

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $media[] = $byId[$id];
            }
        }

        return $media;
    }
}
