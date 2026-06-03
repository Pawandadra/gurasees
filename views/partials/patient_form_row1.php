<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string $phoneIso */
/** @var string $phoneLocal */
/** @var bool $showRegisteredAt */

$showRegisteredAt = $showRegisteredAt ?? false;
$genderLetter = Patient::genderToLetter((string) ($old['gender'] ?? ''));
?>
<div class="patient-row patient-row-1 mb-3<?= $showRegisteredAt ? ' patient-row-1--register' : '' ?>">
    <?php if ($showRegisteredAt): ?>
        <div class="patient-field patient-field-registered-at">
            <label for="registered_at" class="form-label"><?= e(__('patient.field.registered_at')) ?></label>
            <input type="date" class="form-control<?= field_invalid($errors, 'registered_at') ?>"
                   id="registered_at" name="registered_at" required
                   max="<?= e((new DateTimeImmutable('today'))->format('Y-m-d')) ?>"
                   value="<?= e((string) ($old['registered_at'] ?? (new DateTimeImmutable('today'))->format('Y-m-d'))) ?>">
            <?php show_field_error($errors, 'registered_at'); ?>
        </div>
    <?php endif; ?>
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
            <?php
            $phoneFieldPrefix = '';
            require BASE_PATH . '/views/partials/phone_country_picker.php';
            ?>
            <input type="tel" class="form-control<?= field_invalid($errors, 'phone', ['_duplicate']) ?>"
                   id="phone" name="phone" value="<?= e($phoneLocal) ?>" required inputmode="numeric"
                   maxlength="<?= $phoneIso === 'IN' ? '10' : '14' ?>">
        </div>
        <?php show_field_error($errors, 'phone', true); ?>
    </div>
    <?php
    $additionalPhoneIso = (string) ($old['additional_phone_iso'] ?? 'IN');
    $additionalPhoneLocal = (string) ($old['additional_phone_local'] ?? '');
    $phoneFieldPrefix = 'additional_';
    $phoneIso = $additionalPhoneIso;
    ?>
    <div class="patient-field patient-field-additional-phone">
        <label for="additional_phone" class="form-label"><?= e(__('patient.field.additional_phone')) ?></label>
        <div class="input-group phone-input-group<?= field_invalid($errors, 'additional_phone') ?>">
            <?php require BASE_PATH . '/views/partials/phone_country_picker.php'; ?>
            <input type="tel" class="form-control<?= field_invalid($errors, 'additional_phone') ?>"
                   id="additional_phone" name="additional_phone" value="<?= e($additionalPhoneLocal) ?>"
                   inputmode="numeric" maxlength="<?= $additionalPhoneIso === 'IN' ? '10' : '14' ?>"
                   autocomplete="tel">
        </div>
        <?php show_field_error($errors, 'additional_phone'); ?>
    </div>
</div>
