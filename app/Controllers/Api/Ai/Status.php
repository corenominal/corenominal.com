<?php

namespace App\Controllers\Api\Ai;

class Status extends BaseController
{
    public function rewrite(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body   = $this->request->getJSON(true) ?? [];
        $text   = trim($body['text'] ?? '');
        $model  = $body['model'] ?? config('Ollama')->defaultModel;
        $expand = !empty($body['expand']);

        if (empty($text)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No text provided.']);
        }

        $expandLine = $expand ? "\nFeel free to expand on the given text where it adds clarity." : '';

        $prompt = <<<PROMPT
Rewrite the following in 5 alternative versions.
Do not use any markdown formatting in the versions.
Each version should not exceed 500 characters.
Do not use m dashes or numbering in the response.
Keep the meaning intact.
Make it clear, natural, and concise.
Avoid hype, clichés, and corporate tone.{$expandLine}

Tone: friendly, direct, British English.
Audience: technical but not expert.
Response: json, using this exact format: {"suggestions": ["...", "...", "...", "...", "..."]}
Text: {$text}
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

        if (empty($response['suggestions']) || !is_array($response['suggestions'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        return $this->response->setJSON(['suggestions' => array_values($response['suggestions'])]);
    }
}
