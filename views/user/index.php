<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, string> $editErrors */
/** @var int|null $editId */
/** @var list<array<string, mixed>> $users */
/** @var int $actingUserId */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $dbError */

$pageTitle = __('user.manage.title');
$errors = $errors ?? [];
$editErrors = $editErrors ?? [];
$editId = $editId ?? null;
$actingUserId = $actingUserId ?? 0;
$users = $users ?? [];

ob_start();
?>
<div class="user-manage-page">
<h1 class="reception-page-title user-manage-title"><?= e(__('user.manage.title')) ?></h1>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form user-manage-card">
        <h2 class="reception-card-title h6 user-manage-card-title"><?= e(__('user.add.title')) ?></h2>
        <form method="post" action="<?= e(base_url('/users.php')) ?>" class="user-add-form" id="userAddForm"
              data-confirm-message="<?= e(__('user.add.confirm_message')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="row g-2 align-items-start user-add-fields">
                <div class="col-6 col-md-4 col-lg-3">
                    <label for="user_username" class="form-label"><?= e(__('auth.username')) ?></label>
                    <input type="text" class="form-control form-control-sm<?= field_invalid($errors, 'username') ?>"
                           id="user_username" name="username" maxlength="50" required autocomplete="off"
                           pattern="[a-z0-9_]{3,50}"
                           value="<?= e((string) ($_POST['username'] ?? '')) ?>">
                    <?php show_field_error($errors, 'username'); ?>
                </div>
                <div class="col-6 col-md-4 col-lg-3">
                    <label for="user_name" class="form-label"><?= e(__('user.field.display_name')) ?></label>
                    <input type="text" class="form-control form-control-sm<?= field_invalid($errors, 'name') ?>"
                           id="user_name" name="name" maxlength="120" required
                           value="<?= e((string) ($_POST['name'] ?? '')) ?>">
                    <?php show_field_error($errors, 'name'); ?>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="user_role" class="form-label"><?= e(__('user.field.role')) ?></label>
                    <select class="form-select form-select-sm<?= field_invalid($errors, 'role') ?>"
                            id="user_role" name="role" required>
                        <option value=""><?= e(__('user.field.role_choose')) ?></option>
                        <?php foreach (User::ROLES as $role): ?>
                            <option value="<?= e($role) ?>"<?= ($_POST['role'] ?? '') === $role ? ' selected' : '' ?>>
                                <?= e(User::roleLabel($role)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php show_field_error($errors, 'role'); ?>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="user_password" class="form-label"><?= e(__('auth.password')) ?></label>
                    <input type="password" class="form-control form-control-sm<?= field_invalid($errors, 'password') ?>"
                           id="user_password" name="password" minlength="8" required autocomplete="new-password">
                    <?php show_field_error($errors, 'password'); ?>
                </div>
                <div class="col-6 col-md-4 col-lg-2">
                    <label for="user_password_confirm" class="form-label"><?= e(__('user.field.password_confirm')) ?></label>
                    <input type="password" class="form-control form-control-sm<?= field_invalid($errors, 'password_confirm') ?>"
                           id="user_password_confirm" name="password_confirm" minlength="8" required autocomplete="new-password">
                    <?php show_field_error($errors, 'password_confirm'); ?>
                </div>
                <div class="col-12 col-lg-auto user-add-submit-wrap">
                    <label class="form-label d-none d-lg-block" aria-hidden="true">&nbsp;</label>
                    <button type="button" class="btn btn-sm btn-reception-primary text-nowrap confirm-action-trigger"
                            data-confirm-title="<?= e(__('user.add.confirm_title')) ?>"
                            data-confirm="<?= e(__('user.add.confirm_message')) ?>"
                            data-confirm-label="<?= e(__('user.add.submit')) ?>"
                            data-confirm-variant="primary">
                        <?= e(__('user.add.submit')) ?>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card user-manage-card user-manage-list-card">
        <h2 class="reception-card-title h6 user-manage-card-title mb-0">
            <?= e(__('user.list.title', ['count' => count($users)])) ?>
        </h2>

        <?php if ($users === []): ?>
            <p class="text-muted mb-0 mt-2"><?= e(__('user.list.empty')) ?></p>
        <?php else: ?>
            <div class="table-responsive mt-2">
                <table class="table table-hover reception-table mb-0 user-manage-table">
                    <thead>
                    <tr>
                        <th scope="col"><?= e(__('auth.username')) ?></th>
                        <th scope="col"><?= e(__('user.field.display_name')) ?></th>
                        <th scope="col"><?= e(__('user.field.role')) ?></th>
                        <th scope="col"><?= e(__('user.field.status')) ?></th>
                        <th scope="col" class="text-end col-actions"><?= e(__('patient.field.actions')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $user):
                        $userId = (int) $user['id'];
                        $isSelf = $userId === $actingUserId;
                        $isActive = !empty($user['is_active']);
                        $isEditing = $editId === $userId;
                        $rowEditErrors = $isEditing ? $editErrors : [];
                        ?>
                        <tr class="user-manage-row<?= $isEditing ? ' is-editing' : '' ?><?= !$isActive ? ' user-row-inactive' : '' ?>"
                            data-user-id="<?= e((string) $userId) ?>">
                            <td><span class="font-monospace user-username-display"><?= e($user['username']) ?></span></td>
                            <td><span class="user-name-display"><?= e($user['name']) ?></span></td>
                            <td>
                                <span class="user-role-badge user-role-<?= e((string) $user['role']) ?>">
                                    <?= e(User::roleLabel((string) $user['role'])) ?>
                                </span>
                            </td>
                            <td>
                                <span class="user-status-badge user-status-<?= $isActive ? 'active' : 'inactive' ?>">
                                    <?= e($isActive ? __('user.status.active') : __('user.status.inactive')) ?>
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                <div class="user-actions">
                                    <button type="button"
                                            class="patient-action-btn user-edit-trigger"
                                            title="<?= e(__('user.action.edit')) ?>"
                                            aria-label="<?= e(__('user.action.edit') . ': ' . $user['name']) ?>">
                                        <?php require BASE_PATH . '/views/partials/icons/edit.php'; ?>
                                    </button>
                                    <?php if ($isActive): ?>
                                        <form method="post" action="<?= e(base_url('/users.php')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="id" value="<?= e((string) $userId) ?>">
                                            <input type="hidden" name="active" value="0">
                                            <button type="button"
                                                    class="patient-action-btn user-deactivate-trigger confirm-action-trigger<?= $isSelf ? ' disabled' : '' ?>"
                                                    <?= $isSelf ? ' disabled' : '' ?>
                                                    data-confirm-title="<?= e(__('user.deactivate.confirm_title')) ?>"
                                                    data-confirm="<?= e(__('user.deactivate.confirm', ['name' => $user['name']])) ?>"
                                                    data-confirm-label="<?= e(__('user.action.deactivate')) ?>"
                                                    title="<?= e($isSelf ? __('user.error.cannot_deactivate_self') : __('user.action.deactivate')) ?>"
                                                    aria-label="<?= e(__('user.action.deactivate')) ?>">
                                                <?php require BASE_PATH . '/views/partials/icons/cancel.php'; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" action="<?= e(base_url('/users.php')) ?>" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="set_active">
                                            <input type="hidden" name="id" value="<?= e((string) $userId) ?>">
                                            <input type="hidden" name="active" value="1">
                                            <button type="button"
                                                    class="patient-action-btn user-activate-trigger confirm-action-trigger"
                                                    data-confirm-title="<?= e(__('user.activate.confirm_title')) ?>"
                                                    data-confirm="<?= e(__('user.activate.confirm', ['name' => $user['name']])) ?>"
                                                    data-confirm-label="<?= e(__('user.action.activate')) ?>"
                                                    data-confirm-variant="primary"
                                                    title="<?= e(__('user.action.activate')) ?>"
                                                    aria-label="<?= e(__('user.action.activate')) ?>">
                                                <?php require BASE_PATH . '/views/partials/icons/plus.php'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <tr class="user-edit-row<?= $isEditing ? '' : ' d-none' ?>">
                            <td colspan="5">
                                <form method="post" action="<?= e(base_url('/users.php')) ?>" class="user-inline-edit-form">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="id" value="<?= e((string) $userId) ?>">
                                    <p class="small text-muted mb-2">
                                        <?= e(__('user.edit.username_hint', ['username' => $user['username']])) ?>
                                    </p>
                                    <div class="user-inline-edit-grid">
                                        <div>
                                            <label class="form-label small mb-1"><?= e(__('user.field.display_name')) ?></label>
                                            <input type="text"
                                                   class="form-control form-control-sm<?= field_invalid($rowEditErrors, 'name') ?>"
                                                   name="name" maxlength="120" required
                                                   value="<?= e($isEditing ? (string) ($_POST['name'] ?? $user['name']) : (string) $user['name']) ?>">
                                            <?php show_field_error($rowEditErrors, 'name'); ?>
                                        </div>
                                        <div>
                                            <label class="form-label small mb-1"><?= e(__('user.field.role')) ?></label>
                                            <select class="form-select form-select-sm<?= field_invalid($rowEditErrors, 'role') ?>"
                                                    name="role" required<?= $isSelf ? ' disabled' : '' ?>>
                                                <?php foreach (User::ROLES as $role): ?>
                                                    <?php $selectedRole = $isEditing ? (string) ($_POST['role'] ?? $user['role']) : (string) $user['role']; ?>
                                                    <option value="<?= e($role) ?>"<?= $selectedRole === $role ? ' selected' : '' ?>>
                                                        <?= e(User::roleLabel($role)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if ($isSelf): ?>
                                                <input type="hidden" name="role" value="<?= e((string) $user['role']) ?>">
                                            <?php endif; ?>
                                            <?php show_field_error($rowEditErrors, 'role'); ?>
                                        </div>
                                        <div>
                                            <label class="form-label small mb-1"><?= e(__('user.field.status')) ?></label>
                                            <?php
                                            $editActiveValue = $isEditing
                                                ? (string) ($_POST['is_active'] ?? ($isActive ? '1' : '0'))
                                                : ($isActive ? '1' : '0');
                                            ?>
                                            <select class="form-select form-select-sm<?= field_invalid($rowEditErrors, 'is_active') ?>"
                                                    name="is_active"<?= $isSelf ? ' disabled' : '' ?>>
                                                <option value="1"<?= $editActiveValue === '1' ? ' selected' : '' ?>>
                                                    <?= e(__('user.status.active')) ?>
                                                </option>
                                                <option value="0"<?= $editActiveValue === '0' ? ' selected' : '' ?>>
                                                    <?= e(__('user.status.inactive')) ?>
                                                </option>
                                            </select>
                                            <?php if ($isSelf): ?>
                                                <input type="hidden" name="is_active" value="1">
                                            <?php endif; ?>
                                            <?php show_field_error($rowEditErrors, 'is_active'); ?>
                                        </div>
                                        <div>
                                            <label class="form-label small mb-1"><?= e(__('user.field.new_password')) ?></label>
                                            <input type="password" class="form-control form-control-sm<?= field_invalid($rowEditErrors, 'password') ?>"
                                                   name="password" minlength="8" autocomplete="new-password"
                                                   placeholder="<?= e(__('user.field.password_optional')) ?>">
                                            <?php show_field_error($rowEditErrors, 'password'); ?>
                                        </div>
                                        <div>
                                            <label class="form-label small mb-1"><?= e(__('user.field.password_confirm')) ?></label>
                                            <input type="password" class="form-control form-control-sm<?= field_invalid($rowEditErrors, 'password_confirm') ?>"
                                                   name="password_confirm" minlength="8" autocomplete="new-password"
                                                   placeholder="<?= e(__('user.field.password_optional')) ?>">
                                            <?php show_field_error($rowEditErrors, 'password_confirm'); ?>
                                        </div>
                                        <div class="user-inline-edit-actions">
                                            <button type="submit" class="btn btn-sm btn-reception-primary">
                                                <?= e(__('user.edit.submit')) ?>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary user-inline-cancel">
                                                <?= e(__('action.cancel')) ?>
                                            </button>
                                        </div>
                                    </div>
                                    <?php show_field_error($rowEditErrors, '_form'); ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>
</div>
<?php
$pageScripts = ['assets/js/user-add.js', 'assets/js/user-edit-inline.js'];
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
