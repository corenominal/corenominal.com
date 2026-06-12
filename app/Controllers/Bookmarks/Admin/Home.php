<?php

namespace App\Controllers\Bookmarks\Admin;

use App\Controllers\BaseController;
use App\Models\BookmarkModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $bookmarkModel = new BookmarkModel();

        $viewRow = (new BookmarkModel())->selectSum('hitcounter', 'total')->first();

        $stats = [
            'total'   => (new BookmarkModel())->countAllResults(),
            'public'  => (new BookmarkModel())->where('private', 0)->countAllResults(),
            'private' => (new BookmarkModel())->where('private', 1)->countAllResults(),
            'views'   => isset($viewRow['total']) ? (int) $viewRow['total'] : 0,
        ];

        $perPage = 25;
        $search  = trim((string) $this->request->getGet('q'));

        if ($search !== '') {
            $bookmarkModel
                ->groupStart()
                ->like('title', $search)
                ->orLike('url', $search)
                ->orLike('tags', $search)
                ->groupEnd();
        }

        $bookmarks = $bookmarkModel
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        return view('bookmarks/admin/home', [
            'title'            => 'Bookmarks — Admin',
            'js'               => ['bookmarks/admin/home'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'stats'            => $stats,
            'bookmarks'        => $bookmarks,
            'pager'            => $bookmarkModel->pager,
            'search'           => $search,
        ]);
    }

    public function delete(): ResponseInterface
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

        (new BookmarkModel())->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
