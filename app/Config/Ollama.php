<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Ollama extends BaseConfig
{
    public $ip = ''; // Ollama IP address
    public $defaultModel = 'gemma4:e4b';
}