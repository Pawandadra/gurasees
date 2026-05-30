<?php

declare(strict_types=1);

/** @var array<string, mixed> $visit */

$lines = $visit['medicine_lines'] ?? [];
$grandTotal = (float) ($visit['grand_total'] ?? 0);
$paymentStatus = (string) ($visit['payment_status'] ?? '');
$balance = Visit::paymentBalance($visit);
$visitChargeIncl = round((float) ($visit['visit_charge'] ?? 0) + (float) ($visit['visit_gst'] ?? 0), 2);
$medicineIncl = round((float) ($visit['medicine_total'] ?? 0) + (float) ($visit['medicine_gst'] ?? 0), 2);
$courierIncl = round((float) ($visit['courier_charge'] ?? 0) + (float) ($visit['courier_gst'] ?? 0), 2);
$courierStatus = (string) ($visit['courier_status'] ?? '');
?>
<dl class="visit-detail-list patient-detail-list mb-0">
    <div class="patient-detail-item">
        <dt><?= e(__('visit.field.datetime')) ?></dt>
        <dd><?= e(Visit::formatVisitedAt((string) $visit['visited_at'])) ?></dd>
    </div>
    <?php foreach (Visit::medicineDetailSections($visit, $lines) as $medicineSection): ?>
        <div class="patient-detail-item">
            <dt><?= e($medicineSection['label']) ?></dt>
            <dd><?= table_cell(Visit::formatMedicineSummary($medicineSection['lines'])) ?></dd>
        </div>
    <?php endforeach; ?>
    <div class="patient-detail-item">
        <dt><?= e(__('visit.field.visit_charge')) ?></dt>
        <dd><?= e(Medicine::formatPriceDisplay($visitChargeIncl)) ?></dd>
    </div>
    <?php if ($medicineIncl > 0): ?>
        <div class="patient-detail-item">
            <dt><?= e(__('visit.field.medicine_total')) ?></dt>
            <dd><?= e(Medicine::formatPriceDisplay($medicineIncl)) ?></dd>
        </div>
    <?php endif; ?>
    <?php if ($courierIncl > 0): ?>
        <div class="patient-detail-item">
            <dt><?= e(__('visit.field.courier_charge')) ?></dt>
            <dd><?= e(Medicine::formatPriceDisplay($courierIncl)) ?></dd>
        </div>
    <?php endif; ?>
    <div class="patient-detail-item">
        <dt><?= e(__('visit.field.grand_total')) ?></dt>
        <dd class="fw-semibold"><?= e(Medicine::formatPriceDisplay($grandTotal)) ?></dd>
    </div>
    <div class="patient-detail-item">
        <dt><?= e(__('payment.field.method')) ?></dt>
        <dd>
            <?= !empty($visit['payment_method'])
                ? e(PaymentSettings::methodLabel((string) $visit['payment_method']))
                : table_na() ?>
        </dd>
    </div>
    <div class="patient-detail-item">
        <dt><?= e(__('payment.field.status')) ?></dt>
        <dd>
            <?php if ($paymentStatus !== ''): ?>
                <?= PaymentSettings::statusBadgeHtml($paymentStatus) ?>
            <?php else: ?>
                <?= table_na() ?>
            <?php endif; ?>
        </dd>
    </div>
    <?php if (in_array($paymentStatus, ['paid', 'partial'], true)): ?>
        <div class="patient-detail-item">
            <dt><?= e(__('payment.field.paid_amount')) ?></dt>
            <dd><?= e(PaymentSettings::formatAmountDisplay(Visit::paymentPaidAmount($visit))) ?></dd>
        </div>
    <?php endif; ?>
    <?php if ($balance > 0): ?>
        <div class="patient-detail-item">
            <dt><?= e(__('payment.field.balance')) ?></dt>
            <dd><?= e(PaymentSettings::formatAmountDisplay($balance)) ?></dd>
        </div>
    <?php endif; ?>
    <?php if ($courierStatus !== ''): ?>
        <div class="patient-detail-item">
            <dt><?= e(__('visit.field.courier_status')) ?></dt>
            <dd><?= e(Courier::statusLabel($courierStatus)) ?></dd>
        </div>
    <?php endif; ?>
    <div class="patient-detail-item">
        <dt><?= e(__('visit.field.notes')) ?></dt>
        <dd><?= table_cell($visit['notes'] ?? '') ?></dd>
    </div>
    <?php if (trim((string) ($visit['recorded_by_name'] ?? '')) !== ''): ?>
        <div class="patient-detail-item">
            <dt><?= e(__('visit.field.recorded_by')) ?></dt>
            <dd><?= e((string) $visit['recorded_by_name']) ?></dd>
        </div>
    <?php endif; ?>
</dl>
