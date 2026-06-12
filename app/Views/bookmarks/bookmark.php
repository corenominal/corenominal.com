<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-3">
    <a href="<?= esc((string) $backUrl) ?>" class="text-decoration-none text-secondary small">&larr; <?= esc((string) $backLabel) ?></a>
</div>

<?= view('bookmarks/partials/bookmark_items', ['bookmarks' => [$bookmark]]) ?>

<?= $this->endSection() ?>
