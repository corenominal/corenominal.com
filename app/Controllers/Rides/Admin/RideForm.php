<?php

namespace App\Controllers\Rides\Admin;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\RideModel;
use App\Models\RidePhotoModel;

class RideForm extends BaseController
{
    public function create(): string
    {
        return view('rides/admin/ride_form', [
            'title'            => 'Upload Ride',
            'js'               => ['rides/admin/ride-form'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'create',
            'ride'             => null,
            'photos'           => [],
            'bikes'            => [],
        ]);
    }

    public function edit(int $id): mixed
    {
        $ride = (new RideModel())->find($id);

        if (! $ride) {
            return redirect()->to(site_url('admin/rides'))->with('error', 'Ride not found.');
        }

        $photos = (new RidePhotoModel())
            ->where('ride_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $bikes = (new BikeModel())
            ->orderBy('brand', 'ASC')
            ->orderBy('model', 'ASC')
            ->findAll();

        return view('rides/admin/ride_form', [
            'title'            => 'Edit Ride',
            'js'               => ['vendor/leaflet', 'vendor/leaflet.polylineDecorator', 'vendor/chart.umd.min', 'rides/admin/ride-form'],
            'css'              => ['vendor/leaflet'],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'action'           => 'edit',
            'ride'             => $ride,
            'photos'           => $photos,
            'bikes'            => $bikes,
        ]);
    }
}
