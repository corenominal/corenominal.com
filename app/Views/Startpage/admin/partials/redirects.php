<div class="text-end mb-2">
    <a href="/admin/startpage/redirects" class="btn btn-outline-primary btn-sm"><i class="bi bi-gear-fill"></i> Manage</a>
</div>

<table class="table table-striped redirects-table">
    <thead>
        <tr>
            <th>Phrase</th>
            <th>URL</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($redirects as $redirect): ?>
            <tr>
                <td><a class="anchor-redirect" href="#"><?= esc($redirect['phrase']) ?></a></td>
                <td class="redirects-table__url" title="<?= esc($redirect['url']) ?>"><?= esc($redirect['url']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
