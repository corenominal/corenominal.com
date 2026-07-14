<?php

namespace App\Controllers\Bikes\Api;

use App\Controllers\BaseController;
use App\Libraries\Markdown;
use App\Models\BikeModel;
use App\Models\BikeNoteModel;
use CodeIgniter\HTTP\ResponseInterface;

class BikeNotes extends BaseController
{
    /**
     * POST /api/bikes/:bikeId/notes
     */
    public function create(int $bikeId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        if (! (new BikeModel())->find($bikeId)) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Bike not found.']);
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $noteModel = new BikeNoteModel();
        $noteId    = $noteModel->insert([
            'bike_id'   => $bikeId,
            'title'     => trim($json['title'] ?? ''),
            'body'      => $json['body'],
            'body_html' => $this->toHtml($json['body']),
        ], true);

        if (! $noteId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create note.',
            ]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Note created.',
            'id'      => $noteId,
        ]);
    }

    /**
     * PUT /api/bikes/:bikeId/notes/:noteId
     */
    public function update(int $bikeId, int $noteId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $noteModel = new BikeNoteModel();
        $note      = $noteModel->where('bike_id', $bikeId)->find($noteId);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Note not found.']);
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $noteModel->update($noteId, [
            'title'     => trim($json['title'] ?? ''),
            'body'      => $json['body'],
            'body_html' => $this->toHtml($json['body']),
        ]);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Note updated.',
            'id'      => $noteId,
        ]);
    }

    /**
     * DELETE /api/bikes/:bikeId/notes/:noteId
     */
    public function delete(int $bikeId, int $noteId)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $noteModel = new BikeNoteModel();
        $note      = $noteModel->where('bike_id', $bikeId)->find($noteId);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Note not found.']);
        }

        $noteModel->delete($noteId);

        return $this->response->setJSON(['status' => 'success', 'deleted' => $noteId]);
    }

    /**
     * POST /api/bikes/notes/preview
     */
    public function preview(): ResponseInterface
    {
        $json     = $this->request->getJSON(true);
        $markdown = trim($json['markdown'] ?? '');

        if ($markdown === '') {
            return $this->response->setJSON(['html' => '']);
        }

        return $this->response->setJSON(['html' => $this->toHtml($markdown)]);
    }

    private function requireAdmin(): ?ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Forbidden.']);
        }

        return null;
    }

    private function validateInput(array $json): array|bool
    {
        $errors = [];
        $body   = trim($json['body'] ?? '');

        if ($body === '') {
            $errors['body'] = 'Body is required.';
        }

        $title = trim($json['title'] ?? '');
        if (strlen($title) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters.';
        }

        return empty($errors) ? true : $errors;
    }

    private function toHtml(string $markdown): string
    {
        if (trim($markdown) === '') {
            return '';
        }

        try {
            $lib = new Markdown();
            $lib->setMarkdown($markdown);
            return $lib->convert();
        } catch (\Throwable $e) {
            log_message('warning', 'Markdown conversion failed: ' . $e->getMessage());
            return nl2br(esc($markdown));
        }
    }
}
