<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table            = 'messages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array'; // Can be 'object' or 'App\Entities\Message'
    protected $useSoftDeletes   = true;    // Enables 'deleted_at' usage

    // Fields that are allowed to be inserted or updated
    protected $allowedFields = [
        'uuid', 
        'from', 
        'to', 
        'cc', 
        'bcc', 
        'subject', 
        'body', 
        'mailtype', 
        'domain', 
        'sent_at'
    ];

    // Dates
    protected $useTimestamps = true;      // Automatically fills created_at and updated_at
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation Rules
    protected $validationRules = [
        'from'    => 'required|valid_email',
        'to'      => 'required|valid_email',
        'subject' => 'required|min_length[3]',
        'body'    => 'required',
    ];
}