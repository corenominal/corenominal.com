<?php

namespace App\Controllers\Blog;

use App\Models\PostModel;
use App\Models\PostsTagModel;

class Tag extends BaseController
{
    public function show(string $slug): string
    {
        $tagModel   = new PostsTagModel();
        $tagRecords = $tagModel->where('slug', esc($slug, 'url'))->findAll();

        if (empty($tagRecords)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $tagName = $tagRecords[0]['tag'];
        $postIds = array_column($tagRecords, 'post_id');

        $postModel = new PostModel();
        $posts     = $postModel
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereIn('id', $postIds)
            ->orderBy('published_at', 'DESC')
            ->findAll();

        if (empty($posts)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $allPostIds = array_column($posts, 'id');
        $tags       = $tagModel->whereIn('post_id', $allPostIds)->findAll();

        $tagsByPost = [];
        foreach ($tags as $tag) {
            $tagsByPost[$tag['post_id']][] = $tag;
        }

        foreach ($posts as &$post) {
            $post['tags_list'] = $tagsByPost[$post['id']] ?? [];
        }
        unset($post);

        $data['posts']   = $posts;
        $data['tagName'] = $tagName;
        $data['tagSlug'] = $slug;
        $data['title']   = 'Posts tagged "' . esc($tagName) . '"';
        $data['js']      = ['blog/common','blog/home'];

        return view('blog/tag', $data);
    }
}
