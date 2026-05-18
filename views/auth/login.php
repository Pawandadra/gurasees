<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $old */

$errors = $errors ?? [];
$old = $old ?? ['username' => ''];

ob_start();
?>
<div class="auth-card">
    <div class="text-center mb-4">
        <h1 class="auth-title"><?= e(__('app.name')) ?></h1>
        <p class="text-muted mb-0"><?= e(__('auth.login_title')) ?></p>
    </div>

    <?php if (isset($errors['_form'])): ?>
        <div class="alert alert-danger"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <?php if (login_rate_limited()): ?>
        <div class="alert alert-warning"><?= e(__('auth.error.locked', ['seconds' => (string) login_rate_limit_seconds()])) ?></div>
    <?php else: ?>

    <form method="post" action="<?= e(base_url('/login.php')) ?>" class="auth-form" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
            <label for="username" class="form-label"><?= e(__('auth.username')) ?></label>
            <input type="text" class="form-control" id="username" name="username"
                   value="<?= e($old['username']) ?>" required autocomplete="username" maxlength="50" autofocus>
        </div>

        <div class="mb-3">
            <label for="password" class="form-label"><?= e(__('auth.password')) ?></label>
            <input type="password" class="form-control" id="password" name="password"
                   required autocomplete="current-password" maxlength="128">
        </div>

        <div class="mb-3">
            <label for="captcha" class="form-label"><?= e(__('auth.captcha')) ?></label>
            <div class="d-flex align-items-center gap-2 mb-2">
                <img src="<?= e(base_url('/captcha.php')) ?>?t=<?= time() ?>" alt="<?= e(__('auth.captcha')) ?>"
                     class="captcha-img" width="160" height="50" id="captchaImg"
                     data-captcha-url="<?= e(base_url('/captcha.php')) ?>">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="captchaRefresh"
                        title="<?= e(__('auth.captcha_refresh')) ?>">↻</button>
            </div>
            <input type="text" class="form-control<?= isset($errors['captcha']) ? ' is-invalid' : '' ?>"
                   id="captcha" name="captcha" required maxlength="10" autocomplete="off"
                   placeholder="<?= e(__('auth.captcha_placeholder')) ?>">
            <?php if (isset($errors['captcha'])): ?>
                <div class="invalid-feedback"><?= e($errors['captcha']) ?></div>
            <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-reception-primary w-100"><?= e(__('auth.login_submit')) ?></button>
    </form>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/auth.php';
