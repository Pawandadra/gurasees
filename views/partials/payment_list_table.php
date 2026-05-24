<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $paymentRows */
/** @var array<string, string> $paymentColumns */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, status: string, type: string} $listFilters */
/** @var string $summaryPeriod */

$textEndColumns = ['total', 'without_gst', 'gst', 'paid', 'balance'];
?>
<div class="table-responsive">
    <table class="table table-hover reception-table reception-table-sortable mb-0 payment-records-table">
        <thead>
        <tr>
            <?php foreach ($paymentColumns as $colKey => $colLabel): ?>
                <?php
                $thExtra = in_array($colKey, $textEndColumns, true) ? ['text-end'] : [];
                $thClassAttr = responsive_col_attr('payments', $colKey, $thExtra);
                ?>
                <th scope="col"<?= payment_sort_th_attr($colKey, $sort, $dir) ?><?= $thClassAttr ?>>
                    <a href="<?= e(payment_sort_url($colKey, $sort, $dir, $listFilters, $summaryPeriod)) ?>"
                       class="reception-sort-link<?= $sort === $colKey ? ' active' : '' ?>">
                        <?= e($colLabel) ?>
                        <?php if ($sort === $colKey): ?>
                            <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
            <?php endforeach; ?>
            <th scope="col" class="col-actions"><?= e(__('patient.field.actions')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $lastDateKey = null;
        $dateHeaderColspan = count($paymentColumns) + 1;
        foreach ($paymentRows as $row):
            $dateKey = Payment::paymentDateKey((string) $row['payment_date']);
            if ($dateKey !== $lastDateKey) {
                $lastDateKey = $dateKey;
                $colspan = $dateHeaderColspan;
                require BASE_PATH . '/views/partials/payment_date_header_row.php';
            }
            $status = (string) $row['payment_status'];
            $code = (string) $row['patient_code'];
            ?>
            <tr>
                <td<?= responsive_col_attr('payments', 'patient_id') ?>><span class="patient-code"><?= e($code) ?></span></td>
                <td<?= responsive_col_attr('payments', 'patient') ?>><?= e((string) $row['patient_name']) ?></td>
                <td<?= responsive_col_attr('payments', 'phone', ['text-nowrap']) ?>><?= e(phone_format_display((string) $row['phone'])) ?></td>
                <td<?= responsive_col_attr('payments', 'type', ['small']) ?>><?= e(Payment::typeLabel((string) $row['payment_type'])) ?></td>
                <td<?= responsive_col_attr('payments', 'total', ['text-end', 'fw-semibold', 'text-nowrap']) ?>><?= e(PaymentSettings::formatAmountDisplay((float) $row['total_amount'])) ?></td>
                <td<?= responsive_col_attr('payments', 'without_gst', ['text-end', 'text-nowrap']) ?>><?= e(PaymentSettings::formatAmountDisplay((float) $row['amount_without_gst'])) ?></td>
                <td<?= responsive_col_attr('payments', 'gst', ['text-end', 'text-nowrap']) ?>><?= e(PaymentSettings::formatAmountDisplay((float) $row['gst_amount'])) ?></td>
                <td<?= responsive_col_attr('payments', 'paid', ['text-end', 'text-nowrap']) ?>><?= e(PaymentSettings::formatAmountDisplay((float) $row['paid_amount'])) ?></td>
                <td<?= responsive_col_attr('payments', 'balance', ['text-end', 'text-nowrap']) ?>>
                    <?= (float) $row['balance_amount'] > 0
                        ? e(PaymentSettings::formatAmountDisplay((float) $row['balance_amount']))
                        : table_na() ?>
                </td>
                <td<?= responsive_col_attr('payments', 'method', ['small', 'text-nowrap']) ?>>
                    <?= ($row['payment_method'] ?? '') !== ''
                        ? e(PaymentSettings::methodLabel((string) $row['payment_method']))
                        : table_na() ?>
                </td>
                <td<?= responsive_col_attr('payments', 'status') ?>>
                    <span class="payment-status-badge payment-status-<?= e($status) ?>">
                        <?= e(PaymentSettings::statusLabel($status)) ?>
                    </span>
                </td>
                <td class="col-actions">
                    <?php if ($code !== ''): ?>
                        <a href="<?= e(Payment::patientUrl($code)) ?>"
                           class="patient-action-btn"
                           title="<?= e(__('patient.action.view')) ?>"
                           aria-label="<?= e(__('patient.action.view')) ?>">
                            <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
                        </a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
