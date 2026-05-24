<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
?>
<div class="row g-3 mt-0">
    <div class="col-12">
        <label for="remarks" class="form-label">
            <?= e(__('patient.field.remarks')) ?>
            <span class="text-muted fw-normal small">(<?= e(__('patient.field.delivery_optional')) ?>)</span>
        </label>
        <textarea class="form-control<?= field_invalid($errors, 'remarks') ?>"
                  id="remarks" name="remarks" rows="2" maxlength="1000"
                  placeholder="<?= e(__('patient.field.remarks_placeholder')) ?>"><?= e((string) ($old['remarks'] ?? '')) ?></textarea>
        <?php show_field_error($errors, 'remarks'); ?>
    </div>
</div>
