<?php

namespace App\Models;

use CodeIgniter\Model;

class StatusDraftModel extends Model
{
    protected $table            = 'status_drafts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;

    protected $allowedFields = [
        'uuid',
        'content',
        'media_ids',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert   = ['encodeMediaIds'];
    protected $beforeUpdate   = ['encodeMediaIds'];
    protected $afterFind      = ['decodeMediaIds'];

    protected function encodeMediaIds(array $data): array
    {
        if (isset($data['data']['media_ids']) && is_array($data['data']['media_ids'])) {
            $data['data']['media_ids'] = json_encode($data['data']['media_ids']);
        }

        return $data;
    }

    protected function decodeMediaIds(array $data): array
    {
        if ($data['singleton'] && isset($data['data']['media_ids'])) {
            $data['data']['media_ids'] = json_decode($data['data']['media_ids'] ?? '[]', true) ?? [];
        } elseif (! $data['singleton']) {
            foreach ($data['data'] as &$row) {
                if (isset($row['media_ids'])) {
                    $row['media_ids'] = json_decode($row['media_ids'] ?? '[]', true) ?? [];
                }
            }
        }

        return $data;
    }
}
