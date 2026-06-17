<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4 d-flex align-items-center justify-content-between gap-3">
    <h1 class="h4 mb-0">Log</h1>
    <a href="/admin/metrics" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Overview</a>
</div>

<div class="card bg-body-secondary border-0 mb-4">
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <p class="text-secondary small p-3 mb-0">No data yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3 text-nowrap">Timestamp</th>
                            <th>Path</th>
                            <th>Device</th>
                            <th>IP</th>
                            <th class="text-end">Load</th>
                            <th class="text-end pe-3">User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $row): ?>
                        <tr>
                            <td class="ps-3 small text-secondary text-nowrap"><?= esc(date('d M Y H:i', strtotime($row['created_at']))) ?></td>
                            <td class="small font-monospace text-break" style="max-width: 280px;"><?= esc($row['path']) ?></td>
                            <td class="small text-capitalize text-secondary"><?= esc($row['device_type'] ?: '—') ?></td>
                            <td class="small font-monospace text-secondary"><?= esc($row['anonymized_ip'] ?: '—') ?></td>
                            <td class="text-end small text-secondary"><?= $row['load_time_ms'] !== null ? number_format((int) $row['load_time_ms']) . ' ms' : '—' ?></td>
                            <td class="text-end small pe-3"><?= !empty($row['username']) ? esc($row['username']) : '<span class="text-secondary">—</span>' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($pager): ?>
    <?= $pager->links() ?>
<?php endif; ?>

<?= $this->endSection() ?>
