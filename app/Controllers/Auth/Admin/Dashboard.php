<?php

namespace App\Controllers\Auth\Admin;

use App\Models\ApikeyModel;
use App\Models\GroupModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $data['title']            = 'Auth Admin';
        $data['templateMaxWidth'] = '96%';
        $data['templateMenu']     = 'auth/admin/sidebar-menu';
        $data['userCount']        = (new UserModel())->countAllResults();
        $data['groupCount']       = (new GroupModel())->countAllResults();
        $data['apikeyCount']      = (new ApikeyModel())->countAllResults();
        return view('auth/admin/dashboard', $data);
    }
}