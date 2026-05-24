<?php

declare(strict_types=1);

/** @var array<string, mixed> $reportData */
/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */

$dispenseRows = $reportData['rows'] ?? [];
$rowsTotal = (int) ($reportData['rows_total'] ?? count($dispenseRows));
?>
<section class="reception-card mb-3">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('report.medicines.summary')) ?></h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-md-4">
            <?php $label = __('report.metric.active_medicines'); $value = (string) (int) ($reportData['active_medicines'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4">
            <?php $label = __('report.metric.medicines_dispensed'); $value = (string) (int) ($reportData['dispensed_units'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
    </div>
</section>

<section class="reception-card mb-3">
    <h3 class="reception-card-title h6 mb-3"><?= e(__('report.medicines.top_dispensed')) ?></h3>
    <?php if (($reportData['top_dispensed'] ?? []) === []): ?>
        <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm reception-table mb-0">
                <thead>
                <tr>
                    <th><?= e(__('medicine.field.name')) ?></th>
                    <th class="text-end"><?= e(__('report.col.quantity')) ?></th>
                    <th class="text-end"><?= e(__('report.metric.visits')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($reportData['top_dispensed'] as $row): ?>
                    <tr>
                        <td><?= e((string) $row['name']) ?></td>
                        <td class="text-end"><?= (int) $row['units_dispensed'] ?></td>
                        <td class="text-end"><?= (int) $row['visit_count'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="reception-card mt-3 report-detail-card">
    <?php
    $detailTitle = __('report.medicines.detail');
    $detailCount = $rowsTotal;
    require BASE_PATH . '/views/reports/partials/detail_header.php';
    ?>
    <?php if ($dispenseRows === []): ?>
        <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover reception-table mb-0 report-detail-table">
                <thead>
                <tr>
                    <th><?= e(__('report.col.datetime')) ?></th>
                    <th><?= e(__('patient.field.id')) ?></th>
                    <th><?= e(__('report.col.patient_name')) ?></th>
                    <th><?= e(__('report.col.medicine_name')) ?></th>
                    <th class="text-end"><?= e(__('report.col.quantity')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($dispenseRows as $row): ?>
                    <tr>
                        <td class="text-nowrap small"><?= e(Visit::formatVisitedAt((string) $row['visited_at'])) ?></td>
                        <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                        <td><?= e((string) $row['patient_name']) ?></td>
                        <td><?= e((string) $row['medicine_name']) ?></td>
                        <td class="text-end"><?= (int) $row['quantity'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $rowsShown = count($dispenseRows);
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>
