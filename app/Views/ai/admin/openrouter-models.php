<?= $this->extend('templates/default') ?>
<?= $this->section('content') ?>

<div class="mb-4 pb-3 d-flex align-items-start justify-content-between gap-3 flex-wrap">
    <div>
        <h1 class="h3 mb-0">OpenRouter Models</h1>
        <p class="text-secondary mb-0 mt-1">Select the models available in AI Chat.</p>
    </div>
    <div class="d-flex align-items-center gap-3 flex-shrink-0">
        <span id="selected-count" class="text-secondary small"><?= count($enabledIds) ?> selected</span>
        <button type="submit" form="models-form" class="btn btn-primary btn-sm">
            <i class="bi bi-floppy-fill me-1"></i>Save
        </button>
    </div>
</div>

<?php if ($msg = session()->getFlashdata('success')): ?>
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i><?= esc($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>
<?php if ($msg = session()->getFlashdata('error')): ?>
<div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if (empty($allModels)): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    No models loaded. Check that your OpenRouter API key is set in <code>app/Config/Openrouter.php</code>.
</div>
<?php else: ?>

<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between gap-3 flex-wrap">
        <span class="fw-semibold">
            <i class="bi bi-cpu me-2"></i>Available Models
            <span class="badge text-bg-secondary ms-1"><?= count($allModels) ?></span>
        </span>
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="show-selected-btn" class="btn btn-outline-secondary btn-sm text-nowrap">
                Selected only
            </button>
            <input
                type="search"
                id="model-search"
                class="form-control form-control-sm"
                style="max-width:260px"
                placeholder="Filter models…"
                autocomplete="off"
            >
        </div>
    </div>

    <form id="models-form" action="/admin/ai/openrouter-models/save" method="post">
        <?= csrf_field() ?>
        <div id="model-list" class="list-group list-group-flush overflow-y-auto" style="max-height:65vh">
            <?php foreach ($allModels as $m):
                $id      = $m['id'] ?? '';
                $name    = $m['name'] ?? $id;
                $ctx     = isset($m['context_length']) ? number_format($m['context_length']) . ' ctx' : '';
                $checked = in_array($id, $enabledIds) ? 'checked' : '';

                $promptRaw     = (float) ($m['pricing']['prompt']     ?? 0);
                $completionRaw = (float) ($m['pricing']['completion'] ?? 0);
                $isFree        = $promptRaw === 0.0 && $completionRaw === 0.0;

                if ($isFree) {
                    $pricing = '<span class="badge text-bg-success">Free</span>';
                } else {
                    $fmtIn  = '$' . rtrim(rtrim(number_format($promptRaw * 1_000_000, 4), '0'), '.');
                    $fmtOut = '$' . rtrim(rtrim(number_format($completionRaw * 1_000_000, 4), '0'), '.');
                    $pricing = esc($fmtIn) . ' / ' . esc($fmtOut) . ' per M tokens';
                }
            ?>
            <label class="list-group-item list-group-item-action model-row d-flex align-items-center gap-3 py-2 px-3" data-id="<?= esc($id) ?>">
                <input
                    type="checkbox"
                    name="models[]"
                    value="<?= esc($id) ?>"
                    class="form-check-input flex-shrink-0 model-checkbox"
                    <?= $checked ?>
                >
                <div class="flex-grow-1 overflow-hidden">
                    <div class="fw-semibold small text-truncate"><?= esc($name) ?></div>
                    <div class="text-secondary" style="font-size:0.75rem"><?= esc($id) ?><?= $ctx ? ' &middot; ' . esc($ctx) : '' ?></div>
                </div>
                <div class="text-secondary text-end flex-shrink-0" style="font-size:0.75rem"><?= $pricing ?></div>
            </label>
            <?php endforeach; ?>
        </div>
    </form>

    <div class="card-footer text-secondary small" id="no-results" style="display:none">
        No models match your filter.
    </div>
</div>

<?php endif; ?>

<?= $this->endSection() ?>
