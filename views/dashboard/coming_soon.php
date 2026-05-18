<?php

declare(strict_types=1);

/** @var string $pageHeading */

ob_start();
?>
<h1 class="reception-page-title mb-3"><?= e($pageHeading) ?></h1>
<div class="reception-card">
    <p class="text-muted mb-0"><?= e(__('dashboard.coming_soon')) ?></p>
</div>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
