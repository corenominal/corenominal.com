<?php

namespace App\Models;

use CodeIgniter\Model;

class BikeModel extends Model
{
    protected $table          = 'bikes';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'name', 'brand', 'model', 'year', 'components',
        'total_km', 'status', 'notes',
    ];

    protected $validationRules = [
        'brand'    => 'required|max_length[150]',
        'model'    => 'required|max_length[150]',
        'status'   => 'required|in_list[active,retired,sold]',
        'total_km' => 'permit_empty|numeric',
        'year'     => 'permit_empty|integer',
    ];
}
