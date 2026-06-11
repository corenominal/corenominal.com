<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h4 mb-0">Status Dashboard</h1>
    <a href="<?= site_url('admin/status') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-clockwise me-1" aria-hidden="true"></i>Refresh</a>
</div>

<!-- Stat cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($totalStatuses) ?></div>
                <div class="text-secondary small">Total Statuses</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($thisMonth) ?></div>
                <div class="text-secondary small">This Month</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($statusesWithMedia) ?></div>
                <div class="text-secondary small">With Media</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($mastodonSynced) ?></div>
                <div class="text-secondary small">Mastodon Synced</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($thisYear) ?></div>
                <div class="text-secondary small">This Year</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($replies) ?></div>
                <div class="text-secondary small">Replies</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($textOnly) ?></div>
                <div class="text-secondary small">Text Only</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body">
                <div class="fs-3 fw-bold"><?= number_format($totalMedia) ?></div>
                <div class="text-secondary small">Media Files</div>
            </div>
        </div>
    </div>
</div>

<!-- Activity (last 12 months) + Media breakdown -->
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
                        <span class="text-secondary text-center mt-1" style="font-size: 0.6rem; writing-mode: vertical-lr; transform: rotate(180deg);"><?= esc(date('M', strtotime($month . '-01'))) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card bg-body-secondary border-0 h-100">
            <div class="card-header bg-transparent border-bottom">
                <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-file-earmark-fill me-2" aria-hidden="true"></i>Media by Type</h2>
            </div>
            <div class="card-body">
                <?php if (empty($mediaByType)): ?>
                    <p class="text-secondary small mb-0">No media files yet.</p>
                <?php else: ?>
                    <?php foreach ($mediaByType as $mtype):
                        $pct = (int) round(($mtype['cnt'] / $totalMediaForPct) * 100);
                    ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="fw-semibold text-uppercase"><?= esc(strtoupper($mtype['file_ext'])) ?></span>
                            <span class="text-secondary"><?= number_format($mtype['cnt']) ?> &middot; <?= $pct ?>%</span>
                        </div>
                        <div class="progress" style="height: 4px;" role="progressbar" aria-valuenow="<?= $mtype['cnt'] ?>" aria-valuemin="0" aria-valuemax="<?= $totalMediaForPct ?>">
                            <div class="progress-bar bg-primary" style="width: <?= $pct ?>%;"></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent statuses -->
<div class="card bg-body-secondary border-0 mb-4">
    <div class="card-header bg-transparent border-bottom">
        <h2 class="h6 mb-0 fw-semibold"><i class="bi bi-clock-history me-2" aria-hidden="true"></i>Recent Statuses</h2>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentStatuses)): ?>
            <p class="text-secondary small p-3 mb-0">No statuses yet.</p>
        <?php else: ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($recentStatuses as $status): ?>
                <li class="list-group-item bg-transparent">
                    <div class="d-flex align-items-center justify-content-between gap-3">
                        <span class="text-truncate small flex-grow-1">
                            <a href="<?= site_url('status/' . $status['uuid']) ?>" class="text-decoration-none text-body"><?= esc(mb_strimwidth(strip_tags($status['content']), 0, 140, '…')) ?></a>
                        </span>
                        <div class="d-flex align-items-center gap-2 flex-shrink-0 text-secondary small">
                            <?php if (!empty($status['media_ids'])): ?>
                                <i class="bi bi-image-fill text-info" title="Has media" aria-hidden="true"></i>
                            <?php endif; ?>
                            <?php if (!empty($status['mastodon_id'])): ?>
                                <i class="bi bi-mastodon" title="Mastodon synced" aria-hidden="true"></i>
                            <?php endif; ?>
                            <span class="text-nowrap"><?= esc(date('d M Y', strtotime($status['created_at']))) ?></span>
                        </div>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <div class="card-footer bg-transparent text-end">
        <a href="<?= site_url('admin/status/export') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-download me-1" aria-hidden="true"></i>Export Data</a>
    </div>
</div>

<?= $this->endSection() ?>
