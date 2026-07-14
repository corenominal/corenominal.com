<?php

namespace App\Controllers\Bikes\Admin;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\BikePhotoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $bikeModel = new BikeModel();

        $stats = [
            'total'   => (new BikeModel())->countAllResults(),
            'active'  => (new BikeModel())->where('status', 'active')->countAllResults(),
            'retired' => (new BikeModel())->where('status', 'retired')->countAllResults(),
            'sold'    => (new BikeModel())->where('status', 'sold')->countAllResults(),
        ];

        $search = trim((string) $this->request->getGet('q'));

        if ($search !== '') {
            $bikeModel
                ->groupStart()
                ->like('name', $search)
                ->orLike('brand', $search)
                ->orLike('model', $search)
                ->groupEnd();
        }

        $bikes = $bikeModel
            ->orderBy('status', 'ASC')
            ->orderBy('brand', 'ASC')
            ->paginate(25);

        $coverPhotos = [];

        if ($bikes) {
            $photos = (new BikePhotoModel())
                ->whereIn('bike_id', array_column($bikes, 'id'))
                ->orderBy('bike_id', 'ASC')
                ->orderBy('sort_order', 'ASC')
                ->findAll();

            foreach ($photos as $photo) {
                if (! isset($coverPhotos[$photo['bike_id']])) {
                    $coverPhotos[$photo['bike_id']] = $photo['file_name'];
                }
            }
        }

        return view('bikes/admin/home', [
            'title'            => 'Bikes — Admin',
            'js'               => ['bikes/admin/home'],
            'css'              => [],
            'templateMaxWidth' => '100%',
            'templateMenu'     => 'admin/sidebar-menu',
            'stats'            => $stats,
            'bikes'            => $bikes,
            'pager'            => $bikeModel->pager,
            'search'           => $search,
            'coverPhotos'      => $coverPhotos,
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

        (new BikeModel())->whereIn('id', $ids)->delete();

        return $this->response->setJSON([
            'status'  => 'success',
            'deleted' => count($ids),
        ]);
    }
}
