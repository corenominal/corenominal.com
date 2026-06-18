<?php

namespace App\Controllers\Debug;

use App\Libraries\Markdown;

class Openrouter extends BaseController
{
    public function list_models()
    {
        // Get the class name
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        // Store the function name
        $data['function'] = __FUNCTION__;

        // Get the Openrouter config
        $apikey = config('Openrouter')->apikey;

        $client   = \Config\Services::curlrequest();
        $response = $client->request('GET', 'https://openrouter.ai/api/v1/models', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apikey,
                'Content-Type'  => 'application/json',
            ],
        ]);

        $body          = json_decode($response->getBody(), true);
        $data['dump'] = $body['data'] ?? [];

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }

    public function credits()
    {
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        $data['function'] = __FUNCTION__;

        $apikey = config('Openrouter')->apikey;

        $client   = \Config\Services::curlrequest();
        $response = $client->request('GET', 'https://openrouter.ai/api/v1/auth/key', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apikey,
                'Content-Type'  => 'application/json',
            ],
        ]);

        $body          = json_decode($response->getBody(), true);
        $data['dump'] = $body['data'] ?? $body;

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }

    public function deepseek_test_prompt()
    {
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        $data['function'] = __FUNCTION__;

        $apikey = config('Openrouter')->apikey;

        $client   = \Config\Services::curlrequest();
        $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apikey,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model'    => 'deepseek/deepseek-v4-flash',
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => 'Tell me about something interesting.',
                    ],
                ],
            ]),
        ]);

        $body          = json_decode($response->getBody(), true);
        $result = $body['choices'][0]['message']['content'] ?? $body;
        $markdown = New Markdown;
        $markdown->setMarkdown($result);
        $html = $markdown->convert();
        $data['html'] = $html;

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }

    public function nemotron_test_prompt()
    {
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        $data['function'] = __FUNCTION__;

        $apikey = config('Openrouter')->apikey;

        $client   = \Config\Services::curlrequest();
        $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apikey,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model'    => 'nvidia/nemotron-3-nano-30b-a3b:free',
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => 'Tell me about something interesting.',
                    ],
                ],
            ]),
        ]);

        $body          = json_decode($response->getBody(), true);
        $result = $body['choices'][0]['message']['content'] ?? $body;
        $markdown = New Markdown;
        $markdown->setMarkdown($result);
        $html = $markdown->convert();
        $data['html'] = $html;

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }

    public function gpt_oss_quotes_json()
    {
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        $data['function'] = __FUNCTION__;

        $apikey = config('Openrouter')->apikey;

        $client   = \Config\Services::curlrequest();
        $response = $client->request('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $apikey,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model'    => 'openai/gpt-oss-120b:free',
                'messages' => [
                    [
                        'role'    => 'user',
                        'content' => 'Return exactly 5 random quotes as a JSON array of strings. Respond with only the JSON array, no explanation.',
                    ],
                ],
            ]),
        ]);

        $body   = json_decode($response->getBody(), true);
        $result = $body['choices'][0]['message']['content'] ?? '[]';

        // Strip markdown code fences if present
        $result = preg_replace('/^```(?:json)?\s*/i', '', trim($result));
        $result = preg_replace('/\s*```$/', '', $result);

        $data['dump'] = json_decode($result, true) ?? $result;

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }

}
