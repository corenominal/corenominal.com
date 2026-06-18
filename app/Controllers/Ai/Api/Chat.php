<?php

namespace App\Controllers\Ai\Api;

use App\Models\ChatSessionModel;
use App\Models\ChatMessageModel;
use App\Models\SystemPromptModel;
use App\Models\OpenrouterModelModel;

class Chat extends BaseController
{
    public function models(): \CodeIgniter\HTTP\ResponseInterface
    {
        $provider = $this->request->getGet('provider') ?? 'ollama';

        if ($provider === 'openrouter') {
            $model  = new OpenrouterModelModel();
            $rows   = $model->orderBy('model_id', 'ASC')->findAll();
            $models = array_column($rows, 'model_id');
            return $this->response->setJSON(['models' => $models]);
        }

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

    public function search(): \CodeIgniter\HTTP\ResponseInterface
    {
        $q = trim($this->request->getGet('q') ?? '');

        if (mb_strlen($q) < 2) {
            return $this->response->setJSON(['results' => []]);
        }

        $terms = array_values(array_filter(
            array_unique(preg_split('/\s+/', $q)),
            fn($t) => mb_strlen($t) >= 2
        ));

        if (empty($terms)) {
            return $this->response->setJSON(['results' => []]);
        }

        $db = \Config\Database::connect();

        $whereClauses    = [];
        $whereBindings   = [];
        $snippetClauses  = [];
        $snippetBindings = [];

        foreach ($terms as $term) {
            $like = '%' . $term . '%';
            $whereClauses[]    = "(cs.title LIKE ? OR EXISTS (SELECT 1 FROM chat_messages cm WHERE cm.session_id = cs.id AND cm.content LIKE ?))";
            $whereBindings[]   = $like;
            $whereBindings[]   = $like;
            $snippetClauses[]  = "cm2.content LIKE ?";
            $snippetBindings[] = $like;
        }

        $whereSQL   = implode(' AND ', $whereClauses);
        $snippetSQL = implode(' OR ', $snippetClauses);

        $sql = "SELECT cs.uuid, cs.title, cs.updated_at, cs.pinned,
            (SELECT cm2.content FROM chat_messages cm2
             WHERE cm2.session_id = cs.id AND ({$snippetSQL})
             ORDER BY cm2.id ASC LIMIT 1) AS snippet
        FROM chat_sessions cs
        WHERE cs.deleted_at IS NULL
          AND {$whereSQL}
        ORDER BY cs.pinned DESC, cs.updated_at DESC
        LIMIT 25";

        $rows = $db->query($sql, array_merge($snippetBindings, $whereBindings))->getResultArray();

        foreach ($rows as &$row) {
            if ($row['snippet'] !== null) {
                $pos = mb_strlen($row['snippet']);
                foreach ($terms as $term) {
                    $p = mb_stripos($row['snippet'], $term);
                    if ($p !== false && $p < $pos) {
                        $pos = $p;
                    }
                }
                $start   = max(0, $pos - 60);
                $excerpt = mb_substr($row['snippet'], $start, 180);
                if ($start > 0)                                 $excerpt = '…' . ltrim($excerpt);
                if ($start + 180 < mb_strlen($row['snippet'])) $excerpt .= '…';
                $row['snippet'] = $excerpt;
            }
        }
        unset($row);

        return $this->response->setJSON(['results' => $rows]);
    }

    public function sessions(): \CodeIgniter\HTTP\ResponseInterface
    {
        $model    = new ChatSessionModel();
        $sessions = $model->orderBy('pinned', 'DESC')
                          ->orderBy('updated_at', 'DESC')
                          ->findAll();

        return $this->response->setJSON(['sessions' => $sessions]);
    }

    public function createSession(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body     = $this->request->getJSON(true) ?? [];
        $model    = $body['model']    ?? 'llama3.2';
        $provider = $body['provider'] ?? 'ollama';

        $sessionModel = new ChatSessionModel();
        $uuid         = $this->generateUuid();

        $sessionModel->insert([
            'uuid'     => $uuid,
            'title'    => 'New Chat',
            'model'    => $model,
            'provider' => $provider,
            'pinned'   => 0,
        ]);

        $session = $sessionModel->where('uuid', $uuid)->first();

        return $this->response->setStatusCode(201)->setJSON(['session' => $session]);
    }

    public function updateSession(string $uuid): \CodeIgniter\HTTP\ResponseInterface
    {
        $sessionModel = new ChatSessionModel();
        $session      = $sessionModel->where('uuid', $uuid)->first();

        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session not found']);
        }

        $body    = $this->request->getJSON(true) ?? [];
        $allowed = [];

        if (isset($body['title']))    $allowed['title']    = trim($body['title']);
        if (isset($body['pinned']))   $allowed['pinned']   = (int) $body['pinned'];
        if (isset($body['model']))    $allowed['model']    = $body['model'];
        if (isset($body['provider'])) $allowed['provider'] = $body['provider'];

        if (!empty($allowed)) {
            $sessionModel->update($session['id'], $allowed);
        }

        $updated = $sessionModel->find($session['id']);

        return $this->response->setJSON(['session' => $updated]);
    }

    public function deleteSession(string $uuid): \CodeIgniter\HTTP\ResponseInterface
    {
        $sessionModel = new ChatSessionModel();
        $session      = $sessionModel->where('uuid', $uuid)->first();

        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session not found']);
        }

        $messageModel = new ChatMessageModel();
        $messageModel->where('session_id', $session['id'])->delete();
        $sessionModel->delete($session['id']);

        return $this->response->setJSON(['success' => true]);
    }

    public function messages(string $uuid): \CodeIgniter\HTTP\ResponseInterface
    {
        $sessionModel = new ChatSessionModel();
        $session      = $sessionModel->where('uuid', $uuid)->first();

        if (!$session) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'Session not found']);
        }

        $messageModel = new ChatMessageModel();
        $messages     = $messageModel->where('session_id', $session['id'])
                                     ->orderBy('id', 'ASC')
                                     ->findAll();

        foreach ($messages as &$msg) {
            $msg['images']    = isset($msg['images']) && $msg['images'] !== null
                ? (json_decode($msg['images'], true) ?? [])
                : [];
            $msg['documents'] = isset($msg['documents']) && $msg['documents'] !== null
                ? (json_decode($msg['documents'], true) ?? [])
                : [];
        }
        unset($msg);

        return $this->response->setJSON(['session' => $session, 'messages' => $messages]);
    }

    public function extractPdf(): \CodeIgniter\HTTP\ResponseInterface
    {
        $file = $this->request->getFile('file');

        if (!$file || !$file->isValid() || $file->getMimeType() !== 'application/pdf') {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid PDF file']);
        }

        $tmpPath = $file->getTempName();

        $parser = new \Smalot\PdfParser\Parser();
        try {
            $pdf  = $parser->parseFile($tmpPath);
            $text = $pdf->getText();
        } catch (\Exception) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Could not extract text from PDF']);
        }

        $text = trim($text);
        if ($text === '') {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'No text found in PDF (may be image-only)']);
        }

        return $this->response->setJSON(['text' => $text]);
    }

    public function stream(): void
    {
        set_time_limit(300);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-store');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');

        $body        = $this->request->getJSON(true) ?? [];
        $sessionUuid = $body['session_uuid'] ?? null;
        $userMessage = trim($body['message'] ?? '');
        $model       = $body['model']    ?? 'llama3.2';
        $provider    = $body['provider'] ?? 'ollama';
        $images      = is_array($body['images'] ?? null) ? $body['images'] : [];
        $documents   = is_array($body['documents'] ?? null) ? $body['documents'] : [];

        if (empty($userMessage) && empty($images) && empty($documents)) {
            echo "event: error\ndata: " . json_encode(['error' => 'Empty message']) . "\n\n";
            flush();
            exit;
        }

        $sessionModel = new ChatSessionModel();
        $messageModel = new ChatMessageModel();

        $session = $sessionUuid ? $sessionModel->where('uuid', $sessionUuid)->first() : null;

        if (!$session) {
            $sessionUuid = $this->generateUuid();
            if (!empty($userMessage)) {
                $title = mb_substr($userMessage, 0, 60) . (mb_strlen($userMessage) > 60 ? '…' : '');
            } elseif (!empty($documents)) {
                $title = $documents[0]['name'] ?? 'Document';
            } else {
                $title = 'Image';
            }

            $sessionModel->insert([
                'uuid'     => $sessionUuid,
                'title'    => $title,
                'model'    => $model,
                'provider' => $provider,
                'pinned'   => 0,
            ]);

            $session = $sessionModel->where('uuid', $sessionUuid)->first();
        }

        if (!$session) {
            echo "event: error\ndata: " . json_encode(['error' => 'Failed to create session']) . "\n\n";
            flush();
            exit;
        }

        $messageModel->insert([
            'session_id' => $session['id'],
            'role'       => 'user',
            'content'    => $userMessage,
            'images'     => !empty($images) ? json_encode($images) : null,
            'documents'  => !empty($documents) ? json_encode($documents) : null,
        ]);

        $history = $messageModel->where('session_id', $session['id'])
                                ->orderBy('id', 'ASC')
                                ->findAll();

        echo "event: session\ndata: " . json_encode(['uuid' => $sessionUuid, 'title' => $session['title']]) . "\n\n";
        flush();

        $activePrompt = (new SystemPromptModel())->getActive();

        if (($session['provider'] ?? 'ollama') === 'openrouter') {
            $this->streamOpenRouter($session, $history, $messageModel, $activePrompt, $images);
        } else {
            $this->streamOllama($session, $history, $messageModel, $activePrompt);
        }

        exit;
    }

    private function streamOllama(array $session, array $history, ChatMessageModel $messageModel, ?array $activePrompt): void
    {
        $messages = array_map(function ($m) {
            $content = $m['content'];
            if (empty($m['thinking'])) {
                $content = ltrim(preg_replace('/^<think>[\s\S]*?<\/think>/s', '', $content));
            }
            if (!empty($m['documents'])) {
                $docs = json_decode($m['documents'], true);
                if (is_array($docs) && count($docs) > 0) {
                    $docBlocks = array_map(function ($doc) {
                        return "--- " . ($doc['name'] ?? 'document') . " ---\n" . ($doc['content'] ?? '') . "\n--- end of " . ($doc['name'] ?? 'document') . " ---";
                    }, $docs);
                    $preamble = "The following document(s) have been attached:\n\n" . implode("\n\n", $docBlocks);
                    $content  = $content !== '' ? $preamble . "\n\n" . $content : $preamble;
                }
            }
            $msg = ['role' => $m['role'], 'content' => $content];
            if (!empty($m['images'])) {
                $stored = json_decode($m['images'], true);
                if (is_array($stored) && count($stored) > 0) {
                    $msg['images'] = array_map(function ($dataUrl) {
                        if (preg_match('/^data:[^;]+;base64,(.+)$/', $dataUrl, $matches)) {
                            return $matches[1];
                        }
                        return $dataUrl;
                    }, $stored);
                }
            }
            return $msg;
        }, $history);

        if ($activePrompt && $activePrompt['content'] !== '') {
            array_unshift($messages, ['role' => 'system', 'content' => $activePrompt['content']]);
        }

        $ollamaIp = config('Ollama')->ip;
        $payload  = json_encode([
            'model'    => $session['model'],
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
        $fullThinking = '';

        while (!feof($stream)) {
            $line = fgets($stream);
            if (!$line || trim($line) === '') continue;

            $data = json_decode($line, true);
            if (!$data) continue;

            if (!empty($data['message']['thinking'])) {
                $chunk         = $data['message']['thinking'];
                $fullThinking .= $chunk;
                echo "data: " . json_encode(['thinking' => $chunk]) . "\n\n";
                flush();
            }

            if (!empty($data['message']['content'])) {
                $chunk         = $data['message']['content'];
                $fullResponse .= $chunk;
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                flush();
            }

            if (!empty($data['done'])) {
                if ($fullThinking === '' && preg_match('/^<think>([\s\S]*?)<\/think>([\s\S]*)$/s', $fullResponse, $m)) {
                    $fullThinking = trim($m[1]);
                    $fullResponse = ltrim($m[2]);
                }

                $messageModel->insert([
                    'session_id' => $session['id'],
                    'role'       => 'assistant',
                    'model'      => $session['model'],
                    'content'    => $fullResponse,
                    'thinking'   => $fullThinking !== '' ? $fullThinking : null,
                ]);

                $saved = $messageModel->find($messageModel->getInsertID());

                echo "event: done\ndata: " . json_encode([
                    'done'       => true,
                    'model'      => $saved['model'],
                    'created_at' => $saved['created_at'],
                ]) . "\n\n";
                flush();
                break;
            }
        }

        fclose($stream);
    }

    private function streamOpenRouter(array $session, array $history, ChatMessageModel $messageModel, ?array $activePrompt, array $latestImages): void
    {
        $apikey = config('Openrouter')->apikey;

        if ($apikey === '') {
            echo "event: error\ndata: " . json_encode(['error' => 'OpenRouter API key not configured']) . "\n\n";
            flush();
            return;
        }

        // Build messages in OpenAI format
        $messages = [];

        if ($activePrompt && $activePrompt['content'] !== '') {
            $messages[] = ['role' => 'system', 'content' => $activePrompt['content']];
        }

        foreach ($history as $m) {
            $content = $m['content'];
            if (empty($m['thinking'])) {
                $content = ltrim(preg_replace('/^<think>[\s\S]*?<\/think>/s', '', $content));
            }

            if (!empty($m['documents'])) {
                $docs = json_decode($m['documents'], true);
                if (is_array($docs) && count($docs) > 0) {
                    $docBlocks = array_map(function ($doc) {
                        return "--- " . ($doc['name'] ?? 'document') . " ---\n" . ($doc['content'] ?? '') . "\n--- end of " . ($doc['name'] ?? 'document') . " ---";
                    }, $docs);
                    $preamble = "The following document(s) have been attached:\n\n" . implode("\n\n", $docBlocks);
                    $content  = $content !== '' ? $preamble . "\n\n" . $content : $preamble;
                }
            }

            // Build multimodal content array for user messages with images
            if ($m['role'] === 'user' && !empty($m['images'])) {
                $stored = json_decode($m['images'], true);
                if (is_array($stored) && count($stored) > 0) {
                    $parts = [];
                    if ($content !== '') {
                        $parts[] = ['type' => 'text', 'text' => $content];
                    }
                    foreach ($stored as $dataUrl) {
                        $parts[] = ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]];
                    }
                    $messages[] = ['role' => 'user', 'content' => $parts];
                    continue;
                }
            }

            $messages[] = ['role' => $m['role'], 'content' => $content];
        }

        $payload = json_encode([
            'model'    => $session['model'],
            'messages' => $messages,
            'stream'   => true,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$apikey}\r\nConnection: close\r\n",
                'content' => $payload,
                'timeout' => 300,
            ],
        ]);

        $stream = @fopen('https://openrouter.ai/api/v1/chat/completions', 'r', false, $context);

        if (!$stream) {
            echo "event: error\ndata: " . json_encode(['error' => 'Failed to connect to OpenRouter']) . "\n\n";
            flush();
            return;
        }

        $fullResponse = '';
        $fullThinking = '';

        while (!feof($stream)) {
            $line = fgets($stream);
            if (!$line) continue;

            $trimmed = trim($line);
            if ($trimmed === '' || !str_starts_with($trimmed, 'data: ')) continue;

            $json = substr($trimmed, 6);
            if ($json === '[DONE]') break;

            $data = json_decode($json, true);
            if (!$data) continue;

            $chunk    = $data['choices'][0]['delta']['content']   ?? null;
            $thinking = $data['choices'][0]['delta']['reasoning'] ?? null;

            if ($thinking !== null && $thinking !== '') {
                $fullThinking .= $thinking;
                echo "data: " . json_encode(['thinking' => $thinking]) . "\n\n";
                flush();
            }

            if ($chunk !== null && $chunk !== '') {
                $fullResponse .= $chunk;
                echo "data: " . json_encode(['content' => $chunk]) . "\n\n";
                flush();
            }
        }

        fclose($stream);

        $messageModel->insert([
            'session_id' => $session['id'],
            'role'       => 'assistant',
            'model'      => $session['model'],
            'content'    => $fullResponse,
            'thinking'   => $fullThinking !== '' ? $fullThinking : null,
        ]);

        $saved = $messageModel->find($messageModel->getInsertID());

        echo "event: done\ndata: " . json_encode([
            'done'       => true,
            'model'      => $saved['model'],
            'created_at' => $saved['created_at'],
        ]) . "\n\n";
        flush();
    }

    private function generateUuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
