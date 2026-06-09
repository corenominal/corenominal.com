<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Set CORS headers so the API can be called from any origin (browser or server).
        // The allowed headers list must include 'apikey' and 'user-uuid' so browsers don't
        // strip them from cross-origin preflight requests.
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PATCH, PUT, DELETE');
        header('Access-Control-Allow-Headers: apikey, user-uuid, email, Content-Type, Content-Length, Accept-Encoding');

        // Browsers send an OPTIONS preflight before any cross-origin request that uses
        // custom headers. We respond with the CORS headers above and exit immediately —
        // there is no actual request body to process.
        if ('OPTIONS' === $_SERVER['REQUEST_METHOD']) {
            exit();
        }

        // Every request must supply an 'apikey' header. Reject immediately if absent or empty.
        if (!$request->hasHeader('apikey')) {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            exit(json_encode(['error' => 'No API key provided.']));
        }

        $apikey = $request->header('apikey')->getValue();

        if (empty($apikey)) {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            exit(json_encode(['error' => 'Empty API key provided.']));
        }

        // $success tracks whether the key has been validated. We start false and set it
        // true via one of the two validation paths below.
        $success = false;

        // --- Path 1: master key ---
        // The master key is stored in the .env file under apikeys.masterKey and loaded via
        // the ApiKeys config class. A master-key request is treated as admin without needing
        // a user UUID, so $GLOBALS['is_admin'] is set here for controllers to check.
        $config = config('ApiKeys');
        if ($config->masterKey == $apikey) {
            $success = true;
            $GLOBALS['is_admin'] = true;
        }

        // --- Path 2: per-user key stored in the database ---
        // If the master key didn't match we look the key up in the apikeys table.
        // A 'user-uuid' header is required here because the same key value could in theory
        // exist for more than one user; scoping the query by user_uuid ensures we find the
        // correct record and prevents one user's key from accidentally authenticating another.
        if (!$success) {
            if (!$request->hasHeader('user-uuid')) {
                header('HTTP/1.1 401 Unauthorized', true, 401);
                exit(json_encode(['error' => 'No user UUID provided.']));
            }

            $user_uuid = $request->header('user-uuid')->getValue();

            if (empty($user_uuid)) {
                header('HTTP/1.1 401 Unauthorized', true, 401);
                exit(json_encode(['error' => 'Empty user UUID provided.']));
            }

            // Query the apikeys table for a row that matches both the user's UUID and
            // the submitted key. ApikeyModel uses soft deletes, so deleted keys are
            // automatically excluded by the model's default query scope.
            $model  = model('ApikeyModel');
            $record = $model->where('user_uuid', $user_uuid)->where('key', $apikey)->first();

            if ($record) {
                $success = true;

                // If the database record marks this key as admin, expose that fact globally
                // so controllers can gate admin-only routes without re-querying the database.
                if (!empty($record['is_admin'])) {
                    $GLOBALS['is_admin'] = true;
                }
            }
        }

        // If neither validation path succeeded the key is invalid; reject the request.
        if (!$success) {
            header('HTTP/1.1 401 Unauthorized', true, 401);
            exit(json_encode(['error' => 'Invalid API key.']));
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }
}
