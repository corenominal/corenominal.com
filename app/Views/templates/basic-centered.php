<!doctype html>
<html lang="en-GB" data-bs-theme="auto">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <?php $pageTitle = is_array($title) ? implode(' - ', $title) : $title; ?>
        <title><?= esc($pageTitle) ?> - <?= esc(config('App')->siteName) ?></title>
        <meta name="theme-color" content="#0F0F0F">
        <!-- Favicon and touch icons -->
        <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
        <link rel="icon" type="image/png" sizes="32x32" href="/icon-32x32.png">
        <link rel="icon" type="image/png" sizes="16x16" href="/icon-16x16.png">
        <!-- Social verification -->
        <?= social_verification_tags() ?>
        <!-- Stylesheets -->
        <?php $fPath = FCPATH . 'assets/css/vendor/bootstrap-corenominal.css'; ?>
        <link rel="stylesheet" href="/assets/css/vendor/bootstrap-corenominal.css<?= file_exists($fPath) ? '?v=' . filemtime($fPath) : '' ?>"/>
        <?php if(isset($css)): foreach ($css as $file): $cssPath = FCPATH . 'assets/css/' . $file . '.css'; ?>
        <link rel="stylesheet" href="/assets/css/<?= $file ?>.css<?= file_exists($cssPath) ? '?v=' . filemtime($cssPath) : '' ?>">
        <?php endforeach; endif; ?>
        <!-- JavaScript -->
        <?php $fPath = FCPATH . 'assets/js/vendor/bootstrap.bundle.min.js'; ?>
        <script defer src="/assets/js/vendor/bootstrap.bundle.min.js<?= file_exists($fPath) ? '?v=' . filemtime($fPath) : '' ?>"></script>
        <?php $fPath = FCPATH . 'assets/js/theme-select.js'; ?>
        <script defer src="/assets/js/theme-select.js<?= file_exists($fPath) ? '?v=' . filemtime($fPath) : '' ?>"></script>
        <?php $fPath = FCPATH . 'assets/js/common/metrics.js'; ?>
        <script defer src="/assets/js/common/metrics.js<?= file_exists($fPath) ? '?v=' . filemtime($fPath) : '' ?>"></script>
        <?php if(isset($js)): foreach ($js as $file): $jsPath = FCPATH . 'assets/js/' . $file . '.js'; ?>
        <script defer src="/assets/js/<?= $file ?>.js<?= file_exists($jsPath) ? '?v=' . filemtime($jsPath) : '' ?>"></script>
        <?php endforeach; endif; ?>
    </head>

    <body class="">
        <main class="d-flex align-items-center justify-content-center min-vh-100 pt-3 pb-5 px-3">
            <div class="text-center" style="max-width: <?= $templateMaxWidth ?? '68ch' ?>; margin-inline: auto;">
                <?= $this->renderSection('content') ?>
            </div>
        </main>
    </body>
</html>