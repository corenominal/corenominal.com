<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="d-flex align-items-start justify-content-between gap-3 mb-4">
    <div>
        <h1 class="h4 mb-1"><i class="bi bi-key-fill me-2"></i>Set Encryption Key</h1>
        <p class="text-secondary small mb-0">The key is stored as a cookie in your browser and used to encrypt and decrypt your notes.</p>
    </div>
    <a href="<?= site_url('admin/notes') ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">
        <i class="bi bi-arrow-left me-1" aria-hidden="true"></i>Back
    </a>
</div>

<div id="notes-key-status" class="mb-3"></div>

<form id="notes-key-form" novalidate>
    <div class="mb-4">
        <label for="notes-key-input" class="form-label">Encryption Key</label>
        <input type="password" class="form-control" id="notes-key-input" placeholder="Enter your encryption key" autocomplete="off" required>
    </div>
    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-floppy-fill me-1"></i> Save Key</button>
        <button type="button" class="btn btn-outline-primary" id="btn-clear-key"><i class="bi bi-trash3-fill me-1"></i> Clear Key</button>
    </div>
</form>

<?= $this->endSection() ?>
