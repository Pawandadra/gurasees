<?php

declare(strict_types=1);

/** @var array<string, mixed> $reportData */
/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */

$fmt = static fn (float $n): string => PaymentSettings::formatAmountDisplay($n);
$paymentRows = $reportData['rows'] ?? [];
$rowsTotal = (int) ($reportData['rows_total'] ?? count($paymentRows));
?>
<section class="reception-card mb-3">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('report.payments.summary')) ?></h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('payment.summary.paid'); $value = $fmt((float) ($reportData['paid_total'] ?? 0)); $variant = 'paid'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('payment.summary.outstanding'); $value = $fmt((float) ($reportData['pending_total'] ?? 0)); $variant = 'pending'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.transactions'); $value = (string) (int) ($reportData['transaction_count'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.grand_total'); $value = $fmt((float) ($reportData['grand_total'] ?? 0)); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
    </div>
</section>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="reception-card h-100">
            <h3 class="reception-card-title h6 mb-3"><?= e(__('report.payments.by_type')) ?></h3>
            <div class="table-responsive">
                <table class="table table-sm reception-table mb-0">
                    <thead>
                    <tr>
                        <th><?= e(__('payment.field.type')) ?></th>
                        <th class="text-end"><?= e(__('report.col.count')) ?></th>
                        <th class="text-end"><?= e(__('report.col.total')) ?></th>
                        <th class="text-end"><?= e(__('report.col.collected')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (($reportData['by_type'] ?? []) as $type => $row): ?>
                        <tr>
                            <td><?= e(Payment::typeLabel((string) $type)) ?></td>
                            <td class="text-end"><?= (int) ($row['count'] ?? 0) ?></td>
                            <td class="text-end"><?= e($fmt((float) ($row['total'] ?? 0))) ?></td>
                            <td class="text-end"><?= e($fmt((float) ($row['collected'] ?? 0))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
    <div class="col-lg-6">
        <section class="reception-card h-100">
            <h3 class="reception-card-title h6 mb-3"><?= e(__('report.payments.by_method')) ?></h3>
            <?php if (($reportData['by_method'] ?? []) === []): ?>
                <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm reception-table mb-0">
                        <thead>
                        <tr>
                            <th><?= e(__('payment.field.method')) ?></th>
                            <th class="text-end"><?= e(__('report.col.count')) ?></th>
                            <th class="text-end"><?= e(__('report.col.collected')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($reportData['by_method'] as $method => $row): ?>
                            <tr>
                                <td><?= e(PaymentSettings::methodLabel((string) $method)) ?></td>
                                <td class="text-end"><?= (int) ($row['count'] ?? 0) ?></td>
                                <td class="text-end"><?= e($fmt((float) ($row['collected'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="reception-card mt-3">
    <h3 class="reception-card-title h6 mb-3"><?= e(__('report.payments.by_status')) ?></h3>
    <div class="row g-2">
        <?php foreach (PaymentSettings::STATUSES as $status): ?>
            <div class="col-4 col-md-3">
                <?php
                $label = PaymentSettings::statusLabel($status);
                $value = (string) (int) (($reportData['by_status'][$status] ?? 0));
                $variant = $status === 'paid' ? 'paid' : ($status === 'pending' ? 'pending' : 'partial');
                require BASE_PATH . '/views/reports/partials/metric.php';
                ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="reception-card mt-3 report-detail-card">
    <?php
    $detailTitle = __('report.payments.detail');
    $detailCount = $rowsTotal;
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
                    <th><?= e(__('patient.field.phone')) ?></th>
                    <th><?= e(__('payment.field.type')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.total')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.paid')) ?></th>
                    <th class="text-end"><?= e(__('payment.field.balance')) ?></th>
                    <th><?= e(__('payment.field.method')) ?></th>
                    <th><?= e(__('payment.field.status')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($paymentRows as $row): ?>
                    <tr>
                        <td class="text-nowrap small"><?= e(Payment::formatDate((string) $row['payment_date'])) ?></td>
                        <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                        <td><?= e((string) $row['patient_name']) ?></td>
                        <td class="text-nowrap"><?= e(phone_format_display((string) $row['phone'])) ?></td>
                        <td class="small"><?= e(Payment::typeLabel((string) $row['payment_type'])) ?></td>
                        <td class="text-end text-nowrap"><?= e($fmt((float) $row['total_amount'])) ?></td>
                        <td class="text-end text-nowrap"><?= e($fmt((float) $row['paid_amount'])) ?></td>
                        <td class="text-end text-nowrap">
                            <?= (float) $row['balance_amount'] > 0
                                ? e($fmt((float) $row['balance_amount']))
                                : table_na() ?>
                        </td>
                        <td class="small text-nowrap">
                            <?= ($row['payment_method'] ?? '') !== ''
                                ? e(PaymentSettings::methodLabel((string) $row['payment_method']))
                                : table_na() ?>
                        </td>
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
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>
