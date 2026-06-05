<?php

namespace App\Controllers;

class Errors extends BaseController
{
    public function show404()
    {
        $data['title'] = 'Page Not Found';
        return $this->response->setStatusCode(404)->setBody(view('404-not-found', $data));
    }
}
