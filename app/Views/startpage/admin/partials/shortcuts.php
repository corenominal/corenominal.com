<?php if (empty($shortcut_categories)): ?>
    <p class="text-secondary"><em>No shortcuts yet.</em></p>
<?php else: ?>
    <div class="d-flex align-items-center justify-content-end gap-3 mb-2">
        <a href="/admin/startpage/shortcuts" class="btn btn-sm btn-outline-primary"><i class="bi bi-gear-fill"></i> Manage</a>
        <div class="form-check form-switch mb-0">
            <input class="form-check-input" type="checkbox" role="switch" id="shortcuts-new-tab">
            <label class="form-check-label text-secondary small" for="shortcuts-new-tab">New tab</label>
        </div>
    </div>
    <?php foreach ($shortcut_categories as $category): ?>
        <?php if (empty($category['shortcuts'])): ?>
            <?php continue; ?>
        <?php endif; ?>
        <div class="mb-4">
            <h6 class="text-secondary text-uppercase fw-semibold mb-2 small"><?= esc($category['name']) ?></h6>
            <div class="d-flex flex-wrap gap-3">
                <?php foreach ($category['shortcuts'] as $shortcut): ?>
                    <a href="<?= esc($shortcut['url'], 'attr') ?>" class="d-flex flex-column align-items-center text-decoration-none shortcut-item" style="width:64px;" title="<?= esc($shortcut['name'], 'attr') ?>">
                        <?php if ($shortcut['icon_filename'] !== ''): ?>
                            <?php $iconClass = 'mb-1' . ($shortcut['icon_invert'] ? ' invert' : '') . ($shortcut['icon_invert_light'] ? ' invert-light' : ''); ?>
                            <img src="/uploads/startpage/icons/<?= esc($shortcut['icon_filename'], 'attr') ?>" alt="<?= esc($shortcut['name'], 'attr') ?>" style="width:40px;height:40px;object-fit:contain;" class="<?= $iconClass ?>">
                        <?php else: ?>
                            <i class="bi bi-link-45deg mb-1" style="font-size:2.5rem;"></i>
                        <?php endif; ?>
                        <span class="text-center lh-sm" style="font-size:0.7rem;width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc($shortcut['name']) ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
