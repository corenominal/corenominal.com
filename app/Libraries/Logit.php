<?php

namespace App\Libraries;

use Modules\Logs\Models\LogsModel;

class Logit
{
    const INFO     = 0;
    const WARNING  = 1;
    const ERROR    = 2;
    const CRITICAL = 3;
    const DEBUG    = 4;

    private LogsModel $model;

    public function __construct()
    {
        $this->model = new LogsModel();
    }

    public function info(string $message): void
    {
        $this->write($message, self::INFO);
    }

    public function warning(string $message): void
    {
        $this->write($message, self::WARNING);
    }

    public function error(string $message): void
    {
        $this->write($message, self::ERROR);
    }

    public function critical(string $message): void
    {
        $this->write($message, self::CRITICAL);
    }

    public function debug(string $message): void
    {
        $this->write($message, self::DEBUG);
    }

    private function write(string $message, int $level): void
    {
        $this->model->insert([
            'message' => $message,
            'level'   => $level,
        ]);
    }
}
