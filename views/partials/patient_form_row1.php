<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string $phoneIso */
/** @var string $phoneLocal */

$genderLetter = Patient::genderToLetter((string) ($old['gender'] ?? ''));
?>
<div class="patient-row patient-row-1 mb-3">
    <div class="patient-field patient-field-name">
        <label for="name" class="form-label"><?= e(__('patient.field.name')) ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control<?= field_invalid($errors, 'name', ['_duplicate']) ?>"
               id="name" name="name" value="<?= e((string) $old['name']) ?>" required maxlength="120" autocomplete="name">
        <?php show_field_error($errors, 'name', true); ?>
    </div>
    <div class="patient-field patient-field-age">
        <label for="age" class="form-label"><?= e(__('patient.field.age')) ?> <span class="text-danger">*</span></label>
        <input type="number" class="form-control<?= field_invalid($errors, 'age') ?>"
               id="age" name="age" value="<?= e((string) $old['age']) ?>" required min="1" max="120">
        <?php show_field_error($errors, 'age', true); ?>
    </div>
    <div class="patient-field patient-field-gender">
        <label for="gender" class="form-label"><?= e(__('patient.field.gender')) ?> <span class="text-danger">*</span></label>
        <input type="text" class="form-control patient-gender-input text-uppercase<?= field_invalid($errors, 'gender') ?>"
               id="gender" name="gender" value="<?= e($genderLetter) ?>" required maxlength="1"
               autocomplete="off" inputmode="text" spellcheck="false"
               aria-describedby="gender_hint"
               pattern="[MmFfOo]" title="<?= e(__('patient.field.gender_codes')) ?>">
        <span id="gender_hint" class="visually-hidden"><?= e(__('patient.field.gender_codes')) ?></span>
        <?php show_field_error($errors, 'gender', true); ?>
    </div>
    <div class="patient-field patient-field-phone">
        <label for="phone" class="form-label"><?= e(__('patient.field.phone')) ?> <span class="text-danger">*</span></label>
        <div class="input-group phone-input-group<?= field_invalid($errors, 'phone', ['_duplicate']) ?>">
            <?php require BASE_PATH . '/views/partials/phone_country_picker.php'; ?>
            <input type="tel" class="form-control<?= field_invalid($errors, 'phone', ['_duplicate']) ?>"
                   id="phone" name="phone" value="<?= e($phoneLocal) ?>" required inputmode="numeric"
                   maxlength="<?= $phoneIso === 'IN' ? '10' : '14' ?>">
        </div>
        <?php show_field_error($errors, 'phone', true); ?>
    </div>
</div>
