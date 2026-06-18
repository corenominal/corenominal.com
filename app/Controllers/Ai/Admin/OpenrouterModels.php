<?php

namespace App\Controllers\Ai\Admin;

use App\Models\OpenrouterModelModel;

class OpenrouterModels extends BaseController
{
    public function index(): string
    {
        $apikey = config('Openrouter')->apikey;
        $allModels = [];

        if ($apikey !== '') {
            try {
                $client   = \Config\Services::curlrequest();
                $response = $client->request('GET', 'https://openrouter.ai/api/v1/models', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apikey,
                        'Content-Type'  => 'application/json',
                    ],
                    'timeout' => 15,
                ]);
                $body      = json_decode($response->getBody(), true);
                $allModels = $body['data'] ?? [];
                usort($allModels, fn($a, $b) => strcmp($a['id'], $b['id']));
            } catch (\Exception $e) {
                session()->setFlashdata('error', 'Could not fetch models from OpenRouter: ' . $e->getMessage());
            }
        }

        $model        = new OpenrouterModelModel();
        $enabledRows  = $model->findAll();
        $enabledIds   = array_column($enabledRows, 'model_id');

        $data['allModels']          = $allModels;
        $data['enabledIds']         = $enabledIds;
        $data['js']                 = ['ai/admin/openrouter-models'];
        $data['title']              = 'OpenRouter Models';
        $data['templateMaxWidth']   = '100%';
        $data['templateMenu']       = 'ai/admin/sidebar-menu';

        return view('ai/admin/openrouter-models', $data);
    }

    public function save(): \CodeIgniter\HTTP\RedirectResponse
    {
        $selected = $this->request->getPost('models') ?? [];
        $selected = array_values(array_filter(array_map('trim', (array) $selected)));

        $model = new OpenrouterModelModel();
        $db    = \Config\Database::connect();
        $db->table('openrouter_models')->truncate();

        if (!empty($selected)) {
            $rows = array_map(fn($id) => ['model_id' => $id], $selected);
            $model->insertBatch($rows);
        }

        session()->setFlashdata('success', count($selected) . ' model' . (count($selected) !== 1 ? 's' : '') . ' saved.');

        return redirect()->to('/admin/ai/openrouter-models');
    }
}
