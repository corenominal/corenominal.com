<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4">

    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Rides</h1>
        <form method="get" action="<?= site_url('rides') ?>" role="search" aria-label="Search rides" class="d-flex">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    id="rides-search"
                    name="q"
                    class="form-control"
                    placeholder="Search rides&hellip;"
                    value="<?= esc((string) $searchQuery) ?>"
                    autocomplete="off"
                    style="width: 160px;"
                >
            </div>
        </form>
    </div>

    <!-- Stat cards -->
    <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 mb-4">
        <div class="col">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format($stats['total']) ?></div>
                    <div class="text-secondary small">Total Rides</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['total_distance'], 1) ?> km</div>
                    <div class="text-secondary small">Total Distance</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['this_week'], 1) ?> km</div>
                    <div class="text-secondary small">This Week</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['this_month'], 1) ?> km</div>
                    <div class="text-secondary small">This Month</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= number_format((float) $stats['this_year'], 1) ?> km</div>
                    <div class="text-secondary small">This Year</div>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card h-100 bg-body-secondary border-0">
                <div class="card-body">
                    <div class="fs-3 fw-bold"><?= $stats['recording_since'] ? esc(date('M Y', strtotime($stats['recording_since']))) : '&mdash;' ?></div>
                    <div class="text-secondary small">Recording Since</div>
                </div>
            </div>
        </div>
    </div>

    <div
        id="rides-items"
        data-load-url="<?= site_url('rides/load') ?>"
        data-offset="<?= count($rides) ?>"
        data-limit="<?= (int) $rideBatchSize ?>"
        data-has-more="<?= $hasMoreRides ? '1' : '0' ?>"
        data-search="<?= esc((string) $searchQuery) ?>"
        class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-3"
    >
        <?= view('rides/partials/ride_cards', [
            'rides'       => $rides,
            'coverPhotos' => $coverPhotos,
            'bikeNames'   => $bikeNames,
        ]) ?>
    </div>

    <div id="rides-loader" class="py-3 text-center text-secondary small" aria-live="polite" style="display: none;">
        Loading&hellip;
    </div>
    <div id="rides-observer" aria-hidden="true"></div>

</div>

<?= $this->endSection() ?>
