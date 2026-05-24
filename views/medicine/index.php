<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $editErrors */
/** @var int|null $editId */
/** @var list<array<string, mixed>> $medicines */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, kind: string} $listFilters */
/** @var array<string, scalar|null> $sortFilterQuery */
/** @var bool $hasFilters */
/** @var int $totalMedicines */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $dbError */

$pageTitle = __('medicine.manage.title');
$errors = $errors ?? [];
$editErrors = $editErrors ?? [];
$editId = $editId ?? null;
$sort = $sort ?? 'name';
$dir = $dir ?? 'asc';
$listFilters = $listFilters ?? ['q' => '', 'kind' => ''];
$sortFilterQuery = $sortFilterQuery ?? [];
$hasFilters = $hasFilters ?? false;
$totalMedicines = $totalMedicines ?? 0;
$listPath = '/medicines.php';
$medicineColumns = [
    'name' => __('medicine.field.name'),
];

ob_start();
?>
<div class="medicine-manage-page">
<h1 class="reception-page-title medicine-manage-title"><?= e(__('medicine.manage.title')) ?></h1>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form medicine-manage-card">
        <h2 class="reception-card-title h6 medicine-manage-card-title"><?= e(__('medicine.add.title')) ?></h2>

        <form method="post" action="<?= e(base_url('/medicines.php')) ?>" class="medicine-add-form" id="medicineAddForm"
              data-confirm-message="<?= e(__('medicine.add.confirm_message')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <?php require BASE_PATH . '/views/partials/medicine_list_preserve.php'; ?>

            <div class="medicine-add-toolbar">
                <div class="medicine-add-toolbar-field">
                    <label for="medicine_name" class="form-label visually-hidden"><?= e(__('medicine.field.name')) ?></label>
                    <input type="text" class="form-control form-control-sm<?= field_invalid($errors, 'name') ?>"
                           id="medicine_name" name="name" maxlength="120" required
                           placeholder="<?= e(__('medicine.field.name')) ?>"
                           value="<?= e((string) ($_POST['name'] ?? '')) ?>">
                    <?php show_field_error($errors, 'name'); ?>
                </div>
                <div class="medicine-add-toolbar-actions">
                    <button type="button" class="btn btn-sm btn-reception-primary text-nowrap confirm-action-trigger"
                            data-confirm-title="<?= e(__('medicine.add.confirm_title')) ?>"
                            data-confirm="<?= e(__('medicine.add.confirm_message')) ?>"
                            data-confirm-label="<?= e(__('medicine.add.submit')) ?>"
                            data-confirm-variant="primary">
                        <?= e(__('medicine.add.submit')) ?>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card reception-form medicine-manage-card">
        <h2 class="reception-card-title h6 medicine-manage-card-title"><?= e(__('medicine.list.filters')) ?></h2>
        <form method="get" action="<?= e(base_url($listPath)) ?>" class="patient-list-filters medicine-list-filters">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <div class="patient-list-filters-row medicine-list-filters-row">
                <div class="patient-list-filter-search">
                    <label for="medicine_filter_q" class="form-label"><?= e(__('medicine.list.search')) ?></label>
                    <input type="search" class="form-control" id="medicine_filter_q" name="q"
                           value="<?= e($listFilters['q']) ?>"
                           placeholder="<?= e(__('medicine.list.search_placeholder')) ?>"
                           autocomplete="off">
                </div>
                <div class="patient-list-filter-actions">
                    <button type="submit" class="btn btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= e(medicine_list_url($sort, $dir)) ?>"
                           class="btn btn-outline-secondary"><?= e(__('patients.list.clear')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card medicine-manage-card medicine-manage-list-card">
        <h2 class="reception-card-title h6 medicine-manage-card-title mb-0">
            <?= e(__('medicine.list.results', ['count' => $totalMedicines])) ?>
        </h2>

        <?php if ($medicines === []): ?>
            <p class="text-muted mb-0">
                <?= e($hasFilters ? __('medicine.list.empty') : __('medicine.list.empty_all')) ?>
            </p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover reception-table mb-0 medicine-inventory-table">
                    <thead>
                    <tr>
                        <?php foreach ($medicineColumns as $colKey => $colLabel): ?>
                            <th scope="col"<?= medicine_sort_th_attr($colKey, $sort, $dir) ?>>
                                <a href="<?= e(medicine_sort_url($colKey, $sort, $dir, $sortFilterQuery)) ?>"
                                   class="reception-sort-link">
                                    <?= e($colLabel) ?>
                                    <?php if ($sort === $colKey): ?>
                                        <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="text-end col-actions"><?= e(__('patient.field.actions')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <?php
                        $medicineId = (int) $medicine['id'];
                        $isEditing = $editId === $medicineId;
                        $rowEditErrors = $isEditing ? $editErrors : [];
                        ?>
                        <tr class="medicine-inventory-row<?= $isEditing ? ' is-editing' : '' ?>"
                            data-medicine-id="<?= e((string) $medicineId) ?>">
                            <td class="medicine-name-cell">
                                <div class="medicine-name-view<?= $isEditing ? ' d-none' : '' ?>">
                                    <span class="medicine-name-display"><?= e($medicine['name']) ?></span>
                                </div>
                                <form method="post" action="<?= e(base_url('/medicines.php')) ?>"
                                      class="medicine-inline-edit-form<?= $isEditing ? '' : ' d-none' ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= e((string) $medicineId) ?>">
                                    <?php require BASE_PATH . '/views/partials/medicine_list_preserve.php'; ?>
                                    <div class="medicine-inline-edit-row">
                                        <input type="text"
                                               class="form-control form-control-sm medicine-inline-edit-input<?= field_invalid($rowEditErrors, 'name') ?>"
                                               name="name" maxlength="120" required
                                               value="<?= e($isEditing ? (string) ($_POST['name'] ?? $medicine['name']) : (string) $medicine['name']) ?>">
                                        <button type="submit" class="btn btn-sm btn-reception-primary text-nowrap">
                                            <?= e(__('medicine.edit.submit')) ?>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary medicine-inline-cancel text-nowrap">
                                            <?= e(__('action.cancel')) ?>
                                        </button>
                                    </div>
                                    <?php show_field_error($rowEditErrors, 'name'); ?>
                                    <?php show_field_error($rowEditErrors, '_form'); ?>
                                </form>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="medicine-actions">
                                    <button type="button"
                                            class="patient-action-btn medicine-edit-trigger<?= $isEditing ? ' d-none' : '' ?>"
                                            title="<?= e(__('medicine.action.edit')) ?>"
                                            aria-label="<?= e(__('medicine.action.edit') . ': ' . $medicine['name']) ?>">
                                        <?php require BASE_PATH . '/views/partials/icons/edit.php'; ?>
                                    </button>
                                    <form method="post" action="<?= e(base_url('/medicines.php')) ?>" class="patient-action-delete-form">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id" value="<?= e((string) $medicineId) ?>">
                                        <?php require BASE_PATH . '/views/partials/medicine_list_preserve.php'; ?>
                                        <button type="button"
                                                class="patient-action-btn patient-action-delete confirm-action-trigger"
                                                data-confirm-title="<?= e(__('medicine.delete.confirm_title')) ?>"
                                                data-confirm="<?= e(__('medicine.delete.confirm', ['name' => $medicine['name']])) ?>"
                                                data-confirm-label="<?= e(__('medicine.action.remove')) ?>"
                                                title="<?= e(__('medicine.action.remove')) ?>"
                                                aria-label="<?= e(__('medicine.action.remove')) ?>">
                                            <?php require BASE_PATH . '/views/partials/icons/delete.php'; ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>
</div>
<?php
$pageScripts = ['assets/js/medicine-add.js', 'assets/js/medicine-edit-inline.js'];
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
