<?php

namespace App\Controllers\Notes\Api;

use CodeIgniter\Database\RawSql;
use CodeIgniter\HTTP\ResponseInterface;
use Ramsey\Uuid\Uuid;
use App\Libraries\Markdown;
use App\Models\NoteModel;
use App\Models\NoteRevisionModel;

class Notes extends BaseController
{
    /**
     * JSON endpoint for notes list with optional search.
     */
    public function list(): ResponseInterface
    {
        helper('cookie');
        $notekey = get_cookie('noteskey');
        if ($notekey === null) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Notes key not set.']);
        }

        $db      = \Config\Database::connect();
        $escaped = $db->escape($notekey);
        $q       = trim($this->request->getGet('q') ?? '');
        $perPage = 20;
        $page    = max(1, (int) ($this->request->getGet('page') ?? 1));

        $model = new NoteModel();
        $notes = $model
            ->select("id, AES_DECRYPT(title, {$escaped}) AS title, AES_DECRYPT(body, {$escaped}) AS body, pinned, updated_at")
            ->orderBy('pinned', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->findAll();

        if ($q !== '') {
            $notes = array_values(
                array_filter($notes, fn($n) => stripos($n['title'] ?? '', $q) !== false || stripos($n['body'] ?? '', $q) !== false)
            );
        }

        $total      = count($notes);
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page       = min($page, $totalPages);
        $notes      = array_slice($notes, ($page - 1) * $perPage, $perPage);
        $notes      = array_map(fn($n) => array_diff_key($n, ['body' => '']), $notes);

        return $this->response->setJSON([
            'notes'       => $notes,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ]);
    }

    /**
     * Return a single decrypted note as JSON.
     */
    public function find(int $id): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        $db      = \Config\Database::connect();
        $escaped = $db->escape($notekey);
        $model   = new NoteModel();
        $select  = "id, note_id, AES_DECRYPT(title, {$escaped}) AS title, AES_DECRYPT(body, {$escaped}) AS body, pinned";
        $note    = $model->select($select)->find($id);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Note not found.']);
        }

        return $this->response->setJSON($note);
    }

    /**
     * Create a new encrypted note.
     */
    public function create(): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        if (! $this->request->is('json')) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Expecting JSON data.']);
        }

        $data = $this->request->getJSON(true);
        $data = $this->saveNote($data, 0, $notekey);

        if (isset($data['error'])) {
            return $this->response->setStatusCode(500)->setJSON($data);
        }

        return $this->response->setJSON(['id' => $data['id']]);
    }

    /**
     * Update an existing note, creating a revision if the body changed.
     */
    public function update(int $id): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        if (! $this->request->is('json')) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Expecting JSON data.']);
        }

        $data = $this->request->getJSON(true);
        $db   = \Config\Database::connect();

        // Pin-only update: update the pinned column directly and return early.
        if (array_key_exists('pinned', $data) && count($data) === 1) {
            $db->table('notes')->where('id', $id)->update(['pinned' => (int) $data['pinned']]);
            return $this->response->setJSON(['id' => $id]);
        }

        // Create a revision when the body has changed.
        if (! array_key_exists('pinned', $data)) {
            $escaped  = $db->escape($notekey);
            $model    = new NoteModel();
            $select   = "id, note_id, hash, AES_DECRYPT(title, {$escaped}) AS title, AES_DECRYPT(body, {$escaped}) AS body, pinned";
            $existing = $model->select($select)->find($id);

            if ($existing && $existing['body'] !== ($data['body'] ?? '')) {
                $revisionModel = new NoteRevisionModel();
                unset($existing['id'], $existing['created_at'], $existing['updated_at']);
                $existing['title'] = new RawSql("AES_ENCRYPT('" . $db->escapeString($existing['title']) . "', {$escaped})");
                $existing['body']  = new RawSql("AES_ENCRYPT('" . $db->escapeString($existing['body']) . "', {$escaped})");
                $revisionModel->save($existing);
            }
        }

        $data = $this->saveNote($data, $id, $notekey);

        if (isset($data['error'])) {
            return $this->response->setStatusCode(500)->setJSON($data);
        }

        return $this->response->setJSON(['id' => $data['id']]);
    }

    /**
     * Delete a note and its revisions.
     */
    public function delete(int $id): ResponseInterface
    {
        $model = new NoteModel();
        $note  = $model->find($id);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Note not found.']);
        }

        $revisionModel = new NoteRevisionModel();
        $revisionModel->where('note_id', $note['note_id'])->delete();
        $model->delete($id);

        return $this->response->setJSON(['status' => 'success', 'deleted' => $id]);
    }

    /**
     * Convert markdown to HTML and return as JSON.
     */
    public function preview(): ResponseInterface
    {
        if (! $this->request->is('json')) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Expecting JSON data.']);
        }

        $data     = $this->request->getJSON(true);
        $markdown = $data['markdown'] ?? '';

        if ($markdown === '') {
            return $this->response->setJSON(['html' => '']);
        }

        try {
            $lib = new Markdown();
            $lib->setMarkdown($markdown);
            $result = $lib->convert();
            return $this->response->setJSON(['html' => $result['html'] ?? '']);
        } catch (\Throwable $e) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Preview unavailable.']);
        }
    }

    /**
     * List revisions for a note.
     */
    public function listRevisions(int $id): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        $model = new NoteModel();
        $note  = $model->select('note_id')->find($id);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Note not found.']);
        }

        $db            = \Config\Database::connect();
        $escaped       = $db->escape($notekey);
        $revisionModel = new NoteRevisionModel();
        $revisions     = $revisionModel
            ->select("id, AES_DECRYPT(title, {$escaped}) AS title, created_at")
            ->where('note_id', $note['note_id'])
            ->orderBy('created_at', 'DESC')
            ->findAll();

        return $this->response->setJSON($revisions);
    }

    /**
     * Return a single decrypted revision as JSON.
     */
    public function findRevision(int $id, int $rid): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        $model = new NoteModel();
        $note  = $model->select('note_id')->find($id);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Note not found.']);
        }

        $db            = \Config\Database::connect();
        $escaped       = $db->escape($notekey);
        $revisionModel = new NoteRevisionModel();
        $revision      = $revisionModel
            ->select("id, AES_DECRYPT(title, {$escaped}) AS title, AES_DECRYPT(body, {$escaped}) AS body, created_at")
            ->where('note_id', $note['note_id'])
            ->find($rid);

        if (! $revision) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Revision not found.']);
        }

        return $this->response->setJSON($revision);
    }

    /**
     * Delete a single revision belonging to a note.
     */
    public function deleteRevision(int $id, int $rid): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        $model = new NoteModel();
        $note  = $model->select('note_id')->find($id);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Note not found.']);
        }

        $revisionModel = new NoteRevisionModel();
        $revision      = $revisionModel->where('note_id', $note['note_id'])->find($rid);

        if (! $revision) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Revision not found.']);
        }

        $revisionModel->delete($rid);

        return $this->response->setJSON(['status' => 'success', 'deleted' => $rid]);
    }

    /**
     * Delete revisions for a note.
     * Pass {"ids": [...]} to delete specific revisions, or omit the body to delete all.
     */
    public function deleteRevisions(int $id): ResponseInterface
    {
        $notekey = $this->getNotekey();
        if ($notekey === '') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Note key not provided.']);
        }

        $model = new NoteModel();
        $note  = $model->select('note_id')->find($id);

        if (! $note) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Note not found.']);
        }

        $data          = $this->request->getJSON(true) ?? [];
        $revisionModel = new NoteRevisionModel();

        if (array_key_exists('ids', $data)) {
            $ids = array_filter(array_map('intval', (array) $data['ids']));
            if (! empty($ids)) {
                $revisionModel->where('note_id', $note['note_id'])->whereIn('id', $ids)->delete();
            }
        } else {
            $revisionModel->where('note_id', $note['note_id'])->delete();
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    /**
     * Read the note key from the request header.
     */
    private function getNotekey(): string
    {
        return $this->request->hasHeader('notekey')
            ? $this->request->header('notekey')->getValue()
            : '';
    }

    /**
     * Encrypt and persist a note (insert or update).
     */
    private function saveNote(array $data, int $id, string $notekey): array
    {
        $db      = \Config\Database::connect();
        $escaped = $db->escape($notekey);
        $model   = new NoteModel();

        if (! array_key_exists('pinned', $data)) {
            $body          = $data['body'] ?? '';
            $data['title'] = ($body === '') ? 'Untitled Note' : ltrim(strtok($body, "\n"), '# ');
            $data['hash']  = sha1($notekey);
            $data['title'] = new RawSql("AES_ENCRYPT('" . $db->escapeString($data['title']) . "', {$escaped})");
            $data['body']  = new RawSql("AES_ENCRYPT('" . $db->escapeString($body) . "', {$escaped})");
        }

        try {
            if ($id === 0) {
                $data['note_id'] = Uuid::uuid4()->toString();
                $data['id']      = $model->insert($data);
            } else {
                $model->skipValidation(true)->update($id, $data);
                $data['id'] = $id;
            }
        } catch (\Throwable $e) {
            return ['error' => 'Failed to save note.'];
        }

        return $data;
    }
}
