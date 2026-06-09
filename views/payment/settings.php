<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $old */
/** @var string|null $successMessage */
/** @var string $returnUrl */
/** @var string $returnPath */

$pageTitle = __('payment.settings.title');

ob_start();
?>
<div class="page-header-bar page-header-bar--inline mb-4">
    <?php $url = $returnUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0"><?= e(__('payment.settings.title')) ?></h1>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('/payment_settings.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= e($returnPath) ?>">

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-2"><?= e(__('payment.settings.registration')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('payment.settings.registration_hint')) ?></p>

        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-4">
                <label for="default_amount" class="form-label"><?= e(__('payment.field.default_amount')) ?></label>
                <input type="number" class="form-control<?= field_invalid($errors, 'default_amount') ?>"
                       id="default_amount" name="default_amount" min="0" step="0.01"
                       value="<?= e($old['default_amount']) ?>">
                <?php show_field_error($errors, 'default_amount'); ?>
            </div>
            <div class="col-sm-6 col-lg-4">
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
            <div class="col-sm-6 col-lg-4">
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
        <h2 class="reception-card-title h6 mb-2"><?= e(__('visit.settings.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('visit.settings.hint')) ?></p>

        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg-4">
                <label for="visit_default_charge" class="form-label"><?= e(__('visit.field.default_charge')) ?></label>
                <input type="number" class="form-control<?= field_invalid($errors, 'visit_default_charge') ?>"
                       id="visit_default_charge" name="visit_default_charge" min="0" step="0.01"
                       value="<?= e($old['visit_default_charge']) ?>">
                <?php show_field_error($errors, 'visit_default_charge'); ?>
            </div>
            <div class="col-sm-6 col-lg-4">
                <label for="visit_default_method" class="form-label"><?= e(__('payment.field.default_method')) ?></label>
                <select class="form-select<?= field_invalid($errors, 'visit_default_method') ?>"
                        id="visit_default_method" name="visit_default_method">
                    <?php foreach (PaymentSettings::METHODS as $method): ?>
                        <option value="<?= $method ?>"<?= ($old['visit_default_method'] ?? '') === $method ? ' selected' : '' ?>>
                            <?= e(PaymentSettings::methodLabel($method)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php show_field_error($errors, 'visit_default_method'); ?>
            </div>
            <div class="col-sm-6 col-lg-4">
                <label for="visit_default_status" class="form-label"><?= e(__('payment.field.default_status')) ?></label>
                <select class="form-select<?= field_invalid($errors, 'visit_default_status') ?>"
                        id="visit_default_status" name="visit_default_status">
                    <?php foreach (PaymentSettings::STATUSES as $status): ?>
                        <option value="<?= $status ?>"<?= ($old['visit_default_status'] ?? '') === $status ? ' selected' : '' ?>>
                            <?= e(PaymentSettings::statusLabel($status)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php show_field_error($errors, 'visit_default_status'); ?>
            </div>
        </div>
    </section>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('gst.settings.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('gst.settings.hint')) ?></p>

        <div class="row g-3 gst-settings-row">
            <div class="col-6 col-lg-3">
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
            <div class="col-6 col-lg-3">
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
            <div class="col-6 col-lg-3">
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
            <div class="col-6 col-lg-3">
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

    <div class="d-flex flex-wrap gap-2">
        <button type="submit" class="btn btn-reception-primary"><?= e(__('action.save')) ?></button>
        <a href="<?= e($returnUrl) ?>" class="btn btn-outline-secondary"><?= e(__('action.cancel')) ?></a>
    </div>
</form>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
