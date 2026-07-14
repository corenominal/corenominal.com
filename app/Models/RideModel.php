<?php

namespace App\Models;

use CodeIgniter\Model;

class RideModel extends Model
{
    protected $table          = 'rides';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $createdField   = 'created_at';
    protected $updatedField   = 'updated_at';
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'title', 'notes', 'bike_id', 'ride_type',
        'distance_km', 'moving_time_seconds', 'elapsed_time_seconds', 'elevation_gain_m',
        'avg_speed_kmh', 'max_speed_kmh', 'avg_heart_rate', 'max_heart_rate',
        'avg_cadence', 'avg_power', 'started_at', 'trackpoints_json', 'fit_file_name',
    ];

    protected $validationRules = [
        'title'       => 'permit_empty|max_length[255]',
        'bike_id'     => 'permit_empty|integer',
        'distance_km' => 'permit_empty|numeric',
        'ride_type'   => 'permit_empty|in_list[leisure,race,commute,training,indoor,sportive]',
    ];

    /**
     * Recalculates a bike's ridden_km as the sum of distance_km across
     * its currently-assigned rides, and stores the result on the bike.
     * Soft-deleted rides are excluded automatically.
     */
    public function recalculateBikeRiddenKm(?int $bikeId): void
    {
        if ($bikeId === null) {
            return;
        }

        $sum = $this->where('bike_id', $bikeId)->selectSum('distance_km')->first();

        model(BikeModel::class)->update($bikeId, ['ridden_km' => $sum['distance_km'] ?? 0]);
    }
}
