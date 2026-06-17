<?php

namespace App\Models;

use CodeIgniter\Model;

class StartShortcutModel extends Model
{
    protected $table            = 'start_shortcuts';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'category_id',
        'name',
        'url',
        'icon_filename',
        'sort_order',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
