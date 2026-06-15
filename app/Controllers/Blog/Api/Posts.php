<?php

namespace App\Controllers\Blog\Api;

use App\Models\PostModel;
use App\Models\PostsTagModel;

class Posts extends BaseController
{
    public function latest(): \CodeIgniter\HTTP\ResponseInterface
    {
        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $limit = max(1, min($limit, 50));

        $postModel = new PostModel();

        $posts = $postModel
            ->select('uuid, title, slug, excerpt, featured_image, tags, published_at')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->limit($limit)
            ->findAll();

        if (!empty($posts)) {
            $tagModel   = new PostsTagModel();
            $allPostIds = array_column($posts, 'id');

            if (!empty($allPostIds)) {
                $tags       = $tagModel->whereIn('post_id', $allPostIds)->findAll();
                $tagsByPost = [];
                foreach ($tags as $tag) {
                    $tagsByPost[$tag['post_id']][] = $tag;
                }
                foreach ($posts as &$post) {
                    $post['tags'] = $tagsByPost[$post['id']] ?? [];
                }
                unset($post);
            }
        }

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => $posts,
        ]);
    }
}
