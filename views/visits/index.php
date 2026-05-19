<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $visitRows */
/** @var int $totalVisits */
/** @var int $totalPages */
/** @var int $page */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, visit_date: string, medicine_id: string, page: int} $listFilters */
/** @var list<array{id: int, name: string}> $filterMedicines */
/** @var bool $dbError */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$filterState = $listFilters;
$filterMedicines = $filterMedicines ?? [];
$hasFilters = visit_list_has_active_filters($filterState);
$sortFilterQuery = visit_list_query_filters($filterState);
$tableListFilters = patient_build_list_query($sort, $dir, array_merge($sortFilterQuery, ['page' => $page > 1 ? $page : null]));
$visitColumns = [
    'date' => __('visit.field.datetime'),
    'patient_id' => __('patient.field.id'),
    'patient' => __('patient.field.name'),
    'medicines' => __('visit.field.medicines'),
    'visit_charge' => __('visits.list.col.visit_charges'),
    'medicine_total' => __('visits.list.col.medicines'),
    'total' => __('visit.field.grand_total'),
    'recorded_by' => __('visit.field.recorded_by'),
];

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('visits.list.title')) ?></h1>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success reception-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('visits.list.filters')) ?></h2>
        <form method="get" action="<?= e(base_url('/visits.php')) ?>" class="patient-list-filters visit-list-filters">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <div class="patient-list-filters-row visit-list-filters-row">
                <div class="patient-list-filter-search">
                    <label for="visit_filter_q" class="form-label"><?= e(__('visits.list.search')) ?></label>
                    <input type="search" class="form-control" id="visit_filter_q" name="q"
                           value="<?= e($filterState['q']) ?>"
                           placeholder="<?= e(__('visits.list.search_placeholder')) ?>"
                           autocomplete="off">
                </div>
                <div class="patient-list-filter-medicine">
                    <label for="visit_filter_medicine" class="form-label"><?= e(__('visits.list.medicine')) ?></label>
                    <select class="form-select" id="visit_filter_medicine" name="medicine_id">
                        <option value=""><?= e(__('visits.list.medicine_all')) ?></option>
                        <?php foreach ($filterMedicines as $medicine): ?>
                            <option value="<?= (int) $medicine['id'] ?>"<?= $filterState['medicine_id'] === (string) $medicine['id'] ? ' selected' : '' ?>>
                                <?= e($medicine['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="patient-list-filter-date">
                    <label for="visit_filter_date" class="form-label"><?= e(__('visits.list.visit_date')) ?></label>
                    <input type="date" class="form-control" id="visit_filter_date" name="visit_date"
                           value="<?= e($filterState['visit_date']) ?>">
                </div>
                <div class="patient-list-filter-actions">
                    <button type="submit" class="btn btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= e(visit_list_url($sort, $dir)) ?>"
                           class="btn btn-outline-secondary"><?= e(__('patients.list.clear')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('visits.list.results', ['count' => $totalVisits])) ?></h2>
            <?php if ($totalPages > 1): ?>
                <p class="text-muted small mb-0">
                    <?= e(__('patients.list.page', ['page' => $page, 'pages' => $totalPages])) ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($visitRows === []): ?>
            <p class="text-muted mb-0">
                <?= e($hasFilters ? __('visits.list.empty') : __('visits.list.empty_all')) ?>
            </p>
        <?php else: ?>
            <?php
            $listFilters = $sortFilterQuery;
            $actionExtra = $tableListFilters;
            require BASE_PATH . '/views/partials/visit_list_table.php';
            ?>

            <?php if ($totalPages > 1): ?>
                <nav class="patient-list-pagination mt-3" aria-label="<?= e(__('visits.list.pagination')) ?>">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(visit_list_url($sort, $dir, array_merge($tableListFilters, ['page' => $page - 1]))) ?>">
                                    <?= e(__('patients.list.prev')) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                <a class="page-link" href="<?= e(visit_list_url($sort, $dir, array_merge($tableListFilters, ['page' => $p]))) ?>">
                                    <?= e((string) $p) ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(visit_list_url($sort, $dir, array_merge($tableListFilters, ['page' => $page + 1]))) ?>">
                                    <?= e(__('patients.list.next')) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$content = ob_get_clean();
$activeNav = 'visits';

view('layouts/dashboard', compact('pageTitle', 'content', 'activeNav'));
