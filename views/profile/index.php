<?php

declare(strict_types=1);

/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var array<string, string> $errors */
/** @var array{name: string, current_password: string, new_password: string, password_confirm: string} $old */

$errors = $errors ?? [];
$old = $old ?? ['name' => '', 'current_password' => '', 'new_password' => '', 'password_confirm' => ''];

ob_start();
?>
<div class="profile-page">
    <h1 class="reception-page-title mb-4"><?= e(__('profile.title')) ?></h1>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form profile-card">
        <form method="post" action="<?= e(base_url('/profile.php')) ?>" novalidate class="profile-form"
              data-msg-required="<?= e(__('validation.required')) ?>"
              data-confirm-title="<?= e(__('profile.password.confirm_title')) ?>"
              data-confirm-message="<?= e(__('profile.password.confirm_message')) ?>"
              data-confirm-label="<?= e(__('profile.password.confirm_submit')) ?>">
            <?= csrf_field() ?>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
                <h2 class="reception-card-title h6 mb-0"><?= e(__('profile.section.account')) ?></h2>
                <span class="text-muted small"><?= e(__('profile.hint')) ?></span>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="profile_name" class="form-label"><?= e(__('profile.field.full_name')) ?></label>
                    <input type="text" class="form-control<?= field_invalid($errors, 'name') ?>"
                           id="profile_name" name="name" maxlength="120" required
                           value="<?= e($old['name']) ?>">
                    <?php show_field_error($errors, 'name', true); ?>
                </div>
            </div>

            <hr class="my-4">

            <h2 class="reception-card-title h6 mb-3"><?= e(__('profile.section.password')) ?></h2>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="current_password" class="form-label"><?= e(__('profile.field.current_password')) ?></label>
                    <input type="password" class="form-control<?= field_invalid($errors, 'current_password') ?>"
                           id="current_password" name="current_password" autocomplete="current-password">
                    <?php show_field_error($errors, 'current_password'); ?>
                </div>
                <div class="col-md-6"></div>
                <div class="col-md-6">
                    <label for="new_password" class="form-label"><?= e(__('profile.field.new_password')) ?></label>
                    <div class="profile-password-wrap">
                        <input type="password" class="form-control<?= field_invalid($errors, 'new_password') ?>"
                               id="new_password" name="new_password" autocomplete="new-password">
                        <button type="button" class="profile-password-toggle"
                                data-target="#new_password"
                                aria-label="<?= e(__('profile.action.toggle_password')) ?>"
                                title="<?= e(__('profile.action.toggle_password')) ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <?php show_field_error($errors, 'new_password'); ?>
                </div>
                <div class="col-md-6">
                    <label for="password_confirm" class="form-label"><?= e(__('profile.field.confirm_password')) ?></label>
                    <div class="profile-password-wrap">
                        <input type="password" class="form-control<?= field_invalid($errors, 'password_confirm') ?>"
                               id="password_confirm" name="password_confirm" autocomplete="new-password">
                        <button type="button" class="profile-password-toggle"
                                data-target="#password_confirm"
                                aria-label="<?= e(__('profile.action.toggle_password')) ?>"
                                title="<?= e(__('profile.action.toggle_password')) ?>">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    <?php show_field_error($errors, 'password_confirm'); ?>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-reception-primary"><?= e(__('profile.action.save')) ?></button>
            </div>
        </form>
    </section>
</div>
<?php
$content = ob_get_clean();
$pageScripts = ['assets/js/profile.js'];
require BASE_PATH . '/views/layouts/dashboard.php';

