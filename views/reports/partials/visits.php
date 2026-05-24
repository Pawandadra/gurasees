<?php

declare(strict_types=1);

/** @var array<string, mixed> $reportData */
/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */

$fmt = static fn (float $n): string => PaymentSettings::formatAmountDisplay($n);
$visitRows = $reportData['rows'] ?? [];
$rowsTotal = (int) ($reportData['rows_total'] ?? count($visitRows));
?>
<section class="reception-card mb-3">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('report.visits.summary')) ?></h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.visits'); $value = (string) (int) ($reportData['visit_count'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.visit_revenue'); $value = $fmt((float) ($reportData['grand_total'] ?? 0)); $variant = 'paid'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.visit_charges'); $value = $fmt((float) ($reportData['visit_charges'] ?? 0)); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.medicine_charges'); $value = $fmt((float) ($reportData['medicine_charges'] ?? 0)); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.courier_charges'); $value = $fmt((float) ($reportData['courier_charges'] ?? 0)); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.courier_visits'); $value = (string) (int) ($reportData['courier_visits'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
    </div>
</section>

<div class="row g-3">
    <div class="col-lg-5">
        <section class="reception-card h-100">
            <h3 class="reception-card-title h6 mb-3"><?= e(__('report.visits.payment_status')) ?></h3>
            <div class="row g-2">
                <div class="col-6 col-md-4">
                    <?php $label = __('payment.status.paid'); $value = (string) (int) ($reportData['paid_count'] ?? 0); $variant = 'paid'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
                </div>
                <div class="col-6 col-md-4">
                    <?php $label = __('payment.status.pending'); $value = (string) (int) ($reportData['pending_count'] ?? 0); $variant = 'pending'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
                </div>
                <div class="col-6 col-md-4">
                    <?php $label = __('payment.status.partial'); $value = (string) (int) ($reportData['partial_count'] ?? 0); $variant = 'partial'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
                </div>
            </div>
        </section>
    </div>
    <div class="col-lg-7">
        <section class="reception-card h-100">
            <h3 class="reception-card-title h6 mb-3"><?= e(__('report.visits.daily')) ?></h3>
            <?php if (($reportData['daily'] ?? []) === []): ?>
                <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm reception-table mb-0">
                        <thead>
                        <tr>
                            <th><?= e(__('report.col.date')) ?></th>
                            <th class="text-end"><?= e(__('report.metric.visits')) ?></th>
                            <th class="text-end"><?= e(__('report.col.total')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reportData['daily'] as $day): ?>
                            <tr>
                                <td><?= e((string) $day['label']) ?></td>
                                <td class="text-end"><?= (int) $day['visit_count'] ?></td>
                                <td class="text-end"><?= e($fmt((float) $day['total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="reception-card mt-3 report-detail-card">
    <?php
    $detailTitle = __('report.visits.detail');
    $detailCount = $rowsTotal;
    require BASE_PATH . '/views/reports/partials/detail_header.php';
    ?>
    <?php if ($visitRows === []): ?>
        <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover reception-table mb-0 report-detail-table">
                <thead>
                <tr>
                    <th><?= e(__('report.col.datetime')) ?></th>
                    <th><?= e(__('patient.field.id')) ?></th>
                    <th><?= e(__('patient.field.name')) ?></th>
                    <th><?= e(__('patient.field.phone')) ?></th>
                    <th class="text-end"><?= e(__('report.col.total')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.paid')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.balance')) ?></th>
                    <th><?= e(__('payment.field.status')) ?></th>
                    <th><?= e(__('visit.field.medicines')) ?></th>
                    <th><?= e(__('visit.field.notes')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($visitRows as $row): ?>
                    <tr>
                        <td class="text-nowrap small"><?= e(Visit::formatVisitedAt((string) $row['visited_at'])) ?></td>
                        <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                        <td><?= e((string) $row['patient_name']) ?></td>
                        <td class="text-nowrap"><?= e(phone_format_display((string) $row['phone'])) ?></td>
                        <td class="text-end text-nowrap fw-semibold"><?= e($fmt((float) $row['grand_total'])) ?></td>
                        <td class="text-end text-nowrap"><?= e($fmt((float) $row['paid_amount'])) ?></td>
                        <td class="text-end text-nowrap">
                            <?= (float) $row['balance_amount'] > 0
                                ? e($fmt((float) $row['balance_amount']))
                                : table_na() ?>
                        </td>
                        <td>
                            <?php if ($row['payment_status'] !== ''): ?>
                                <span class="payment-status-badge payment-status-<?= e((string) $row['payment_status']) ?>">
                                    <?= e(PaymentSettings::statusLabel((string) $row['payment_status'])) ?>
                                </span>
                            <?php else: ?>
                                <?= table_na() ?>
                            <?php endif; ?>
                        </td>
                        <td class="small"><?= table_cell((string) ($row['medicines_summary'] ?? '')) ?></td>
                        <td class="small"><?= table_cell((string) ($row['notes'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $rowsShown = count($visitRows);
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>
