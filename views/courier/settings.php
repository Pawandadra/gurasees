<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $old */
/** @var string|null $successMessage */
/** @var string $returnUrl */
/** @var string $returnPath */

$pageTitle = __('courier.settings.title');

ob_start();
?>
<div class="page-header-bar page-header-bar--inline mb-4">
    <?php $url = $returnUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0"><?= e(__('courier.settings.title')) ?></h1>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<form method="post" action="<?= e(base_url('/courier_settings.php')) ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="return" value="<?= e($returnPath) ?>">

    <section class="reception-card reception-form mb-4">
        <p class="text-muted small mb-3"><?= e(__('courier.settings.sender_hint')) ?></p>

        <div class="row g-3">
            <div class="col-md-6">
                <label for="courier_sender_name" class="form-label"><?= e(__('courier.settings.sender_name')) ?></label>
                <input type="text" class="form-control<?= field_invalid($errors, 'courier_sender_name') ?>"
                       id="courier_sender_name" name="courier_sender_name" maxlength="120"
                       placeholder="<?= e(__('app.name')) ?>"
                       value="<?= e($old['courier_sender_name']) ?>">
                <?php show_field_error($errors, 'courier_sender_name'); ?>
                <p class="form-text mb-0"><?= e(__('courier.settings.sender_name_hint')) ?></p>
            </div>
            <div class="col-md-6">
                <label for="courier_sender_phone" class="form-label"><?= e(__('courier.settings.sender_phone')) ?></label>
                <input type="text" class="form-control<?= field_invalid($errors, 'courier_sender_phone') ?>"
                       id="courier_sender_phone" name="courier_sender_phone" maxlength="30"
                       inputmode="tel" autocomplete="tel"
                       value="<?= e($old['courier_sender_phone']) ?>">
                <?php show_field_error($errors, 'courier_sender_phone'); ?>
            </div>
            <div class="col-12">
                <label for="courier_sender_address" class="form-label"><?= e(__('courier.settings.sender_address')) ?></label>
                <textarea class="form-control<?= field_invalid($errors, 'courier_sender_address') ?>"
                          id="courier_sender_address" name="courier_sender_address" rows="3" maxlength="500"><?= e($old['courier_sender_address']) ?></textarea>
                <?php show_field_error($errors, 'courier_sender_address'); ?>
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
