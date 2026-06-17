<div class="text-end mb-2">
    <a href="/admin/startpage/search" class="btn btn-outline-primary btn-sm"><i class="bi bi-gear-fill"></i> Manage</a>
</div>

<table class="table table-striped redirects-table">
    <thead>
        <tr>
            <th>Phrase</th>
            <th>URL</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($search_engines as $engine): ?>
            <tr>
                <td><a class="anchor-search-engine" href="#"><?= esc($engine['phrase']) ?></a></td>
                <td class="redirects-table__url" title="<?= esc($engine['url']) ?>"><?= esc($engine['url']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
