<?php

declare(strict_types=1);

/** @var string $detailTitle */
/** @var int $detailCount */
/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
    <h3 class="reception-card-title h6 mb-0">
        <?= e($detailTitle) ?>
        <span class="text-muted fw-normal">(<?= e(__('report.detail.count', ['count' => $detailCount])) ?>)</span>
    </h3>
    <a href="<?= e(report_export_url($filters)) ?>"
       class="btn btn-sm btn-outline-secondary text-nowrap report-export-btn">
        <?= e(__('report.export_csv')) ?>
    </a>
</div>
