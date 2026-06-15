<?php

namespace App\Controllers\Blog\Admin;

use App\Libraries\Markdown;
use App\Models\PostModel;
use App\Models\PostsMetaModel;
use App\Models\PostsTagModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Ramsey\Uuid\Uuid;

class Posts extends BaseController
{
    private function expectsJsonResponse(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json');
    }

    private function jsonWithCsrf(array $payload, int $statusCode = 200): \CodeIgniter\HTTP\ResponseInterface
    {
        $payload['csrf'] = [
            'name' => csrf_token(),
            'hash' => csrf_hash(),
        ];

        return $this->response->setStatusCode($statusCode)->setJSON($payload);
    }

    public function create(): string
    {
        $tagModel = new PostsTagModel();
        $tags     = $tagModel->select('tag')->distinct()->orderBy('tag')->findAll();

        $data['post']             = null;
        $data['title']            = 'New Post';
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'admin/sidebar-menu';
        $data['js']               = ['blog/common', 'blog/admin/post_editor'];
        $data['action']           = site_url('admin/blog/posts/store');
        $data['isNew']            = true;
        $data['all_tags']         = array_column($tags, 'tag');
        $data['post_video']       = '';

        return view('blog/admin/post_editor', $data);
    }

    public function store(): \CodeIgniter\HTTP\ResponseInterface
    {
        helper(['url', 'security']);

        $input     = $this->request->getPost();
        $postModel = new PostModel();
        $tagModel  = new PostsTagModel();

        [$titleRaw, $titleHtml, $bodyRaw, $bodyHtml] = $this->convertMarkdown(
            trim($input['title'] ?? ''),
            trim($input['body'] ?? '')
        );

        $slug    = $this->resolveSlug(trim($input['slug'] ?? ''), $titleRaw, $postModel);
        $tagsRaw = trim($input['tags'] ?? '');
        $status  = ($input['_save_action'] ?? '') === 'publish' ? 'published' : ($input['status'] ?? 'draft');
        $pubAt   = !empty($input['published_at']) ? $input['published_at'] : null;
        if ($status === 'published' && $pubAt === null) {
            $pubAt = date('Y-m-d H:i:s');
        }

        $postData = [
            'uuid'           => Uuid::uuid4()->toString(),
            'title'          => $titleRaw,
            'title_html'     => $titleHtml,
            'slug'           => $slug,
            'body'           => $bodyRaw,
            'body_html'      => $bodyHtml,
            'excerpt'        => trim($input['excerpt'] ?? ''),
            'tags'           => $tagsRaw,
            'featured_image' => trim($input['featured_image'] ?? ''),
            'visibility'     => (int) ($input['visibility'] ?? 0),
            'status'         => $status,
            'comment_status' => 'open',
            'hitcounter'     => 0,
            'published_at'   => $pubAt,
        ];

        if ($status === 'published') {
            $errors = [];
            if (empty($postData['title'])) {
                $errors['title'] = 'Title is required for published posts.';
            }
            if (empty($postData['body'])) {
                $errors['body'] = 'Body is required for published posts.';
            }
            if (empty($postData['tags'])) {
                $errors['tags'] = 'Tags are required for published posts.';
            }
            if (empty($postData['excerpt'])) {
                $errors['excerpt'] = 'Excerpt is required for published posts.';
            }

            if (!empty($errors)) {
                if ($this->expectsJsonResponse()) {
                    return $this->jsonWithCsrf([
                        'success' => false,
                        'message' => 'Please fix the highlighted errors and try again.',
                        'errors'  => $errors,
                    ], 422);
                }

                return redirect()->back()->withInput()->with('errors', $errors);
            }
        }

        if (!$postModel->save($postData)) {
            if ($this->expectsJsonResponse()) {
                return $this->jsonWithCsrf([
                    'success' => false,
                    'message' => 'Unable to save this post right now.',
                    'errors'  => $postModel->errors(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('errors', $postModel->errors());
        }

        $postId = $postModel->getInsertID();
        $this->saveTags($tagModel, $postId, $tagsRaw);

        $videoFilename = trim($input['video_filename'] ?? '');
        if (!empty($videoFilename)) {
            $metaModel = new PostsMetaModel();
            $metaModel->save([
                'post_id'    => $postId,
                'meta_key'   => 'post_video',
                'meta_value' => $videoFilename,
            ]);
        }

        $editUrl = site_url('admin/blog/posts/' . $postId . '/edit');

        if ($this->expectsJsonResponse()) {
            return $this->jsonWithCsrf([
                'success'    => true,
                'message'    => 'Post created successfully.',
                'post_id'    => $postId,
                'edit_url'   => $editUrl,
                'update_url' => site_url('admin/blog/posts/' . $postId . '/update'),
            ]);
        }

        return redirect()->to($editUrl)->with('success', 'Post created successfully.');
    }

    public function edit(int $id): string
    {
        $postModel = new PostModel();
        $tagModel  = new PostsTagModel();

        $post = $postModel->find($id);

        if (!$post) {
            throw new PageNotFoundException("Post #{$id} not found.");
        }

        $post['tags_list'] = $tagModel->where('post_id', $id)->findAll();

        $tags    = $tagModel->select('tag')->distinct()->orderBy('tag')->findAll();
        $allTags = array_column($tags, 'tag');

        $metaModel  = new PostsMetaModel();
        $videoMeta  = $metaModel->where('post_id', $id)->where('meta_key', 'post_video')->first();

        $data['post']       = $post;
        $data['title']      = 'Edit Post';
        $data['templateMaxWidth'] = '100%';
        $data['templateMenu']     = 'admin/sidebar-menu';
        $data['js']         = ['blog/common', 'blog/admin/post_editor'];
        $data['action']     = site_url('admin/blog/posts/' . $id . '/update');
        $data['isNew']      = false;
        $data['all_tags']   = $allTags;
        $data['post_video'] = $videoMeta ? $videoMeta['meta_value'] : '';

        return view('blog/admin/post_editor', $data);
    }

    public function update(int $id): \CodeIgniter\HTTP\ResponseInterface
    {
        helper(['url', 'security']);

        $postModel = new PostModel();
        $tagModel  = new PostsTagModel();

        $existing = $postModel->find($id);

        if (!$existing) {
            throw new PageNotFoundException("Post #{$id} not found.");
        }

        $input = $this->request->getPost();

        [$titleRaw, $titleHtml, $bodyRaw, $bodyHtml] = $this->convertMarkdown(
            trim($input['title'] ?? ''),
            trim($input['body'] ?? '')
        );

        $slug    = $existing['status'] === 'published'
            ? $existing['slug']
            : $this->resolveSlug(trim($input['slug'] ?? ''), $titleRaw, $postModel, $id);
        $tagsRaw = trim($input['tags'] ?? '');
        $status  = ($input['_save_action'] ?? '') === 'publish' ? 'published' : ($input['status'] ?? 'draft');
        $pubAt   = !empty($input['published_at']) ? $input['published_at'] : null;
        if ($status === 'published' && $pubAt === null) {
            $pubAt = date('Y-m-d H:i:s');
        }

        $postData = [
            'id'               => $id,
            'title'            => $titleRaw,
            'title_html'       => $titleHtml,
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'slug'             => $slug,
            'body'             => $bodyRaw,
            'body_html'        => $bodyHtml,
            'excerpt'          => trim($input['excerpt'] ?? ''),
            'tags'             => $tagsRaw,
            'featured_image'   => trim($input['featured_image'] ?? ''),
            'visibility'       => (int) ($input['visibility'] ?? 0),
            'status'           => $status,
            'published_at'     => $pubAt,
        ];

        if ($status === 'published') {
            $errors = [];
            if (empty($postData['title'])) {
                $errors['title'] = 'Title is required for published posts.';
            }
            if (empty($postData['body'])) {
                $errors['body'] = 'Body is required for published posts.';
            }
            if (empty($postData['tags'])) {
                $errors['tags'] = 'Tags are required for published posts.';
            }
            if (empty($postData['excerpt'])) {
                $errors['excerpt'] = 'Excerpt is required for published posts.';
            }

            if (!empty($errors)) {
                if ($this->expectsJsonResponse()) {
                    return $this->jsonWithCsrf([
                        'success' => false,
                        'message' => 'Please fix the highlighted errors and try again.',
                        'errors'  => $errors,
                    ], 422);
                }

                return redirect()->back()->withInput()->with('errors', $errors);
            }
        }

        if (!$postModel->save($postData)) {
            if ($this->expectsJsonResponse()) {
                return $this->jsonWithCsrf([
                    'success' => false,
                    'message' => 'Unable to update this post right now.',
                    'errors'  => $postModel->errors(),
                ], 422);
            }

            return redirect()->back()->withInput()->with('errors', $postModel->errors());
        }

        \Config\Database::connect()->table('posts_tags')->where('post_id', $id)->delete();
        $this->saveTags($tagModel, $id, $tagsRaw);

        $videoFilename = trim($input['video_filename'] ?? '');
        $metaModel     = new PostsMetaModel();
        if (!empty($videoFilename)) {
            $existingMeta = $metaModel->where('post_id', $id)->where('meta_key', 'post_video')->first();
            if ($existingMeta) {
                $metaModel->update($existingMeta['id'], ['meta_value' => $videoFilename]);
            } else {
                $metaModel->save(['post_id' => $id, 'meta_key' => 'post_video', 'meta_value' => $videoFilename]);
            }
        } else {
            $metaModel->where('post_id', $id)->where('meta_key', 'post_video')->delete();
        }

        $editUrl = site_url('admin/blog/posts/' . $id . '/edit');

        if ($this->expectsJsonResponse()) {
            return $this->jsonWithCsrf([
                'success'    => true,
                'message'    => 'Post updated successfully.',
                'post_id'    => $id,
                'edit_url'   => $editUrl,
                'update_url' => site_url('admin/blog/posts/' . $id . '/update'),
            ]);
        }

        return redirect()->to($editUrl)->with('success', 'Post updated successfully.');
    }

    public function preview(): \CodeIgniter\HTTP\ResponseInterface
    {
        $input   = $this->request->getJSON(true);
        $bodyRaw = trim($input['markdown'] ?? '');

        if (empty($bodyRaw)) {
            return $this->response->setJSON(['body_html' => '']);
        }

        try {
            $markdown = new Markdown();
            $markdown->setMarkdown($bodyRaw);
            $bodyHtml = $markdown->convert();
        } catch (\Exception $e) {
            $bodyHtml = '';
        }

        return $this->response->setJSON(['body_html' => $bodyHtml]);
    }

    public function upload_featured_image(): \CodeIgniter\HTTP\ResponseInterface
    {
        $file = $this->request->getFile('featured_image');

        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No file uploaded.']);
        }

        $mime    = $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid file type.']);
        }

        $tmpName  = $file->getTempName();
        $sizeInfo = @getimagesize($tmpName);
        if (!$sizeInfo) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Unable to read image.']);
        }

        [$width, $height] = [$sizeInfo[0], $sizeInfo[1]];
        if ($width !== 1200 || $height !== 630) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Image must be exactly 1200 x 630 pixels.']);
        }

        $ext = $file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION);
        $ext = strtolower($ext);
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $uuid     = Uuid::uuid4()->toString();
        $filename = 'og-' . $uuid . '.' . $ext;
        $destDir  = FCPATH . 'media/';

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        try {
            $file->move($destDir, $filename);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Failed to move uploaded file.']);
        }

        return $this->response->setJSON(['success' => true, 'filename' => $filename, 'url' => site_url('media/' . $filename)]);
    }

    public function remove_featured_image(): \CodeIgniter\HTTP\ResponseInterface
    {
        $filename = $this->request->getPost('filename');
        if (empty($filename)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No filename provided.']);
        }

        return $this->response->setJSON(['success' => true]);
    }

    public function list_featured_images(): \CodeIgniter\HTTP\ResponseInterface
    {
        $dir   = FCPATH . 'media/';
        $files = [];

        if (is_dir($dir)) {
            $items = scandir($dir);
            foreach ($items as $f) {
                if ($f === '.' || $f === '..') continue;
                if (strpos($f, 'og-') !== 0) continue;
                $path = $dir . $f;
                if (!is_file($path)) continue;
                $files[] = [
                    'filename' => $f,
                    'url'      => site_url('media/' . $f),
                ];
            }
        }

        return $this->response->setJSON(['files' => $files]);
    }

    public function upload_body_image(): \CodeIgniter\HTTP\ResponseInterface
    {
        $file = $this->request->getFile('image');

        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No file uploaded.']);
        }

        $mime    = $file->getMimeType();
        $allowed = ['image/png', 'image/jpeg', 'image/webp', 'image/gif'];
        if (!in_array($mime, $allowed, true)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid file type. Allowed: png, jpeg, webp, gif.']);
        }

        $tmpName  = $file->getTempName();
        $sizeInfo = @getimagesize($tmpName);
        if (!$sizeInfo) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Unable to read image dimensions.']);
        }

        [$width, $height] = [$sizeInfo[0], $sizeInfo[1]];

        $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        if ($ext === 'jpeg') {
            $ext = 'jpg';
        }

        $uuid     = Uuid::uuid4()->toString();
        $filename = $uuid . '.' . $ext;
        $destDir  = FCPATH . 'media/';

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $destPath = $destDir . $filename;

        if ($width > 1920) {
            $newWidth  = 1920;
            $newHeight = (int) round($height * ($newWidth / $width));
            try {
                $this->resizeImage($tmpName, $destPath, $mime, $width, $height, $newWidth, $newHeight);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Failed to resize image.']);
            }
        } else {
            try {
                $file->move($destDir, $filename);
            } catch (\Exception $e) {
                return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Failed to save uploaded file.']);
            }
        }

        return $this->response->setJSON(['success' => true, 'filename' => $filename, 'url' => site_url('media/' . $filename)]);
    }

    public function upload_video(): \CodeIgniter\HTTP\ResponseInterface
    {
        $file = $this->request->getFile('video');

        if (!$file || !$file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No file uploaded.']);
        }

        $mime    = $file->getMimeType();
        $allowed = ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'];
        if (!in_array($mime, $allowed, true)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'Invalid file type. Allowed: mp4, webm, ogg, mov.']);
        }

        $ext = match ($mime) {
            'video/mp4'       => 'mp4',
            'video/webm'      => 'webm',
            'video/ogg'       => 'ogg',
            'video/quicktime' => 'mov',
            default           => 'mp4',
        };

        $uuid     = Uuid::uuid4()->toString();
        $filename = $uuid . '.' . $ext;
        $destDir  = FCPATH . 'media/';

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        try {
            $file->move($destDir, $filename);
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setJSON(['success' => false, 'error' => 'Failed to save uploaded file.']);
        }

        $postId = (int) $this->request->getPost('post_id');
        if ($postId > 0) {
            $metaModel    = new PostsMetaModel();
            $existingMeta = $metaModel->where('post_id', $postId)->where('meta_key', 'post_video')->first();
            if ($existingMeta) {
                $metaModel->update($existingMeta['id'], ['meta_value' => $filename]);
            } else {
                $metaModel->save(['post_id' => $postId, 'meta_key' => 'post_video', 'meta_value' => $filename]);
            }
        }

        return $this->response->setJSON(['success' => true, 'filename' => $filename, 'url' => site_url('media/' . $filename)]);
    }

    public function remove_video(): \CodeIgniter\HTTP\ResponseInterface
    {
        $filename = $this->request->getPost('filename');
        $postId   = (int) $this->request->getPost('post_id');

        if (empty($filename)) {
            return $this->response->setStatusCode(400)->setJSON(['success' => false, 'error' => 'No filename provided.']);
        }

        $basename = basename($filename);
        $path     = FCPATH . 'media/' . $basename;
        if (is_file($path)) {
            @unlink($path);
        }

        if ($postId > 0) {
            $metaModel = new PostsMetaModel();
            $metaModel->where('post_id', $postId)->where('meta_key', 'post_video')->delete();
        }

        return $this->response->setJSON(['success' => true]);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function resizeImage(
        string $src,
        string $dest,
        string $mime,
        int $srcW,
        int $srcH,
        int $dstW,
        int $dstH
    ): void {
        $srcImg = match ($mime) {
            'image/jpeg' => \imagecreatefromjpeg($src),
            'image/png'  => \imagecreatefrompng($src),
            'image/webp' => \imagecreatefromwebp($src),
            'image/gif'  => \imagecreatefromgif($src),
            default      => false,
        };

        if (!$srcImg) {
            throw new \RuntimeException('GD could not open source image.');
        }

        $dstImg = \imagecreatetruecolor($dstW, $dstH);

        if (in_array($mime, ['image/png', 'image/webp', 'image/gif'], true)) {
            \imagealphablend($dstImg, false);
            \imagesavealpha($dstImg, true);
            $transparent = \imagecolorallocatealpha($dstImg, 0, 0, 0, 127);
            \imagefill($dstImg, 0, 0, $transparent);
        }

        \imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);

        match ($mime) {
            'image/jpeg' => \imagejpeg($dstImg, $dest, 85),
            'image/png'  => \imagepng($dstImg, $dest),
            'image/webp' => \imagewebp($dstImg, $dest, 85),
            'image/gif'  => \imagegif($dstImg, $dest),
            default      => null,
        };

        \imagedestroy($srcImg);
        \imagedestroy($dstImg);
    }

    private function convertMarkdown(string $titleRaw, string $bodyRaw): array
    {
        $titleHtml = $titleRaw;
        $bodyHtml  = '';

        try {
            $markdown = new Markdown();

            if (!empty($titleRaw)) {
                $markdown->setMarkdown('# ' . $titleRaw);
                $titleHtml = html_entity_decode(strip_tags($markdown->convert()), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            }

            if (!empty($bodyRaw)) {
                $markdown->setMarkdown($bodyRaw);
                $bodyHtml = $markdown->convert();
            }
        } catch (\Exception $e) {
            $bodyHtml = nl2br(esc($bodyRaw));
        }

        return [$titleRaw, $titleHtml, $bodyRaw, $bodyHtml];
    }

    private function resolveSlug(string $slug, string $title, PostModel $postModel, ?int $excludeId = null): string
    {
        $base      = !empty($slug) ? $slug : url_title($title, '-', true);
        $candidate = $base;
        $i         = 1;

        while (true) {
            $builder = $postModel->where('slug', $candidate);
            if ($excludeId !== null) {
                $builder = $builder->where('id !=', $excludeId);
            }
            if ($builder->countAllResults() === 0) {
                break;
            }
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private function saveTags(PostsTagModel $tagModel, int $postId, string $tagsRaw): void
    {
        if (empty($tagsRaw)) {
            return;
        }

        $tagList = array_filter(array_map('trim', explode(',', $tagsRaw)));

        foreach ($tagList as $tag) {
            $tagSlug = url_title($tag, '-', true);
            $tagModel->skipValidation(true)->save([
                'post_id' => $postId,
                'tag'     => $tag,
                'slug'    => $tagSlug,
            ]);
        }
    }
}
