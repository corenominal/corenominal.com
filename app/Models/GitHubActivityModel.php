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
        } catch (\Exception $e) {
            return '';
        }
    }
}
