<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4 d-flex align-items-center justify-content-between gap-3">
    <h1 class="h4 mb-0">Paths</h1>
    <a href="/admin/metrics" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Overview</a>
</div>

<div class="card bg-body-secondary border-0 mb-4">
    <div class="card-body p-0">
        <?php if (empty($paths)): ?>
            <p class="text-secondary small p-3 mb-0">No data yet.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="ps-3">Path</th>
                            <th class="text-end">Hits</th>
                            <th class="text-end">Unique IPs</th>
                            <th class="text-end">Avg Load</th>
                            <th class="text-end pe-3">Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paths as $p): ?>
                        <tr>
                            <td class="ps-3 font-monospace small text-break"><?= esc($p['path']) ?></td>
                            <td class="text-end small"><?= number_format($p['hits']) ?></td>
                            <td class="text-end small"><?= number_format($p['unique_ips']) ?></td>
                            <td class="text-end small text-secondary"><?= $p['avg_load_ms'] !== null ? number_format((int) $p['avg_load_ms']) . ' ms' : '—' ?></td>
                            <td class="text-end small text-secondary pe-3 text-nowrap"><?= esc(date('d M Y H:i', strtotime($p['last_seen']))) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?= $this->endSection() ?>
