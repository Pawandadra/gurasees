<?php

declare(strict_types=1);

/** @var array<string, mixed> $bulk */
/** @var array<string, string> $portionErrors */

$portionErrors = $portionErrors ?? [];
$bulkId = (int) ($bulk['id'] ?? 0);
$defaultPortionMl = (int) ($bulk['portion_size_ml'] ?? 100);
$portionMl = (int) ($_POST['portion_ml'] ?? $defaultPortionMl);
?>
<form method="post" action="<?= e(base_url('/medicines.php')) ?>" class="medicine-portion-inline-form py-2">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="portion">
    <input type="hidden" name="bulk_id" value="<?= $bulkId ?>">

    <p class="small text-muted mb-2">
        <?= e(__('medicine.portion.inline_hint', [
            'name' => (string) $bulk['name'],
            'available' => Medicine::formatVolumeMl((int) $bulk['stock_quantity']),
        ])) ?>
    </p>

    <div class="row g-2 align-items-end">
        <div class="col-md-2 col-lg-2">
            <label class="form-label small mb-1"><?= e(__('medicine.portion.field.size')) ?></label>
            <div class="input-group input-group-sm">
                <input type="number" class="form-control<?= field_invalid($portionErrors, 'portion_ml') ?>"
                       name="portion_ml" min="1" step="1" required value="<?= e((string) $portionMl) ?>">
                <span class="input-group-text">ml</span>
            </div>
            <?php show_field_error($portionErrors, 'portion_ml'); ?>
        </div>
        <div class="col-md-2 col-lg-2">
            <label class="form-label small mb-1"><?= e(__('medicine.portion.field.bottles')) ?></label>
            <input type="number" class="form-control form-control-sm<?= field_invalid($portionErrors, 'bottle_count') ?>"
                   name="bottle_count" min="1" step="1" required
                   value="<?= e((string) ($_POST['bottle_count'] ?? '')) ?>">
            <?php show_field_error($portionErrors, 'bottle_count'); ?>
        </div>
        <div class="col-md-4 col-lg-3">
            <label class="form-label small mb-1"><?= e(__('medicine.portion.field.sellable_name')) ?></label>
            <input type="text" class="form-control form-control-sm<?= field_invalid($portionErrors, 'sellable_name') ?>"
                   name="sellable_name" maxlength="120" required
                   placeholder="<?= e(__('medicine.portion.sellable_placeholder')) ?>"
                   value="<?= e((string) ($_POST['sellable_name'] ?? '')) ?>">
            <?php show_field_error($portionErrors, 'sellable_name'); ?>
        </div>
        <div class="col-md-2 col-lg-2">
            <label class="form-label small mb-1"><?= e(__('medicine.field.price')) ?></label>
            <div class="input-group input-group-sm">
                <span class="input-group-text">₹</span>
                <input type="number" class="form-control<?= field_invalid($portionErrors, 'portion_unit_price') ?>"
                       name="unit_price" min="0.01" step="0.01" required
                       value="<?= e((string) ($_POST['unit_price'] ?? '')) ?>">
            </div>
            <?php show_field_error($portionErrors, 'portion_unit_price'); ?>
        </div>
        <div class="col-md-2 col-lg-auto">
            <button type="submit" class="btn btn-sm btn-reception-primary w-100">
                <?= e(__('medicine.portion.submit')) ?>
            </button>
        </div>
    </div>
</form>
