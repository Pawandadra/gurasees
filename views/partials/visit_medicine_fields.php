<?php

declare(strict_types=1);

/** @var list<array{id: int, name: string, unit_price: string}> $catalogMedicines */
/** @var list<array{medicine_id: int, quantity: int}> $visitMedicineLines */
/** @var array<string, string> $visitErrors */

$catalogMedicines = $catalogMedicines ?? [];
$visitMedicineLines = $visitMedicineLines ?? [];
$visitErrors = $visitErrors ?? [];
?>
<div class="visit-medicines-block mt-3 pt-3 border-top"
     id="visitMedicines"
     data-medicines="<?= e(json_encode($catalogMedicines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-label-add="<?= e(__('visit.medicine.add_line')) ?>"
     data-label-remove="<?= e(__('visit.medicine.remove_line')) ?>"
     data-label-medicine="<?= e(__('visit.medicine.field.medicine')) ?>"
     data-label-quantity="<?= e(__('visit.medicine.field.quantity')) ?>"
     data-label-price="<?= e(__('visit.medicine.field.unit_price')) ?>"
     data-label-line="<?= e(__('visit.medicine.field.line_total')) ?>"
     data-label-select="<?= e(__('visit.medicine.select')) ?>"
     data-label-total="<?= e(__('visit.field.charges')) ?>"
     data-currency="₹">
    <h3 class="h6 mb-2"><?= e(__('visit.medicine.title')) ?></h3>
    <p class="text-muted small mb-2"><?= e(__('visit.medicine.hint')) ?></p>

    <?php if (isset($visitErrors['medicines'])): ?>
        <div class="alert alert-danger py-2 small"><?= e($visitErrors['medicines']) ?></div>
    <?php endif; ?>

    <?php if ($catalogMedicines === []): ?>
        <p class="text-muted small mb-0"><?= e(__('visit.medicine.none_available')) ?></p>
    <?php else: ?>
        <div class="visit-medicine-lines mb-2" id="visitMedicineLines"
             data-initial="<?= e(json_encode($visitMedicineLines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
        </div>
        <div id="visitMedicineHiddenInputs" aria-hidden="true"></div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" id="visitMedicineAddBtn">
                <?= e(__('visit.medicine.add_line')) ?>
            </button>
            <p class="mb-0 fw-semibold visit-medicine-total" id="visitMedicineTotal" aria-live="polite">
                <?= e(__('visit.field.charges')) ?>: ₹0.00
            </p>
        </div>
    <?php endif; ?>
</div>
