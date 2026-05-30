<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $courierRows */
/** @var array<string, string> $courierColumns */
/** @var string $sort */
/** @var string $dir */
/** @var array<string, scalar|null> $sortFilterQuery */

$sortFilterQuery = $sortFilterQuery ?? [];
?>
<div class="table-responsive">
    <table class="table table-hover reception-table reception-table-sortable mb-0 courier-pending-table visit-records-table">
        <thead>
        <tr>
            <?php foreach ($courierColumns as $colKey => $colLabel): ?>
                <th scope="col"<?= courier_sort_th_attr($colKey, $sort, $dir) ?>>
                    <a href="<?= e(courier_sort_url($colKey, $sort, $dir, $sortFilterQuery)) ?>"
                       class="reception-sort-link<?= $sort === $colKey ? ' active' : '' ?>">
                        <?= e($colLabel) ?>
                        <?php if ($sort === $colKey): ?>
                            <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
            <?php endforeach; ?>
            <th scope="col"><?= e(__('patient.field.delivery_address')) ?></th>
            <th scope="col"><?= e(__('courier.field.medicines')) ?></th>
            <th scope="col"<?= courier_sort_th_attr('date', $sort, $dir) ?>>
                <a href="<?= e(courier_sort_url('date', $sort, $dir, $sortFilterQuery)) ?>"
                   class="reception-sort-link<?= $sort === 'date' ? ' active' : '' ?>">
                    <?= e(__('visit.field.time')) ?>
                    <?php if ($sort === 'date'): ?>
                        <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                    <?php endif; ?>
                </a>
            </th>
            <th scope="col" class="col-actions"><?= e(__('patient.field.actions')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $lastDateKey = null;
        $dateHeaderColspan = count($courierColumns) + 4;
        foreach ($courierRows as $row):
            $dateKey = Visit::visitedDateKey((string) $row['visited_at']);
            if ($dateKey !== $lastDateKey) {
                $lastDateKey = $dateKey;
                $colspan = $dateHeaderColspan;
                require BASE_PATH . '/views/partials/visit_date_header_row.php';
            }
            $lines = $row['courier_lines'] ?? [];
            $courierStatus = (string) ($row['courier_status'] ?? Courier::STATUS_PENDING);
            $visitId = (int) $row['visit_id'];
            $viewUrl = base_url('/courier_view.php?' . http_build_query(array_merge(
                ['visit_id' => $visitId],
                patient_build_list_query($sort, $dir, $sortFilterQuery)
            )));
            ?>
            <tr class="reception-table-row-link" data-href="<?= e($viewUrl) ?>" tabindex="0" role="link"
                aria-label="<?= e(__('courier.action.view')) ?>">
                <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                <td><?= e((string) $row['patient_name']) ?></td>
                <td class="text-nowrap"><?= e(phone_format_display((string) $row['phone'])) ?></td>
                <td class="text-nowrap small"><?= e((string) ($row['delivery_method_label'] ?? '')) ?></td>
                <td>
                    <span class="courier-status-badge courier-status-<?= e($courierStatus) ?>">
                        <?= e(Courier::statusLabel($courierStatus)) ?>
                    </span>
                </td>
                <td class="courier-delivery-address"><?= table_cell((string) $row['delivery_display']) ?></td>
                <td class="small visit-history-medicines"><?= table_cell(Courier::formatMedicineSummary($lines)) ?></td>
                <td class="text-nowrap small"><?= e(Visit::formatVisitedTime((string) $row['visited_at'])) ?></td>
                <td class="text-end col-actions">
                    <?php require BASE_PATH . '/views/partials/courier_actions.php'; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
            