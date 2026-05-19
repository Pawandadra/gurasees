<?php

declare(strict_types=1);

$pageTitle = __('role.manager');

ob_start();
?>
<h1 class="reception-page-title mb-3"><?= e(__('role.manager')) ?></h1>
<div class="reception-card">
    <p class="mb-3"><?= e(__('symptom.manager.intro')) ?></p>
    <div class="d-flex flex-wrap gap-2">
        <a href="<?= e(base_url('/medicines.php')) ?>" class="btn btn-reception-primary"><?= e(__('nav.medicines')) ?></a>
        <a href="<?= e(base_url('/courier.php')) ?>" class="btn btn-outline-secondary"><?= e(__('nav.courier')) ?></a>
        <a href="<?= e(base_url('/symptoms.php')) ?>" class="btn btn-outline-secondary"><?= e(__('nav.symptoms')) ?></a>
        <a href="<?= e(base_url('/payment_settings.php')) ?>" class="btn btn-outline-secondary"><?= e(__('nav.payment_settings')) ?></a>
    </div>
</div>
<?php
$content = ob_get_clean();
$activeNav = 'dashboard';
require BASE_PATH . '/views/layouts/dashboard.php';
