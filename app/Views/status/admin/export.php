<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h1 class="h4 mb-0">Export Data</h1>
    <span class="badge bg-secondary fs-6"><?= number_format($totalStatuses) ?> statuses</span>
</div>

<div class="row g-4">

    <!-- JSON Export -->
    <div class="col-12 col-lg-4">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded bg-primary bg-opacity-10 text-primary fs-4">
                        <i class="bi bi-filetype-json" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2 class="h5 mb-0">JSON Export</h2>
                        <span class="badge bg-primary bg-opacity-25 text-primary-emphasis mt-1">Full data</span>
                    </div>
                </div>
                <p class="text-secondary mb-0 flex-grow-1">
                    Exports all statuses as a structured JSON file. Includes every field — content, HTML, media IDs, Mastodon metadata, and timestamps.
                </p>
                <a href="<?= site_url('admin/status/export/json') ?>" class="btn btn-primary w-100">
                    <i class="bi bi-download me-2" aria-hidden="true"></i>Download JSON
                </a>
            </div>
        </div>
    </div>

    <!-- SQL Export -->
    <div class="col-12 col-lg-4">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded bg-warning bg-opacity-10 text-warning fs-4">
                        <i class="bi bi-database" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2 class="h5 mb-0">SQL Export</h2>
                        <span class="badge bg-warning bg-opacity-25 text-warning-emphasis mt-1">Database dump</span>
                    </div>
                </div>
                <p class="text-secondary mb-0 flex-grow-1">
                    Generates a SQL dump with a <code>CREATE TABLE</code> statement and <code>INSERT</code> rows for all statuses. Use to restore or migrate to any MySQL-compatible database.
                </p>
                <a href="<?= site_url('admin/status/export/sql') ?>" class="btn btn-outline-primary w-100">
                    <i class="bi bi-download me-2" aria-hidden="true"></i>Download SQL
                </a>
            </div>
        </div>
    </div>

    <!-- AI Analysis Export -->
    <div class="col-12 col-lg-4">
        <div class="card h-100 bg-body-secondary border-0">
            <div class="card-body d-flex flex-column gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-2 rounded bg-secondary bg-opacity-25 text-secondary fs-4">
                        <i class="bi bi-stars" aria-hidden="true"></i>
                    </div>
                    <div>
                        <h2 class="h5 mb-0">AI Analysis</h2>
                        <span class="badge bg-secondary bg-opacity-25 mt-1">Writing style</span>
                    </div>
                </div>
                <p class="text-secondary mb-0">
                    Exports all status text as plain text prefixed with an AI prompt for generating a personal writing style guide.
                </p>
                <div class="p-3 rounded bg-body-tertiary small">
                    <p class="fw-semibold text-uppercase mb-1 small"><i class="bi bi-chat-square-quote me-1" aria-hidden="true"></i>Prompt</p>
                    <p class="text-secondary mb-2" id="ai-prompt-text">Analyze the following messages for my personal writing style. Look at sentence structure, level of formality, use of emojis, punctuation habits, and common vocabulary. Create a concise 'Style Guide' I can use for future prompts.</p>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="copy-prompt-btn">
                        <i class="bi bi-clipboard me-1" aria-hidden="true"></i>Copy prompt
                    </button>
                </div>
                <a href="<?= site_url('admin/status/export/ai') ?>" class="btn btn-outline-primary w-100 mt-auto">
                    <i class="bi bi-download me-2" aria-hidden="true"></i>Download for AI
                </a>
            </div>
        </div>
    </div>

</div>

<?= $this->endSection() ?>
