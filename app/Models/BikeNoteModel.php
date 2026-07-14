<?php

namespace App\Models;

use CodeIgniter\Model;

class BikeNoteModel extends Model
{
    protected $table          = 'bike_notes';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = ['bike_id', 'title', 'body', 'body_html'];

    protected $validationRules = [
        'bike_id' => 'required|integer',
        'body'    => 'required',
    ];
}
