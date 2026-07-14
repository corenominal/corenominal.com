<?php

namespace App\Models;

use CodeIgniter\Model;

class RidePhotoModel extends Model
{
    protected $table         = 'ride_photos';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'ride_id', 'file_name', 'file_ext', 'mime_type',
        'width', 'height', 'filesize', 'sort_order',
    ];
}
