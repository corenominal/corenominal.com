<?php

namespace App\Controllers\Status;

use App\Controllers\BaseController;
use App\Libraries\MastodonPoster;
use App\Models\StatusDraftModel;
use App\Models\StatusMediaModel;
use App\Models\StatusModel;
use CodeIgniter\HTTP\ResponseInterface;

class Home extends BaseController
{
    public function index(): string
    {
        $limit        = 20;
        $query        = trim((string) $this->request->getGet('q'));
        $timelineData = $this->getTimelineBatch(0, $limit, $query);
        $mastodon     = new MastodonPoster();

        $draftCount = 0;

        if (user_in_group('administrators')) {
            $draftCount = (new StatusDraftModel())->countAllResults();
        }

        return view('status/home', [
            'title'            => 'Status Timeline',
            'js'               => ['status/home'],
            'css'              => ['status/timeline'],
            'templateMaxWidth' => '640px',
            'statuses'         => $timelineData['statuses'],
            'hasMoreStatuses'  => $timelineData['hasMore'],
            'statusBatchSize'  => $limit,
            'searchQuery'      => $query,
            'mastodonHandle'   => config('Mastodon')->account,
            'mastodonProfile'  => config('Mastodon')->profile,
            'mastodonDisplayname' => config('Mastodon')->displayname,
            'mastodonEnabled'  => $mastodon->isEnabled(),
            'draftCount'       => $draftCount,
        ]);
    }

    public function show(string $uuid): string
    {
        $statusModel = new StatusModel();
        $status      = $statusModel->where('uuid', $uuid)->first();

        if ($status === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Status not found.');
        }

        $status = status_with_media($status);

        $referer     = (string) $this->request->getHeaderLine('Referer');
        $timelineUrl = site_url('status');

        $data['backUrl']   = $timelineUrl;
        $data['backLabel'] = 'Back to timeline';

        return view('status/status', array_merge($data, [
            'title'            => 'Status',
            'js'               => ['status/home'],
            'css'              => ['status/timeline'],
            'templateMaxWidth' => '640px',
            'status'           => $status,
            'mastodonHandle'   => config('Mastodon')->account,
            'mastodonProfile'  => config('Mastodon')->profile,
            'mastodonDisplayname' => config('Mastodon')->displayname,
        ]));
    }

    public function loadMoreStatuses(): ResponseInterface
    {
        $offset = max(0, (int) $this->request->getGet('offset'));
        $limit  = (int) $this->request->getGet('limit');
        $query  = trim((string) $this->request->getGet('q'));

        $limit = max(1, min(50, $limit ?: 20));

        $timelineData = $this->getTimelineBatch($offset, $limit, $query);

        $html = view('status/partials/timeline_items', [
            'statuses'        => $timelineData['statuses'],
            'mastodonHandle'  => config('Mastodon')->account,
            'mastodonProfile' => config('Mastodon')->profile,
            'mastodonDisplayname' => config('Mastodon')->displayname,
        ]);

        return $this->response->setJSON([
            'statuses'   => $timelineData['statuses'],
            'html'       => $html,
            'nextOffset' => $offset + count($timelineData['statuses']),
            'hasMore'    => $timelineData['hasMore'],
        ]);
    }

    private function getTimelineBatch(int $offset, int $limit, string $query = ''): array
    {
        $statusModel = new StatusModel();

        if ($query !== '') {
            $statusModel
                ->groupStart()
                ->like('content', $query)
                ->orLike('content_html', $query)
                ->groupEnd();
        }

        $rows = $statusModel
            ->orderBy('created_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit + 1, $offset);

        $hasMore  = count($rows) > $limit;
        $statuses = array_slice($rows, 0, $limit);

        $mediaIds = [];

        foreach ($statuses as $status) {
            foreach ($status['media_ids'] ?? [] as $mediaId) {
                $id = (int) $mediaId;

                if ($id > 0) {
                    $mediaIds[] = $id;
                }
            }
        }

        $mediaById = [];

        if ($mediaIds !== []) {
            $mediaRows = (new StatusMediaModel())
                ->whereIn('id', array_values(array_unique($mediaIds)))
                ->findAll();

            foreach ($mediaRows as $row) {
                $mediaById[(int) $row['id']] = status_media_row_to_array($row);
            }
        }

        foreach ($statuses as $i => $status) {
            $statuses[$i]['media'] = [];

            foreach ($status['media_ids'] ?? [] as $mediaId) {
                $id = (int) $mediaId;

                if (isset($mediaById[$id])) {
                    $statuses[$i]['media'][] = $mediaById[$id];
                }
            }
        }

        return ['statuses' => $statuses, 'hasMore' => $hasMore];
    }


}
