<?php

namespace App\Models;

use CodeIgniter\Model;

class PostsTagModel extends Model
{
    protected $table            = 'posts_tags';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';

    protected $allowedFields = [
        'id',
        'post_id',
        'tag',
        'slug',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'tag'  => 'required|min_length[1]|max_length[100]',
        'slug' => 'required|alpha_dash|is_unique[posts_tags.slug,id,{id}]',
    ];
}
