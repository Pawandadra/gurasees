<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $patientRows */
/** @var int $totalPatients */
/** @var int $totalPages */
/** @var int $page */
/** @var int $perPage */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, gender: string, age_min: string, age_max: string, date_from: string, date_to: string, page: int} $listFilters */
/** @var bool $dbError */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$listPath = '/patients.php';
$return = 'patients';
$filterState = $listFilters;
$hasFilters = patient_list_has_active_filters($filterState);
$sortFilterQuery = patient_list_query_filters($filterState);
$tableListFilters = patient_build_list_query($sort, $dir, array_merge($sortFilterQuery, ['page' => $page > 1 ? $page : null]));
$patientColumns = [
    'id' => __('patient.field.id'),
    'name' => __('patient.field.name'),
    'age' => __('patient.field.age'),
    'gender' => __('patient.field.gender'),
    'phone' => __('patient.field.phone'),
    'address' => __('patient.field.address'),
    'date' => __('patient.field.last_visited'),
];

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('patients.list.title')) ?></h1>

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
        <h2 class="reception-card-title h6 mb-3"><?= e(__('patients.list.filters')) ?></h2>
        <form method="get" action="<?= e(base_url($listPath)) ?>" class="patient-list-filters">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <div class="patient-list-filters-row">
                <div class="patient-list-filter-search">
                    <label for="patient_filter_q" class="form-label"><?= e(__('patients.list.search')) ?></label>
                    <input type="search" class="form-control" id="patient_filter_q" name="q"
                           value="<?= e($filterState['q']) ?>"
                           placeholder="<?= e(__('patients.list.search_placeholder')) ?>"
                           autocomplete="off">
                </div>
                <div class="patient-list-filter-gender">
                    <label for="patient_filter_gender" class="form-label"><?= e(__('patient.field.gender')) ?></label>
                    <select class="form-select" id="patient_filter_gender" name="gender">
                        <option value=""><?= e(__('patients.list.gender_all')) ?></option>
                        <?php foreach (['male', 'female', 'other'] as $genderOption): ?>
                            <option value="<?= $genderOption ?>"<?= $filterState['gender'] === $genderOption ? ' selected' : '' ?>>
                                <?= e(__('patient.gender.' . $genderOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="patient-list-filter-age">
                    <span class="form-label d-block"><?= e(__('patients.list.age_range')) ?></span>
                    <div class="patient-list-range-inputs">
                        <input type="number" class="form-control" name="age_min" min="1" max="120" step="1"
                               value="<?= e($filterState['age_min']) ?>"
                               placeholder="<?= e(__('patients.list.age_min')) ?>"
                               aria-label="<?= e(__('patients.list.age_min')) ?>">
                        <span class="patient-list-range-sep" aria-hidden="true">–</span>
                        <input type="number" class="form-control" name="age_max" min="1" max="120" step="1"
                               value="<?= e($filterState['age_max']) ?>"
                               placeholder="<?= e(__('patients.list.age_max')) ?>"
                               aria-label="<?= e(__('patients.list.age_max')) ?>">
                    </div>
                </div>
                <div class="patient-list-filter-date">
                    <span class="form-label d-block"
                          title="<?= e(__('patients.list.date_range_hint')) ?>"><?= e(__('patients.list.date_range')) ?></span>
                    <div class="patient-list-range-inputs">
                        <input type="date" class="form-control" name="date_from"
                               value="<?= e($filterState['date_from']) ?>"
                               aria-label="<?= e(__('patients.list.date_from')) ?>">
                        <span class="patient-list-range-sep" aria-hidden="true">–</span>
                        <input type="date" class="form-control" name="date_to"
                               value="<?= e($filterState['date_to']) ?>"
                               aria-label="<?= e(__('patients.list.date_to')) ?>">
                    </div>
                </div>
                <div class="patient-list-filter-actions">
                    <button type="submit" class="btn btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= e(patient_list_url($listPath, $sort, $dir, ['return' => 'patients'])) ?>"
                           class="btn btn-outline-secondary"><?= e(__('patients.list.clear')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('patients.list.results', ['count' => $totalPatients])) ?></h2>
            <?php if ($totalPages > 1): ?>
                <p class="text-muted small mb-0">
                    <?= e(__('patients.list.page', ['page' => $page, 'pages' => $totalPages])) ?>
                </p>
            <?php endif; ?>
        </div>

        <?php if ($patientRows === []): ?>
            <p class="text-muted mb-0">
                <?= e($hasFilters ? __('patients.list.empty') : __('patients.list.empty_all')) ?>
            </p>
        <?php else: ?>
            <?php
            $emptyMessage = '';
            $listFilters = $sortFilterQuery;
            $actionExtra = $tableListFilters;
            require BASE_PATH . '/views/partials/patient_list_table.php';
            ?>

            <?php if ($totalPages > 1): ?>
                <nav class="patient-list-pagination mt-3" aria-label="<?= e(__('patients.list.pagination')) ?>">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(patient_list_url($listPath, $sort, $dir, array_merge($tableListFilters, ['page' => $page - 1]))) ?>">
                                    <?= e(__('patients.list.prev')) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                <a class="page-link" href="<?= e(patient_list_url($listPath, $sort, $dir, array_merge($tableListFilters, ['page' => $p]))) ?>">
                                    <?= e((string) $p) ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(patient_list_url($listPath, $sort, $dir, array_merge($tableListFilters, ['page' => $page + 1]))) ?>">
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
$activeNav = 'patients';

view('layouts/dashboard', compact('pageTitle', 'content', 'activeNav'));
