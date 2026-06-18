<?php

namespace App\Models;

use CodeIgniter\Model;

class TodoItemModel extends Model
{
    protected $table            = 'todo_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;

    protected $allowedFields = [
        'uuid',
        'user_uuid',
        'status',
        'markdown',
        'html',
        'category',
        'is_pinned',
        'completed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';
}
