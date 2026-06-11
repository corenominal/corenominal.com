<?php

namespace App\Controllers\Api\Ai;

class Blog extends BaseController
{
    public function analyse(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body    = $this->request->getJSON(true) ?? [];
        $content = trim($body['content'] ?? '');
        $title   = trim($body['title'] ?? '');
        $model   = $body['model'] ?? config('Ollama')->defaultModel;

        if (empty($content)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No content provided.']);
        }

        $titleLine = $title ? "\nTitle: {$title}" : '';

        $prompt = <<<PROMPT
Analyse the following blog post and return a JSON response with two fields:
- "summary": a concise 2–3 sentence description of what the post is about and its main points.
- "suggestions": an array of improvement suggestions, each as an object with "area" (the aspect being addressed, e.g. "Structure", "Clarity", "SEO", "Tone", "Accessibility") and "suggestion" (a clear, actionable recommendation).

Provide between 3 and 8 suggestions covering a mix of areas.
Do not use markdown in any field values.
Respond only with valid JSON using this exact format:
{"summary": "...", "suggestions": [{"area": "...", "suggestion": "..."}, ...]}{$titleLine}

Content:
{$content}
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
                'timeout' => 90,
            ],
        ]);

        $result = @file_get_contents("http://{$ollamaIp}:11434/api/generate", false, $context);

        if ($result === false) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Failed to connect to Ollama.']);
        }

        $data     = json_decode($result, true);
        $response = json_decode($data['response'] ?? '{}', true);

        if (empty($response['summary']) || empty($response['suggestions']) || !is_array($response['suggestions'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        return $this->response->setJSON([
            'summary'     => $response['summary'],
            'suggestions' => array_values($response['suggestions']),
        ]);
    }

    public function rewrite(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body    = $this->request->getJSON(true) ?? [];
        $content = trim($body['content'] ?? '');
        $title   = trim($body['title'] ?? '');
        $model   = $body['model'] ?? config('Ollama')->defaultModel;

        if (empty($content)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No content provided.']);
        }

        $titleInstruction = $title
            ? "Also rewrite the title if it can be improved, and include it in the response as \"title\".\nTitle: {$title}\n"
            : "Do not include a \"title\" field in the response.\n";

        $responseFormat = $title
            ? '{"title": "...", "content": "..."}'
            : '{"content": "..."}';

        $prompt = <<<PROMPT
Rewrite the following blog post to fix any typos, spelling mistakes, grammar errors, and punctuation issues. Improve sentence flow and readability where needed, but preserve the author's original style, voice, and tone throughout. Do not use em dashes (—) anywhere in the rewritten text; use commas or restructure the sentence instead. Use colons and semicolons sparingly, only where they genuinely improve clarity.

{$titleInstruction}
Return only valid JSON in this exact format: {$responseFormat}
Do not include any markdown in field values.

Content:
{$content}
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
                'timeout' => 120,
            ],
        ]);

        $result = @file_get_contents("http://{$ollamaIp}:11434/api/generate", false, $context);

        if ($result === false) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Failed to connect to Ollama.']);
        }

        $data     = json_decode($result, true);
        $response = json_decode($data['response'] ?? '{}', true);

        if (empty($response['content'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        $output = ['content' => $response['content']];
        if ($title && !empty($response['title'])) {
            $output['title'] = $response['title'];
        }

        return $this->response->setJSON($output);
    }

    public function excerpt(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body    = $this->request->getJSON(true) ?? [];
        $content = trim($body['content'] ?? '');
        $title   = trim($body['title'] ?? '');
        $model   = $body['model'] ?? config('Ollama')->defaultModel;
        $length  = trim($body['length'] ?? 'medium');

        if (empty($content)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No content provided.']);
        }

        $lengths = [
            'short'  => 'one sentence',
            'medium' => 'two to three sentences',
            'long'   => 'a short paragraph of four to five sentences',
        ];

        if (!array_key_exists($length, $lengths)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid length. Must be short, medium, or long.']);
        }

        $titleLine = $title ? "\nTitle: {$title}" : '';

        $prompt = <<<PROMPT
Write an excerpt for the following blog post. The excerpt should be {$lengths[$length]} long, capture the essence of the post, and match the author's original style, voice, and tone. It should read as a natural teaser that draws the reader in — do not copy sentences verbatim from the post. The excerpt must be plain text only: no markdown, no bold, no italics, no headings, no bullet points, no quotation marks, no special characters.

Respond only with valid JSON in this exact format: {"excerpt": "..."}{$titleLine}

Content:
{$content}
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

        if (empty($response['excerpt'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        return $this->response->setJSON(['excerpt' => $response['excerpt']]);
    }

    public function creative(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body    = $this->request->getJSON(true) ?? [];
        $content = trim($body['content'] ?? '');
        $title   = trim($body['title'] ?? '');
        $model   = $body['model'] ?? config('Ollama')->defaultModel;

        if (empty($content)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No content provided.']);
        }

        $titleInstruction = $title
            ? "Also rewrite the title to better reflect the new tone, and include it in the response as \"title\".\nTitle: {$title}\n"
            : "Do not include a \"title\" field in the response.\n";

        $responseFormat = $title
            ? '{"title": "...", "content": "..."}'
            : '{"content": "..."}';

        $prompt = <<<PROMPT
Rewrite the following blog post to make it more engaging, vivid, and compelling. Elevate the language with stronger word choices and more varied sentence structure. Draw the reader in from the first sentence and keep the writing energetic throughout. Preserve the core meaning and factual content of the original.

Do not use emojis. Do not use em dashes (—); use commas or restructure the sentence instead. Use colons and semicolons sparingly, only where they genuinely improve clarity.

{$titleInstruction}
Return only valid JSON in this exact format: {$responseFormat}

Content:
{$content}
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
                'timeout' => 120,
            ],
        ]);

        $result = @file_get_contents("http://{$ollamaIp}:11434/api/generate", false, $context);

        if ($result === false) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Failed to connect to Ollama.']);
        }

        $data     = json_decode($result, true);
        $response = json_decode($data['response'] ?? '{}', true);

        if (empty($response['content'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        $output = ['content' => $response['content']];
        if ($title && !empty($response['title'])) {
            $output['title'] = $response['title'];
        }

        return $this->response->setJSON($output);
    }

    public function outline(): \CodeIgniter\HTTP\ResponseInterface
    {
        $body  = $this->request->getJSON(true) ?? [];
        $topic = trim($body['topic'] ?? '');
        $model = $body['model'] ?? config('Ollama')->defaultModel;

        if (empty($topic)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'No topic provided.']);
        }

        $prompt = <<<PROMPT
Suggest a blog post outline for the following topic or working title. Return a logical structure of H2 sections, each with optional H3 subheadings where they add value. Keep headings concise and clear — plain text only, no markdown symbols, no numbering.

Respond only with valid JSON in this exact format:
{"outline": [{"heading": "...", "subheadings": ["...", "..."]}, ...]}

Use an empty array for subheadings when a section does not need them. Provide between 4 and 8 top-level sections.

Topic: {$topic}
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

        if (empty($response['outline']) || !is_array($response['outline'])) {
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Unexpected response from Ollama.']);
        }

        return $this->response->setJSON(['outline' => array_values($response['outline'])]);
    }
}
