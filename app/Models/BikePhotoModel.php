<?php

namespace App\Models;

use CodeIgniter\Model;

class BikePhotoModel extends Model
{
    protected $table         = 'bike_photos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'bike_id', 'file_name', 'file_ext', 'mime_type',
        'width', 'height', 'filesize', 'sort_order',
    ];
}
