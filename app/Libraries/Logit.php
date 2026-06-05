<?php

namespace App\Libraries;

use Modules\Logs\Models\LogsModel;

/**
 * Writes log entries to the `logs` database table via LogsModel.
 *
 * Available as a shared service: service('logit')
 * service('logit')->info('Something happened');
 * service('logit')->error('Something went wrong');
 */
class Logit
{
    /** @var int Informational message */
    const INFO = 0;

    /** @var int Non-critical issue that should be reviewed */
    const WARNING = 1;

    /** @var int Recoverable error condition */
    const ERROR = 2;

    /** @var int Severe error requiring immediate attention */
    const CRITICAL = 3;

    /** @var int Verbose diagnostic output */
    const DEBUG = 4;

    private LogsModel $model;

    public function __construct()
    {
        $this->model = new LogsModel();
    }

    /**
     * Log an informational message (level 0).
     */
    public function info(string $message): void
    {
        $this->write($message, self::INFO);
    }

    /**
     * Log a warning message (level 1).
     */
    public function warning(string $message): void
    {
        $this->write($message, self::WARNING);
    }

    /**
     * Log an error message (level 2).
     */
    public function error(string $message): void
    {
        $this->write($message, self::ERROR);
    }

    /**
     * Log a critical message (level 3).
     */
    public function critical(string $message): void
    {
        $this->write($message, self::CRITICAL);
    }

    /**
     * Log a debug message (level 4).
     */
    public function debug(string $message): void
    {
        $this->write($message, self::DEBUG);
    }

    /**
     * @param int $level One of the Logit::* level constants.
     */
    private function write(string $message, int $level): void
    {
        $this->model->insert([
            'message' => $message,
            'level'   => $level,
        ]);
    }
}
