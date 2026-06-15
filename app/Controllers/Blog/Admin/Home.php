<?php

namespace App\Controllers\Blog\Admin;

use App\Models\PostModel;
use App\Models\PostsTagModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $postModel = new PostModel();
        $tagModel  = new PostsTagModel();

        $stats = [
            'total_posts'     => (new PostModel())->countAll(),
            'published_posts' => (new PostModel())->where('status', 'published')->countAllResults(),
            'draft_posts'     => (new PostModel())->where('status', 'draft')->countAllResults(),
            'trashed_posts'   => (new PostModel())->where('status', 'trashed')->countAllResults(),
            'total_tags'      => $tagModel->countAllResults(),
            'total_views'     => (int) ($postModel->builder()->selectSum('hitcounter')->get()->getRow()->hitcounter ?? 0),
        ];

        $perPage      = 25;
        $search       = trim((string) $this->request->getGet('q'));
        $statusFilter = trim((string) $this->request->getGet('status'));

        $allowedStatuses = ['published', 'draft', 'revision', 'trashed'];

        if ($search !== '') {
            $postModel->groupStart()
                ->like('title', $search)
                ->orLike('slug', $search)
                ->orLike('tags', $search)
                ->groupEnd();
        }

        if (in_array($statusFilter, $allowedStatuses, true)) {
            $postModel->where('status', $statusFilter);
        } else {
            $statusFilter = '';
        }

        $posts = $postModel
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate($perPage);

        return view('blog/admin/home', [
            'title'            => 'Blog Admin',
            'js'               => ['blog/admin/home'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'stats'            => $stats,
            'posts'            => $posts,
            'pager'            => $postModel->pager,
            'search'           => $search,
            'statusFilter'     => $statusFilter,
        ]);
    }

    public function deletePosts(): ResponseInterface
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

        $model = new PostModel();

        $alreadyTrashed = $model->where('status', 'trashed')->whereIn('id', $ids)->findAll();
        $trashedIds     = array_column($alreadyTrashed, 'id');
        $toTrashIds     = array_values(array_diff($ids, $trashedIds));

        if (!empty($toTrashIds)) {
            $model->whereIn('id', $toTrashIds)->set(['status' => 'trashed'])->update();
        }

        if (!empty($trashedIds)) {
            $model->whereIn('id', $trashedIds)->delete(null, true);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'trashed' => count($toTrashIds),
            'deleted' => count($trashedIds),
        ]);
    }
}
