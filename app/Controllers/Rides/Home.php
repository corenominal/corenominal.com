<?php

namespace App\Controllers\Rides;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\RideModel;
use App\Models\RidePhotoModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $limit = 12;
        $query = trim((string) $this->request->getGet('q'));

        $stats = [
            'total'          => (new RideModel())->countAllResults(),
            'total_distance' => (new RideModel())->selectSum('distance_km')->first()['distance_km'] ?? 0,
            'this_week'      => (new RideModel())->where('started_at >=', date('Y-m-d 00:00:00', strtotime('monday this week')))->selectSum('distance_km')->first()['distance_km'] ?? 0,
            'this_month'     => (new RideModel())->where('started_at >=', date('Y-m-01 00:00:00'))->selectSum('distance_km')->first()['distance_km'] ?? 0,
            'this_year'      => (new RideModel())->where('started_at >=', date('Y-01-01 00:00:00'))->selectSum('distance_km')->first()['distance_km'] ?? 0,
            'recording_since' => (new RideModel())->orderBy('started_at', 'ASC')->first()['started_at'] ?? null,
        ];

        $ridesData = $this->getRidesBatch(0, $limit, $query);

        return view('rides/home', [
            'title'            => 'Rides',
            'js'               => ['rides/home'],
            'css'              => [],
            'templateMaxWidth' => '960px',
            'stats'            => $stats,
            'rides'            => $ridesData['rides'],
            'coverPhotos'      => $ridesData['coverPhotos'],
            'bikeNames'        => $ridesData['bikeNames'],
            'hasMoreRides'     => $ridesData['hasMore'],
            'rideBatchSize'    => $limit,
            'searchQuery'      => $query,
        ]);
    }

    public function show(int $id): string
    {
        helper('rides');

        $ride = (new RideModel())->find($id);

        if ($ride === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Ride not found.');
        }

        $photos = (new RidePhotoModel())
            ->where('ride_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        $bikeName = null;

        if (! empty($ride['bike_id'])) {
            $bike = (new BikeModel())->find($ride['bike_id']);

            if ($bike) {
                $bikeName = $bike['name'] !== null && $bike['name'] !== ''
                    ? $bike['name']
                    : trim($bike['brand'] . ' ' . $bike['model']);
            }
        }

        return view('rides/show', [
            'title'            => $ride['title'] !== null && $ride['title'] !== '' ? $ride['title'] : 'Ride',
            'js'               => ['vendor/leaflet', 'vendor/leaflet.polylineDecorator', 'vendor/chart.umd.min', 'rides/show'],
            'css'              => ['vendor/leaflet'],
            'templateMaxWidth' => '960px',
            'ride'             => $ride,
            'photos'           => $photos,
            'bikeName'         => $bikeName,
            'backUrl'          => site_url('rides'),
            'backLabel'        => 'Back to rides',
        ]);
    }

    public function loadMore(): ResponseInterface
    {
        $offset = max(0, (int) $this->request->getGet('offset'));
        $limit  = (int) $this->request->getGet('limit');
        $query  = trim((string) $this->request->getGet('q'));

        $limit = max(1, min(50, $limit ?: 12));

        $ridesData = $this->getRidesBatch($offset, $limit, $query);

        $html = view('rides/partials/ride_cards', [
            'rides'       => $ridesData['rides'],
            'coverPhotos' => $ridesData['coverPhotos'],
            'bikeNames'   => $ridesData['bikeNames'],
        ]);

        return $this->response->setJSON([
            'html'       => $html,
            'nextOffset' => $offset + count($ridesData['rides']),
            'hasMore'    => $ridesData['hasMore'],
        ]);
    }

    private function getRidesBatch(int $offset, int $limit, string $query = ''): array
    {
        $rideModel = new RideModel();

        if ($query !== '') {
            $rideModel
                ->groupStart()
                ->like('title', $query)
                ->orLike('notes', $query)
                ->groupEnd();
        }

        $rows = $rideModel
            ->orderBy('started_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit + 1, $offset);

        $hasMore = count($rows) > $limit;
        $rides   = array_slice($rows, 0, $limit);

        $coverPhotos = [];
        $bikeNames   = [];

        if ($rides !== []) {
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

            if ($bikeIds !== []) {
                $bikes = (new BikeModel())->whereIn('id', $bikeIds)->findAll();

                foreach ($bikes as $bike) {
                    $bikeNames[$bike['id']] = $bike['name'] !== null && $bike['name'] !== ''
                        ? $bike['name']
                        : trim($bike['brand'] . ' ' . $bike['model']);
                }
            }
        }

        return [
            'rides'       => $rides,
            'coverPhotos' => $coverPhotos,
            'bikeNames'   => $bikeNames,
            'hasMore'     => $hasMore,
        ];
    }
}
