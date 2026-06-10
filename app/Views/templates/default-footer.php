<footer class="mt-auto py-3 px-3">
    <div style="max-width: <?= $templateMaxWidth ?? '68ch' ?>; margin-inline: auto;" class="text-center">
        <small>
            <span class="d-block py-3"><img class="img-32" src="/assets/img/bone.svg" alt=""></span>
            <br>
            <span class="flip-horizontal">&copy;</span> <?= date('Y') ?> corenominal. All rights reserved.<br>
            <a class="text-decoration-none me-2" href="<?= config('Urls')->github ?>"><i class="bi bi-github"></i> GitHub</a> <a class="text-decoration-none me-2" href="<?= config('Urls')->readme ?>"><i class="bi bi-file-text-fill"></i> README</a> <a class="text-decoration-none" href="<?= config('Urls')->license ?>"><i class="bi bi-file-earmark-text-fill"></i> License</a>
        </small>
        <?php if( session()->get('is_admin') ): ?>
        <br>
        <small class="text-secondary d-inline-block pt-2"><strong>Hostname:</strong> <?= gethostname() ?><br><strong>PHP version:</strong> <?= phpversion() ?> / <strong>CodeIgniter version:</strong> <?= \CodeIgniter\CodeIgniter::CI_VERSION ?></small>
        <?php endif; ?>
    </div>
</footer>