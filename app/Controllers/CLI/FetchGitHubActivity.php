<?php

namespace App\Controllers\CLI;

use App\Controllers\BaseController;
use App\Models\GitHubActivityModel;
use CodeIgniter\CLI\CLI;

/**
 * Fetches public GitHub activity for the configured user and persists
 * new events to the database. Intended to be run via crontab.
 *
 * Usage:
 *   sudo -u _www php /path/to/codeigniter/public/index.php cli/fetch-github-activity
 *
 * Crontab example (every 30 minutes):
 *   *\/30 * * * * sudo -u _www php /path/to/codeigniter/public/index.php cli/fetch-github-activity >> /dev/null 2>&1
 */
final class FetchGitHubActivity extends BaseController
{
    public function index(): void
    {
        $username = (string) config('GitHub')->username;

        if (empty($username)) {
            CLI::error('GitHub username is not configured.');

            return;
        }

        $headers = [
            'User-Agent: ' . (config('App')->siteName ?: 'PhilipNewborough'),
            'Accept: application/vnd.github.v3+json',
        ];

        $token = (string) config('GitHub')->token;

        if (! empty($token)) {
            $headers[] = 'Authorization: token ' . $token;
        }

        $events = $this->apiGet(
            'https://api.github.com/users/' . rawurlencode($username) . '/events/public',
            $headers
        );

        if ($events === null) {
            CLI::error('Failed to fetch GitHub activity.');

            return;
        }

        $formatted = $this->formatGitHubEvents($events, $headers);
        $model     = new GitHubActivityModel();
        $inserted  = 0;
        $skipped   = 0;

        foreach ($formatted as $item) {
            if (empty($item['github_event_id'])) {
                continue;
            }

            if ($model->existsByGitHubEventId($item['github_event_id'])) {
                $skipped++;
                continue;
            }

            $model->insert([
                'github_event_id'   => $item['github_event_id'],
                'type'              => $item['type'],
                'repo'              => $item['repo'],
                'icon'              => $item['icon'],
                'label'             => $item['label'],
                'label_class'       => $item['label_class'],
                'description'       => $item['description'],
                'link'              => $item['link'],
                'github_created_at' => $item['github_created_at'],
            ]);

            $inserted++;
        }

        CLI::write(sprintf(
            'Done. Inserted: %d, Skipped (already exist): %d',
            $inserted,
            $skipped
        ));
    }

    private function formatGitHubEvents(array $events, array $authHeaders): array
    {
        $formatted = [];

        foreach ($events as $event) {
            $eventId     = (string) ($event['id'] ?? '');
            $type        = $event['type'] ?? '';
            $repo        = $event['repo']['name'] ?? '';
            $createdAt   = $event['created_at'] ?? '';
            $link        = 'https://github.com/' . $repo;
            $icon        = 'bi-code-square';
            $description = '';
            $label       = '';
            $labelClass  = 'secondary';

            switch ($type) {
                case 'PushEvent':
                    $commits = $event['payload']['commits'] ?? null;
                    $count   = isset($event['payload']['size'])
                        ? (int) $event['payload']['size']
                        : (is_array($commits) ? count($commits) : 0);

                    if ($count === 0 && isset($event['payload']['before'], $event['payload']['head']) && is_string($repo) && $repo !== '') {
                        $before     = $event['payload']['before'];
                        $head       = $event['payload']['head'];
                        $compareUrl = 'https://api.github.com/repos/' . $repo . '/compare/' . rawurlencode($before) . '...' . rawurlencode($head);
                        $compare    = $this->apiGet($compareUrl, $authHeaders);

                        if (is_array($compare) && isset($compare['total_commits'])) {
                            $count = (int) $compare['total_commits'];
                        }
                    }

                    $branch      = ltrim(str_replace('refs/heads/', '', $event['payload']['ref'] ?? 'main'), '/');
                    $icon        = 'bi-git';
                    $label       = 'push';
                    $labelClass  = 'primary';
                    $description = sprintf(
                        'Pushed %d commit%s to <code>%s</code> on <strong>%s</strong>',
                        $count,
                        $count !== 1 ? 's' : '',
                        esc($branch),
                        esc($repo)
                    );
                    break;

                case 'CreateEvent':
                    $refType     = $event['payload']['ref_type'] ?? 'repository';
                    $ref         = $event['payload']['ref'] ?? '';
                    $icon        = 'bi-plus-circle-fill';
                    $label       = 'create';
                    $labelClass  = 'success';
                    $description = $ref
                        ? sprintf('Created %s <code>%s</code> on <strong>%s</strong>', esc($refType), esc($ref), esc($repo))
                        : sprintf('Created repository <strong>%s</strong>', esc($repo));
                    break;

                case 'DeleteEvent':
                    $refType     = $event['payload']['ref_type'] ?? 'branch';
                    $ref         = $event['payload']['ref'] ?? '';
                    $icon        = 'bi-dash-circle';
                    $label       = 'delete';
                    $labelClass  = 'danger';
                    $description = sprintf('Deleted %s <code>%s</code> on <strong>%s</strong>', esc($refType), esc($ref), esc($repo));
                    break;

                case 'WatchEvent':
                    $icon        = 'bi-star-fill';
                    $label       = 'star';
                    $labelClass  = 'warning';
                    $description = sprintf('Starred <strong>%s</strong>', esc($repo));
                    break;

                case 'ForkEvent':
                    $icon        = 'bi-diagram-2-fill';
                    $label       = 'fork';
                    $labelClass  = 'info';
                    $description = sprintf('Forked <strong>%s</strong>', esc($repo));
                    break;

                case 'IssuesEvent':
                    $action      = $event['payload']['action'] ?? 'updated';
                    $issueTitle  = $event['payload']['issue']['title'] ?? '';
                    $link        = $event['payload']['issue']['html_url'] ?? $link;
                    $icon        = 'bi-exclamation-circle-fill';
                    $label       = strtolower($action) . ' issue';
                    $labelClass  = 'secondary';
                    $description = sprintf('%s issue <em>"%s"</em> on <strong>%s</strong>', ucfirst((string) esc($action)), esc($issueTitle), esc($repo));
                    break;

                case 'PullRequestEvent':
                    $action      = $event['payload']['action'] ?? 'updated';
                    $prTitle     = $event['payload']['pull_request']['title'] ?? '';
                    $link        = $event['payload']['pull_request']['html_url'] ?? $link;
                    $icon        = 'bi-arrow-left-right';
                    $label       = strtolower($action) . ' PR';
                    $labelClass  = 'primary';
                    $description = sprintf('%s PR <em>"%s"</em> on <strong>%s</strong>', ucfirst((string) esc($action)), esc($prTitle), esc($repo));
                    break;

                case 'IssueCommentEvent':
                    $issueTitle  = $event['payload']['issue']['title'] ?? '';
                    $link        = $event['payload']['comment']['html_url'] ?? $link;
                    $icon        = 'bi-chat-fill';
                    $label       = 'comment';
                    $labelClass  = 'secondary';
                    $description = sprintf('Commented on <em>"%s"</em> in <strong>%s</strong>', esc($issueTitle), esc($repo));
                    break;

                case 'ReleaseEvent':
                    $tagName     = $event['payload']['release']['tag_name'] ?? '';
                    $link        = $event['payload']['release']['html_url'] ?? $link;
                    $icon        = 'bi-tag-fill';
                    $label       = 'release';
                    $labelClass  = 'success';
                    $description = sprintf('Released <code>%s</code> on <strong>%s</strong>', esc($tagName), esc($repo));
                    break;

                case 'PullRequestReviewEvent':
                    $prTitle     = $event['payload']['pull_request']['title'] ?? '';
                    $link        = $event['payload']['pull_request']['html_url'] ?? $link;
                    $icon        = 'bi-eye-fill';
                    $label       = 'review';
                    $labelClass  = 'info';
                    $description = sprintf('Reviewed PR <em>"%s"</em> on <strong>%s</strong>', esc($prTitle), esc($repo));
                    break;

                case 'CommitCommentEvent':
                    $link        = $event['payload']['comment']['html_url'] ?? $link;
                    $icon        = 'bi-chat-square-dots-fill';
                    $label       = 'commit comment';
                    $labelClass  = 'secondary';
                    $description = sprintf('Commented on a commit in <strong>%s</strong>', esc($repo));
                    break;

                default:
                    $icon        = 'bi-activity';
                    $description = sprintf('Activity on <strong>%s</strong>', esc($repo));
                    break;
            }

            if (! empty($description)) {
                $formatted[] = [
                    'github_event_id'   => $eventId,
                    'icon'              => $icon,
                    'description'       => $description,
                    'link'              => $link,
                    'repo'              => $repo,
                    'github_created_at' => $createdAt,
                    'type'              => $type,
                    'label'             => $label,
                    'label_class'       => $labelClass,
                ];
            }
        }

        return $formatted;
    }

    private function apiGet(string $url, array $headers = []): ?array
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => $headers,
        ]);

        $body       = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error      = curl_error($ch);
        curl_close($ch);

        if ($body === false || $error !== '') {
            log_message('error', 'CLI\FetchGitHubActivity::apiGet failed [' . $url . ']: ' . $error);

            return null;
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            log_message('error', 'CLI\FetchGitHubActivity::apiGet non-2xx [' . $url . ']: HTTP ' . $statusCode);

            return null;
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : null;
    }
}
