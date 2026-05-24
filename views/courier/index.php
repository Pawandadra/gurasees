<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $courierRows */
/** @var bool $dbError */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, status: string} $listFilters */
/** @var array<string, scalar|null> $sortFilterQuery */
/** @var bool $hasFilters */
/** @var int $totalPackages */

$sort = $sort ?? 'date';
$dir = $dir ?? 'desc';
$listFilters = $listFilters ?? ['q' => '', 'status' => ''];
$sortFilterQuery = $sortFilterQuery ?? [];
$hasFilters = $hasFilters ?? false;
$totalPackages = $totalPackages ?? 0;
$listPath = '/courier.php';
$courierColumns = [
    'patient_id' => __('patient.field.id'),
    'patient' => __('patient.field.name'),
    'phone' => __('patient.field.phone'),
    'status' => __('courier.field.status'),
];

ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h1 class="reception-page-title mb-0"><?= e(__('courier.list.title')) ?></h1>
    <a href="<?= e(courier_settings_url(array_merge(['sort' => $sort, 'dir' => $dir], $sortFilterQuery))) ?>"
       class="btn btn-outline-secondary btn-sm text-nowrap">
        <?= e(__('courier.settings.configure')) ?>
    </a>
</div>

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
        <h2 class="reception-card-title h6 mb-3"><?= e(__('courier.list.filters')) ?></h2>
        <form method="get" action="<?= e(base_url($listPath)) ?>" class="patient-list-filters courier-list-filters">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <div class="patient-list-filters-row courier-list-filters-row">
                <div class="patient-list-filter-search">
                    <label for="courier_filter_q" class="form-label"><?= e(__('courier.list.search')) ?></label>
                    <input type="search" class="form-control" id="courier_filter_q" name="q"
                           value="<?= e($listFilters['q']) ?>"
                           placeholder="<?= e(__('courier.list.search_placeholder')) ?>"
                           autocomplete="off">
                </div>
                <div class="patient-list-filter-gender">
                    <label for="courier_filter_status" class="form-label"><?= e(__('courier.field.status')) ?></label>
                    <select class="form-select" id="courier_filter_status" name="status">
                        <option value=""><?= e(__('courier.list.status_all')) ?></option>
                        <option value="pending"<?= $listFilters['status'] === Courier::STATUS_PENDING ? ' selected' : '' ?>>
                            <?= e(__('courier.status.pending')) ?>
                        </option>
                        <option value="sent"<?= $listFilters['status'] === Courier::STATUS_SENT ? ' selected' : '' ?>>
                            <?= e(__('courier.status.sent')) ?>
                        </option>
                        <option value="canceled"<?= $listFilters['status'] === Courier::STATUS_CANCELED ? ' selected' : '' ?>>
                            <?= e(__('courier.status.canceled')) ?>
                        </option>
                    </select>
                </div>
                <div class="patient-list-filter-actions">
                    <button type="submit" class="btn btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= e(courier_list_url($sort, $dir)) ?>"
                           class="btn btn-outline-secondary"><?= e(__('patients.list.clear')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('courier.list.results', ['count' => $totalPackages])) ?></h2>
        </div>

        <?php if ($courierRows === []): ?>
            <p class="text-muted mb-0">
                <?= e($hasFilters ? __('courier.list.empty') : __('courier.list.empty_all')) ?>
            </p>
        <?php else: ?>
            <?php require BASE_PATH . '/views/partials/courier_list_table.php'; ?>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
