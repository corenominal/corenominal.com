<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>

<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h4 mb-0">Bookmarks</h1>
        <form method="get" action="<?= site_url('bookmarks') ?>" role="search" aria-label="Search bookmarks" class="d-flex">
            <div class="input-group input-group-sm">
                <span class="input-group-text"><i class="bi bi-search" aria-hidden="true"></i></span>
                <input
                    type="search"
                    id="bookmarks-search"
                    name="q"
                    class="form-control"
                    placeholder="Search&hellip;"
                    value="<?= esc((string) $searchQuery) ?>"
                    autocomplete="off"
                    style="width: 160px;"
                >
            </div>
        </form>
    </div>

    <div
        id="bookmarks-items"
        data-load-url="<?= site_url('bookmarks/load') ?>"
        data-offset="<?= count($bookmarks) ?>"
        data-limit="<?= (int) $bookmarkBatchSize ?>"
        data-has-more="<?= $hasMoreBookmarks ? '1' : '0' ?>"
        data-search="<?= esc((string) $searchQuery) ?>"
    >
        <?= view('bookmarks/partials/bookmark_items', ['bookmarks' => $bookmarks]) ?>
    </div>

    <div id="bookmarks-loader" class="py-3 text-center text-secondary small" aria-live="polite" style="display: none;">
        Loading&hellip;
    </div>
    <div id="bookmarks-observer" aria-hidden="true"></div>
</div>

<?= $this->endSection() ?>
