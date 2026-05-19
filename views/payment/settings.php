<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $old */
/** @var string|null $successMessage */
/** @var bool $paymentEnabled */

$pageTitle = __('payment.settings.title');

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('payment.settings.title')) ?></h1>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('/payment_settings.php')) ?>">
    <?= csrf_field() ?>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('payment.settings.defaults')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('payment.settings.hint')) ?></p>

        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="default_amount" class="form-label"><?= e(__('payment.field.default_amount')) ?></label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control<?= field_invalid($errors, 'default_amount') ?>"
                           id="default_amount" name="default_amount" min="0" step="0.01"
                           value="<?= e($old['default_amount']) ?>">
                </div>
                <?php show_field_error($errors, 'default_amount'); ?>
                <p class="form-text mb-0"><?= e(__('payment.settings.amount_zero_hint')) ?></p>
            </div>
            <div class="col-md-4">
                <label for="default_method" class="form-label"><?= e(__('payment.field.default_method')) ?></label>
                <select class="form-select<?= field_invalid($errors, 'default_method') ?>"
                        id="default_method" name="default_method">
                    <?php foreach (PaymentSettings::METHODS as $method): ?>
                        <option value="<?= $method ?>"<?= $old['default_method'] === $method ? ' selected' : '' ?>>
                            <?= e(PaymentSettings::methodLabel($method)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php show_field_error($errors, 'default_method'); ?>
            </div>
            <div class="col-md-4">
                <label for="default_status" class="form-label"><?= e(__('payment.field.default_status')) ?></label>
                <select class="form-select<?= field_invalid($errors, 'default_status') ?>"
                        id="default_status" name="default_status">
                    <?php foreach (PaymentSettings::STATUSES as $status): ?>
                        <option value="<?= $status ?>"<?= $old['default_status'] === $status ? ' selected' : '' ?>>
                            <?= e(PaymentSettings::statusLabel($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php show_field_error($errors, 'default_status'); ?>
            </div>
        </div>
    </section>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('gst.settings.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('gst.settings.hint')) ?></p>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="gst_registration_percent" class="form-label"><?= e(__('gst.field.registration')) ?></label>
                <div class="input-group">
                    <input type="number" class="form-control<?= field_invalid($errors, 'gst_registration_percent') ?>"
                           id="gst_registration_percent" name="gst_registration_percent"
                           min="0" max="100" step="0.01"
                           value="<?= e($old['gst_registration_percent']) ?>">
                    <span class="input-group-text">%</span>
                </div>
                <?php show_field_error($errors, 'gst_registration_percent'); ?>
            </div>
            <div class="col-md-4">
                <label for="gst_visit_percent" class="form-label"><?= e(__('gst.field.visit_charge')) ?></label>
                <div class="input-group">
                    <input type="number" class="form-control<?= field_invalid($errors, 'gst_visit_percent') ?>"
                           id="gst_visit_percent" name="gst_visit_percent"
                           min="0" max="100" step="0.01"
                           value="<?= e($old['gst_visit_percent']) ?>">
                    <span class="input-group-text">%</span>
                </div>
                <?php show_field_error($errors, 'gst_visit_percent'); ?>
            </div>
            <div class="col-md-4">
                <label for="gst_medicine_percent" class="form-label"><?= e(__('gst.field.medicine')) ?></label>
                <div class="input-group">
                    <input type="number" class="form-control<?= field_invalid($errors, 'gst_medicine_percent') ?>"
                           id="gst_medicine_percent" name="gst_medicine_percent"
                           min="0" max="100" step="0.01"
                           value="<?= e($old['gst_medicine_percent']) ?>">
                    <span class="input-group-text">%</span>
                </div>
                <?php show_field_error($errors, 'gst_medicine_percent'); ?>
            </div>
            <div class="col-md-4">
                <label for="gst_courier_percent" class="form-label"><?= e(__('gst.field.courier')) ?></label>
                <div class="input-group">
                    <input type="number" class="form-control<?= field_invalid($errors, 'gst_courier_percent') ?>"
                           id="gst_courier_percent" name="gst_courier_percent"
                           min="0" max="100" step="0.01"
                           value="<?= e($old['gst_courier_percent']) ?>">
                    <span class="input-group-text">%</span>
                </div>
                <?php show_field_error($errors, 'gst_courier_percent'); ?>
            </div>
        </div>
    </section>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('visit.settings.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('visit.settings.hint')) ?></p>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="visit_default_charge" class="form-label"><?= e(__('visit.field.default_charge')) ?></label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control<?= field_invalid($errors, 'visit_default_charge') ?>"
                           id="visit_default_charge" name="visit_default_charge" min="0" step="0.01"
                           value="<?= e($old['visit_default_charge']) ?>">
                </div>
                <?php show_field_error($errors, 'visit_default_charge'); ?>
            </div>
        </div>
    </section>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('courier.settings.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('courier.settings.hint')) ?></p>

        <div class="row g-3">
            <div class="col-md-4">
                <label for="courier_default_charge" class="form-label"><?= e(__('visit.field.courier_charge')) ?></label>
                <div class="input-group">
                    <span class="input-group-text">₹</span>
                    <input type="number" class="form-control<?= field_invalid($errors, 'courier_default_charge') ?>"
                           id="courier_default_charge" name="courier_default_charge" min="0" step="0.01"
                           value="<?= e($old['courier_default_charge']) ?>">
                </div>
                <?php show_field_error($errors, 'courier_default_charge'); ?>
                <p class="form-text mb-0"><?= e(__('courier.settings.charge_hint')) ?></p>
            </div>
        </div>
    </section>

    <section class="reception-card mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('payment.settings.preview')) ?></h2>
        <?php if ($paymentEnabled): ?>
            <p class="text-muted mb-0"><?= e(__('payment.settings.enabled', ['amount' => $old['default_amount']])) ?></p>
        <?php else: ?>
            <p class="text-muted mb-0"><?= e(__('payment.settings.disabled')) ?></p>
        <?php endif; ?>
    </section>

    <button type="submit" class="btn btn-reception-primary"><?= e(__('action.save')) ?></button>
</form>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
