<?php

namespace App\Controllers\Bikes\Admin;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\BikePhotoModel;

class BikeForm extends BaseController
{
    public function create(): string
    {
        return view('bikes/admin/bike_form', [
            'title'            => 'Add Bike',
            'js'               => ['bikes/admin/bike-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'create',
            'bike'             => null,
            'photos'           => [],
        ]);
    }

    public function edit(int $id): mixed
    {
        $bike = (new BikeModel())->find($id);

        if (! $bike) {
            return redirect()->to(site_url('admin/bikes'))->with('error', 'Bike not found.');
        }

        $photos = (new BikePhotoModel())
            ->where('bike_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return view('bikes/admin/bike_form', [
            'title'            => 'Edit Bike',
            'js'               => ['bikes/admin/bike-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'edit',
            'bike'             => $bike,
            'photos'           => $photos,
        ]);
    }
}
