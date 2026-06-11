<?php

namespace App\Controllers\Debug;

class Ai_apis extends BaseController
{
    public function list_available_models()
    {
        // Get the class name
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        // Store the function name
        $data['function'] = __FUNCTION__;

        // Get API masterkey
        $masterkey = config('ApiKeys')->masterKey;

        // Make a GET request to the API endpoint
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Content-Type: application/json\r\n" .
                    "apikey: {$masterkey}\r\n",
                'timeout' => 60,
            ],
        ]);
        $result = @file_get_contents(base_url('/api/ai/ollama/list'), false, $context);

        $data['dump'] = $result === false ? 'Request failed' : $result;

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }

    public function status_rewrite()
    {
        // Get the class name
        $class         = str_replace(__NAMESPACE__, '', static::class);
        $data['class'] = ltrim($class, '\\');
        // Store the function name
        $data['function'] = __FUNCTION__;

        // Get API masterkey
        $masterkey = config('ApiKeys')->masterKey;
        $data['status'] = 'There was a fox named Foo, who had a hole in his shoe. He said, "I don\'t care, I\'ll just patch it with some glue!"';

        // Make a POST request to the API endpoint
        $payload = json_encode([
            'text'  => $data['status'],
            'model' => 'gemma4:e4b',
        ]);
        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\n" .
                    "apikey: {$masterkey}\r\n",
                'content' => $payload,
                'timeout' => 60,
            ],
        ]);
        $result = @file_get_contents(base_url('/api/ai/status/rewrite'), false, $context);

        $data['dump'] = $result === false ? 'Request failed' : $result;

        $data['js']    = ['debug/debug'];
        $data['css']   = [];
        $data['title'] = 'Debug';

        return view('debug/default', $data);
    }
}
