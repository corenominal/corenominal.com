<!doctype html>
<html lang="en-GB" data-bs-theme="auto" class="sidebar-template">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= esc((string)$title) ?> - <?= esc((string)config('App')->siteName) ?></title>
    <meta name="theme-color" content="#000000">
    <!-- Favicon and touch icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icon-16x16.png">
    <!-- Stylesheets -->
    <?php $f = FCPATH . 'assets/css/vendor/bootstrap-corenominal.css'; ?>
    <link rel="stylesheet" href="/assets/css/vendor/bootstrap-corenominal.css<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"/>
    <?php $f = FCPATH . 'assets/css/vendor/bootstrap-icons.css'; ?>
    <link rel="stylesheet" href="/assets/css/vendor/bootstrap-icons.css<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"/>
    <!-- Stylesheets Dynamic -->
    <?php if(isset($css)): foreach ($css as $file): $cssPath = FCPATH . 'assets/css/' . $file . '.css'; ?>
    <link rel="stylesheet" href="/assets/css/<?= $file ?>.css<?= file_exists($cssPath) ? '?v=' . filemtime($cssPath) : '' ?>">
    <?php endforeach; endif; ?>
    <!-- JavaScript -->
    <?php $f = FCPATH . 'assets/js/vendor/bootstrap.bundle.min.js'; ?>
    <script defer src="/assets/js/vendor/bootstrap.bundle.min.js<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"></script>
    <?php $f = FCPATH . 'assets/js/common/theme-select.js'; ?>
    <script defer src="/assets/js/common/theme-select.js<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"></script>
    <?php $f = FCPATH . 'assets/js/common/logout.js'; ?>
    <script defer src="/assets/js/common/logout.js<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"></script>
    <?php $f = FCPATH . 'assets/js/common/appmenu.js'; ?>
    <script defer src="/assets/js/common/appmenu.js<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"></script>
    <?php $f = FCPATH . 'assets/js/common/metrics.js'; ?>
    <script defer src="/assets/js/common/metrics.js<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"></script>
    <!-- JavaScript Dynamic -->
    <?php $f = FCPATH . 'assets/js/templates/default.js'; ?>
    <script defer src="/assets/js/templates/default.js<?= file_exists($f) ? '?v=' . filemtime($f) : '' ?>"></script>
    <?php if(isset($js)): foreach ($js as $file): $jsPath = FCPATH . 'assets/js/' . $file . '.js'; ?>
    <script defer src="/assets/js/<?= $file ?>.js<?= file_exists($jsPath) ? '?v=' . filemtime($jsPath) : '' ?>"></script>
    <?php endforeach; endif; ?>
</head>
<body class="sidebar-template d-flex">

    <!-- ═══════════════════════════════════════════════════════
         SIDEBAR  (collapsed = icon-rail by default)
    ════════════════════════════════════════════════════════════ -->
    <div id="sidebar" class="d-none d-lg-flex flex-column h-100 collapsed">

        <!-- Brand -->
        <div class="sidebar-brand">
            <a href="#" id="sidebarToggle" class="sidebar-logo-link" aria-label="Toggle sidebar" title="Toggle sidebar">
                <img src="/icon.svg" alt="" width="40" height="40" class="rounded-circle flex-shrink-0">
                <span class="sidebar-label fw-semibold text-nowrap">corenominal</span>
            </a>
        </div>

        <!-- Navigation -->
        <nav class="flex-grow-1 overflow-y-auto py-2 px-2 sidebar-nav">
            <?php
            $templateMenu = $templateMenu ?? 'templates/default-menu';
            echo view($templateMenu);
            ?>
        </nav>

        <!-- Sidebar footer -->
        <div class="p-2">
            <a href="/" class="sidebar-footer-link"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Home Page">
                <i class="bi bi-slash-square flex-shrink-0" aria-hidden="true"></i>
                <span class="sidebar-label">Home</span>
            </a>
            <a href="#" data-api-url="" class="sidebar-footer-link trigger-appmenu"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="App Menu">
                <i class="bi bi-grid-3x3-gap-fill flex-shrink-0" aria-hidden="true"></i>
                <span class="sidebar-label">App Menu</span>
            </a>
            <?php if( user_in_group('administrators') ): ?>
            <a href="/admin" class="sidebar-footer-link"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Admin">
                <i class="bi bi-gear flex-shrink-0" aria-hidden="true"></i>
                <span class="sidebar-label">Admin</span>
            </a>
            <?php endif; ?>
            <?php if( user_in_group('debug') ): ?>
            <a href="/debug" class="sidebar-footer-link"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Debug">
                <i class="bi bi-bug flex-shrink-0" aria-hidden="true"></i>
                <span class="sidebar-label">Debug</span>
            </a>
            <?php endif; ?>
            <div class="dropdown dropup">
                <a href="#" class="sidebar-footer-link dropdown-toggle"
                   data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-circle-half flex-shrink-0" aria-hidden="true"></i>
                    <span class="sidebar-label">Theme</span>
                </a>
                <ul class="dropdown-menu">
                    <li><button class="dropdown-item" type="button" data-theme="light"><i class="bi bi-sun me-2" aria-hidden="true"></i><span class="sidebar-label">Light</span></button></li>
                    <li><button class="dropdown-item" type="button" data-theme="dark"><i class="bi bi-moon me-2" aria-hidden="true"></i><span class="sidebar-label">Dark</span></button></li>
                    <li><button class="dropdown-item" type="button" data-theme="auto"><i class="bi bi-circle-half me-2" aria-hidden="true"></i><span class="sidebar-label">Auto</span></button></li>
                </ul>
            </div>
            <?php if( is_logged_in() ): ?>
            <a href="#" class="sidebar-footer-link trigger-logout"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Sign out">
                <i class="bi bi-box-arrow-right flex-shrink-0" aria-hidden="true"></i>
                <span class="sidebar-label">Sign Out</span>
            </a>
            <?php else: ?>
            <a href="/auth?redirect=<?= urlencode(current_url()) ?>" class="sidebar-footer-link"
               data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="Sign in">
                <i class="bi bi-box-arrow-in-right flex-shrink-0" aria-hidden="true"></i>
                <span class="sidebar-label">Sign in</span>
            </a>
            <?php endif; ?>
        </div>

    </div>


    <!-- ═══════════════════════════════════════════════════════
         MAIN CONTENT
    ════════════════════════════════════════════════════════════ -->
    <div class="flex-grow-1 d-flex flex-column h-100 overflow-hidden">

        <!-- Top bar -->
        <div class="topbar d-flex align-items-center gap-2 px-3">
            <a href="#" class="d-lg-none"
               data-bs-toggle="offcanvas"
               data-bs-target="#mobileSidebar"
               aria-controls="mobileSidebar"
               aria-label="Open sidebar">
                <img src="/icon.svg" alt="Logo" width="40" height="40" class="rounded-circle">
            </a>
        </div>

        <!-- Scrollable area -->
        <div class="scrollable-area flex-grow-1 overflow-y-auto d-flex flex-column">

            <main class="pt-3 pb-5 px-3">
                <div style="max-width: <?= $templateMaxWidth ?? '68ch' ?>; margin-inline: auto;">
                    <?= $this->renderSection('content') ?>
                </div>
            </main>

            <!-- Footer -->
            <?php
            $templateFooter = $templateFooter ?? 'templates/default-footer';
            echo view($templateFooter);
            ?>

        </div><!-- /scrollable area -->

    </div><!-- /main -->
</body>
</html>
