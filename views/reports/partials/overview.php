<?php

declare(strict_types=1);

/** @var array<string, mixed> $reportData */
/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */

$payments = $reportData['payments'] ?? [];
$visits = $reportData['visits'] ?? [];
$patients = $reportData['patients'] ?? [];
$courier = $reportData['courier'] ?? [];
$medicines = $reportData['medicines'] ?? [];
$fmt = static fn (float $n): string => PaymentSettings::formatAmountDisplay($n);
$paymentRows = $payments['rows'] ?? [];
$visitRows = $visits['rows'] ?? [];
$paymentRowsTotal = (int) ($payments['rows_total'] ?? count($paymentRows));
$visitRowsTotal = (int) ($visits['rows_total'] ?? count($visitRows));
?>
<section class="reception-card mb-3">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('report.overview.summary')) ?></h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.collected');
            $value = $fmt((float) ($payments['paid_total'] ?? 0));
            $variant = 'paid';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.outstanding');
            $value = $fmt((float) ($payments['pending_total'] ?? 0));
            $variant = 'pending';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.visits');
            $value = (string) (int) ($visits['visit_count'] ?? 0);
            $variant = '';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.registrations');
            $value = (string) (int) ($patients['registration_count'] ?? 0);
            $variant = '';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.visit_revenue');
            $value = $fmt((float) ($visits['grand_total'] ?? 0));
            $variant = '';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.courier_packages');
            $value = (string) (int) ($courier['package_count'] ?? 0);
            $variant = '';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.medicines_dispensed');
            $value = (string) (int) ($medicines['dispensed_units'] ?? 0);
            $variant = '';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
        <div class="col-6 col-lg-3">
            <?php
            $label = __('report.metric.total_patients');
            $value = (string) (int) ($patients['total_patients'] ?? 0);
            $variant = '';
            require BASE_PATH . '/views/reports/partials/metric.php';
            ?>
        </div>
    </div>
</section>

<p class="text-muted small mb-3"><?= e(__('report.overview.hint')) ?></p>

<section class="reception-card mb-3 report-detail-card">
    <?php
    $detailTitle = __('report.overview.payments_detail');
    $detailCount = $paymentRowsTotal;
    require BASE_PATH . '/views/reports/partials/detail_header.php';
    ?>
    <?php if ($paymentRows === []): ?>
        <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover reception-table mb-0 report-detail-table">
                <thead>
                <tr>
                    <th><?= e(__('report.col.datetime')) ?></th>
                    <th><?= e(__('patient.field.id')) ?></th>
                    <th><?= e(__('patient.field.name')) ?></th>
                    <th><?= e(__('payment.field.type')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.total')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.paid')) ?></th>
                    <th><?= e(__('payment.field.status')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($paymentRows as $row): ?>
                    <tr>
                        <td class="text-nowrap small"><?= e(Payment::formatDate((string) $row['payment_date'])) ?></td>
                        <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                        <td><?= e((string) $row['patient_name']) ?></td>
                        <td class="small"><?= e(Payment::typeLabel((string) $row['payment_type'])) ?></td>
                        <td class="text-end text-nowrap"><?= e($fmt((float) $row['total_amount'])) ?></td>
                        <td class="text-end text-nowrap"><?= e($fmt((float) $row['paid_amount'])) ?></td>
                        <td>
                            <span class="payment-status-badge payment-status-<?= e((string) $row['payment_status']) ?>">
                                <?= e(PaymentSettings::statusLabel((string) $row['payment_status'])) ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $rowsShown = count($paymentRows);
        $rowsTotal = $paymentRowsTotal;
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>

<section class="reception-card report-detail-card">
    <?php
    $detailTitle = __('report.overview.visits_detail');
    $detailCount = $visitRowsTotal;
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
                    <th class="text-end"><?= e(__('report.col.total')) ?></th>
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
                        <td class="text-end text-nowrap fw-semibold"><?= e($fmt((float) $row['grand_total'])) ?></td>
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
        $rowsTotal = $visitRowsTotal;
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>
