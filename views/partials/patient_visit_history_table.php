<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $visits */
/** @var string $code */

$visits = $visits ?? [];
$code = $code ?? '';
$showPaidColumn = Visit::listHasPartialPayment($visits);
?>
<div class="table-responsive visit-history-table-wrap patient-visit-history-wrap">
    <table class="table reception-table visit-history-table patient-visit-history-table mb-0">
        <colgroup class="<?= $showPaidColumn ? 'visit-cols-with-paid' : 'visit-cols-no-paid' ?>">
            <col class="visit-col-medicines">
            <col class="visit-col-total">
            <col class="visit-col-method">
            <col class="visit-col-status">
            <?php if ($showPaidColumn): ?>
                <col class="visit-col-paid">
            <?php endif; ?>
            <col class="visit-col-balance">
            <col class="visit-col-notes">
            <col class="visit-col-datetime">
            <col class="visit-col-actions">
        </colgroup>
        <thead>
        <tr>
            <th scope="col"><?= e(__('visit.field.medicines')) ?></th>
            <th scope="col" class="text-end"><?= e(__('visit.field.grand_total')) ?></th>
            <th scope="col"<?= responsive_col_attr('patient_visits', 'method') ?>><?= e(__('payment.field.method')) ?></th>
            <th scope="col"><?= e(__('payment.field.status')) ?></th>
            <?php if ($showPaidColumn): ?>
                <th scope="col"<?= responsive_col_attr('patient_visits', 'paid', ['text-end']) ?>><?= e(__('payment.field.paid_amount')) ?></th>
            <?php endif; ?>
            <th scope="col"<?= responsive_col_attr('patient_visits', 'balance', ['text-end']) ?>><?= e(__('payment.field.balance')) ?></th>
            <th scope="col"<?= responsive_col_attr('patient_visits', 'notes') ?>><?= e(__('visit.field.notes')) ?></th>
            <th scope="col"><?= e(__('visit.field.datetime')) ?></th>
            <th scope="col" class="col-actions"><span class="visually-hidden"><?= e(__('patient.field.actions')) ?></span></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($visits as $visit):
            $visitId = (int) ($visit['id'] ?? 0);
            $lines = $visit['medicine_lines'] ?? [];
            $grandTotal = (float) ($visit['grand_total'] ?? 0);
            $paymentStatus = (string) ($visit['payment_status'] ?? '');
            $notes = trim((string) ($visit['notes'] ?? ''));
            $balance = Visit::paymentBalance($visit);
            $rowClass = 'patient-visit-history-row' . ($visitId > 0 ? ' visit-detail-row' : '');
            $rowData = $visitId > 0
                ? ' data-visit-id="' . e((string) $visitId) . '" data-patient-code="' . e($code) . '" tabindex="0" role="button" aria-label="' . e(__('visit.detail.view')) . '"'
                : '';
            ?>
            <tr class="<?= e($rowClass) ?>"<?= $rowData ?>>
                <td class="small visit-history-medicines">
                    <?= table_cell(Visit::formatMedicineSummary($lines)) ?>
                </td>
                <td class="text-end visit-history-total text-nowrap">
                    <?= $grandTotal > 0
                        ? e(Medicine::formatPriceDisplay($grandTotal))
                        : table_na() ?>
                </td>
                <td<?= responsive_col_attr('patient_visits', 'method', ['visit-history-method', 'text-nowrap']) ?>>
                    <?= !empty($visit['payment_method'])
                        ? e(PaymentSettings::methodLabel((string) $visit['payment_method']))
                        : table_na() ?>
                </td>
                <td class="visit-history-status">
                    <?php if ($paymentStatus !== ''): ?>
                        <?= PaymentSettings::statusBadgeHtml($paymentStatus) ?>
                    <?php else: ?>
                        <?= table_na() ?>
                    <?php endif; ?>
                </td>
                <?php if ($showPaidColumn): ?>
                    <td<?= responsive_col_attr('patient_visits', 'paid', ['text-end', 'visit-history-paid', 'text-nowrap']) ?>>
                        <?php if (in_array($paymentStatus, ['paid', 'partial'], true)): ?>
                            <?= e(PaymentSettings::formatAmountDisplay(Visit::paymentPaidAmount($visit))) ?>
                        <?php else: ?>
                            <?= table_na() ?>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
                <td<?= responsive_col_attr('patient_visits', 'balance', ['text-end', 'visit-history-balance', 'text-nowrap']) ?>>
                    <?= $balance > 0
                        ? e(PaymentSettings::formatAmountDisplay($balance))
                        : table_na() ?>
                </td>
                <td<?= responsive_col_attr('patient_visits', 'notes', ['small', 'visit-history-notes']) ?>>
                    <?= table_cell($notes) ?>
                </td>
                <td class="visit-history-datetime">
                    <div class="visit-datetime-cell">
                        <span class="visit-datetime-date"><?= e(Visit::formatVisitedDate((string) $visit['visited_at'])) ?></span>
                        <span class="visit-datetime-time"><?= e(Visit::formatVisitedTime((string) $visit['visited_at'])) ?></span>
                    </div>
                </td>
                <td class="col-actions">
                    <?php if ($visitId > 0): ?>
                        <button type="button" class="patient-action-btn patient-action-view visit-detail-trigger"
                                data-visit-id="<?= e((string) $visitId) ?>"
                                data-patient-code="<?= e($code) ?>"
                                title="<?= e(__('visit.detail.view')) ?>"
                                aria-label="<?= e(__('visit.detail.view')) ?>">
                            <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
