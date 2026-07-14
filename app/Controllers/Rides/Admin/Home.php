<?php

namespace App\Controllers\Rides\Admin;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\RideModel;
use App\Models\RidePhotoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $rideModel = new RideModel();

        $stats = [
            'total'          => (new RideModel())->countAllResults(),
            'total_distance' => (new RideModel())->selectSum('distance_km')->first()['distance_km'] ?? 0,
            'this_month'     => (new RideModel())->where('started_at >=', date('Y-m-01 00:00:00'))->selectSum('distance_km')->first()['distance_km'] ?? 0,
            'this_year'      => (new RideModel())->where('started_at >=', date('Y-01-01 00:00:00'))->selectSum('distance_km')->first()['distance_km'] ?? 0,
        ];

        $search = trim((string) $this->request->getGet('q'));

        if ($search !== '') {
            $rideModel
                ->groupStart()
                ->like('title', $search)
                ->orLike('notes', $search)
                ->groupEnd();
        }

        $rides = $rideModel
            ->orderBy('started_at', 'DESC')
            ->paginate(25);

        $coverPhotos = [];
        $bikeNames   = [];

        if ($rides) {
            $rideIds = array_column($rides, 'id');

            $photos = (new RidePhotoModel())
                ->whereIn('ride_id', $rideIds)
                ->orderBy('ride_id', 'ASC')
                ->orderBy('sort_order', 'ASC')
                ->findAll();

            foreach ($photos as $photo) {
                if (! isset($coverPhotos[$photo['ride_id']])) {
                    $coverPhotos[$photo['ride_id']] = $photo['file_name'];
                }
            }

            $bikeIds = array_unique(array_filter(array_column($rides, 'bike_id')));

            if ($bikeIds) {
                $bikes = (new BikeModel())->whereIn('id', $bikeIds)->findAll();
                foreach ($bikes as $bike) {
                    $bikeNames[$bike['id']] = $bike['name'] !== null && $bike['name'] !== ''
                        ? $bike['name']
                        : trim($bike['brand'] . ' ' . $bike['model']);
                }
            }
        }

        return view('rides/admin/home', [
            'title'            => 'Rides — Admin',
            'js'               => ['rides/admin/home'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'stats'            => $stats,
            'rides'            => $rides,
            'pager'            => $rideModel->pager,
            'search'           => $search,
            'coverPhotos'      => $coverPhotos,
            'bikeNames'        => $bikeNames,
        ]);
    }

    public function delete(): ResponseInterface
    {
        $json = $this->request->getJSON(true);
        $ids  = $json['ids'] ?? [];

        $ids = array_values(array_filter(array_map('intval', $ids), static fn ($id) => $id > 0));

        if (empty($ids)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'  => 'error',
                'message' => 'No valid IDs provided.',
            ]);
        }

        $rideModel = new RideModel();

        $bikeIds = array_unique(array_filter(array_column(
            $rideModel->whereIn('id', $ids)->select('bike_id')->findAll(),
            'bike_id'
        )));

        $rideModel->whereIn('id', $ids)->delete();

        foreach ($bikeIds as $bikeId) {
            $rideModel->recalculateBikeRiddenKm((int) $bikeId);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
