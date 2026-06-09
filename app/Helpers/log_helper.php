<?php

/**
 * Writes a log entry to the database via LogsModel.
 *
 * @param string $msg   The message to log.
 * @param int    $level Log level — use \App\Libraries\Logit constants:
 *                      Logit::INFO     (0) — informational message
 *                      Logit::WARNING  (1) — non-critical issue that should be reviewed
 *                      Logit::ERROR    (2) — recoverable error condition
 *                      Logit::CRITICAL (3) — severe error requiring immediate attention
 *                      Logit::DEBUG    (4) — verbose diagnostic output
 */
function logit(string $msg = 'No message provided', int $level = 0)
{
    try {
        $model = new \App\Models\LogsModel();
        return $model->insert([
            'message' => $msg,
            'level'   => $level,
        ]);
    } catch (\Throwable $e) {
        error_log('logit() failed: ' . $e->getMessage());
        return false;
    }
}
