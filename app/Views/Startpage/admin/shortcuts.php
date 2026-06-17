<?= $this->extend('templates/default') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">

            <div class="border-bottom border-1 mb-4 pb-4 d-flex align-items-center justify-content-between gap-3">
                <h1 class="h3 mb-0">Shortcuts</h1>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modal-add-category">
                        <i class="bi bi-folder-plus"></i><span class="d-none d-lg-inline"> Add Category</span>
                    </button>
                    <a href="/admin/startpage" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                </div>
            </div>

            <?php if (empty($categories)): ?>
                <p class="text-secondary">No categories yet. Add a category to get started.</p>
            <?php else: ?>

                <?php foreach ($categories as $catIndex => $category): ?>
                <div class="card mb-3" id="category-card-<?= $category['id'] ?>" data-category-id="<?= $category['id'] ?>">
                    <div class="card-header d-flex align-items-center justify-content-between gap-2">
                        <span class="fw-semibold category-name"><?= esc($category['name']) ?></span>
                        <div class="d-flex gap-1">
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-move-category-up"
                                data-id="<?= $category['id'] ?>"
                                aria-label="Move category up"
                                <?= $catIndex === 0 ? 'disabled' : '' ?>>
                                <i class="bi bi-arrow-up"></i>
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-outline-secondary btn-move-category-down"
                                data-id="<?= $category['id'] ?>"
                                aria-label="Move category down"
                                <?= $catIndex === count($categories) - 1 ? 'disabled' : '' ?>>
                                <i class="bi bi-arrow-down"></i>
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary btn-edit-category"
                                data-id="<?= $category['id'] ?>"
                                data-name="<?= esc($category['name'], 'attr') ?>"
                                aria-label="Edit category">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-outline-primary btn-delete-category"
                                data-id="<?= $category['id'] ?>"
                                data-name="<?= esc($category['name'], 'attr') ?>"
                                aria-label="Delete category">
                                <i class="bi bi-trash3-fill"></i>
                            </button>
                            <button type="button"
                                class="btn btn-sm btn-primary btn-add-shortcut"
                                data-category-id="<?= $category['id'] ?>"
                                data-category-name="<?= esc($category['name'], 'attr') ?>"
                                aria-label="Add shortcut">
                                <i class="bi bi-plus-lg"></i><span class="d-none d-lg-inline"> Add Shortcut</span>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($category['shortcuts'])): ?>
                            <p class="text-secondary p-3 mb-0">No shortcuts in this category.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover align-middle mb-0" id="shortcuts-table-<?= $category['id'] ?>">
                                    <thead>
                                        <tr>
                                            <th style="width:2rem"></th>
                                            <th style="width:3rem">Icon</th>
                                            <th>Name</th>
                                            <th>URL</th>
                                            <th class="text-end" style="width:8rem">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($category['shortcuts'] as $scIndex => $shortcut): ?>
                                        <tr id="shortcut-row-<?= $shortcut['id'] ?>" data-shortcut-id="<?= $shortcut['id'] ?>">
                                            <td class="text-center">
                                                <div class="d-flex flex-column gap-1">
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary p-0 lh-1 btn-move-shortcut-up"
                                                        data-id="<?= $shortcut['id'] ?>"
                                                        data-category-id="<?= $category['id'] ?>"
                                                        aria-label="Move up"
                                                        <?= $scIndex === 0 ? 'disabled' : '' ?>>
                                                        <i class="bi bi-chevron-up"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-sm btn-outline-secondary p-0 lh-1 btn-move-shortcut-down"
                                                        data-id="<?= $shortcut['id'] ?>"
                                                        data-category-id="<?= $category['id'] ?>"
                                                        aria-label="Move down"
                                                        <?= $scIndex === count($category['shortcuts']) - 1 ? 'disabled' : '' ?>>
                                                        <i class="bi bi-chevron-down"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($shortcut['icon_filename'] !== ''): ?>
                                                    <?php $adminIconClass = trim(($shortcut['icon_invert'] ? 'invert ' : '') . ($shortcut['icon_invert_light'] ? 'invert-light' : '')); ?>
                                                    <img src="/uploads/startpage/icons/<?= esc($shortcut['icon_filename'], 'attr') ?>" alt="<?= esc($shortcut['name'], 'attr') ?> icon" class="<?= $adminIconClass ?>" style="width:32px;height:32px;object-fit:contain;">
                                                <?php else: ?>
                                                    <i class="bi bi-link-45deg text-secondary" style="font-size:1.5rem;"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= esc($shortcut['name']) ?></td>
                                            <td><a href="<?= esc($shortcut['url'], 'attr') ?>" target="_blank" rel="noopener noreferrer" class="text-truncate d-block" style="max-width:300px;"><?= esc($shortcut['url']) ?></a></td>
                                            <td class="text-end">
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <button type="button"
                                                        class="btn btn-outline-primary btn-edit-shortcut"
                                                        data-id="<?= $shortcut['id'] ?>"
                                                        data-category-id="<?= $shortcut['category_id'] ?>"
                                                        data-name="<?= esc($shortcut['name'], 'attr') ?>"
                                                        data-url="<?= esc($shortcut['url'], 'attr') ?>"
                                                        data-icon-filename="<?= esc($shortcut['icon_filename'], 'attr') ?>"
                                                        data-icon-invert="<?= (int) $shortcut['icon_invert'] ?>"
                                                        data-icon-invert-light="<?= (int) $shortcut['icon_invert_light'] ?>"
                                                        aria-label="Edit">
                                                        <i class="bi bi-pencil-fill"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="btn btn-outline-primary btn-delete-shortcut"
                                                        data-id="<?= $shortcut['id'] ?>"
                                                        data-name="<?= esc($shortcut['name'], 'attr') ?>"
                                                        aria-label="Delete">
                                                        <i class="bi bi-trash3-fill"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>

            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="modal-add-category" tabindex="-1" aria-labelledby="modal-add-category-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-category-label">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="add-category-name" class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="add-category-name" placeholder="e.g. Development" maxlength="100" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-add-category-confirm">Add Category</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="modal-edit-category" tabindex="-1" aria-labelledby="modal-edit-category-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-edit-category-label">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-category-id">
                <div class="mb-3">
                    <label for="edit-category-name" class="form-label">Category Name</label>
                    <input type="text" class="form-control" id="edit-category-name" maxlength="100" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-edit-category-confirm">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Category Modal -->
<div class="modal fade" id="modal-delete-category" tabindex="-1" aria-labelledby="modal-delete-category-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-category-label">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="delete-category-id">
                <p>Delete category <strong id="delete-category-name"></strong> and all its shortcuts? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-delete-category-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Shortcut Modal -->
<div class="modal fade" id="modal-add-shortcut" tabindex="-1" aria-labelledby="modal-add-shortcut-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-add-shortcut-label">Add Shortcut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="add-shortcut-category-id">
                <div class="mb-3">
                    <label for="add-shortcut-category-display" class="form-label">Category</label>
                    <input type="text" class="form-control" id="add-shortcut-category-display" readonly>
                </div>
                <div class="mb-3">
                    <label for="add-shortcut-name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="add-shortcut-name" placeholder="e.g. GitHub" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label for="add-shortcut-url" class="form-label">URL</label>
                    <input type="url" class="form-control" id="add-shortcut-url" placeholder="https://example.com" maxlength="500" required>
                </div>
                <div class="mb-3">
                    <?php if (! empty($shortcut_icons)): ?>
                        <label class="form-label">Icon</label>
                        <div class="border rounded p-2 mb-3">
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="small text-secondary">Use an existing icon</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-add-shortcut-clear-existing-icon">
                                    <i class="bi bi-x-lg"></i><span class="d-none d-sm-inline"> Clear</span>
                                </button>
                            </div>
                            <div class="row row-cols-4 row-cols-sm-6 g-2">
                                <?php foreach ($shortcut_icons as $iconIndex => $icon): ?>
                                    <?php
                                    $iconInputId = 'add-shortcut-existing-icon-' . $iconIndex;
                                    $iconLabel   = 'Use icon from ' . $icon['label'];
                                    if ((int) $icon['usage_count'] > 1) {
                                        $iconLabel .= ' and ' . ((int) $icon['usage_count'] - 1) . ' more';
                                    }
                                    ?>
                                    <div class="col">
                                        <input
                                            type="radio"
                                            class="btn-check add-shortcut-existing-icon"
                                            name="add-shortcut-existing-icon"
                                            id="<?= esc($iconInputId, 'attr') ?>"
                                            value="<?= esc($icon['filename'], 'attr') ?>"
                                            autocomplete="off"
                                            aria-label="<?= esc($iconLabel, 'attr') ?>">
                                        <label
                                            class="btn btn-outline-secondary w-100 p-2 d-flex align-items-center justify-content-center"
                                            for="<?= esc($iconInputId, 'attr') ?>"
                                            title="<?= esc($iconLabel, 'attr') ?>">
                                            <img src="/uploads/startpage/icons/<?= esc($icon['filename'], 'attr') ?>" alt="" style="width:32px;height:32px;object-fit:contain;">
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    <label for="add-shortcut-icon" class="form-label"><?= empty($shortcut_icons) ? 'Icon' : 'Upload New Icon' ?></label>
                    <input type="file" class="form-control" id="add-shortcut-icon" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,image/x-icon,image/vnd.microsoft.icon">
                    <div class="form-text">Displayed at 40&times;40px. Accepted formats: PNG, JPEG, GIF, WebP, SVG, ICO. Max 512&nbsp;KB.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon Display</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="add-shortcut-icon-invert">
                        <label class="form-check-label" for="add-shortcut-icon-invert">Invert in dark mode (<code>.invert</code>)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="add-shortcut-icon-invert-light">
                        <label class="form-check-label" for="add-shortcut-icon-invert-light">Invert in light mode (<code>.invert-light</code>)</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-add-shortcut-confirm">Add Shortcut</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Shortcut Modal -->
<div class="modal fade" id="modal-edit-shortcut" tabindex="-1" aria-labelledby="modal-edit-shortcut-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-edit-shortcut-label">Edit Shortcut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit-shortcut-id">
                <div class="mb-3">
                    <label for="edit-shortcut-category-id" class="form-label">Category</label>
                    <select class="form-select" id="edit-shortcut-category-id">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= esc($cat['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="edit-shortcut-name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="edit-shortcut-name" maxlength="100" required>
                </div>
                <div class="mb-3">
                    <label for="edit-shortcut-url" class="form-label">URL</label>
                    <input type="url" class="form-control" id="edit-shortcut-url" maxlength="500" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Current Icon</label>
                    <div id="edit-shortcut-current-icon" class="mb-2"></div>
                    <label for="edit-shortcut-icon" class="form-label">Replace Icon</label>
                    <input type="file" class="form-control" id="edit-shortcut-icon" accept="image/png,image/jpeg,image/gif,image/webp,image/svg+xml,image/x-icon,image/vnd.microsoft.icon">
                    <div class="form-text">Leave blank to keep the current icon. Max 512&nbsp;KB.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Icon Display</label>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit-shortcut-icon-invert">
                        <label class="form-check-label" for="edit-shortcut-icon-invert">Invert in dark mode (<code>.invert</code>)</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="edit-shortcut-icon-invert-light">
                        <label class="form-check-label" for="edit-shortcut-icon-invert-light">Invert in light mode (<code>.invert-light</code>)</label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-edit-shortcut-confirm">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Shortcut Modal -->
<div class="modal fade" id="modal-delete-shortcut" tabindex="-1" aria-labelledby="modal-delete-shortcut-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-shortcut-label">Delete Shortcut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="delete-shortcut-id">
                <p>Delete shortcut <strong id="delete-shortcut-name"></strong>? This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn-delete-shortcut-confirm">Delete</button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
