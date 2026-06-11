<?php

namespace App\Controllers\Status;

use App\Controllers\BaseController;
use App\Models\StatusMediaModel;
use App\Models\StatusModel;
use CodeIgniter\HTTP\ResponseInterface;

class Feed extends BaseController
{
    public function rss(): ResponseInterface
    {
        $limit       = 20;
        $statusModel = new StatusModel();
        $statuses    = $statusModel
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        $mediaIds = [];

        foreach ($statuses as $status) {
            foreach ($status['media_ids'] ?? [] as $mediaId) {
                $id = (int) $mediaId;

                if ($id > 0) {
                    $mediaIds[] = $id;
                }
            }
        }

        $mediaById = [];

        if ($mediaIds !== []) {
            $rows = (new StatusMediaModel())
                ->whereIn('id', array_values(array_unique($mediaIds)))
                ->findAll();

            foreach ($rows as $row) {
                $mediaById[(int) $row['id']] = [
                    'id'          => (int) $row['id'],
                    'description' => (string) ($row['description'] ?? ''),
                    'url'         => '/uploads/status/media/' . ($row['file_name'] ?? ''),
                    'mimeType'    => (string) ($row['mime_type'] ?? ''),
                    'width'       => (int) ($row['width'] ?? 0),
                    'height'      => (int) ($row['height'] ?? 0),
                    'filesize'    => (int) ($row['filesize'] ?? 0),
                ];
            }
        }

        foreach ($statuses as $i => $status) {
            $statuses[$i]['media'] = [];

            foreach ($status['media_ids'] ?? [] as $mediaId) {
                $id = (int) $mediaId;

                if (isset($mediaById[$id])) {
                    $statuses[$i]['media'][] = $mediaById[$id];
                }
            }
        }

        $siteUrl  = rtrim((string) config('App')->baseURL, '/');
        $siteName = (string) config('App')->siteName;

        $xml = view('status/feed/rss', [
            'statuses' => $statuses,
            'siteUrl'  => $siteUrl,
            'siteName' => $siteName,
            'feedUrl'  => $siteUrl . '/status/feed/rss',
        ]);

        return $this->response
            ->setContentType('application/rss+xml; charset=UTF-8')
            ->setBody($xml);
    }
}
