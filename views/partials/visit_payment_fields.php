<?php

declare(strict_types=1);

/** @var array<string, string> $visitErrors */
/** @var array<string, string> $visitBilling */

$visitErrors = $visitErrors ?? [];
$visitBilling = $visitBilling ?? Visit::billingDefaults();
$paymentStatus = (string) ($visitBilling['payment_status'] ?? PaymentSettings::defaultStatus());
$showPartialPaid = $paymentStatus === 'partial';
?>
<div class="visit-payment-section mt-3 pt-3 border-top"
     id="visitPaymentSection"
     data-initial-total="0">
    <h4 class="visit-form-panel-title h6 mb-2"><?= e(__('payment.section.title')) ?></h4>

    <div class="visit-payment-row">
        <div class="patient-payment-field patient-payment-method-col visit-payment-method-col">
            <label for="visit_payment_method" class="form-label"><?= e(__('payment.field.method')) ?> <span class="text-danger visit-payment-required-mark">*</span></label>
            <div class="patient-payment-control">
                <select class="form-select visit-payment-detail-input<?= field_invalid($visitErrors, 'payment_method') ?>"
                        id="visit_payment_method" name="payment_method">
                    <?php foreach (PaymentSettings::METHODS as $method): ?>
                        <option value="<?= $method ?>"<?= ($visitBilling['payment_method'] ?? '') === $method ? ' selected' : '' ?>>
                            <?= e(PaymentSettings::methodLabel($method)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php show_field_error($visitErrors, 'payment_method'); ?>
        </div>
        <div class="patient-payment-field patient-payment-status-col visit-payment-status-col">
            <span class="form-label d-block" id="visit_payment_status_label"><?= e(__('payment.field.status')) ?> <span class="text-danger visit-payment-required-mark">*</span></span>
            <div class="patient-payment-control">
                <div class="gender-toggle-group payment-status-group visit-payment-status-group<?= isset($visitErrors['payment_status']) ? ' is-invalid' : '' ?>"
                     role="radiogroup" aria-labelledby="visit_payment_status_label">
                    <?php foreach (PaymentSettings::STATUSES as $status): ?>
                        <input class="btn-check visit-payment-status-input visit-payment-detail-input" type="radio" name="payment_status"
                               id="visit_payment_status_<?= $status ?>" value="<?= $status ?>"
                            <?= $paymentStatus === $status ? ' checked' : '' ?>>
                        <label class="btn" for="visit_payment_status_<?= $status ?>"><?= e(PaymentSettings::statusLabel($status)) ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php show_field_error($visitErrors, 'payment_status'); ?>
        </div>
        <div class="patient-payment-field patient-payment-partial-col visit-payment-partial-col visit-payment-partial-field<?= $showPartialPaid ? '' : ' d-none' ?>"
             id="visitPaymentPartialField">
            <label for="visit_payment_paid_amount" class="form-label"><?= e(__('payment.field.paid_amount')) ?> <span class="text-danger visit-payment-required-mark">*</span></label>
            <div class="patient-payment-control">
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control visit-payment-detail-input<?= field_invalid($visitErrors, 'payment_paid_amount') ?>"
                           id="visit_payment_paid_amount" name="payment_paid_amount" min="0.01" step="0.01"
                           value="<?= e((string) ($visitBilling['payment_paid_amount'] ?? '')) ?>">
                </div>
            </div>
            <?php show_field_error($visitErrors, 'payment_paid_amount'); ?>
        </div>
    </div>
    <p class="text-muted small mb-0 visit-payment-zero-hint d-none" id="visitPaymentZeroHint"><?= e(__('visit.payment.zero_hint')) ?></p>
</div>
