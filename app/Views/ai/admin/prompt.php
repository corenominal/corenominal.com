<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4 pb-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0">Default Prompt</h1>
        <p class="text-secondary mb-0 mt-1">Prepended as the system prompt for every chat session.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-shrink-0">
        <span id="dirty-indicator" class="badge text-bg-warning d-none">Unsaved changes</span>
        <button type="submit" form="prompt-form" class="btn btn-primary btn-sm"><i class="bi bi-floppy-fill me-1"></i>Save revision</button>
    </div>
</div>

<?php if ($msg = session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?= esc($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if ($msg = session()->getFlashdata('info')): ?>
<div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-info-circle-fill me-2"></i><?= esc($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if ($msg = session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row g-3">

    <!-- Prompt editor -->
    <div class="col-12 col-lg-8">
        <form id="prompt-form" action="/admin/ai/prompt/update" method="post">
            <?= csrf_field() ?>
            <div class="card">
                <div class="card-body p-0">
                    <textarea
                        id="prompt-content"
                        name="content"
                        class="form-control border-0 rounded font-monospace"
                        style="min-height:400px;resize:vertical;background:transparent"
                        placeholder="Enter your default system prompt here…"
                    ><?= esc($current ? $current['content'] : '') ?></textarea>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center text-secondary small">
                    <span id="char-count">0 characters</span>
                    <?php if ($current): ?>
                    <span>Last saved <?= date('d M Y, H:i', strtotime($current['created_at'])) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Revision history -->
    <div class="col-12 col-lg-4">
        <div class="card">
            <div class="card-header fw-semibold d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history me-2"></i>Revision History</span>
                <?php if (!empty($revisions)): ?>
                <span class="badge text-bg-secondary"><?= count($revisions) ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($revisions)): ?>
            <div class="card-body text-center text-secondary py-5">
                <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-25"></i>
                No revisions yet.<br>
                <span class="small">Save a prompt to start the history.</span>
            </div>
            <?php else: ?>
            <div class="list-group list-group-flush overflow-y-auto" style="max-height:70vh">
                <?php
                $total = count($revisions);
                foreach ($revisions as $i => $rev):
                    $isCurrent = ($i === 0);
                    $revNum    = $total - $i;
                ?>
                <div class="list-group-item py-3">
                    <div class="d-flex align-items-center justify-content-between gap-2 mb-1">
                        <div class="d-flex align-items-center gap-2">
                            <span class="fw-semibold small">Rev <?= $revNum ?></span>
                            <?php if ($isCurrent): ?>
                            <span class="badge text-bg-success">Current</span>
                            <?php endif; ?>
                        </div>
                        <?php if (!$isCurrent): ?>
                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary btn-revert"
                            data-id="<?= $rev['id'] ?>"
                            data-rev="<?= $revNum ?>">
                            Revert
                        </button>
                        <?php endif; ?>
                    </div>
                    <div class="text-secondary small mb-1">
                        <i class="bi bi-calendar3 me-1"></i><?= date('d M Y, H:i', strtotime($rev['created_at'])) ?>
                        &nbsp;·&nbsp;
                        <?= number_format(mb_strlen($rev['content'])) ?> chars
                    </div>
                    <div class="text-secondary small font-monospace revision-preview">
                        <?= esc(mb_substr(trim($rev['content']), 0, 140)) ?><?= mb_strlen($rev['content']) > 140 ? '…' : '' ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Revert confirmation modal -->
<div class="modal fade" id="modal-revert" tabindex="-1" aria-labelledby="modal-revert-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-revert-label">Revert Prompt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Revert to <strong id="revert-rev-label"></strong>? This will create a new revision with that version's content and set it as the active prompt.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="revert-form" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-arrow-counterclockwise me-1"></i>Revert</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
