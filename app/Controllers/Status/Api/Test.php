<?php

namespace App\Controllers\Status\Api;

use CodeIgniter\HTTP\ResponseInterface;

class Test extends BaseController
{
    public function ping(): ResponseInterface
    {
        return $this->response->setJSON(['status' => 'success', 'message' => 'pong']);
    }
}
