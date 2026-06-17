<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="border-bottom border-1 mb-4 pb-4 d-flex align-items-center justify-content-between gap-3">
                <h1 class="h3 mb-0">Import / Export</h1>
                <a href="/admin/startpage" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>

            <ul class="nav nav-tabs mb-4" id="import-export-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="tab-history" data-bs-toggle="tab" data-bs-target="#panel-history" type="button" role="tab" aria-controls="panel-history" aria-selected="true">
                        <i class="bi bi-clock-history me-1"></i> History
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-redirects" data-bs-toggle="tab" data-bs-target="#panel-redirects" type="button" role="tab" aria-controls="panel-redirects" aria-selected="false">
                        <i class="bi bi-arrow-left-right me-1"></i> Redirects
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="tab-search" data-bs-toggle="tab" data-bs-target="#panel-search" type="button" role="tab" aria-controls="panel-search" aria-selected="false">
                        <i class="bi bi-search me-1"></i> Search Engines
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="import-export-tab-content">

                <!-- HISTORY TAB -->
                <div class="tab-pane fade show active" id="panel-history" role="tabpanel" aria-labelledby="tab-history">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-download me-1"></i> Export History
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-secondary">Download all search history records as a JSON file.</p>
                                    <a href="/admin/startpage/export/history" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Download JSON
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-upload me-1"></i> Import History
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-secondary">Upload a previously exported history JSON file. Records are matched by query term — existing records will be updated, new records will be inserted.</p>
                                    <form class="import-form" data-endpoint="/admin/startpage/import/history" enctype="multipart/form-data" novalidate>
                                        <div class="mb-3">
                                            <label for="history-import-file" class="form-label">JSON File</label>
                                            <input class="form-control" type="file" id="history-import-file" name="import_file" accept=".json,application/json" required>
                                        </div>
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="bi bi-upload me-1"></i> Import
                                        </button>
                                    </form>
                                    <div class="import-result mt-3 d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- REDIRECTS TAB -->
                <div class="tab-pane fade" id="panel-redirects" role="tabpanel" aria-labelledby="tab-redirects">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-download me-1"></i> Export Redirects
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-secondary">Download all redirects, including soft-deleted records, as a JSON file.</p>
                                    <a href="/admin/startpage/export/redirects" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Download JSON
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-upload me-1"></i> Import Redirects
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-secondary">Upload a previously exported redirects JSON file. Records are matched by phrase — existing records (including soft-deleted) will be updated, new records will be inserted.</p>
                                    <form class="import-form" data-endpoint="/admin/startpage/import/redirects" enctype="multipart/form-data" novalidate>
                                        <div class="mb-3">
                                            <label for="redirects-import-file" class="form-label">JSON File</label>
                                            <input class="form-control" type="file" id="redirects-import-file" name="import_file" accept=".json,application/json" required>
                                        </div>
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="bi bi-upload me-1"></i> Import
                                        </button>
                                    </form>
                                    <div class="import-result mt-3 d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SEARCH ENGINES TAB -->
                <div class="tab-pane fade" id="panel-search" role="tabpanel" aria-labelledby="tab-search">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-download me-1"></i> Export Search Engines
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-secondary">Download all search engine shortcuts, including soft-deleted records, as a JSON file.</p>
                                    <a href="/admin/startpage/export/search" class="btn btn-outline-primary">
                                        <i class="bi bi-file-earmark-arrow-down-fill me-1"></i> Download JSON
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <i class="bi bi-upload me-1"></i> Import Search Engines
                                </div>
                                <div class="card-body">
                                    <p class="card-text text-secondary">Upload a previously exported search engines JSON file. Records are matched by phrase — existing records (including soft-deleted) will be updated, new records will be inserted.</p>
                                    <form class="import-form" data-endpoint="/admin/startpage/import/search" enctype="multipart/form-data" novalidate>
                                        <div class="mb-3">
                                            <label for="search-import-file" class="form-label">JSON File</label>
                                            <input class="form-control" type="file" id="search-import-file" name="import_file" accept=".json,application/json" required>
                                        </div>
                                        <button type="submit" class="btn btn-outline-success">
                                            <i class="bi bi-upload me-1"></i> Import
                                        </button>
                                    </form>
                                    <div class="import-result mt-3 d-none"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
