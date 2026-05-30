<?php

declare(strict_types=1);

/** @var array<string, mixed> $reportData */
/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */

$fmt = static fn (float $n): string => PaymentSettings::formatAmountDisplay($n);
$courierRows = $reportData['rows'] ?? [];
$rowsTotal = (int) ($reportData['rows_total'] ?? count($courierRows));
?>
<section class="reception-card mb-3">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('report.courier.summary')) ?></h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.courier_packages'); $value = (string) (int) ($reportData['package_count'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.courier_revenue'); $value = $fmt((float) ($reportData['courier_revenue'] ?? 0)); $variant = 'paid'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
    </div>
</section>

<section class="reception-card">
    <h3 class="reception-card-title h6 mb-3"><?= e(__('report.courier.by_status')) ?></h3>
    <div class="row g-2">
        <div class="col-4">
            <?php $label = Courier::statusLabel(Courier::STATUS_PENDING); $value = (string) (int) ($reportData['pending_count'] ?? 0); $variant = 'pending'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-4">
            <?php $label = Courier::statusLabel(Courier::STATUS_SENT); $value = (string) (int) ($reportData['sent_count'] ?? 0); $variant = 'paid'; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-4">
            <?php $label = Courier::statusLabel(Courier::STATUS_CANCELED); $value = (string) (int) ($reportData['canceled_count'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
    </div>
</section>

<section class="reception-card mt-3 report-detail-card">
    <?php
    $detailTitle = __('report.courier.detail');
    $detailCount = $rowsTotal;
    require BASE_PATH . '/views/reports/partials/detail_header.php';
    ?>
    <?php if ($courierRows === []): ?>
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
                    <th><?= e(__('patient.field.delivery_address')) ?></th>
                    <th><?= e(__('visit.form.delivery_method')) ?></th>
                    <th><?= e(__('courier.field.status')) ?></th>
                    <th><?= e(__('report.col.dispatched_at')) ?></th>
                    <th class="text-end"><?= e(__('report.metric.courier_charges')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($courierRows as $row): ?>
                    <tr>
                        <td class="text-nowrap small"><?= e(Visit::formatVisitedAt((string) $row['visited_at'])) ?></td>
                        <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                        <td><?= e((string) $row['patient_name']) ?></td>
                        <td class="text-nowrap"><?= e(phone_format_display((string) $row['phone'])) ?></td>
                        <td class="small"><?= e((string) $row['delivery_address']) ?></td>
                        <td class="text-nowrap small"><?= e((string) ($row['delivery_method_label'] ?? '')) ?></td>
                        <td>
                            <?php
                            $courierBadgeClass = match ((string) $row['courier_status']) {
                                Courier::STATUS_SENT => 'paid',
                                Courier::STATUS_PENDING => 'pending',
                                Courier::STATUS_CANCELED => 'canceled',
                                default => 'partial',
                            };
                            ?>
                            <span class="payment-status-badge payment-status-<?= e($courierBadgeClass) ?>">
                                <?= e(Courier::statusLabel((string) $row['courier_status'])) ?>
                            </span>
                        </td>
                        <td class="text-nowrap small">
                            <?= $row['courier_dispatched_at'] !== ''
                                ? e(Visit::formatVisitedAt((string) $row['courier_dispatched_at']))
                                : table_na() ?>
                        </td>
                        <td class="text-end text-nowrap"><?= e($fmt((float) $row['courier_charge'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $rowsShown = count($courierRows);
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>
