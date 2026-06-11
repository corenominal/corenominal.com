<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Mastodon extends BaseConfig
{
    public string $url          = '';
    public string $apiv1        = '';
    public string $apiv2        = '';
    public string $access_token = '';
    public string $account      = '';
    public string $profile      = '';
    public string $displayname  = '';
}
