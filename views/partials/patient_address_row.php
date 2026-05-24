<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
?>
<div class="row g-3">
    <div class="col-md-6">
        <label for="address" class="form-label"><?= e(__('patient.field.address')) ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control<?= field_invalid($errors, 'address') ?>"
               id="address" name="address" value="<?= e((string) $old['address']) ?>" required maxlength="500">
        <?php show_field_error($errors, 'address', true); ?>
    </div>
    <?php require BASE_PATH . '/views/partials/delivery_address_field.php'; ?>
</div>
