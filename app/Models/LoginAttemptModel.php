<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginAttemptModel extends Model
{
    protected $table      = 'login_attempts';
    protected $allowedFields = ['ip_address', 'email'];
    protected $useTimestamps = true;

    public function record(string $ip, string $email): void
    {
        $this->insert(['ip_address' => $ip, 'email' => $email]);
    }

    public function countRecent(string $ip, int $windowMinutes): int
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$windowMinutes} minutes"));
        return $this->where('ip_address', $ip)
                    ->where('created_at >=', $since)
                    ->countAllResults();
    }

    public function isBlocked(string $ip): bool
    {
        $config = config('Fail2Ban');
        return $this->countRecent($ip, $config->banWindowMinutes) >= $config->maxAttempts;
    }

    public function clearForIp(string $ip): void
    {
        $this->where('ip_address', $ip)->delete();
    }
}
