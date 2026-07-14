<?php

namespace App\Controllers\Rides\Api;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use App\Models\RideModel;
use CodeIgniter\HTTP\ResponseInterface;
use Ramsey\Uuid\Uuid;
use Sportlog\FIT\Decoder;
use Sportlog\FIT\Profile\Messages\RecordMessage;
use Sportlog\FIT\Profile\Messages\SessionMessage;
use Sportlog\FIT\Profile\Types\MesgNum;

class Rides extends BaseController
{
    private const RIDE_TYPES = ['leisure', 'race', 'commute', 'training', 'indoor', 'sportive'];

    /**
     * POST /api/rides/upload
     */
    public function upload()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $file = $this->request->getFile('fit');

        if (! $file || ! $file->isValid()) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'No file uploaded.']);
        }

        $rideType = $this->request->getPost('ride_type');
        if (! in_array($rideType, self::RIDE_TYPES, true)) {
            $rideType = 'leisure';
        }

        $ext = strtolower($file->getClientExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        if (! in_array($ext, ['fit', 'zip'], true)) {
            return $this->response->setStatusCode(400)->setJSON(['status' => 'error', 'message' => 'Invalid file type. Only .fit or .zip files are accepted.']);
        }

        $destDir = WRITEPATH . 'uploads/rides/fit/';
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0755, true);
        }

        $filename = Uuid::uuid4()->toString() . '.fit';
        $destPath = $destDir . $filename;

        if ($ext === 'zip') {
            try {
                $fitContents = $this->extractFitFromZip($file->getTempName());
            } catch (\RuntimeException $e) {
                return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'message' => $e->getMessage()]);
            }

            if (file_put_contents($destPath, $fitContents) === false) {
                return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save extracted file.']);
            }
        } else {
            try {
                $file->move($destDir, $filename);
            } catch (\Exception) {
                return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save uploaded file.']);
            }
        }

        try {
            $rideFields = $this->parseFitFile($destPath);
        } catch (\Throwable $e) {
            @unlink($destPath);

            return $this->response->setStatusCode(422)->setJSON([
                'status'  => 'error',
                'message' => 'Unable to parse this FIT file: ' . $e->getMessage(),
            ]);
        }

        $rideFields['fit_file_name'] = $filename;
        $rideFields['ride_type']     = $rideType;

        $rideId = (new RideModel())->insert($rideFields, true);

        if (! $rideId) {
            @unlink($destPath);

            return $this->response->setStatusCode(500)->setJSON(['status' => 'error', 'message' => 'Failed to save ride.']);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Ride uploaded.',
            'id'      => $rideId,
        ]);
    }

    /**
     * PUT /api/rides/:id
     */
    public function update(?int $id = null)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $rideModel = new RideModel();
        $ride      = $rideModel->find($id);

        if (! $ride) {
            return $this->response->setStatusCode(404)->setJSON(['status' => 'error', 'message' => 'Ride not found.']);
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON(['status' => 'error', 'errors' => $validation]);
        }

        $oldBikeId = $ride['bike_id'] !== null ? (int) $ride['bike_id'] : null;
        $data      = $this->extractData($json);
        $newBikeId = $data['bike_id'];

        $rideModel->update($id, $data);

        $affectedBikeIds = array_unique(array_filter([$oldBikeId, $newBikeId], static fn ($v) => $v !== null));
        foreach ($affectedBikeIds as $bikeId) {
            $rideModel->recalculateBikeRiddenKm((int) $bikeId);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Ride updated.',
            'id'      => $id,
        ]);
    }

    private function requireAdmin(): ?ResponseInterface
    {
        if (empty($GLOBALS['is_admin'])) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 'error',
                'message' => 'Forbidden.',
            ]);
        }

        return null;
    }

    private function validateInput(array $json): array|bool
    {
        $errors     = [];
        $title      = trim($json['title'] ?? '');
        $bikeId     = $json['bike_id'] ?? '';
        $distanceKm = $json['distance_km'] ?? '';
        $rideType   = $json['ride_type'] ?? '';

        if (strlen($title) > 255) {
            $errors['title'] = 'Title must not exceed 255 characters.';
        }

        if ($bikeId !== '' && $bikeId !== null) {
            if (! ctype_digit((string) $bikeId)) {
                $errors['bike_id'] = 'Invalid bike selected.';
            } elseif (! (new BikeModel())->find((int) $bikeId)) {
                $errors['bike_id'] = 'Selected bike does not exist.';
            }
        }

        if ($distanceKm !== '' && $distanceKm !== null && ! is_numeric($distanceKm)) {
            $errors['distance_km'] = 'Distance must be a number.';
        }

        if ($rideType !== '' && $rideType !== null && ! in_array($rideType, self::RIDE_TYPES, true)) {
            $errors['ride_type'] = 'Invalid ride type.';
        }

        return empty($errors) ? true : $errors;
    }

    private function extractData(array $json): array
    {
        $bikeId     = $json['bike_id'] ?? '';
        $distanceKm = $json['distance_km'] ?? '';
        $rideType   = $json['ride_type'] ?? '';

        return [
            'title'       => trim($json['title'] ?? ''),
            'notes'       => trim($json['notes'] ?? ''),
            'bike_id'     => ($bikeId !== '' && $bikeId !== null) ? (int) $bikeId : null,
            'distance_km' => ($distanceKm !== '' && $distanceKm !== null) ? (float) $distanceKm : 0,
            'ride_type'   => in_array($rideType, self::RIDE_TYPES, true) ? $rideType : 'leisure',
        ];
    }

    /**
     * Extracts the first .fit entry found in a zip archive (Garmin Connect
     * sometimes exports activities as a zip instead of a raw .fit).
     */
    private function extractFitFromZip(string $zipPath): string
    {
        $zip = new \ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Unable to open zip archive.');
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);

            if ($name !== false && strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'fit') {
                $contents = $zip->getFromIndex($i);
                $zip->close();

                if ($contents === false) {
                    throw new \RuntimeException('Unable to read .fit file from zip archive.');
                }

                return $contents;
            }
        }

        $zip->close();

        throw new \RuntimeException('No .fit file found inside the zip archive.');
    }

    /**
     * Decodes a .fit file into the fields needed to create a ride row.
     */
    private function parseFitFile(string $path): array
    {
        $decoder     = new Decoder();
        $messageList = $decoder->read($path);

        $sessions = $messageList->getMessages(MesgNum::SESSION);
        $records  = $messageList->getMessages(MesgNum::RECORD);

        if (empty($sessions) && empty($records)) {
            throw new \RuntimeException('No activity data found in this FIT file.');
        }

        /** @var SessionMessage|null $session */
        $session = $sessions[0] ?? null;

        $startTime = $session?->getStartTime() ?? ($records[0] ?? null)?->getTimestamp();

        $fields                     = $this->mapSessionToRideFields($session);
        $fields['title']            = $this->defaultTitle($startTime);
        $fields['trackpoints_json'] = $this->buildTrackpoints($records);

        return $fields;
    }

    /**
     * Builds a default title like "Morning Ride" based on the ride's local start time.
     */
    private function defaultTitle(?\DateTime $startTime): string
    {
        $time = $startTime !== null ? clone $startTime : new \DateTime();
        $time->setTimezone(new \DateTimeZone(config('App')->appTimezone));

        $hour = (int) $time->format('G');

        return match (true) {
            $hour >= 5 && $hour < 12  => 'Morning Ride',
            $hour >= 12 && $hour < 17 => 'Afternoon Ride',
            $hour >= 17 && $hour < 21 => 'Evening Ride',
            default                   => 'Night Ride',
        };
    }

    private function mapSessionToRideFields(?SessionMessage $session): array
    {
        if ($session === null) {
            return [
                'distance_km'          => 0,
                'moving_time_seconds'  => null,
                'elapsed_time_seconds' => null,
                'elevation_gain_m'     => null,
                'avg_speed_kmh'        => null,
                'max_speed_kmh'        => null,
                'avg_heart_rate'       => null,
                'max_heart_rate'       => null,
                'avg_cadence'          => null,
                'avg_power'            => null,
                'started_at'           => null,
            ];
        }

        $avgSpeed  = $session->getAvgSpeed() ?? $session->getEnhancedAvgSpeed();
        $maxSpeed  = $session->getMaxSpeed() ?? $session->getEnhancedMaxSpeed();
        $startTime = $session->getStartTime();
        $distance  = $session->getTotalDistance();

        return [
            'distance_km'          => $distance !== null ? round($distance / 1000, 2) : 0,
            'moving_time_seconds'  => $session->getTotalTimerTime() !== null ? (int) round($session->getTotalTimerTime()) : null,
            'elapsed_time_seconds' => $session->getTotalElapsedTime() !== null ? (int) round($session->getTotalElapsedTime()) : null,
            'elevation_gain_m'     => $session->getTotalAscent(),
            'avg_speed_kmh'        => $avgSpeed !== null ? round($avgSpeed * 3.6, 2) : null,
            'max_speed_kmh'        => $maxSpeed !== null ? round($maxSpeed * 3.6, 2) : null,
            'avg_heart_rate'       => $session->getAvgHeartRate(),
            'max_heart_rate'       => $session->getMaxHeartRate(),
            'avg_cadence'          => $session->getAvgCadence(),
            'avg_power'            => $session->getAvgPower(),
            'started_at'           => $startTime !== null ? $startTime->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Builds the compact trackpoints array ([lat, lng, elevation, time_offset_seconds])
     * from FIT record messages. Returns null when there is no usable GPS data
     * (e.g. an indoor trainer ride).
     */
    private function buildTrackpoints(array $records): ?string
    {
        $semicirclesToDegrees = 180 / 2 ** 31;
        $points                = [];
        $firstTimestamp        = null;

        /** @var RecordMessage $record */
        foreach ($records as $record) {
            $lat = $record->getPositionLat();
            $lng = $record->getPositionLong();

            if ($lat === null || $lng === null) {
                continue;
            }

            $timestamp = $record->getTimestamp();
            if ($firstTimestamp === null && $timestamp !== null) {
                $firstTimestamp = $timestamp;
            }

            $elevation = $record->getEnhancedAltitude() ?? $record->getAltitude();
            $offset    = ($timestamp !== null && $firstTimestamp !== null)
                ? $timestamp->getTimestamp() - $firstTimestamp->getTimestamp()
                : 0;

            $points[] = [
                round($lat * $semicirclesToDegrees, 6),
                round($lng * $semicirclesToDegrees, 6),
                $elevation !== null ? round($elevation, 1) : null,
                $offset,
            ];
        }

        return $points ? json_encode($points) : null;
    }
}
