<?php

namespace App\Models;

use CodeIgniter\Model;

class ChatMessageModel extends Model
{
    protected $table            = 'chat_messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['session_id', 'role', 'model', 'content', 'thinking', 'images', 'documents'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';
}
