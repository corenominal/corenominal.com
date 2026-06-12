<?php

use App\Models\StatusMediaModel;

if (! function_exists('status_with_media')) {
    function status_with_media(array $status): array
    {
        $mediaIds = $status['media_ids'] ?? [];
        $status['media'] = [];

        if (empty($mediaIds)) {
            return $status;
        }

        $ids  = array_values(array_unique(array_map('intval', $mediaIds)));
        $rows = (new StatusMediaModel())->whereIn('id', $ids)->findAll();

        $byId = [];

        foreach ($rows as $row) {
            $byId[(int) $row['id']] = status_media_row_to_array($row);
        }

        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $status['media'][] = $byId[$id];
            }
        }

        return $status;
    }
}

if (! function_exists('status_media_row_to_array')) {
    function status_media_row_to_array(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'description' => (string) ($row['description'] ?? ''),
            'url'         => '/uploads/status/media/' . ($row['file_name'] ?? ''),
            'mimeType'    => (string) ($row['mime_type'] ?? ''),
            'width'       => isset($row['width']) ? (int) $row['width'] : null,
            'height'      => isset($row['height']) ? (int) $row['height'] : null,
            'filesize'    => isset($row['filesize']) ? (int) $row['filesize'] : null,
        ];
    }
}
