<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $portionErrors */
/** @var list<array<string, mixed>> $medicines */
/** @var string $addType */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $dbError */

$pageTitle = __('medicine.manage.title');
$errors = $errors ?? [];
$portionErrors = $portionErrors ?? [];
$addType = $addType ?? Medicine::KIND_UNIT;
$portionBulkId = (int) ($_POST['bulk_id'] ?? 0);

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('medicine.manage.title')) ?></h1>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (isset($portionErrors['_form'])): ?>
        <div class="alert alert-danger"><?= e($portionErrors['_form']) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('medicine.add.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('medicine.add.hint')) ?></p>

        <form method="post" action="<?= e(base_url('/medicines.php')) ?>" class="medicine-add-form" id="medicineAddForm">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">

            <div class="row g-2 g-xl-3 align-items-end medicine-add-fields">
                <div class="col-auto medicine-add-type-col">
                    <label for="medicine_type" class="form-label"><?= e(__('medicine.field.type')) ?></label>
                    <select class="form-select medicine-add-type-select" id="medicine_type" name="medicine_type" required>
                        <option value="unit"<?= $addType === Medicine::KIND_UNIT ? ' selected' : '' ?>>
                            <?= e(__('medicine.kind.unit')) ?>
                        </option>
                        <option value="bulk"<?= $addType === Medicine::KIND_BULK ? ' selected' : '' ?>>
                            <?= e(__('medicine.kind.bulk')) ?>
                        </option>
                    </select>
                </div>
                <div class="col medicine-add-name-col">
                    <label for="medicine_name" class="form-label"><?= e(__('medicine.field.name')) ?></label>
                    <input type="text" class="form-control form-control-sm<?= field_invalid($errors, 'name') ?>"
                           id="medicine_name" name="name" maxlength="120" required
                           value="<?= e((string) ($_POST['name'] ?? '')) ?>">
                    <?php show_field_error($errors, 'name'); ?>
                </div>

                <div class="col-6 col-md-4 col-xl-auto medicine-add-unit-col" data-medicine-fields="unit">
                    <label for="medicine_unit_price" class="form-label"><?= e(__('medicine.field.price')) ?></label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control<?= field_invalid($errors, 'unit_price') ?>"
                               id="medicine_unit_price" name="unit_price" min="0.01" step="0.01"
                               value="<?= e((string) ($_POST['unit_price'] ?? '')) ?>">
                    </div>
                    <?php show_field_error($errors, 'unit_price'); ?>
                </div>
                <div class="col-6 col-md-4 col-xl-auto medicine-add-unit-col" data-medicine-fields="unit">
                    <label for="medicine_stock" class="form-label"><?= e(__('medicine.field.stock')) ?></label>
                    <input type="number" class="form-control form-control-sm<?= field_invalid($errors, 'stock_quantity') ?>"
                           id="medicine_stock" name="stock_quantity" min="0" step="1"
                           value="<?= e((string) ($_POST['stock_quantity'] ?? '')) ?>">
                    <?php show_field_error($errors, 'stock_quantity'); ?>
                </div>

                <div class="col-6 col-md-4 col-xl-auto medicine-add-bulk-col" data-medicine-fields="bulk" hidden>
                    <label for="container_ml" class="form-label"><?= e(__('medicine.bulk.field.container_ml')) ?></label>
                    <div class="input-group input-group-sm" data-volume-group>
                        <input type="number" class="form-control<?= field_invalid($errors, 'container_ml') ?>"
                               id="container_ml" name="container_ml" min="1" step="1"
                               data-volume-input
                               value="<?= e((string) ($_POST['container_ml'] ?? '')) ?>">
                        <input type="hidden" name="container_volume_unit"
                               value="<?= e((string) ($_POST['container_volume_unit'] ?? 'ml')) ?>" data-volume-unit-field>
                        <button type="button" class="input-group-text volume-unit-toggle" data-unit="ml"
                                title="<?= e(__('medicine.unit.toggle')) ?>">ml</button>
                    </div>
                    <?php show_field_error($errors, 'container_ml'); ?>
                </div>
                <div class="col-6 col-md-4 col-xl-auto medicine-add-bulk-col" data-medicine-fields="bulk" hidden>
                    <label for="container_count" class="form-label"><?= e(__('medicine.bulk.field.container_count')) ?></label>
                    <input type="number" class="form-control form-control-sm<?= field_invalid($errors, 'container_count') ?>"
                           id="container_count" name="container_count" min="1" step="1"
                           value="<?= e((string) ($_POST['container_count'] ?? '')) ?>">
                    <?php show_field_error($errors, 'container_count'); ?>
                </div>
                <div class="col-6 col-md-4 col-xl-auto medicine-add-bulk-col" data-medicine-fields="bulk" hidden>
                    <label for="unit_size_ml" class="form-label"><?= e(__('medicine.field.unit_size')) ?></label>
                    <div class="input-group input-group-sm" data-volume-group>
                        <input type="number" class="form-control<?= field_invalid($errors, 'unit_size_ml') ?>"
                               id="unit_size_ml" name="unit_size_ml" min="1" step="1"
                               data-volume-input
                               value="<?= e((string) ($_POST['unit_size_ml'] ?? '100')) ?>">
                        <input type="hidden" name="unit_size_volume_unit"
                               value="<?= e((string) ($_POST['unit_size_volume_unit'] ?? 'ml')) ?>" data-volume-unit-field>
                        <button type="button" class="input-group-text volume-unit-toggle" data-unit="ml"
                                title="<?= e(__('medicine.unit.toggle')) ?>">ml</button>
                    </div>
                    <?php show_field_error($errors, 'unit_size_ml'); ?>
                </div>

                <div class="col-auto medicine-add-submit-col">
                    <label class="form-label d-none d-xl-block" aria-hidden="true">&nbsp;</label>
                    <button type="submit" class="btn btn-reception-primary text-nowrap"><?= e(__('medicine.add.submit')) ?></button>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('medicine.list.title')) ?></h2>

        <?php if ($medicines === []): ?>
            <p class="text-muted mb-0"><?= e(__('medicine.list.empty')) ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover reception-table mb-0 medicine-inventory-table">
                    <thead>
                    <tr>
                        <th scope="col"><?= e(__('medicine.field.name')) ?></th>
                        <th scope="col"><?= e(__('medicine.field.type')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('medicine.field.price')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('medicine.field.stock')) ?></th>
                        <th scope="col"><?= e(__('medicine.field.source')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('patient.field.actions')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <?php
                        $isBulk = (string) $medicine['kind'] === Medicine::KIND_BULK;
                        $bulkId = (int) $medicine['id'];
                        $showPortionRow = $isBulk && $portionBulkId === $bulkId && $portionErrors !== [];
                        ?>
                        <tr>
                            <td><?= e($medicine['name']) ?></td>
                            <td>
                                <span class="medicine-kind-badge medicine-kind-<?= e((string) $medicine['kind']) ?>">
                                    <?= e(Medicine::kindLabel((string) $medicine['kind'])) ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if ($isBulk): ?>
                                    <?= table_na() ?>
                                <?php else: ?>
                                    <?= e(Medicine::formatPriceDisplay((float) $medicine['unit_price'])) ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end"><?= e((string) $medicine['stock_display']) ?></td>
                            <td class="small text-muted">
                                <?php if ($isBulk && !empty($medicine['portion_size_ml'])): ?>
                                    <?= e(__('medicine.bulk.unit_size_display', [
                                        'size' => Medicine::formatVolumeMl((int) $medicine['portion_size_ml']),
                                    ])) ?>
                                <?php elseif (!empty($medicine['bulk_source_name'])): ?>
                                    <?= e(__('medicine.source.from_bulk', [
                                        'name' => (string) $medicine['bulk_source_name'],
                                        'size' => Medicine::formatVolumeMl((int) ($medicine['portion_size_ml'] ?? 0)),
                                    ])) ?>
                                <?php else: ?>
                                    <?= table_na() ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <?php if ($isBulk && (int) $medicine['stock_quantity'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#portionRow<?= $bulkId ?>"
                                            aria-expanded="<?= $showPortionRow ? 'true' : 'false' ?>">
                                        <?= e(__('medicine.portion.action')) ?>
                                    </button>
                                <?php endif; ?>
                                <form method="post" action="<?= e(base_url('/medicines.php')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id" value="<?= e((string) $medicine['id']) ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger patient-delete-trigger"
                                            data-patient-name="<?= e($medicine['name']) ?>">
                                        <?= e(__('medicine.action.remove')) ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php if ($isBulk): ?>
                            <tr class="medicine-portion-row collapse<?= $showPortionRow ? ' show' : '' ?>" id="portionRow<?= $bulkId ?>">
                                <td colspan="6" class="bg-light">
                                    <?php
                                    $bulk = $medicine;
                                    require BASE_PATH . '/views/partials/medicine_portion_form.php';
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$pageScripts = ['assets/js/medicine-add.js'];
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
