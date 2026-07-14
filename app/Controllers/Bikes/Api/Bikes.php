<?php

namespace App\Controllers\Bikes\Api;

use App\Controllers\BaseController;
use App\Models\BikeModel;
use CodeIgniter\HTTP\ResponseInterface;

class Bikes extends BaseController
{
    private const STATUSES = ['active', 'retired', 'sold'];

    /**
     * POST /api/bikes
     */
    public function create()
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $bikeModel = new BikeModel();
        $bikeId    = $bikeModel->insert($this->extractData($json), true);

        if (! $bikeId) {
            return $this->response->setStatusCode(500)->setJSON([
                'status'  => 'error',
                'message' => 'Failed to create bike.',
            ]);
        }

        return $this->response->setStatusCode(201)->setJSON([
            'status'  => 'success',
            'message' => 'Bike created.',
            'id'      => $bikeId,
        ]);
    }

    /**
     * PUT /api/bikes/:id
     */
    public function update(?int $id = null)
    {
        if ($check = $this->requireAdmin()) {
            return $check;
        }

        $bikeModel = new BikeModel();
        $bike      = $bikeModel->find($id);

        if (! $bike) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 'error',
                'message' => 'Bike not found.',
            ]);
        }

        $json = $this->request->getJSON(true);

        $validation = $this->validateInput($json);
        if ($validation !== true) {
            return $this->response->setStatusCode(422)->setJSON([
                'status' => 'error',
                'errors' => $validation,
            ]);
        }

        $bikeModel->update($id, $this->extractData($json));

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => 'Bike updated.',
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
        $errors  = [];
        $brand   = trim($json['brand'] ?? '');
        $model   = trim($json['model'] ?? '');
        $status  = trim($json['status'] ?? '');
        $year    = $json['year'] ?? '';
        $totalKm = $json['total_km'] ?? '';

        if ($brand === '') {
            $errors['brand'] = 'Brand is required.';
        } elseif (strlen($brand) > 150) {
            $errors['brand'] = 'Brand must not exceed 150 characters.';
        }

        if ($model === '') {
            $errors['model'] = 'Model is required.';
        } elseif (strlen($model) > 150) {
            $errors['model'] = 'Model must not exceed 150 characters.';
        }

        if (! in_array($status, self::STATUSES, true)) {
            $errors['status'] = 'Status must be one of: ' . implode(', ', self::STATUSES) . '.';
        }

        if ($year !== '' && $year !== null && ! ctype_digit((string) $year)) {
            $errors['year'] = 'Year must be a whole number.';
        }

        if ($totalKm !== '' && $totalKm !== null && ! is_numeric($totalKm)) {
            $errors['total_km'] = 'Total km must be a number.';
        }

        return empty($errors) ? true : $errors;
    }

    private function extractData(array $json): array
    {
        $year    = $json['year'] ?? '';
        $totalKm = $json['total_km'] ?? '';

        return [
            'name'       => trim($json['name'] ?? ''),
            'brand'      => trim($json['brand']),
            'model'      => trim($json['model']),
            'year'       => ($year !== '' && $year !== null) ? (int) $year : null,
            'components' => trim($json['components'] ?? ''),
            'total_km'   => ($totalKm !== '' && $totalKm !== null) ? (float) $totalKm : 0,
            'status'     => trim($json['status']),
            'notes'      => trim($json['notes'] ?? ''),
        ];
    }
}
