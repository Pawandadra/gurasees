<?php

declare(strict_types=1);

/** @var string|null $pageTitle */
/** @var string|null $activeNav */

$pageTitle = $pageTitle ?? __('nav.dashboard');
$locale = locale();
$user = auth_user();
$role = $user['role'] ?? '';
$roleLabel = auth_role_label($role);
$activeNav = $activeNav ?? nav_active_id();
?>
<!DOCTYPE html>
<html lang="<?= $locale === 'pa' ? 'pa' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> — <?= e(__('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= e(base_url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="reception-body app-layout">
<header class="reception-header shadow-sm">
    <div class="container-fluid px-3 px-lg-4">
        <div class="d-flex align-items-center justify-content-between py-2 gap-3">
            <div class="d-flex align-items-center gap-2">
                <button type="button" class="btn btn-sm btn-outline-light app-sidebar-toggle d-lg-none"
                        id="sidebarToggle" aria-controls="appSidebar" aria-expanded="false"
                        aria-label="<?= e(__('nav.menu_toggle')) ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <a href="<?= e(base_url('/dashboard.php')) ?>" class="reception-brand text-decoration-none">
                    <?= e(__('app.name')) ?>
                </a>
                <span class="reception-role-badge d-none d-sm-inline"><?= e($roleLabel) ?></span>
            </div>
            <?php require BASE_PATH . '/views/partials/header_patient_search.php'; ?>
            <div class="d-flex align-items-center gap-2 flex-wrap flex-shrink-0">
                <span class="text-white-50 small d-none d-md-inline"><?= e($user['name'] ?? '') ?></span>
                <div class="btn-group btn-group-sm reception-lang" role="group" aria-label="<?= e(__('lang.label')) ?>">
                    <a href="<?= e(lang_url('en')) ?>"
                       class="btn btn-sm<?= $locale === 'en' ? ' btn-light' : ' btn-outline-light' ?>"><?= e(__('lang.english')) ?></a>
                    <a href="<?= e(lang_url('pa')) ?>"
                       class="btn btn-sm<?= $locale === 'pa' ? ' btn-light' : ' btn-outline-light' ?>"><?= e(__('lang.punjabi')) ?></a>
                </div>
                <form method="post" action="<?= e(base_url('/logout.php')) ?>" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-outline-light"><?= e(__('nav.logout')) ?></button>
                </form>
            </div>
        </div>
    </div>
</header>

<div class="app-shell">
    <?php require BASE_PATH . '/views/partials/sidebar.php'; ?>
    <main class="app-main">
        <div class="app-main-inner">
            <?= $content ?? '' ?>
        </div>
    </main>
</div>

<?php require BASE_PATH . '/views/partials/patient_delete_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script src="<?= e(base_url('assets/js/sidebar.js')) ?>" defer></script>
<script src="<?= e(base_url('assets/js/patient-delete.js')) ?>"></script>
<script src="<?= e(base_url('assets/js/patient-search.js')) ?>" defer></script>
<?php foreach ($pageScripts ?? [] as $script): ?>
<script src="<?= e(base_url($script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
