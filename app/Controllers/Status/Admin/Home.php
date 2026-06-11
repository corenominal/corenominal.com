<?php

namespace App\Controllers\Status\Admin;

use App\Controllers\BaseController;
use App\Models\StatusMediaModel;
use App\Models\StatusModel;

class Home extends BaseController
{
    public function index(): string
    {
        $statusModel = new StatusModel();
        $db          = \Config\Database::connect();

        $totalStatuses     = $statusModel->countAllResults();
        $statusesWithMedia = $statusModel->where("media_ids != '[]'")->countAllResults();
        $textOnly          = $totalStatuses - $statusesWithMedia;
        $mastodonSynced    = $statusModel->where("mastodon_id IS NOT NULL")->where("mastodon_id !=", '')->countAllResults();
        $replies           = $statusModel->where("in_reply_to_id IS NOT NULL")->where("in_reply_to_id !=", '')->countAllResults();
        $totalMedia        = (new StatusMediaModel())->countAllResults();

        $monthStart     = date('Y-m-01');
        $nextMonthStart = date('Y-m-01', strtotime('+1 month'));
        $yearStart      = date('Y-01-01');
        $nextYearStart  = date('Y-01-01', strtotime('+1 year'));

        $thisMonth = $statusModel
            ->where('created_at >=', $monthStart)
            ->where('created_at <', $nextMonthStart)
            ->countAllResults();

        $thisYear = $statusModel
            ->where('created_at >=', $yearStart)
            ->where('created_at <', $nextYearStart)
            ->countAllResults();

        $monthlyData = [];

        for ($i = 11; $i >= 0; $i--) {
            $monthlyData[date('Y-m', strtotime("-{$i} months"))] = 0;
        }

        $activityRows = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
             FROM statuses
             WHERE deleted_at IS NULL
               AND created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY month
             ORDER BY month ASC"
        )->getResultArray();

        foreach ($activityRows as $row) {
            if (array_key_exists($row['month'], $monthlyData)) {
                $monthlyData[$row['month']] = (int) $row['cnt'];
            }
        }

        $maxMonthly = max(array_values($monthlyData)) ?: 1;

        $mediaByType = $db->query(
            'SELECT file_ext, COUNT(*) AS cnt FROM status_media GROUP BY file_ext ORDER BY cnt DESC LIMIT 8'
        )->getResultArray();

        $totalMediaForPct = array_sum(array_column($mediaByType, 'cnt')) ?: 1;

        $recentStatuses = $statusModel->orderBy('created_at', 'DESC')->limit(5)->findAll();

        return view('status/admin/home', [
            'title'             => 'Status — Admin',
            'js'                => ['status/admin/home'],
            'totalStatuses'     => $totalStatuses,
            'statusesWithMedia' => $statusesWithMedia,
            'textOnly'          => $textOnly,
            'mastodonSynced'    => $mastodonSynced,
            'replies'           => $replies,
            'totalMedia'        => $totalMedia,
            'thisMonth'         => $thisMonth,
            'thisYear'          => $thisYear,
            'monthlyData'       => $monthlyData,
            'maxMonthly'        => $maxMonthly,
            'mediaByType'       => $mediaByType,
            'totalMediaForPct'  => $totalMediaForPct,
            'recentStatuses'    => $recentStatuses,
        ]);
    }
}
