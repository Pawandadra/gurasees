<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $patientRows */
/** @var array<string, string> $patientColumns */
/** @var string $sort */
/** @var string $dir */
/** @var string $listPath */
/** @var array<string, scalar|null> $listFilters */
/** @var string $return */
/** @var string $emptyMessage */
/** @var array<string, scalar|null> $actionExtra */
/** @var bool $tableSortable */
$actionExtra = $actionExtra ?? [];
$tableSortable = $tableSortable ?? true;
?>
<div class="table-responsive">
    <table class="table table-hover reception-table mb-0<?= $tableSortable ? ' reception-table-sortable' : '' ?>">
        <thead>
        <tr>
            <?php foreach ($patientColumns as $colKey => $colLabel): ?>
                <?php
                $thExtra = $colKey === 'address' ? ['col-address'] : [];
                $thClassAttr = responsive_col_attr('patients', $colKey, $thExtra);
                if ($tableSortable):
                ?>
                <th scope="col"<?= patient_sort_th_attr($colKey, $sort, $dir) ?><?= $thClassAttr ?>>
                    <a href="<?= e(patient_sort_url($colKey, $sort, $dir, $listPath, $listFilters)) ?>"
                       class="reception-sort-link<?= $sort === $colKey ? ' active' : '' ?>"
                       title="<?= e(__('reception.sort.sort_by', ['column' => $colLabel])) ?>">
                        <?= e($colLabel) ?>
                        <?php if ($sort === $colKey): ?>
                            <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                        <?php endif; ?>
                    </a>
                </th>
                <?php else: ?>
                <th scope="col"<?= $thClassAttr ?>><?= e($colLabel) ?></th>
                <?php endif; ?>
            <?php endforeach; ?>
            <th scope="col" class="col-actions"><?= e(__('patient.field.actions')) ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($patientRows as $row): ?>
            <tr>
                <td<?= responsive_col_attr('patients', 'id') ?>><span class="patient-code"><?= e($row['patient_code']) ?></span></td>
                <td<?= responsive_col_attr('patients', 'name') ?>><?= e($row['name']) ?></td>
                <td<?= responsive_col_attr('patients', 'age') ?>><?= e((string) $row['age']) ?></td>
                <td<?= responsive_col_attr('patients', 'gender') ?>><?= e(Patient::genderLabel((string) $row['gender'])) ?></td>
                <td<?= responsive_col_attr('patients', 'phone') ?>><?= e(phone_format_display((string) $row['phone'])) ?></td>
                <td<?= responsive_col_attr('patients', 'address', ['col-address']) ?> title="<?= e((string) $row['address']) ?>"><?= table_cell($row['address'] ?? '') ?></td>
                <td<?= responsive_col_attr('patients', 'date') ?>><?= e(Patient::formatListLastVisited($row)) ?></td>
                <td class="col-actions">
                    <?php
                    $patientCode = (string) $row['patient_code'];
                    require BASE_PATH . '/views/partials/patient_actions.php';
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
