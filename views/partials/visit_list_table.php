<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $visitRows */
/** @var array<string, string> $visitColumns */
/** @var string $sort */
/** @var string $dir */
/** @var array<string, scalar|null> $listFilters */
/** @var array<string, scalar|null> $actionExtra */

$actionExtra = $actionExtra ?? [];
$textEndColumns = ['total'];
?>
<div class="table-responsive">
    <table class="table table-hover reception-table reception-table-sortable mb-0 visit-records-table">
        <thead>
        <tr>
            <?php foreach ($visitColumns as $colKey => $colLabel): ?>
                <?php
                $isSortable = $colKey !== 'medicines';
                $thClasses = [];
                if (in_array($colKey, $textEndColumns, true)) {
                    $thClasses[] = 'text-end';
                }
                $thClassAttr = $thClasses !== [] ? ' class="' . implode(' ', $thClasses) . '"' : '';
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
            ?>
            <tr>
                <?php foreach ($visitColumns as $colKey => $colLabel): ?>
                    <?php if ($colKey === 'patient_id'): ?>
                        <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                    <?php elseif ($colKey === 'patient'): ?>
                        <td><?= e((string) $row['patient_name']) ?></td>
                    <?php elseif ($colKey === 'age'): ?>
                        <td><?= e((string) ($row['age'] ?? '')) ?></td>
                    <?php elseif ($colKey === 'gender'): ?>
                        <td><?= e(Patient::genderLabel((string) ($row['gender'] ?? ''))) ?></td>
                    <?php elseif ($colKey === 'phone'): ?>
                        <td class="text-nowrap"><?= e(phone_format_display((string) ($row['phone'] ?? ''))) ?></td>
                    <?php elseif ($colKey === 'medicines'): ?>
                        <td class="small visit-history-medicines"><?= table_cell(Visit::formatMedicineSummary($lines)) ?></td>
                    <?php elseif ($colKey === 'total'): ?>
                        <td class="text-end fw-semibold text-nowrap">
                            <?= $grandTotal > 0 ? e(Medicine::formatPriceDisplay($grandTotal)) : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'payment_method'): ?>
                        <td class="small text-nowrap">
                            <?= !empty($row['payment_method'])
                                ? e(PaymentSettings::methodLabel((string) $row['payment_method']))
                                : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'payment_status'): ?>
                        <td class="small text-nowrap">
                            <?= !empty($row['payment_status'])
                                ? e(PaymentSettings::statusLabel((string) $row['payment_status']))
                                : table_na() ?>
                        </td>
                    <?php elseif ($colKey === 'date'): ?>
                        <td class="text-nowrap small"><?= e(Visit::formatVisitedTime((string) $row['visited_at'])) ?></td>
                    <?php elseif ($colKey === 'recorded_by'): ?>
                        <td class="small text-nowrap"><?= table_cell($row['recorded_by_name'] ?? '') ?></td>
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
