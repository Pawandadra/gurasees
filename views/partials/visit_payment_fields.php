<?php

declare(strict_types=1);

/** @var array<string, string> $visitErrors */
/** @var array<string, string> $visitBilling */

$visitErrors = $visitErrors ?? [];
$visitBilling = $visitBilling ?? Visit::billingDefaults();
$paymentStatus = (string) ($visitBilling['payment_status'] ?? PaymentSettings::defaultStatus());
$showPartialPaid = $paymentStatus === 'partial';
?>
<div class="visit-payment-section mt-3 pt-3 border-top" id="visitPaymentSection">
    <div class="payment-fields-grid visit-payment-fields visit-payment-fields--hidden" id="visitPaymentFields">
        <div class="payment-field payment-field--method visit-payment-method-col">
            <label for="visit_payment_method" class="form-label">
                <?= e(__('payment.field.method')) ?>
                <span class="text-danger visit-payment-required-mark">*</span>
            </label>
            <select class="form-select visit-payment-detail-input<?= field_invalid($visitErrors, 'payment_method') ?>"
                    id="visit_payment_method" name="payment_method">
                <?php foreach (PaymentSettings::METHODS as $method): ?>
                    <option value="<?= $method ?>"<?= ($visitBilling['payment_method'] ?? '') === $method ? ' selected' : '' ?>>
                        <?= e(PaymentSettings::methodLabel($method)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php show_field_error($visitErrors, 'payment_method'); ?>
        </div>

        <div class="payment-field payment-field--status visit-payment-status-col">
            <label for="visit_payment_status" class="form-label">
                <?= e(__('payment.field.status')) ?>
                <span class="text-danger visit-payment-required-mark">*</span>
            </label>
            <select class="form-select visit-payment-detail-input visit-payment-status-select<?= field_invalid($visitErrors, 'payment_status') ?>"
                    id="visit_payment_status" name="payment_status">
                <?php foreach (PaymentSettings::STATUSES as $status): ?>
                    <option value="<?= $status ?>"<?= $paymentStatus === $status ? ' selected' : '' ?>>
                        <?= e(PaymentSettings::statusLabel($status)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php show_field_error($visitErrors, 'payment_status'); ?>
        </div>

        <div class="payment-field payment-field--partial visit-payment-partial-col visit-payment-partial-field<?= $showPartialPaid ? '' : ' d-none' ?>"
             id="visitPaymentPartialField">
            <label for="visit_payment_paid_amount" class="form-label">
                <?= e(__('payment.field.paid_amount')) ?>
                <span class="text-danger visit-payment-required-mark">*</span>
            </label>
            <input type="number" class="form-control visit-payment-detail-input<?= field_invalid($visitErrors, 'payment_paid_amount') ?>"
                   id="visit_payment_paid_amount" name="payment_paid_amount" min="0.01" step="0.01"
                   value="<?= e((string) ($visitBilling['payment_paid_amount'] ?? '')) ?>">
            <?php show_field_error($visitErrors, 'payment_paid_amount'); ?>
        </div>
    </div>
</div>
