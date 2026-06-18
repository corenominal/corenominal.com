<?php

namespace App\Models;

use CodeIgniter\Model;

class SystemPromptModel extends Model
{
    protected $table         = 'system_prompts';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = ['content'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    public function getActive(): ?array
    {
        return $this->orderBy('id', 'DESC')->first();
    }
}
