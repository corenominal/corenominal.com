<?php

namespace App\Controllers\Startpage\Admin;

use App\Controllers\BaseController;
use App\Models\StartShortcutCategoryModel;
use App\Models\StartShortcutModel;

class Shortcuts extends BaseController
{
    private const ICON_UPLOAD_PATH   = FCPATH . 'uploads/startpage/icons/';
    private const ICON_ALLOWED_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'image/webp', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'];
    private const ICON_MAX_SIZE      = 512; // KB

    public function index(): string
    {
        $categoryModel = new StartShortcutCategoryModel();
        $shortcutModel = new StartShortcutModel();

        $categories = $categoryModel->orderBy('sort_order', 'ASC')->findAll();

        foreach ($categories as &$cat) {
            $cat['shortcuts'] = $shortcutModel
                ->where('category_id', $cat['id'])
                ->orderBy('sort_order', 'ASC')
                ->findAll();
        }
        unset($cat);

        $data['categories']       = $categories;
        $data['shortcut_icons']   = $this->getAvailableShortcutIcons();
        $data['js']               = ['startpage/admin/shortcuts'];
        $data['css']              = [];
        $data['title']            = 'Start Page — Shortcuts';
        $data['templateMenu']     = 'admin/sidebar-menu';
        $data['templateMaxWidth'] = '100%';

        return view('startpage/admin/shortcuts', $data);
    }

    // ── Categories ─────────────────────────────────────────────────────────────

    public function categoryAdd(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $name = trim($json['name'] ?? '');

        if ($name === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Category name is required.',
            ]);
        }

        $model     = new StartShortcutCategoryModel();
        $maxOrder  = $model->selectMax('sort_order')->first();
        $sortOrder = ($maxOrder['sort_order'] ?? 0) + 1;

        $id = $model->insert(['name' => $name, 'sort_order' => $sortOrder]);

        return $this->response->setJSON([
            'status' => 'success',
            'id'     => $id,
            'name'   => esc($name),
        ]);
    }

    public function categoryEdit(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $id   = (int) ($json['id'] ?? 0);
        $name = trim($json['name'] ?? '');

        if ($id <= 0 || $name === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Valid ID and name are required.',
            ]);
        }

        $model = new StartShortcutCategoryModel();

        if ($model->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Category not found.',
            ]);
        }

        $model->update($id, ['name' => $name]);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function categoryDelete(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $id   = (int) ($json['id'] ?? 0);

        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Valid ID is required.',
            ]);
        }

        $categoryModel = new StartShortcutCategoryModel();
        $shortcutModel = new StartShortcutModel();

        if ($categoryModel->find($id) === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Category not found.',
            ]);
        }

        $shortcuts = $shortcutModel->where('category_id', $id)->findAll();
        foreach ($shortcuts as $shortcut) {
            $this->deleteIconFile($shortcut['icon_filename'], (int) $shortcut['id']);
            $shortcutModel->delete($shortcut['id']);
        }

        $categoryModel->delete($id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function categoryReorder(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json       = $this->request->getJSON(true);
        $orderedIds = $json['ids'] ?? [];

        if (! is_array($orderedIds) || empty($orderedIds)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ids array is required.',
            ]);
        }

        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds), fn($id) => $id > 0));

        $model = new StartShortcutCategoryModel();
        foreach ($orderedIds as $position => $id) {
            $model->update($id, ['sort_order' => $position + 1]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    // ── Shortcuts ──────────────────────────────────────────────────────────────

    public function shortcutAdd(): \CodeIgniter\HTTP\ResponseInterface
    {
        $categoryId = (int) $this->request->getPost('category_id');
        $name       = trim($this->request->getPost('name') ?? '');
        $url        = trim($this->request->getPost('url') ?? '');

        if ($categoryId <= 0 || $name === '' || $url === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Category, name, and URL are required.',
            ]);
        }

        $categoryModel = new StartShortcutCategoryModel();
        if ($categoryModel->find($categoryId) === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Category not found.',
            ]);
        }

        $iconFilename = $this->handleIconUpload();
        if (is_array($iconFilename)) {
            return $this->response->setStatusCode(400)->setJSON($iconFilename);
        }

        if ($iconFilename === null) {
            $iconFilename = $this->handleExistingIconSelection();
            if (is_array($iconFilename)) {
                return $this->response->setStatusCode(400)->setJSON($iconFilename);
            }
        }

        $model     = new StartShortcutModel();
        $maxOrder  = $model->selectMax('sort_order')->where('category_id', $categoryId)->first();
        $sortOrder = ($maxOrder['sort_order'] ?? 0) + 1;

        $id = $model->insert([
            'category_id'   => $categoryId,
            'name'          => $name,
            'url'           => $url,
            'icon_filename' => $iconFilename ?? '',
            'sort_order'    => $sortOrder,
        ]);

        return $this->response->setJSON([
            'status'        => 'success',
            'id'            => $id,
            'name'          => esc($name),
            'url'           => esc($url),
            'icon_filename' => esc($iconFilename ?? ''),
        ]);
    }

    public function shortcutEdit(): \CodeIgniter\HTTP\ResponseInterface
    {
        $id         = (int) $this->request->getPost('id');
        $categoryId = (int) $this->request->getPost('category_id');
        $name       = trim($this->request->getPost('name') ?? '');
        $url        = trim($this->request->getPost('url') ?? '');

        if ($id <= 0 || $categoryId <= 0 || $name === '' || $url === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'ID, category, name, and URL are required.',
            ]);
        }

        $model    = new StartShortcutModel();
        $existing = $model->find($id);

        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Shortcut not found.',
            ]);
        }

        $categoryModel = new StartShortcutCategoryModel();
        if ($categoryModel->find($categoryId) === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Category not found.',
            ]);
        }

        $iconFilename = $existing['icon_filename'];

        $uploadedFile = $this->request->getFile('icon');
        if ($uploadedFile !== null && $uploadedFile->isValid() && ! $uploadedFile->hasMoved()) {
            $newIcon = $this->handleIconUpload();
            if (is_array($newIcon)) {
                return $this->response->setStatusCode(400)->setJSON($newIcon);
            }
            if ($newIcon !== null) {
                $this->deleteIconFile($existing['icon_filename'], (int) $existing['id']);
                $iconFilename = $newIcon;
            }
        }

        $model->update($id, [
            'category_id'   => $categoryId,
            'name'          => $name,
            'url'           => $url,
            'icon_filename' => $iconFilename,
        ]);

        return $this->response->setJSON([
            'status'        => 'success',
            'icon_filename' => esc($iconFilename),
        ]);
    }

    public function shortcutDelete(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $id   = (int) ($json['id'] ?? 0);

        if ($id <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'Valid ID is required.',
            ]);
        }

        $model    = new StartShortcutModel();
        $existing = $model->find($id);

        if ($existing === null) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Shortcut not found.',
            ]);
        }

        $this->deleteIconFile($existing['icon_filename'], (int) $existing['id']);
        $model->delete($id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function shortcutReorder(): \CodeIgniter\HTTP\ResponseInterface
    {
        $json       = $this->request->getJSON(true);
        $categoryId = (int) ($json['category_id'] ?? 0);
        $orderedIds = $json['ids'] ?? [];

        if ($categoryId <= 0 || ! is_array($orderedIds) || empty($orderedIds)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'category_id and ids array are required.',
            ]);
        }

        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds), fn($id) => $id > 0));

        $model = new StartShortcutModel();
        foreach ($orderedIds as $position => $id) {
            $model->where('category_id', $categoryId)->update($id, ['sort_order' => $position + 1]);
        }

        return $this->response->setJSON(['status' => 'success']);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function handleIconUpload(): string|array|null
    {
        $file = $this->request->getFile('icon');

        if ($file === null || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }

        if ($file->getMimeType() !== null && ! in_array($file->getMimeType(), self::ICON_ALLOWED_TYPES, true)) {
            return [
                'status'  => 'error',
                'message' => 'Invalid file type. Allowed: PNG, JPEG, GIF, WebP, SVG, ICO.',
            ];
        }

        if ($file->getSize() > self::ICON_MAX_SIZE * 1024) {
            return [
                'status'  => 'error',
                'message' => 'Icon file must be under ' . self::ICON_MAX_SIZE . 'KB.',
            ];
        }

        $newName = $file->getRandomName();
        $file->move(self::ICON_UPLOAD_PATH, $newName);

        return $newName;
    }

    private function handleExistingIconSelection(): string|array|null
    {
        $filename = trim((string) ($this->request->getPost('existing_icon_filename') ?? ''));

        if ($filename === '') {
            return null;
        }

        if (! $this->isAvailableShortcutIcon($filename)) {
            return [
                'status'  => 'error',
                'message' => 'Selected icon is no longer available.',
            ];
        }

        return $filename;
    }

    private function getAvailableShortcutIcons(): array
    {
        $shortcutModel = new StartShortcutModel();
        $shortcuts     = $shortcutModel
            ->select('name, icon_filename')
            ->where('icon_filename !=', '')
            ->orderBy('name', 'ASC')
            ->findAll();

        $icons = [];
        foreach ($shortcuts as $shortcut) {
            $filename = (string) $shortcut['icon_filename'];

            if (! $this->isSafeIconFilename($filename) || ! is_file(self::ICON_UPLOAD_PATH . $filename)) {
                continue;
            }

            if (! isset($icons[$filename])) {
                $icons[$filename] = [
                    'filename'    => $filename,
                    'label'       => $shortcut['name'],
                    'usage_count' => 1,
                ];
                continue;
            }

            $icons[$filename]['usage_count']++;
        }

        return array_values($icons);
    }

    private function isAvailableShortcutIcon(string $filename): bool
    {
        if (! $this->isSafeIconFilename($filename) || ! is_file(self::ICON_UPLOAD_PATH . $filename)) {
            return false;
        }

        return (new StartShortcutModel())->where('icon_filename', $filename)->first() !== null;
    }

    private function isSafeIconFilename(string $filename): bool
    {
        return preg_match('/\A[A-Za-z0-9._-]+\z/', $filename) === 1;
    }

    private function deleteIconFile(string $filename, ?int $excludingShortcutId = null): void
    {
        if ($filename === '' || ! $this->isSafeIconFilename($filename)) {
            return;
        }

        $shortcutModel = new StartShortcutModel();
        $shortcutModel->where('icon_filename', $filename);

        if ($excludingShortcutId !== null) {
            $shortcutModel->where('id !=', $excludingShortcutId);
        }

        if ($shortcutModel->first() !== null) {
            return;
        }

        $path = self::ICON_UPLOAD_PATH . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}
