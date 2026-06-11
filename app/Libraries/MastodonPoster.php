<?php

namespace App\Libraries;

use Config\Mastodon as MastodonConfig;

class MastodonPoster
{
    private MastodonConfig $config;

    public function __construct()
    {
        $this->config = config('Mastodon');
    }

    public function isEnabled(): bool
    {
        return ! empty($this->config->url) && ! empty($this->config->access_token);
    }

    /**
     * Post a status to Mastodon, uploading any attached media first.
     *
     * @param  array $mediaItems Rows from StatusMediaModel (keys: file_name, mime_type, description).
     * @return array{mastodon_id: string, mastodon_url: string}
     */
    public function post(string $content, array $mediaItems = []): array
    {
        $mastodonMediaIds = [];

        foreach ($mediaItems as $media) {
            $mastodonMediaIds[] = $this->uploadMedia($media);
        }

        return $this->postStatus($content, $mastodonMediaIds);
    }

    /**
     * Update an existing Mastodon status, re-uploading any attached media.
     *
     * @param array $mediaItems Rows from StatusMediaModel (keys: file_name, mime_type, description).
     */
    public function update(string $mastodonId, string $content, array $mediaItems = []): void
    {
        $mastodonMediaIds = [];

        foreach ($mediaItems as $media) {
            $mastodonMediaIds[] = $this->uploadMedia($media);
        }

        $endpoint = rtrim((string) $this->config->url, '/') . '/api/v1/statuses/' . $mastodonId;

        $body = ['status' => $content];

        if (! empty($mastodonMediaIds)) {
            $body['media_ids'] = array_values($mastodonMediaIds);
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config->access_token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Mastodon status update cURL error: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Mastodon status update failed (' . $httpCode . '): ' . $response);
        }
    }

    public function delete(string $mastodonId): void
    {
        $endpoint = rtrim((string) $this->config->url, '/') . '/api/v1/statuses/' . $mastodonId;

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config->access_token,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Mastodon status delete cURL error: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Mastodon status delete failed (' . $httpCode . '): ' . $response);
        }
    }

    private function uploadMedia(array $media): string
    {
        $filePath = FCPATH . 'uploads/status/media/' . basename((string) $media['file_name']);

        if (! file_exists($filePath)) {
            throw new \RuntimeException('Media file not found: ' . $media['file_name']);
        }

        $endpoint = rtrim((string) $this->config->url, '/') . '/api/v2/media';

        $cfile = new \CURLFile(
            $filePath,
            (string) ($media['mime_type'] ?? 'application/octet-stream'),
            basename($filePath)
        );

        $postData = ['file' => $cfile];

        if (! empty($media['description'])) {
            $postData['description'] = (string) $media['description'];
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config->access_token,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Mastodon media upload cURL error: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Mastodon media upload failed (' . $httpCode . '): ' . $response);
        }

        $data = json_decode((string) $response, true);

        if (! isset($data['id'])) {
            throw new \RuntimeException('Mastodon media upload response missing ID.');
        }

        if ($httpCode === 202) {
            $this->waitForMedia((string) $data['id']);
        }

        return (string) $data['id'];
    }

    private function waitForMedia(string $mediaId): void
    {
        $endpoint = rtrim((string) $this->config->url, '/') . '/api/v1/media/' . $mediaId;
        $maxTries = 5;

        for ($i = 0; $i < $maxTries; $i++) {
            sleep(2);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $this->config->access_token,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT => 10,
            ]);

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_exec($ch);
            curl_close($ch);

            if ($httpCode === 200) {
                return;
            }
        }
    }

    /**
     * @param  string[] $mastodonMediaIds
     * @return array{mastodon_id: string, mastodon_url: string}
     */
    private function postStatus(string $content, array $mastodonMediaIds = []): array
    {
        $endpoint = rtrim((string) $this->config->url, '/') . '/api/v1/statuses';

        $body = ['status' => $content];

        if (! empty($mastodonMediaIds)) {
            $body['media_ids'] = array_values($mastodonMediaIds);
        }

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($body),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->config->access_token,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            throw new \RuntimeException('Mastodon status post cURL error: ' . $curlError);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            throw new \RuntimeException('Mastodon status post failed (' . $httpCode . '): ' . $response);
        }

        $data = json_decode((string) $response, true);

        if (! isset($data['id'])) {
            throw new \RuntimeException('Mastodon status post response missing ID.');
        }

        return [
            'mastodon_id'  => (string) $data['id'],
            'mastodon_url' => (string) ($data['url'] ?? ''),
        ];
    }
}
