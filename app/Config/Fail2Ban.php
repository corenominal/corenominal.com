<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Fail2Ban extends BaseConfig
{
    public int $maxAttempts = 5;
    public int $banWindowMinutes = 15;
}
