<?php

namespace App\Controllers\Api\Ai;

class Images extends BaseController
{
    public function alttext(): \CodeIgniter\HTTP\ResponseInterface
    {
        [$base64, $model, $error] = $this->resolveImage();
        if ($error) return $error;

        $prompt = 'Generate concise, descriptive alt text for this image suitable for screen readers. Be specific but brief (under 125 characters). Do not begin with "Image of" or "Photo of". Return only the alt text, nothing else.';

        $result = $this->ollama($base64, $model, $prompt);
        if ($result instanceof \CodeIgniter\HTTP\ResponseInterface) return $result;

        return $this->response->setJSON(['alt_text' => $result]);
    }

    public function describe(): \CodeIgniter\HTTP\ResponseInterface
    {
        [$base64, $model, $error] = $this->resolveImage();
        if ($error) return $error;

        $prompt = 'Describe this image in detail.';

        $result = $this->ollama($base64, $model, $prompt);
        if ($result instanceof \CodeIgniter\HTTP\ResponseInterface) return $result;

        return $this->response->setJSON(['description' => $result]);
    }

    private function resolveImage(): array
    {
        $body  = $this->request->getJSON(true) ?? [];
        $url   = trim($body['url'] ?? '');
        $image = trim($body['image'] ?? '');
        $model = $body['model'] ?? 'llama3.2-vision';

        if (empty($url) && empty($image)) {
            return [null, null, $this->response->setStatusCode(400)->setJSON(['error' => 'Provide either a url or a base64-encoded image.'])];
        }

        if (!empty($url)) {
            $scheme = parse_url($url, PHP_URL_SCHEME);
            if (!in_array($scheme, ['http', 'https'], true)) {
                return [null, null, $this->response->setStatusCode(400)->setJSON(['error' => 'Image URL must use http or https.'])];
            }

            $imageData = @file_get_contents($url);
            if ($imageData === false) {
                return [null, null, $this->response->setStatusCode(422)->setJSON(['error' => 'Could not fetch image from URL.'])];
            }

            $base64 = base64_encode($imageData);
        } else {
            // Strip data URI prefix if present (e.g. "data:image/png;base64,...")
            $base64 = preg_replace('/^data:[^;]+;base64,/', '', $image);

            if (base64_decode($base64, true) === false) {
                return [null, null, $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid base64 image data.'])];
            }
        }

        return [$base64, $model, null];
    }

    private function ollama(string $base64, string $model, string $prompt): string|\CodeIgniter\HTTP\ResponseInterface
    {
        $ollamaIp = config('Ollama')->ip;
        $payload  = json_encode([
            'model'  => $model,
            'prompt' => $prompt,
            'images' => [$base64],
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

        $data = json_decode($result, true);
        $text = trim($data['response'] ?? '');

        if (empty($text)) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        return $text;
    }
}
