<?php

namespace App\Models;

use CodeIgniter\Model;
use DateTime;

class GitHubActivityModel extends Model
{
    protected $table            = 'github_activity';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields = [
        'github_event_id',
        'type',
        'repo',
        'icon',
        'label',
        'label_class',
        'description',
        'link',
        'github_created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Returns the latest activity records, ordered by GitHub event date descending.
     * Adds a computed `time_ago` field to each row.
     */
    public function getLatest(int $limit = 8): array
    {
        $rows = $this->orderBy('github_created_at', 'DESC')->limit($limit)->findAll();

        return array_map(function (array $row): array {
            $row['time_ago'] = $this->timeAgo($row['github_created_at'] ?? '');

            return $row;
        }, $rows);
    }

    /**
     * Returns all events for the last $days days grouped by Y-m-d date (most recent date first).
     */
    public function getGroupedByDate(int $days = 7): array
    {
        $rows = $this->where("github_created_at >= DATE_SUB(CURDATE(), INTERVAL {$days} DAY)")
                     ->orderBy('github_created_at', 'DESC')
                     ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $date             = substr($row['github_created_at'], 0, 10);
            $grouped[$date][] = $row;
        }

        return $grouped;
    }

    /**
     * Returns activity counts keyed by Y-m-d for the last $days days (oldest first).
     * Days with no events have a count of 0.
     */
    public function getHeatmapData(int $days = 30): array
    {
        $db     = \Config\Database::connect();
        $result = $db->query(
            "SELECT DATE(github_created_at) AS activity_date, COUNT(*) AS cnt
             FROM github_activity
             WHERE github_created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             GROUP BY DATE(github_created_at)",
            [$days]
        )->getResultArray();

        $counts = array_column($result, 'cnt', 'activity_date');

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date        = date('Y-m-d', strtotime("-{$i} days"));
            $data[$date] = (int) ($counts[$date] ?? 0);
        }

        return $data;
    }

    /**
     * Returns true if a record with the given GitHub event ID already exists.
     */
    public function existsByGitHubEventId(string $eventId): bool
    {
        return $this->where('github_event_id', $eventId)->countAllResults() > 0;
    }

    private function timeAgo(string $dateString): string
    {
        if (empty($dateString)) {
            return '';
        }

        try {
            $then = new DateTime($dateString);
            $now  = new DateTime();
            $diff = $now->diff($then);

            if ($diff->days > 7) {
                return $then->format('d M Y');
            }

            if ($diff->days >= 1) {
                return $diff->days . 'd ago';
            }

            if ($diff->h >= 1) {
                return $diff->h . 'h ago';
            }

            if ($diff->i >= 1) {
                return $diff->i . 'm ago';
            }

            return 'just now';
        } catch (\Exception) {
            return '';
        }
    }
}
