<?php
namespace Modules\Logs\Models;

use CodeIgniter\Model;

class LogsModel extends Model
{
    protected $table      = 'logs';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'message',
        'level',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
