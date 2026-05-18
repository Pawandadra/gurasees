<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string $phoneIso */
/** @var string $phoneLocal */
/** @var bool $showPhonePlaceholder */

$showPhonePlaceholder = $showPhonePlaceholder ?? false;
?>
<div class="patient-row patient-row-1 mb-3">
    <div class="patient-field patient-field-name">
        <label for="name" class="form-label"><?= e(__('patient.field.name')) ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control<?= field_invalid($errors, 'name') ?>"
               id="name" name="name" value="<?= e((string) $old['name']) ?>" required maxlength="120"
            <?= $showPhonePlaceholder ? ' autocomplete="name"' : '' ?>>
        <?php show_field_error($errors, 'name'); ?>
    </div>
    <div class="patient-field patient-field-age">
        <label for="age" class="form-label"><?= e(__('patient.field.age')) ?> <span class="text-danger">*</span></label>
        <input type="number" class="form-control<?= field_invalid($errors, 'age') ?>"
               id="age" name="age" value="<?= e((string) $old['age']) ?>" required min="1" max="120">
        <?php show_field_error($errors, 'age'); ?>
    </div>
    <div class="patient-field patient-field-gender">
        <span class="form-label" id="gender_label"><?= e(__('patient.field.gender')) ?> <span class="text-danger">*</span></span>
        <div class="gender-toggle-group<?= isset($errors['gender']) ? ' is-invalid' : '' ?>" role="radiogroup" aria-labelledby="gender_label">
            <?php foreach (['male', 'female', 'other'] as $g): ?>
                <input class="btn-check" type="radio" name="gender" id="gender_<?= $g ?>"
                       value="<?= $g ?>"<?= ($old['gender'] ?? '') === $g ? ' checked' : '' ?><?= $g === 'male' ? ' required' : '' ?>>
                <label class="btn" for="gender_<?= $g ?>"><?= e(__('patient.gender.' . $g)) ?></label>
            <?php endforeach; ?>
        </div>
        <?php show_field_error($errors, 'gender'); ?>
    </div>
    <div class="patient-field patient-field-phone">
        <label for="phone" class="form-label"><?= e(__('patient.field.phone')) ?> <span class="text-danger">*</span></label>
        <div class="input-group phone-input-group<?= field_invalid($errors, 'phone') ?>">
            <?php require BASE_PATH . '/views/partials/phone_country_picker.php'; ?>
            <input type="tel" class="form-control<?= field_invalid($errors, 'phone') ?>"
                   id="phone" name="phone" value="<?= e($phoneLocal) ?>" required inputmode="numeric"
                   maxlength="<?= $phoneIso === 'IN' ? '10' : '14' ?>"
                <?= $showPhonePlaceholder && $phoneIso === 'IN' ? ' placeholder="9876543210"' : '' ?>>
        </div>
        <?php show_field_error($errors, 'phone'); ?>
    </div>
</div>
