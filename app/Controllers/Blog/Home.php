<?php

namespace App\Controllers\Blog;

use App\Models\PostModel;
use App\Models\PostsMetaModel;
use App\Models\PostsTagModel;

class Home extends BaseController
{
    private const POSTS_PER_PAGE = 10;

    public function index(): string
    {
        $postModel = new PostModel();

        $latestPost = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->first();

        $otherPosts   = [];
        $hasMorePosts = false;
        if ($latestPost) {
            $otherPosts = $postModel
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->where('id !=', $latestPost['id'])
                ->orderBy('published_at', 'DESC')
                ->limit(self::POSTS_PER_PAGE)
                ->findAll();

            $totalOtherPosts = $postModel
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->where('id !=', $latestPost['id'])
                ->countAllResults();

            $hasMorePosts = $totalOtherPosts > self::POSTS_PER_PAGE;
        }

        $allPostIds = [];
        if ($latestPost) {
            $allPostIds[] = $latestPost['id'];
        }
        foreach ($otherPosts as $p) {
            $allPostIds[] = $p['id'];
        }

        $tagsByPost = [];
        if (!empty($allPostIds)) {
            $tagModel = new PostsTagModel();
            $tags     = $tagModel->whereIn('post_id', $allPostIds)->findAll();
            foreach ($tags as $tag) {
                $tagsByPost[$tag['post_id']][] = $tag;
            }
        }

        if ($latestPost) {
            $metaModel  = new PostsMetaModel();
            $videoMeta  = $metaModel->where('post_id', $latestPost['id'])->where('meta_key', 'post_video')->first();
            $latestPost['post_video'] = $videoMeta ? $videoMeta['meta_value'] : '';
            $latestPost['tags_list']  = $tagsByPost[$latestPost['id']] ?? [];
            $latestPost['body_html']  = str_replace('<img ', '<img loading="lazy" ', $latestPost['body_html']);
        }
        foreach ($otherPosts as &$p) {
            $p['tags_list'] = $tagsByPost[$p['id']] ?? [];
        }
        unset($p);

        $data['js']           = ['blog/common','blog/home'];
        $data['title']        = 'Blog';
        $data['latestPost']   = $latestPost;
        $data['otherPosts']   = $otherPosts;
        $data['hasMorePosts'] = $hasMorePosts;

        return view('blog/home', $data);
    }

    public function morePosts(): \CodeIgniter\HTTP\ResponseInterface
    {
        $offset    = max(0, (int) $this->request->getGet('offset'));
        $postModel = new PostModel();

        $latestPost = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderBy('published_at', 'DESC')
            ->first();

        if (!$latestPost) {
            return $this->response->setJSON([
                'status'  => 'success',
                'data'    => [],
                'hasMore' => false,
            ]);
        }

        $posts = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where('id !=', $latestPost['id'])
            ->orderBy('published_at', 'DESC')
            ->limit(self::POSTS_PER_PAGE, $offset)
            ->findAll();

        $postIds    = array_column($posts, 'id');
        $tagsByPost = [];
        if (!empty($postIds)) {
            $tagModel = new PostsTagModel();
            $tags     = $tagModel->whereIn('post_id', $postIds)->findAll();
            foreach ($tags as $tag) {
                $tagsByPost[$tag['post_id']][] = $tag;
            }
        }

        foreach ($posts as &$p) {
            $p['tags_list']            = $tagsByPost[$p['id']] ?? [];
            $p['published_at_formatted'] = date('j M Y', strtotime($p['published_at']));
            $p['published_at_iso']       = date('Y-m-d\TH:i:sP', strtotime($p['published_at']));
        }
        unset($p);

        $total = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where('id !=', $latestPost['id'])
            ->countAllResults();

        $hasMore = ($offset + self::POSTS_PER_PAGE) < $total;

        return $this->response->setJSON([
            'status'  => 'success',
            'data'    => $posts,
            'hasMore' => $hasMore,
        ]);
    }
}
