<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$selected = array_map('intval', (array) ($old['symptoms'] ?? []));

try {
    $symptomOptions = Symptom::listActive();
} catch (Throwable) {
    $symptomOptions = [];
}
?>
<div class="patient-symptoms-section mt-3">
    <span class="form-label d-block mb-2"><?= e(__('patient.field.symptoms')) ?></span>
    <?php if ($symptomOptions === []): ?>
        <p class="text-muted small mb-0"><?= e(__('symptom.list.empty_reception')) ?></p>
    <?php else: ?>
        <div class="symptoms-checkbox-row" role="group" aria-label="<?= e(__('patient.field.symptoms')) ?>">
            <?php foreach ($symptomOptions as $symptom): ?>
                <?php $sid = (int) $symptom['id']; ?>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" name="symptoms[]"
                           value="<?= $sid ?>" id="symptom_<?= $sid ?>"
                        <?= in_array($sid, $selected, true) ? ' checked' : '' ?>>
                    <label class="form-check-label" for="symptom_<?= $sid ?>"><?= e($symptom['label']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
