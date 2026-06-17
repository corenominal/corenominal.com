<?php

namespace App\Controllers\Startpage\Api;

use App\Controllers\BaseController;

class Redirects extends BaseController
{
    public function create(): \CodeIgniter\HTTP\ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ]);
        }

        if (! $this->request->is('json')) {
            return $this->response->setJSON(['error' => 'Expecting JSON data.']);
        }

        $data  = $this->request->getJSON(true);
        $model = model('StartRedirectsModel');

        $test = $model->where('phrase', $data['phrase'])->first();

        if (! $test) {
            $model->insert($data);
            return $this->response->setJSON($data);
        }

        return $this->response->setJSON(['error' => 'Redirect already exists.']);
    }
}
