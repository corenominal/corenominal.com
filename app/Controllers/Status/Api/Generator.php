<?php

namespace App\Controllers\Status\Api;

use App\Controllers\BaseController;

class Generator extends BaseController
{
    public function stream(): void
    {
        set_time_limit(300);
        session()->close();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $body     = $this->request->getJSON(true) ?? [];
        $messages = is_array($body['messages'] ?? null) ? $body['messages'] : [];
        $model    = trim($body['model']    ?? config('Ollama')->defaultModel);
        $provider = trim($body['provider'] ?? 'ollama');

        if (empty($messages)) {
            echo "event: error\ndata: " . json_encode(['error' => 'No messages provided']) . "\n\n";
            flush();
            exit;
        }

        if ($provider === 'openrouter') {
            $this->streamOpenRouter($messages, $model);
        } else {
            $this->streamOllama($messages, $model);
        }

        exit;
    }

    private function streamOllama(array $messages, string $model): void
    {
        $ollamaIp = config('Ollama')->ip;

        $payload = json_encode([
            'model'    => $model,
            'messages' => $messages,
            'stream'   => true,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nConnection: close\r\n",
                'content' => $payload,
                'timeout' => 300,
            ],
        ]);

        $stream = @fopen("http://{$ollamaIp}:11434/api/chat", 'r', false, $context);

        if (!$stream) {
            echo "event: error\ndata: " . json_encode(['error' => 'Failed to connect to Ollama']) . "\n\n";
            flush();
            return;
        }

        $fullResponse = '';

        while (!feof($stream)) {
            $line = fgets($stream);
            if (!$line || trim($line) === '') continue;

            $data = json_decode($line, true);
            if (!$data) continue;

            if (!empty($data['message']['content'])) {
                $chunk         = $data['message']['content'];
                $fullResponse .= $chunk;
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                flush();
            }

            if (!empty($data['done'])) {
                echo "event: done\ndata: " . json_encode(['done' => true]) . "\n\n";
                flush();
                break;
            }
        }

        fclose($stream);
    }

    private function streamOpenRouter(array $messages, string $model): void
    {
        $apikey = config('Openrouter')->apikey;

        if ($apikey === '') {
            echo "event: error\ndata: " . json_encode(['error' => 'OpenRouter API key not configured']) . "\n\n";
            flush();
            return;
        }

        $payload = json_encode([
            'model'    => $model,
            'messages' => $messages,
            'stream'   => true,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => "Content-Type: application/json\r\nAuthorization: Bearer {$apikey}\r\nConnection: close\r\n",
                'content'       => $payload,
                'timeout'       => 300,
                'ignore_errors' => true,
            ],
        ]);

        $stream = @fopen('https://openrouter.ai/api/v1/chat/completions', 'r', false, $context);

        if (!$stream) {
            echo "event: error\ndata: " . json_encode(['error' => 'Failed to connect to OpenRouter']) . "\n\n";
            flush();
            return;
        }

        $meta       = stream_get_meta_data($stream);
        $statusLine = $meta['wrapper_data'][0] ?? '';
        preg_match('/HTTP\/\S+\s+(\d+)/', $statusLine, $statusMatch);
        $statusCode = (int) ($statusMatch[1] ?? 200);

        if ($statusCode !== 200) {
            $body    = stream_get_contents($stream);
            fclose($stream);
            $errData = json_decode($body, true);
            $errMsg  = $errData['error']['message'] ?? $errData['error'] ?? "OpenRouter error {$statusCode}";
            echo "event: error\ndata: " . json_encode(['error' => $errMsg]) . "\n\n";
            flush();
            return;
        }

        while (!feof($stream)) {
            $line = fgets($stream);
            if (!$line) continue;

            $trimmed = trim($line);
            if ($trimmed === '' || !str_starts_with($trimmed, 'data: ')) continue;

            $json = substr($trimmed, 6);
            if ($json === '[DONE]') break;

            $data = json_decode($json, true);
            if (!$data) continue;

            $chunk = $data['choices'][0]['delta']['content'] ?? null;

            if ($chunk !== null && $chunk !== '') {
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                flush();
            }
        }

        fclose($stream);

        echo "event: done\ndata: " . json_encode(['done' => true]) . "\n\n";
        flush();
    }
}
