<?php

declare(strict_types=1);

/** @var array<string, mixed> $old */

$primaryAddress = trim((string) ($old['address'] ?? ''));
$deliveryAddress = trim((string) ($old['delivery_address'] ?? ''));
$deliverySame = ($old['delivery_same_as_address'] ?? '') === '1'
    || ($primaryAddress !== '' && $deliveryAddress === $primaryAddress);
?>
<div class="col-md-6 delivery-address-field">
    <label for="delivery_address" class="form-label">
        <?= e(__('patient.field.delivery_address')) ?>
        <span class="text-muted fw-normal small">(<?= e(__('patient.field.delivery_optional')) ?>)</span>
    </label>
    <input type="text" class="form-control delivery-address-input<?= $deliverySame ? ' delivery-address-synced' : '' ?>"
           id="delivery_address" name="delivery_address"
           value="<?= e((string) ($old['delivery_address'] ?? '')) ?>" maxlength="500"
        <?= $deliverySame ? ' readonly' : '' ?>>
    <div class="form-check delivery-same-check mt-2">
        <input type="checkbox" class="form-check-input" id="delivery_same_as_address"
               name="delivery_same_as_address" value="1"<?= $deliverySame ? ' checked' : '' ?>>
        <label class="form-check-label" for="delivery_same_as_address">
            <?= e(__('patient.field.delivery_same_as_address')) ?>
        </label>
    </div>
</div>
