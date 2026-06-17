<?php

namespace App\Controllers\Metrics\Admin;

use App\Controllers\BaseController;
use App\Models\MetricsModel;

class Dashboard extends BaseController
{
    private array $commonData = [
        'templateMenu'     => 'metrics/admin/sidebar-menu',
        'templateMaxWidth' => '100%',
    ];

    public function index(): string
    {
        $db    = \Config\Database::connect();
        $model = new MetricsModel();

        $totalHits   = $model->where('is_admin', 0)->countAllResults();
        $today       = $model->where('is_admin', 0)->where('DATE(created_at)', date('Y-m-d'))->countAllResults();
        $weekStart   = date('Y-m-d', strtotime('monday this week'));
        $thisWeek    = $model->where('is_admin', 0)->where('created_at >=', $weekStart)->countAllResults();
        $monthStart  = date('Y-m-01');
        $thisMonth   = $model->where('is_admin', 0)->where('created_at >=', $monthStart)->countAllResults();

        $uniquePaths = (int) $db->query(
            'SELECT COUNT(DISTINCT path) AS cnt FROM metrics WHERE is_admin = 0'
        )->getRow()->cnt;

        $uniqueIPs = (int) $db->query(
            'SELECT COUNT(DISTINCT anonymized_ip) AS cnt FROM metrics WHERE is_admin = 0'
        )->getRow()->cnt;

        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthlyData[date('Y-m', strtotime("-{$i} months"))] = 0;
        }
        $activityRows = $db->query(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS cnt
             FROM metrics
             WHERE is_admin = 0
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

        $deviceRows = $db->query(
            'SELECT device_type, COUNT(*) AS cnt FROM metrics WHERE is_admin = 0 GROUP BY device_type ORDER BY cnt DESC'
        )->getResultArray();
        $totalForPct = array_sum(array_column($deviceRows, 'cnt')) ?: 1;

        $topPaths = $db->query(
            'SELECT path, COUNT(*) AS cnt FROM metrics WHERE is_admin = 0 GROUP BY path ORDER BY cnt DESC LIMIT 10'
        )->getResultArray();

        return view('metrics/admin/dashboard', array_merge($this->commonData, [
            'title'       => 'Metrics — Admin',
            'totalHits'   => $totalHits,
            'today'       => $today,
            'thisWeek'    => $thisWeek,
            'thisMonth'   => $thisMonth,
            'uniquePaths' => $uniquePaths,
            'uniqueIPs'   => $uniqueIPs,
            'monthlyData' => $monthlyData,
            'maxMonthly'  => $maxMonthly,
            'deviceRows'  => $deviceRows,
            'totalForPct' => $totalForPct,
            'topPaths'    => $topPaths,
        ]));
    }

    public function paths(): string
    {
        $db = \Config\Database::connect();

        $paths = $db->query(
            "SELECT
                path,
                COUNT(*) AS hits,
                COUNT(DISTINCT anonymized_ip) AS unique_ips,
                ROUND(AVG(load_time_ms)) AS avg_load_ms,
                MAX(created_at) AS last_seen
             FROM metrics
             WHERE is_admin = 0
             GROUP BY path
             ORDER BY hits DESC"
        )->getResultArray();

        return view('metrics/admin/paths', array_merge($this->commonData, [
            'title' => 'Metrics — Paths',
            'paths' => $paths,
        ]));
    }

    public function log(): string
    {
        $model = new MetricsModel();
        $model->where('is_admin', 0)->orderBy('created_at', 'DESC');
        $rows  = $model->paginate(50);
        $pager = $model->pager;

        return view('metrics/admin/log', array_merge($this->commonData, [
            'title' => 'Metrics — Log',
            'rows'  => $rows,
            'pager' => $pager,
        ]));
    }
}
