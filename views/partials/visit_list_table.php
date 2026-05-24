<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $visitRows */
/** @var array<string, string> $visitColumns */
/** @var string $sort */
/** @var string $dir */
/** @var array<string, scalar|null> $listFilters */
/** @var array<string, scalar|null> $actionExtra */

$actionExtra = $actionExtra ?? [];
$textEndColumns = ['total', 'paid_amount', 'balance'];
?>
<div class="table-responsive">
    <table class="table table-hover reception-table reception-table-sortable mb-0 visit-records-table">
        <thead>
        <tr>
            <?php foreach ($visitColumns as $colKey => $colLabel): ?>
                <?php
                $isSortable = !in_array($colKey, ['medicines', 'paid_amount', 'balance'], true);
                $thExtra = in_array($colKey, $textEndColumns, true) ? ['text-end'] : [];
                $thClassAttr = responsive_col_attr('visits', $colKey, $thExtra);
                ?>
                <th scope="col"<?= $isSortable ? visit_sort_th_attr($colKey, $sort, $dir) : '' ?><?= $thClassAttr ?>>
                    <?php if ($isSortable): ?>
                        <a href="<?= e(visit_sort_url($colKey, $sort, $dir, $listFilters)) ?>"
                           class="reception-sort-link<?= $sort === $colKey ? ' active' : '' ?>"
                           title="<?= e(__('reception.sort.sort_by', ['column' => $colLabel])) ?>">
                            <?= e($colLabel) ?>
                            <?php if ($sort === $colKey): ?>
                                <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                            <?php endif; ?>
                        </a>
                    <?php else: ?>
                        <?= e($colLabel) ?>
                    <?php endif; ?>
                </th>
            <?php endforeach; ?>
            <th scope="col" class="col-actions"><?= e(__('patient.field.actions')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $lastDateKey = null;
        $dateHeaderColspan = count($visitColumns) + 1;
        foreach ($visitRows as $row):
            $dateKey = Visit::visitedDateKey((string) $row['visited_at']);
            if ($dateKey !== $lastDateKey) {
                $lastDateKey = $dateKey;
                $colspan = $dateHeaderColspan;
                require BASE_PATH . '/views/partials/visit_date_header_row.php';
            }
            $lines = $row['medicine_lines'] ?? [];
            $grandTotal = (float) ($row['grand_total'] ?? 0);
            $paymentStatus = (string) ($row['payment_status'] ?? '');
            $balance = Visit::paymentBalance($row);
            ?>
            <tr>
                <?php foreach ($visitColumns as $colKey => $colLabel): ?>
                    <?php if ($colKey === 'patient_id'): ?>
                        <td<?= responsive_col_attr('visits', $colKey) ?>><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                    <?php elseif ($colKey === 'patient'): ?>
                        <td<?= responsive_col_attr('visits', $colKey) ?>><?= e((string) $row['patient_name']) ?></td>
                    <?php elseif ($colKey === 'age'): ?>
                        <td<?= responsive_col_attr('visits', $colKey) ?>><?= e((string) ($row['age'] ?? '')) ?></td>
                    <?php elseif ($colKey === 'gender'): ?>
                        <td<?= responsive_col_attr('visits', $colKey) ?>><?= e(Patient::genderLabel((string) ($row['gender'] ?? ''))) ?></td>
                    <?php elseif ($colKey === 'phone'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['text-nowrap']) ?>><?= e(phone_format_display((string) ($row['phone'] ?? ''))) ?></td>
                    <?php elseif ($colKey === 'medicines'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['small', 'visit-history-medicines']) ?>><?= table_cell(Visit::formatMedicineSummary($lines)) ?></td>
                    <?php elseif ($colKey === 'total'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['text-end', 'fw-semibold', 'text-nowrap']) ?>>
                            <?= $grandTotal > 0 ? e(Medicine::formatPriceDisplay($grandTotal)) : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'payment_method'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['small', 'text-nowrap']) ?>>
                            <?= !empty($row['payment_method'])
                                ? e(PaymentSettings::methodLabel((string) $row['payment_method']))
                                : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'payment_status'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['small', 'text-nowrap']) ?>>
                            <?= $paymentStatus !== ''
                                ? e(PaymentSettings::statusLabel($paymentStatus))
                                : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'paid_amount'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['text-end', 'text-nowrap', 'small']) ?>>
                            <?php
                            $paidAmount = Visit::paymentPaidAmount($row);
                            if (in_array($paymentStatus, ['paid', 'partial'], true)):
                                ?>
                                <?= e(PaymentSettings::formatAmountDisplay($paidAmount)) ?>
                            <?php else: ?>
                                <?= table_na() ?>
                            <?php endif; ?>
                        </td>
                    <?php elseif ($colKey === 'balance'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['text-end', 'text-nowrap', 'small']) ?>>
                            <?= $balance > 0
                                ? e(PaymentSettings::formatAmountDisplay($balance))
                                : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'recorded_by'): ?>
                        <td<?= responsive_col_attr('visits', $colKey, ['small', 'text-nowrap']) ?>><?= table_cell($row['recorded_by_name'] ?? '') ?></td>
                    <?php endif; ?>
                <?php endforeach; ?>
                <td class="col-actions">
                    <?php
                    $patientCode = (string) $row['patient_code'];
                    require BASE_PATH . '/views/partials/visit_list_actions.php';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
