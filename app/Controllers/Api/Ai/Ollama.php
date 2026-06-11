<?php

namespace App\Controllers\Api\Ai;

class Ollama extends BaseController
{
    public function list(): \CodeIgniter\HTTP\ResponseInterface
    {
        $ollamaIp = config('Ollama')->ip;

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
            ],
        ]);

        $result = @file_get_contents("http://{$ollamaIp}:11434/api/tags", false, $context);

        if ($result === false) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Failed to connect to Ollama']);
        }

        $data   = json_decode($result, true);
        $models = array_column($data['models'] ?? [], 'name');
        sort($models);

        return $this->response->setJSON(['models' => $models]);
    }
}
