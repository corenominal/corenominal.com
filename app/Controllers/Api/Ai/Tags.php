<?php

namespace App\Controllers\Api\Ai;

class Tags extends BaseController
{
    public function generate(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body  = $this->request->getJSON(true) ?? [];
        $text  = trim($body['text'] ?? '');
        $model = $body['model'] ?? config('Ollama')->defaultModel;

        if (empty($text)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No text provided.']);
        }

        $prompt = <<<PROMPT
Read the following text and suggest a list of relevant tags that could be used to tag or categorise it on a blog or social media platform. Return between 5 and 15 tags. Each tag must be lowercase, contain no spaces, no hyphens, and no punctuation of any kind. Do not include markdown or any explanation.

Respond only with valid JSON in this exact format: {"tags": ["tag1", "tag2"]}

Text:
{$text}
PROMPT;

        $ollamaIp = config('Ollama')->ip;
        $payload  = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'format' => 'json',
            'stream' => false,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 60,
            ],
        ]);

        $result = @file_get_contents("http://{$ollamaIp}:11434/api/generate", false, $context);

        if ($result === false) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Failed to connect to Ollama.']);
        }

        $data     = json_decode($result, true);
        $response = json_decode($data['response'] ?? '{}', true);

        if (empty($response['tags']) || !is_array($response['tags'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        $tags = array_values(array_filter(array_map(function (string $tag): string {
            return preg_replace('/[^a-z0-9]/', '', strtolower($tag));
        }, $response['tags'])));

        return $this->response->setJSON(['tags' => $tags]);
    }
}
