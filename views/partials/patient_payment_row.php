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

if (!PaymentSettings::isEnabled()) {
    return;
}

$paymentStatus = (string) ($old['payment_status'] ?? 'paid');
$gstPercent = GstSettings::formatPercent(GstSettings::registrationPercent());
$showPartialPaid = $paymentStatus === 'partial';
$amountValue = (string) ($old['payment_amount'] ?? '');
$showPaymentDetails = $amountValue === '' || (float) $amountValue > 0;
?>
<div class="patient-payment-section"
     id="patientPaymentSection"
     data-gst-percent="<?= e($gstPercent) ?>">
    <div class="row g-3 patient-payment-row">
        <div class="col-sm-6 col-md-4 col-lg-3 payment-field payment-field--amount">
            <label for="payment_amount" class="form-label"><?= e(__('payment.field.amount')) ?></label>
            <div class="payment-field-body">
                <div class="payment-field-control">
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control<?= field_invalid($errors, 'payment_amount') ?>"
                               id="payment_amount" name="payment_amount" min="0" step="0.01"
                               value="<?= e($amountValue) ?>">
                    </div>
                </div>
                <p class="payment-field-meta payment-gst-summary small text-muted mb-0<?= $showPaymentDetails ? '' : ' d-none' ?>"
                   id="paymentGstSummary" aria-live="polite"
                   data-label-gst="<?= e(__('payment.field.gst_col')) ?>"
                   data-label-without-gst="<?= e(__('payment.field.without_gst_col')) ?>"></p>
            </div>
            <?php show_field_error($errors, 'payment_amount', true); ?>
        </div>

        <div class="col-sm-6 col-md-4 col-lg-3 payment-field payment-field--method payment-detail-col<?= $showPaymentDetails ? '' : ' d-none' ?>">
            <label for="payment_method" class="form-label">
                <?= e(__('payment.field.method')) ?>
                <span class="text-danger payment-required-mark">*</span>
            </label>
            <div class="payment-field-body">
                <div class="payment-field-control">
                    <select class="form-select payment-detail-input<?= field_invalid($errors, 'payment_method') ?>"
                            id="payment_method" name="payment_method">
                        <?php foreach (PaymentSettings::METHODS as $method): ?>
                            <option value="<?= $method ?>"<?= ($old['payment_method'] ?? '') === $method ? ' selected' : '' ?>>
                                <?= e(PaymentSettings::methodLabel($method)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="payment-field-meta payment-field-meta--spacer" aria-hidden="true"></div>
            </div>
            <?php show_field_error($errors, 'payment_method', true); ?>
        </div>

        <div class="col-sm-6 col-md-4 col-lg-3 payment-field payment-field--status payment-detail-col<?= $showPaymentDetails ? '' : ' d-none' ?>">
            <label for="payment_status" class="form-label">
                <?= e(__('payment.field.status')) ?>
                <span class="text-danger payment-required-mark">*</span>
            </label>
            <div class="payment-field-body">
                <div class="payment-field-control">
                    <select class="form-select payment-detail-input payment-status-select<?= field_invalid($errors, 'payment_status') ?>"
                            id="payment_status" name="payment_status">
                        <?php foreach (PaymentSettings::STATUSES as $status): ?>
                            <option value="<?= $status ?>"<?= $paymentStatus === $status ? ' selected' : '' ?>>
                                <?= e(PaymentSettings::statusLabel($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="payment-field-meta payment-field-meta--spacer" aria-hidden="true"></div>
            </div>
            <?php show_field_error($errors, 'payment_status', true); ?>
        </div>

        <div class="col-sm-6 col-md-4 col-lg-2 payment-field payment-field--partial payment-partial-field<?= $showPartialPaid ? '' : ' d-none' ?>"
             id="paymentPartialField">
            <label for="payment_paid_amount" class="form-label">
                <?= e(__('payment.field.paid_amount')) ?>
                <span class="text-danger payment-required-mark">*</span>
            </label>
            <div class="payment-field-body">
                <div class="payment-field-control">
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control payment-detail-input<?= field_invalid($errors, 'payment_paid_amount') ?>"
                               id="payment_paid_amount" name="payment_paid_amount" min="0.01" step="0.01"
                               value="<?= e((string) ($old['payment_paid_amount'] ?? '')) ?>">
                    </div>
                </div>
                <div class="payment-field-meta payment-field-meta--spacer" aria-hidden="true"></div>
            </div>
            <?php show_field_error($errors, 'payment_paid_amount', true); ?>
        </div>
    </div>
</div>
