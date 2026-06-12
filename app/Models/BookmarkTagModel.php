<?php

namespace App\Models;

use CodeIgniter\Model;

class BookmarkTagModel extends Model
{
    protected $table         = 'bookmarks_tags';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'id', 'bookmark_id', 'tag', 'slug',
        'created_at', 'updated_at',
    ];

    protected $validationRules = [
        'tag'  => 'required|min_length[1]|max_length[100]',
        'slug' => 'required|alpha_dash',
    ];
}
