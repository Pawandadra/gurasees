<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $visitRows */
/** @var array<string, string> $visitColumns */
/** @var string $sort */
/** @var string $dir */
/** @var array<string, scalar|null> $listFilters */
/** @var array<string, scalar|null> $actionExtra */

$actionExtra = $actionExtra ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover reception-table reception-table-sortable mb-0 visit-records-table">
        <thead>
        <tr>
            <?php foreach ($visitColumns as $colKey => $colLabel): ?>
                <?php $isSortable = $colKey !== 'medicines'; ?>
                <th scope="col"<?= $isSortable ? visit_sort_th_attr($colKey, $sort, $dir) : '' ?><?= in_array($colKey, ['visit_charge', 'medicine_total', 'total'], true) ? ' class="text-end"' : '' ?>>
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
        <?php foreach ($visitRows as $row): ?>
            <?php
            $lines = $row['medicine_lines'] ?? [];
            $visitCharge = (float) ($row['visit_charge'] ?? 0) + (float) ($row['visit_gst'] ?? 0);
            $medicineTotal = (float) ($row['medicine_total'] ?? 0) + (float) ($row['medicine_gst'] ?? 0);
            $grandTotal = (float) ($row['grand_total'] ?? 0);
            ?>
            <tr>
                <td class="text-nowrap"><?= e(Visit::formatVisitedAt((string) $row['visited_at'])) ?></td>
                <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                <td><?= e((string) $row['patient_name']) ?></td>
                <td class="small visit-history-medicines"><?= e(Visit::formatMedicineSummary($lines)) ?></td>
                <td class="text-end text-nowrap">
                    <?= $visitCharge > 0 ? e(Medicine::formatPriceDisplay($visitCharge)) : '—' ?>
                </td>
                <td class="text-end text-nowrap">
                    <?= $medicineTotal > 0 ? e(Medicine::formatPriceDisplay($medicineTotal)) : '—' ?>
                </td>
                <td class="text-end fw-semibold text-nowrap">
                    <?= $grandTotal > 0 ? e(Medicine::formatPriceDisplay($grandTotal)) : '—' ?>
                </td>
                <td class="small text-nowrap"><?= e((string) ($row['recorded_by_name'] ?: '—')) ?></td>
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
