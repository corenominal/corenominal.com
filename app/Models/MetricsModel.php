<?php

namespace App\Models;

use CodeIgniter\Model;

class MetricsModel extends Model
{
    protected $table            = 'metrics';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'path',
        'user_uuid',
        'username',
        'is_admin',
        'device_type',
        'anonymized_ip',
        'useragent',
        'load_time_ms',
        'window_width',
        'window_height',
        'created_at',
    ];

    protected $useTimestamps = false;

}
