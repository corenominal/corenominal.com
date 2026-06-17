<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <h1 class="h4 mb-0">Metrics</h1>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($totalHits) ?></div>
                <div class="text-secondary small">Total Hits</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($today) ?></div>
                <div class="text-secondary small">Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($thisWeek) ?></div>
                <div class="text-secondary small">This Week</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($thisMonth) ?></div>
                <div class="text-secondary small">This Month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($uniquePaths) ?></div>
                <div class="text-secondary small">Unique Paths</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($uniqueIPs) ?></div>
                <div class="text-secondary small">Unique IPs</div>
            </div>
        </div>
    </div>
</div>

<!-- Activity chart + device breakdown -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-header bg-transparent border-bottom">
                <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-bar-chart-fill me-2" aria-hidden="true"></i>Activity &mdash; Last 12 Months</h2>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-end gap-1" style="height: 120px;">
                    <?php foreach ($monthlyData as $month => $count):
                        $heightPct = (int) round(($count / $maxMonthly) * 100);
                    ?>
                    <div class="d-flex flex-column align-items-center flex-grow-1 h-100" title="<?= esc(date('M Y', strtotime($month . '-01'))) ?>: <?= $count ?>">
                        <span class="text-secondary" style="font-size: 0.6rem;"><?= $count > 0 ? $count : '' ?></span>
                        <div class="mt-auto bg-primary rounded-top w-100" style="height: <?= max(2, $heightPct) ?>%;"></div>
                        <span class="text-secondary text-center mt-1 font-monospace" style="font-size: 0.6rem; writing-mode: vertical-lr; transform: rotate(180deg);"><?= esc(date('M', strtotime($month . '-01'))) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-header bg-transparent border-bottom">
                <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-phone me-2" aria-hidden="true"></i>Device Types</h2>
            </div>
            <div class="card-body">
                <?php if (empty($deviceRows)): ?>
                    <p class="text-secondary small mb-0">No data yet.</p>
                <?php else: ?>
                    <?php foreach ($deviceRows as $device):
                        $pct = (int) round(($device['cnt'] / $totalForPct) * 100);
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold text-capitalize"><?= esc($device['device_type'] ?: 'unknown') ?></span>
                            <span class="text-secondary"><?= number_format($device['cnt']) ?> &middot; <?= $pct ?>%</span>
                        </div>
                        <div class="progress" style="height: 4px;" role="progressbar" aria-valuenow="<?= $device['cnt'] ?>" aria-valuemin="0" aria-valuemax="<?= $totalForPct ?>">
                            <div class="progress-bar bg-primary" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top paths -->
<div class="card bg-body-secondary border-0 mb-4">
    <div class="card-header bg-transparent border-bottom d-flex align-items-center justify-content-between">
        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-signpost-split me-2" aria-hidden="true"></i>Top 10 Paths</h2>
        <a href="/admin/metrics/paths" class="btn btn-sm btn-outline-primary">View all</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($topPaths)): ?>
            <p class="text-secondary small p-3 mb-0">No data yet.</p>
        <?php else: ?>
            <?php $maxHits = (int) ($topPaths[0]['cnt'] ?? 1) ?: 1; ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($topPaths as $p):
                    $pct = (int) round(($p['cnt'] / $totalHits) * 100);
                ?>
                <li class="list-group-item bg-transparent">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <span class="text-truncate small font-monospace flex-grow-1"><?= esc($p['path']) ?></span>
                        <span class="text-secondary small flex-shrink-0"><?= number_format($p['cnt']) ?> <span class="text-muted">(<?= $pct ?>%)</span></span>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent text-end">
        <a href="/admin/metrics/log" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul me-1" aria-hidden="true"></i>View Log</a>
    </div>
</div>

<?= $this->endSection() ?>
