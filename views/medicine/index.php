<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var list<array{id: int, name: string, unit_price: string, stock_quantity: int}> $medicines */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $dbError */

$pageTitle = __('medicine.manage.title');
$errors = $errors ?? [];

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

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('medicine.add.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('medicine.add.hint')) ?></p>

        <form method="post" action="<?= e(base_url('/medicines.php')) ?>" class="medicine-add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-3">
                    <label for="medicine_name" class="form-label"><?= e(__('medicine.field.name')) ?></label>
                    <input type="text" class="form-control<?= field_invalid($errors, 'name') ?>"
                           id="medicine_name" name="name" maxlength="120" required
                           value="<?= e((string) ($_POST['name'] ?? '')) ?>">
                    <?php show_field_error($errors, 'name'); ?>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label for="medicine_unit_price" class="form-label"><?= e(__('medicine.field.price')) ?></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control<?= field_invalid($errors, 'unit_price') ?>"
                               id="medicine_unit_price" name="unit_price" min="0.01" step="0.01" required
                               value="<?= e((string) ($_POST['unit_price'] ?? '')) ?>">
                    </div>
                    <?php show_field_error($errors, 'unit_price'); ?>
                </div>
                <div class="col-md-3 col-lg-2">
                    <label for="medicine_stock" class="form-label"><?= e(__('medicine.field.stock')) ?></label>
                    <input type="number" class="form-control<?= field_invalid($errors, 'stock_quantity') ?>"
                           id="medicine_stock" name="stock_quantity" min="0" step="1" required
                           value="<?= e((string) ($_POST['stock_quantity'] ?? '')) ?>">
                    <?php show_field_error($errors, 'stock_quantity'); ?>
                </div>
                <div class="col-md-2 col-lg-auto">
                    <button type="submit" class="btn btn-reception-primary w-100"><?= e(__('medicine.add.submit')) ?></button>
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
                <table class="table table-hover reception-table mb-0">
                    <thead>
                    <tr>
                        <th scope="col"><?= e(__('medicine.field.name')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('medicine.field.price')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('medicine.field.stock')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('medicine.action.remove')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($medicines as $medicine): ?>
                        <tr>
                            <td><?= e($medicine['name']) ?></td>
                            <td class="text-end"><?= e(Medicine::formatPriceDisplay((float) $medicine['unit_price'])) ?></td>
                            <td class="text-end"><?= e((string) $medicine['stock_quantity']) ?></td>
                            <td class="text-end">
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
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
