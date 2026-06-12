<?php

namespace App\Controllers\Bookmarks\Api;

use App\Controllers\BaseController;
use Ramsey\Uuid\Uuid;

class ScreenshotPreview extends BaseController
{
    /**
     * GET /api/bookmarks/screenshot/preview
     */
    public function url()
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ]);
        }

        $url = trim($this->request->getGet('url') ?? '');

        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'A valid URL is required.',
            ]);
        }

        $config = config('ScreenshotOne');

        if (empty($config->apikey)) {
            return $this->response->setStatusCode(503)->setJSON([
                'status'  => 'error',
                'message' => 'Screenshot service not configured.',
            ]);
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
            $queryString   = http_build_query($params);
            $signature     = hash_hmac('sha256', $queryString, $config->secretkey);
            $screenshotUrl = 'https://api.screenshotone.com/take?' . $queryString . '&signature=' . $signature;
        } else {
            $screenshotUrl = 'https://api.screenshotone.com/take?' . http_build_query($params);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'url'    => $screenshotUrl,
        ]);
    }

    /**
     * POST /api/bookmarks/screenshot/capture
     */
    public function capture()
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ]);
        }

        $json = $this->request->getJSON(true);
        $url  = trim($json['url'] ?? '');

        if (empty($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'A valid URL is required.',
            ]);
        }

        $config = config('ScreenshotOne');

        if (empty($config->apikey)) {
            return $this->response->setStatusCode(503)->setJSON([
                'status'  => 'error',
                'message' => 'Screenshot service not configured.',
            ]);
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
            $queryString   = http_build_query($params);
            $signature     = hash_hmac('sha256', $queryString, $config->secretkey);
            $screenshotUrl = 'https://api.screenshotone.com/take?' . $queryString . '&signature=' . $signature;
        } else {
            $screenshotUrl = 'https://api.screenshotone.com/take?' . http_build_query($params);
        }

        $ch = curl_init($screenshotUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER     => ['Accept: image/jpeg, image/*'],
        ]);

        $imageData  = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError  = curl_error($ch);

        if ($curlError !== '' || $statusCode !== 200 || strlen($imageData) < 100) {
            return $this->response->setStatusCode(502)->setJSON([
                'status'  => 'error',
                'message' => 'Screenshot capture failed.',
            ]);
        }

        $uuid     = Uuid::uuid4()->toString();
        $filename = $uuid . '.jpg';
        $destPath = FCPATH . 'uploads' . DIRECTORY_SEPARATOR . 'bookmarks' . DIRECTORY_SEPARATOR . 'media' . DIRECTORY_SEPARATOR . $filename;

        if (file_put_contents($destPath, $imageData) === false) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to save screenshot.',
            ]);
        }

        return $this->response->setJSON([
            'status'   => 'success',
            'filename' => $filename,
        ]);
    }
}
