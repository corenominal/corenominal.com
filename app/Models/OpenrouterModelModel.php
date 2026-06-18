<?php

namespace App\Models;

use CodeIgniter\Model;

class OpenrouterModelModel extends Model
{
    protected $table      = 'openrouter_models';
    protected $primaryKey = 'id';
    protected $allowedFields = ['model_id'];
    protected $useTimestamps = false;
}
