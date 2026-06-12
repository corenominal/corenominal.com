<?php

namespace App\Controllers\Bookmarks\Api;

use App\Controllers\BaseController;
use App\Libraries\Markdown;
use App\Models\BookmarkModel;
use App\Models\BookmarkTagModel;
use Ramsey\Uuid\Uuid;

class Bookmarks extends BaseController
{
    /**
     * POST /api/bookmarks
     */
    public function create()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $title     = trim($json['title']);
        $url       = trim($json['url']);
        $tags      = trim($json['tags']);
        $notes     = trim($json['notes'] ?? '');
        $private   = (int) ($json['private'] ?? 0);
        $dashboard = (int) ($json['dashboard'] ?? 0);

        $bookmarkModel = new BookmarkModel();

        if ($bookmarkModel->where('url', $url)->first()) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => ['url' => 'A bookmark with this URL already exists.'],
            ]);
        }

        $favicon        = $this->getFavicon($url);
        $notesHtml      = $this->convertMarkdown($notes);
        $tagsNormalized = $this->normalizeTags($tags);
        $uuid           = Uuid::uuid4()->toString();

        $imageFile = trim($json['image_file'] ?? '');
        $image     = preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\.jpg$/i', $imageFile) ? $imageFile : '';

        $youtubeVideoId = $this->extractYoutubeVideoId($url);

        if (empty($image) && ! empty($youtubeVideoId)) {
            $image = $this->downloadYoutubeThumbnail($youtubeVideoId, $uuid);
        }

        if (empty($image) && $this->hasInspirationTag($tagsNormalized)) {
            $image = $this->captureScreenshot($url, $uuid);
        }

        $bookmarkId = $bookmarkModel->insert([
            'uuid'       => $uuid,
            'title'      => $title,
            'title_html' => esc($title),
            'url'        => $url,
            'favicon'    => $favicon,
            'notes'      => $notes,
            'notes_html' => $notesHtml,
            'tags'       => $tagsNormalized,
            'image'      => $image,
            'private'    => $private,
            'dashboard'  => $dashboard,
            'hitcounter' => 0,
        ], true);

        if (! $bookmarkId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create bookmark.',
            ]);
        }

        $this->saveTags(new BookmarkTagModel(), $bookmarkId, $tagsNormalized);

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Bookmark created.',
            'uuid'    => $uuid,
        ]);
    }

    /**
     * PUT /api/bookmarks/:uuid
     */
    public function update(?string $uuid = null)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        if (empty($uuid)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'UUID is required.',
            ]);
        }

        $bookmarkModel = new BookmarkModel();
        $bookmark      = $bookmarkModel->where('uuid', $uuid)->first();

        if (! $bookmark) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Bookmark not found.',
            ]);
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $title     = trim($json['title']);
        $url       = trim($json['url']);
        $tags      = trim($json['tags']);
        $notes     = trim($json['notes'] ?? '');
        $private   = (int) ($json['private'] ?? 0);
        $dashboard = (int) ($json['dashboard'] ?? 0);

        $favicon = ($url !== $bookmark['url'])
            ? $this->getFavicon($url)
            : ($bookmark['favicon'] ?: $this->getFavicon($url));

        $notesHtml      = $this->convertMarkdown($notes);
        $tagsNormalized = $this->normalizeTags($tags);

        $existingImage  = $bookmark['image'] ?? '';
        $urlChanged     = ($url !== $bookmark['url']);
        $youtubeVideoId = $this->extractYoutubeVideoId($url);

        $imageFile = trim($json['image_file'] ?? '');
        if (preg_match('/^[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}\.jpg$/i', $imageFile)) {
            $existingImage = $imageFile;
        } elseif (! empty($youtubeVideoId) && (empty($existingImage) || $urlChanged)) {
            $newImage = $this->downloadYoutubeThumbnail($youtubeVideoId, $uuid);
            if ($newImage !== '') {
                $existingImage = $newImage;
            }
        } elseif ($this->hasInspirationTag($tagsNormalized) && (empty($existingImage) || $urlChanged)) {
            $newImage = $this->captureScreenshot($url, $uuid);
            if ($newImage !== '') {
                $existingImage = $newImage;
            }
        }

        $bookmarkModel->where('uuid', $uuid)->set([
            'title'      => $title,
            'title_html' => esc($title),
            'url'        => $url,
            'favicon'    => $favicon,
            'notes'      => $notes,
            'notes_html' => $notesHtml,
            'tags'       => $tagsNormalized,
            'image'      => $existingImage,
            'private'    => $private,
            'dashboard'  => $dashboard,
        ])->update();

        $tagModel = new BookmarkTagModel();
        $tagModel->where('bookmark_id', $bookmark['id'])->delete();
        $this->saveTags($tagModel, $bookmark['id'], $tagsNormalized);

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Bookmark updated.',
            'uuid'    => $uuid,
        ]);
    }

    /**
     * GET /api/bookmarks/latest
     */
    public function latest()
    {
        $limit = (int) ($this->request->getGet('limit') ?? 20);
        $page  = (int) ($this->request->getGet('page')  ?? 1);

        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        if ($page < 1) {
            $page = 1;
        }

        $offset = ($page - 1) * $limit;

        $bookmarkModel = new BookmarkModel();

        $total = $bookmarkModel->where('private', 0)->countAllResults(false);

        $bookmarks = $bookmarkModel
            ->where('private', 0)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit, $offset);

        $items = array_map(static function (array $bookmark): array {
            return [
                'uuid'       => $bookmark['uuid'],
                'title'      => $bookmark['title'],
                'url'        => $bookmark['url'],
                'favicon'    => $bookmark['favicon'],
                'notes_html' => $bookmark['notes_html'],
                'tags'       => $bookmark['tags'],
                'image'      => $bookmark['image'],
                'created_at' => $bookmark['created_at'],
            ];
        }, $bookmarks);

        return $this->response->setJSON([
            'status' => 'success',
            'total'  => $total,
            'page'   => $page,
            'limit'  => $limit,
            'items'  => $items,
        ]);
    }

    /**
     * GET /api/bookmarks/check-url
     */
    public function checkUrl()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $url = trim($this->request->getGet('url') ?? '');

        if ($url === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'URL is required.',
            ]);
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'URL is not valid.',
            ]);
        }

        $bookmark = (new BookmarkModel())->where('url', $url)->first();

        return $this->response->setJSON([
            'status' => 'success',
            'exists' => $bookmark !== null,
            'uuid'   => $bookmark['uuid'] ?? null,
        ]);
    }

    private function requireAdmin(): ?\CodeIgniter\HTTP\ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ]);
        }

        return null;
    }

    private function validateInput(array $json): array|bool
    {
        $errors = [];
        $title  = trim($json['title'] ?? '');
        $url    = trim($json['url']   ?? '');
        $tags   = trim($json['tags']  ?? '');

        if ($title === '') {
            $errors['title'] = 'Title is required.';
        } elseif (strlen($title) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters.';
        }

        if ($url === '') {
            $errors['url'] = 'URL is required.';
        } elseif (! filter_var($url, FILTER_VALIDATE_URL)) {
            $errors['url'] = 'URL is not valid.';
        }

        if ($tags === '') {
            $errors['tags'] = 'At least one tag is required.';
        }

        return empty($errors) ? true : $errors;
    }

    private function getFavicon(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return '';
        }

        return 'https://www.google.com/s2/favicons?domain=' . urlencode($host) . '&sz=32';
    }

    private function convertMarkdown(string $notes): string
    {
        if ($notes === '') {
            return '';
        }

        try {
            $markdown = new Markdown();
            $markdown->setMarkdown($notes);
            return $markdown->convert();
        } catch (\Exception) {
            return '';
        }
    }

    private function normalizeTags(string $tags): string
    {
        $items = array_filter(array_map('trim', explode(',', $tags)));

        return implode(', ', $items);
    }

    private function saveTags(BookmarkTagModel $tagModel, int $bookmarkId, string $tagsString): void
    {
        $tags = array_filter(array_map('trim', explode(',', $tagsString)));

        foreach ($tags as $tag) {
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $tag), '-'));
            $tagModel->skipValidation(true)->insert([
                'bookmark_id' => $bookmarkId,
                'tag'         => $tag,
                'slug'        => $slug,
            ]);
        }
    }

    private function extractYoutubeVideoId(string $url): string
    {
        if (empty($url)) {
            return '';
        }

        if (preg_match('/youtu\.be\/([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        if (preg_match('/youtube\.com\/(?:watch\?(?:.*&)?v=|embed\/|v\/)([a-zA-Z0-9_-]{11})/', $url, $matches)) {
            return $matches[1];
        }

        return '';
    }

    private function downloadYoutubeThumbnail(string $videoId, string $uuid): string
    {
        $qualities = ['maxresdefault', 'sddefault', 'hqdefault', 'mqdefault', 'default'];

        foreach ($qualities as $quality) {
            $thumbnailUrl = "https://img.youtube.com/vi/{$videoId}/{$quality}.jpg";
            $imageData    = @file_get_contents($thumbnailUrl);

            if ($imageData === false) {
                continue;
            }

            $size = @getimagesizefromstring($imageData);

            if ($size === false || ($size[0] === 120 && $size[1] === 90)) {
                continue;
            }

            $filename = $uuid . '.jpg';
            $destPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bookmarks' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $filename;

            if (file_put_contents($destPath, $imageData) === false) {
                return '';
            }

            return $filename;
        }

        return '';
    }

    private function hasInspirationTag(string $tagsNormalized): bool
    {
        $tags = array_map('trim', explode(',', strtolower($tagsNormalized)));

        return in_array('inspiration', $tags, true);
    }

    private function captureScreenshot(string $url, string $uuid): string
    {
        $config = config('ScreenshotOne');

        if (empty($config->apikey)) {
            return '';
        }

        $params = [
            'access_key'      => $config->apikey,
            'url'             => $url,
            'viewport_width'  => '1280',
            'viewport_height' => '720',
            'block_ads'       => 'true',
            'dark_mode'       => 'true',
            'format'          => 'jpg',
        ];

        if (! empty($config->secretkey)) {
            ksort($params);
            $queryString = http_build_query($params);
            $signature   = hash_hmac('sha256', $queryString, $config->secretkey);
            $apiUrl      = 'https://api.screenshotone.com/take?' . $queryString . '&signature=' . $signature;
        } else {
            $apiUrl = 'https://api.screenshotone.com/take?' . http_build_query($params);
        }

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => ['Accept: image/jpeg, image/*'],
        ]);

        $imageData  = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);

        if ($curlError !== '' || $statusCode !== 200 || strlen($imageData) < 100) {
            return '';
        }

        $filename = $uuid . '.jpg';
        $destPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bookmarks' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($destPath, $imageData) === false) {
            return '';
        }

        return $filename;
    }
}
