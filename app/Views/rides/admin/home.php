<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0">Rides</h1>
        <a href="<?= site_url('admin/rides/create') ?>" class="btn btn-sm btn-primary flex-shrink-0">
            <i class="bi bi-cloud-arrow-up-fill me-1" aria-hidden="true"></i>Upload Ride
        </a>
    </div>

    <!-- Stat cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['total']) ?></div>
                    <div class="text-secondary small">Total Rides</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['total_distance'], 1) ?> km</div>
                    <div class="text-secondary small">Total Distance</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['this_month'], 1) ?> km</div>
                    <div class="text-secondary small">This Month</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['this_year'], 1) ?> km</div>
                    <div class="text-secondary small">This Year</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar -->
    <div class="d-flex align-items-center justify-content-between gap-2 mb-3 flex-wrap">
        <form method="get" action="<?= site_url('admin/rides') ?>" class="d-flex" role="search">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    name="q"
                    class="form-control"
                    placeholder="Search rides&hellip;"
                    value="<?= esc((string) $search) ?>"
                    autocomplete="off"
                    style="width: 220px;"
                >
            </div>
        </form>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btn-delete" disabled>
            <i class="bi bi-trash3-fill me-1" aria-hidden="true"></i>Delete Selected
        </button>
    </div>

    <!-- Rides table -->
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-striped align-middle mb-0">
            <thead>
                <tr>
                    <th style="width: 32px;">
                        <input type="checkbox" id="select-all" class="form-check-input" aria-label="Select all">
                    </th>
                    <th style="width: 64px;"></th>
                    <th>Ride</th>
                    <th class="d-none d-md-table-cell">Bike</th>
                    <th class="d-none d-lg-table-cell">Distance</th>
                    <th style="width: 80px;"></th>
                </tr>
            </thead>
            <tbody>
                <?php if ($rides): ?>
                    <?php foreach ($rides as $ride): ?>
                        <?php $cover = $coverPhotos[$ride['id']] ?? null; ?>
                        <tr data-ride-id="<?= (int) $ride['id'] ?>">
                            <td>
                                <input
                                    type="checkbox"
                                    class="form-check-input row-checkbox"
                                    data-id="<?= (int) $ride['id'] ?>"
                                    aria-label="Select <?= esc($ride['title'] ?? 'ride') ?>"
                                >
                            </td>
                            <td>
                                <?php if ($cover): ?>
                                    <img src="<?= site_url('uploads/rides/media/' . $cover) ?>" alt="" width="48" height="48" class="rounded object-fit-cover" style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="d-flex align-items-center justify-content-center bg-body-secondary rounded text-secondary" style="width: 48px; height: 48px;">
                                        <i class="bi bi-map" aria-hidden="true"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= site_url('admin/rides/' . $ride['id'] . '/edit') ?>" class="text-body fw-medium text-decoration-none">
                                    <?= esc($ride['title'] !== null && $ride['title'] !== '' ? $ride['title'] : 'Untitled ride') ?>
                                </a>
                                <?php
                                $rideTypeLabel = match ($ride['ride_type'] ?? 'leisure') {
                                    'race'     => 'Race',
                                    'commute'  => 'Commute',
                                    'training' => 'Training',
                                    'indoor'   => 'Indoor / Turbo',
                                    'sportive' => 'Sportive / Charity Ride',
                                    default    => 'Leisure',
                                };
                                ?>
                                <span class="badge text-bg-secondary"><?= esc($rideTypeLabel) ?></span>
                                <div class="small text-secondary">
                                    <?= $ride['started_at'] ? esc(date('j M Y, H:i', strtotime($ride['started_at']))) : '&mdash;' ?>
                                </div>
                            </td>
                            <td class="d-none d-md-table-cell small text-secondary">
                                <?= $ride['bike_id'] && isset($bikeNames[$ride['bike_id']]) ? esc($bikeNames[$ride['bike_id']]) : '&mdash;' ?>
                            </td>
                            <td class="d-none d-lg-table-cell small text-secondary">
                                <?= number_format((float) $ride['distance_km'], 1) ?> km
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="<?= site_url('admin/rides/' . $ride['id'] . '/edit') ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-delete-single"
                                        data-id="<?= (int) $ride['id'] ?>"
                                        title="Delete"
                                    >
                                        <i class="bi bi-trash3" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-secondary text-center py-4">No rides found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pager): ?>
        <div class="mt-3">
            <?= $pager->links('default', 'bootstrap_full') ?>
        </div>
    <?php endif; ?>

</div>

<!-- Delete confirmation modal -->
<div class="modal fade" id="modal-delete-confirm" tabindex="-1" aria-labelledby="modal-delete-confirm-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="modal-delete-confirm-label">Delete Ride</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete <strong id="delete-modal-count">0</strong> ride(s)? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-delete-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
