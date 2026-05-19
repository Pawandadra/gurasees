<?php

declare(strict_types=1);

/** @var array{id: int, username: string, name: string, role: string, is_active: int, created_at: string} $profileUser */
/** @var array<string, string> $profileErrors */
/** @var array<string, string> $passwordErrors */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $canChangeRole */

$profileErrors = $profileErrors ?? [];
$passwordErrors = $passwordErrors ?? [];

ob_start();
?>

<style>
    .profile-grid {
        display: grid;
        grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
        gap: 22px;
    }

    .profile-premium-card {
        border: 1px solid rgba(12, 89, 71, 0.12);
        border-radius: 18px;
        background: #ffffff;
        box-shadow: 0 10px 28px rgba(15, 79, 65, 0.08);
        padding: 26px;
    }

    .profile-card-heading {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .profile-card-icon {
        width: 46px;
        height: 46px;
        border-radius: 15px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #0b5d4b;
        background: rgba(11, 93, 75, 0.09);
        border: 1px solid rgba(11, 93, 75, 0.18);
    }

    .profile-card-icon svg {
        width: 24px;
        height: 24px;
    }

    .profile-title-small {
        margin: 0;
        color: #0b5d4b;
        font-weight: 800;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        font-size: 15px;
    }

    .profile-subtitle {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .profile-readonly-box {
        height: 43px;
        border-radius: 8px;
        border: 1px solid #dfe7e4;
        background: #f7faf8;
        color: #52635f;
        display: flex;
        align-items: center;
        padding: 0 14px;
        font-weight: 600;
    }

    .profile-role-pill {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        padding: 6px 12px;
        font-size: 13px;
        color: #0b5d4b;
        background: rgba(11, 93, 75, 0.09);
        border: 1px solid rgba(11, 93, 75, 0.16);
        font-weight: 700;
    }

    @media (max-width: 992px) {
        .profile-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<h1 class="reception-page-title mb-4"><?= e(__('nav.profile')) ?></h1>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if ($errorMessage !== null): ?>
    <div class="alert alert-danger"><?= e($errorMessage) ?></div>
<?php endif; ?>

<div class="profile-grid">
    <section class="profile-premium-card">
        <div class="profile-card-heading">
            <div class="profile-card-icon">
                <?php require BASE_PATH . '/views/partials/icons/profile.php'; ?>
            </div>

            <div>
                <h2 class="profile-title-small"><?= e(__('profile.details.title')) ?></h2>
                <p class="profile-subtitle"><?= e(__('profile.details.hint')) ?></p>
            </div>
        </div>

        <?php if (!empty($profileErrors['user'])): ?>
            <div class="alert alert-danger"><?= e($profileErrors['user']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('/profile.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="update_profile">

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label"><?= e(__('users.field.username')) ?></label>

                    <div class="profile-readonly-box">
                        <?= e($profileUser['username']) ?>
                    </div>

                    <input type="hidden" name="username" value="<?= e($profileUser['username']) ?>">
                </div>

                <div class="col-md-6">
                    <label class="form-label"><?= e(__('users.field.name')) ?></label>

                    <input type="text"
                           name="name"
                           maxlength="120"
                           class="form-control<?= field_invalid($profileErrors, 'name') ?>"
                           value="<?= e((string) ($_POST['name'] ?? $profileUser['name'])) ?>">

                    <?php show_field_error($profileErrors, 'name'); ?>
                </div>

                
                <div class="col-12">
                    <button type="submit" class="btn btn-reception-primary">
                        <?= e(__('profile.action.update_profile')) ?>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="profile-premium-card">
        <div class="profile-card-heading">
            <div class="profile-card-icon">
                <svg xmlns="http://www.w3.org/2000/svg"
                     viewBox="0 0 24 24"
                     fill="none"
                     stroke="currentColor"
                     stroke-width="2"
                     stroke-linecap="round"
                     stroke-linejoin="round"
                     aria-hidden="true">
                    <rect x="4" y="11" width="16" height="9" rx="2"></rect>
                    <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                    <path d="M12 15v2"></path>
                </svg>
            </div>

            <div>
                <h2 class="profile-title-small"><?= e(__('profile.password.title')) ?></h2>
                <p class="profile-subtitle"><?= e(__('profile.password.hint')) ?></p>
            </div>
        </div>

        <?php if (!empty($passwordErrors['user'])): ?>
            <div class="alert alert-danger"><?= e($passwordErrors['user']) ?></div>
        <?php endif; ?>

        <form method="post" action="<?= e(base_url('/profile.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="change_password">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label"><?= e(__('profile.field.old_password')) ?></label>

                    <input type="password"
                           name="old_password"
                           class="form-control<?= field_invalid($passwordErrors, 'old_password') ?>">

                    <?php show_field_error($passwordErrors, 'old_password'); ?>
                </div>

                <div class="col-12">
                    <label class="form-label"><?= e(__('users.field.new_password')) ?></label>

                    <input type="password"
                           name="new_password"
                           class="form-control<?= field_invalid($passwordErrors, 'new_password') ?>">

                    <?php show_field_error($passwordErrors, 'new_password'); ?>
                </div>

                <div class="col-12">
                    <label class="form-label"><?= e(__('users.field.confirm_password')) ?></label>

                    <input type="password"
                           name="new_password_confirm"
                           class="form-control<?= field_invalid($passwordErrors, 'new_password_confirm') ?>">

                    <?php show_field_error($passwordErrors, 'new_password_confirm'); ?>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-outline-secondary">
                        <?= e(__('profile.action.change_password')) ?>
                    </button>
                </div>
            </div>
        </form>
    </section>
</div>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';