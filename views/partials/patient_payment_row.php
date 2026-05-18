<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

if (!class_exists('PaymentSettings', false)) {
    load_model('PaymentSettings');
}
if (!class_exists('GstSettings', false)) {
    load_model('GstSettings');
}

$paymentStatus = (string) ($old['payment_status'] ?? 'paid');
$gstPercent = GstSettings::formatPercent(GstSettings::registrationPercent());
$showPartialPaid = $paymentStatus === 'partial';
$amountValue = (string) ($old['payment_amount'] ?? '');
$showPaymentDetails = $amountValue === '' || (float) $amountValue > 0;
?>
<div class="patient-payment-section mt-3"
     id="patientPaymentSection"
     data-gst-percent="<?= e($gstPercent) ?>">
    <div class="row g-3 patient-payment-row align-items-end">
        <div class="col-md-4 col-lg-3">
            <label for="payment_amount" class="form-label"><?= e(__('payment.field.amount')) ?></label>
            <div class="input-group">
                <span class="input-group-text">₹</span>
                <input type="number" class="form-control<?= field_invalid($errors, 'payment_amount') ?>"
                       id="payment_amount" name="payment_amount" min="0" step="0.01"
                       value="<?= e($amountValue) ?>">
            </div>
            <?php show_field_error($errors, 'payment_amount'); ?>
        </div>
        <div class="col-12 payment-gst-summary small text-muted<?= $showPaymentDetails ? '' : ' d-none' ?>" id="paymentGstSummary" aria-live="polite"></div>
        <div class="col-md-8 col-lg-9 payment-details-fields<?= $showPaymentDetails ? '' : ' d-none' ?>" id="paymentDetailsFields">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="payment_method" class="form-label"><?= e(__('payment.field.method')) ?> <span class="text-danger payment-required-mark">*</span></label>
                    <select class="form-select payment-detail-input<?= field_invalid($errors, 'payment_method') ?>"
                            id="payment_method" name="payment_method">
                        <?php foreach (PaymentSettings::METHODS as $method): ?>
                            <option value="<?= $method ?>"<?= ($old['payment_method'] ?? '') === $method ? ' selected' : '' ?>>
                                <?= e(PaymentSettings::methodLabel($method)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php show_field_error($errors, 'payment_method'); ?>
                </div>
                <div class="col-md-4">
                    <span class="form-label d-block" id="payment_status_label"><?= e(__('payment.field.status')) ?> <span class="text-danger payment-required-mark">*</span></span>
                    <div class="gender-toggle-group payment-status-group<?= isset($errors['payment_status']) ? ' is-invalid' : '' ?>"
                         role="radiogroup" aria-labelledby="payment_status_label">
                        <?php foreach (PaymentSettings::STATUSES as $status): ?>
                            <input class="btn-check payment-status-input payment-detail-input" type="radio" name="payment_status"
                                   id="payment_status_<?= $status ?>" value="<?= $status ?>"
                                <?= $paymentStatus === $status ? ' checked' : '' ?>>
                            <label class="btn" for="payment_status_<?= $status ?>"><?= e(PaymentSettings::statusLabel($status)) ?></label>
                        <?php endforeach; ?>
                    </div>
                    <?php show_field_error($errors, 'payment_status'); ?>
                </div>
                <div class="col-md-4 payment-partial-field<?= $showPartialPaid ? '' : ' d-none' ?>" id="paymentPartialField">
                    <label for="payment_paid_amount" class="form-label"><?= e(__('payment.field.paid_amount')) ?> <span class="text-danger payment-required-mark">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control payment-detail-input<?= field_invalid($errors, 'payment_paid_amount') ?>"
                               id="payment_paid_amount" name="payment_paid_amount" min="0.01" step="0.01"
                               value="<?= e((string) ($old['payment_paid_amount'] ?? '')) ?>">
                    </div>
                    <?php show_field_error($errors, 'payment_paid_amount'); ?>
                </div>
            </div>
        </div>
    </div>
</div>
