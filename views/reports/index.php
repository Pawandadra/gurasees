<?php

declare(strict_types=1);

/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */
/** @var string $reportType */
/** @var string $period */
/** @var array<string, scalar|null> $queryFilters */
/** @var bool $hasCustomDates */
/** @var array<string, mixed> $reportData */
/** @var bool $dbError */
/** @var string $pageTitle */
/** @var string $activeNav */

$reportType = $reportType ?? Report::TYPE_OVERVIEW;
$queryFilters = $queryFilters ?? report_query_filters($filters);
$reportOptions = report_type_options();

ob_start();
?>
<div class="reports-page">
    <h1 class="reception-page-title reports-page-title mb-3"><?= e(__('report.title')) ?></h1>

    <?php if ($dbError): ?>
        <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
    <?php else: ?>

        <nav class="report-type-nav mb-3" aria-label="<?= e(__('report.type.label')) ?>">
            <ul class="nav nav-pills report-type-pills flex-wrap gap-1">
                <?php foreach ($reportOptions as $typeKey => $typeLabel): ?>
                    <?php $typeQuery = array_merge($queryFilters, ['report' => $typeKey]); ?>
                    <li class="nav-item">
                        <a class="nav-link<?= $reportType === $typeKey ? ' active' : '' ?>"
                           href="<?= e(report_url($typeQuery)) ?>">
                            <?= e($typeLabel) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>

        <?php require BASE_PATH . '/views/reports/partials/period_toolbar.php'; ?>

        <?php
        $partial = BASE_PATH . '/views/reports/partials/' . $reportType . '.php';
        if (is_readable($partial)) {
            require $partial;
        } else {
            require BASE_PATH . '/views/reports/partials/overview.php';
        }
        ?>

    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
