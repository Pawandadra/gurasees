<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<int, array<string, string>> $editErrors */
/** @var list<array{id: int, username: string, name: string, role: string, is_active: int, created_at: string}> $users */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $dbError */
/** @var array{id: int, role: string, name: string}|null $currentUser */
/** @var int $editId */

$errors = $errors ?? [];
$editErrors = $editErrors ?? [];
$currentUserId = (int) ($currentUser['id'] ?? 0);
$editId = (int) ($editId ?? 0);

if (!function_exists('users_local_date')) {
    function users_local_date(string $date): string
    {
        $timestamp = strtotime($date);

        if ($timestamp === false) {
            return $date;
        }

        $monthKey = 'month.' . date('M', $timestamp);

        return date('d', $timestamp) . ' ' . __($monthKey) . ' ' . date('Y', $timestamp);
    }
}

if (!function_exists('users_display_name')) {
    function users_display_name(array $user): string
    {
        $username = strtolower(trim((string) $user['username']));
        $name = strtolower(trim((string) $user['name']));

        if ($username === 'admin' || $name === 'administrator') {
            return __('users.seed.admin_name');
        }

        if ($username === 'manager' || $name === 'clinic manager') {
            return __('users.seed.manager_name');
        }

        if ($username === 'reception' || $name === 'reception desk') {
            return __('users.seed.reception_name');
        }

        return (string) $user['name'];
    }
}

ob_start();
?>

<style>
    .users-action-column {
        min-width: 115px;
    }

    .users-action-wrap {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 10px;
        flex-wrap: nowrap;
    }

    .users-action-wrap form {
        margin: 0;
    }

    .users-icon-btn {
    width: 42px;
    height: 42px;
    min-width: 42px;
    border-radius: 12px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.18s ease;
    border-width: 1px;
}

.users-icon-btn svg {
    width: 18px;
    height: 18px;
    stroke-width: 2.2;
}

.users-edit-icon-btn {
    color: #0b5d4b;
    background: rgba(11, 93, 75, 0.08);
    border-color: rgba(11, 93, 75, 0.22);
}

.users-edit-icon-btn:hover {
    color: #ffffff;
    background: #0b5d4b;
    border-color: #0b5d4b;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(11, 93, 75, 0.22);
}

.users-delete-icon-btn {
    color: #dc3545;
    background: rgba(220, 53, 69, 0.07);
    border-color: rgba(220, 53, 69, 0.28);
}

.users-delete-icon-btn:hover {
    color: #ffffff;
    background: #dc3545;
    border-color: #dc3545;
    transform: translateY(-1px);
    box-shadow: 0 8px 18px rgba(220, 53, 69, 0.22);
}

.users-icon-btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}

    .users-equal-btn {
        width: 100px;
        min-width: 100px;
        height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        border-radius: 8px;
        line-height: 1.2;
        white-space: nowrap;
        font-size: 14px;
        font-weight: 500;
    }

    .users-edit-row td {
        background: #f7faf8;
        border-top: 0;
    }

    .users-edit-box {
        border: 1px solid rgba(12, 89, 71, 0.18);
        border-radius: 14px;
        padding: 18px;
        background: #ffffff;
        box-shadow: 0 8px 22px rgba(15, 79, 65, 0.08);
    }

    .users-edit-title {
        color: #0b5d4b;
        font-weight: 700;
        letter-spacing: 0.03em;
    }

    .reception-table td,
    .reception-table th {
        vertical-align: middle;
    }

    @media (max-width: 1200px) {
        .users-action-wrap {
            justify-content: flex-start;
            flex-wrap: wrap;
        }

        .users-equal-btn {
            width: 105px;
            min-width: 105px;
        }
    }
</style>

<h1 class="reception-page-title mb-4"><?= e(__('nav.users')) ?></h1>

<?php if ($dbError): ?>

    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>

<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('users.create.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('users.create.hint')) ?></p>

        <form method="post" action="<?= e(base_url('/users.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <input type="hidden" name="action" value="add">

            <div class="row g-3 align-items-end">
                <div class="col-md-4 col-lg-2">
                    <label for="username" class="form-label"><?= e(__('users.field.username')) ?></label>

                    <input type="text"
                           class="form-control<?= field_invalid($errors, 'username') ?>"
                           id="username"
                           name="username"
                           maxlength="50"
                           value="<?= e((string) ($_POST['username'] ?? '')) ?>">

                    <?php show_field_error($errors, 'username'); ?>
                </div>

                <div class="col-md-4 col-lg-3">
                    <label for="name" class="form-label"><?= e(__('users.field.name')) ?></label>

                    <input type="text"
                           class="form-control<?= field_invalid($errors, 'name') ?>"
                           id="name"
                           name="name"
                           maxlength="120"
                           value="<?= e((string) ($_POST['name'] ?? '')) ?>">

                    <?php show_field_error($errors, 'name'); ?>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label for="role" class="form-label"><?= e(__('users.field.role')) ?></label>

                    <?php $postedRole = (string) ($_POST['role'] ?? 'receptionist'); ?>

                    <select class="form-select<?= field_invalid($errors, 'role') ?>"
                            id="role"
                            name="role">
                        <option value="admin" <?= $postedRole === 'admin' ? 'selected' : '' ?>>
                            <?= e(__('role.admin')) ?>
                        </option>

                        <option value="manager" <?= $postedRole === 'manager' ? 'selected' : '' ?>>
                            <?= e(__('role.manager')) ?>
                        </option>

                        <option value="receptionist" <?= $postedRole === 'receptionist' ? 'selected' : '' ?>>
                            <?= e(__('role.receptionist')) ?>
                        </option>
                    </select>

                    <?php show_field_error($errors, 'role'); ?>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label for="password" class="form-label"><?= e(__('users.field.password')) ?></label>

                    <input type="password"
                           class="form-control<?= field_invalid($errors, 'password') ?>"
                           id="password"
                           name="password">

                    <?php show_field_error($errors, 'password'); ?>
                </div>

                <div class="col-md-4 col-lg-2">
                    <label for="password_confirm" class="form-label">
                        <?= e(__('users.field.confirm_password')) ?>
                    </label>

                    <input type="password"
                           class="form-control<?= field_invalid($errors, 'password_confirm') ?>"
                           id="password_confirm"
                           name="password_confirm">

                    <?php show_field_error($errors, 'password_confirm'); ?>
                </div>

                <div class="col-md-4 col-lg-1">
                    <button type="submit" class="btn btn-reception-primary w-100">
                        <?= e(__('users.action.add')) ?>
                    </button>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('users.list.title')) ?></h2>

            <p class="text-muted small mb-0">
                <?= e(__('users.list.count', ['count' => count($users)])) ?>
            </p>
        </div>

        <?php if ($users === []): ?>

            <p class="text-muted mb-0"><?= e(__('users.list.empty')) ?></p>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover reception-table mb-0 align-middle">
                    <thead>
                    <tr>
                        <th><?= e(__('users.table.id')) ?></th>
                        <th><?= e(__('users.table.username')) ?></th>
                        <th><?= e(__('users.table.name')) ?></th>
                        <th><?= e(__('users.table.role')) ?></th>
                        <th><?= e(__('users.table.status')) ?></th>
                        <th><?= e(__('users.table.created')) ?></th>
                        <th class="text-end users-action-column">
                            <?= e(__('users.table.action')) ?>
                        </th>
                    </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($users as $user): ?>
                        <?php
                        $rowEditErrors = $editErrors[$user['id']] ?? [];
                        $isEditing = $editId === (int) $user['id'];
                        ?>

                        <tr>
                            <td><?= e((string) $user['id']) ?></td>

                            <td>
                                <strong><?= e($user['username']) ?></strong>
                            </td>

                            <td><?= e(users_display_name($user)) ?></td>

                            <td><?= e(__('role.' . $user['role'])) ?></td>

                            <td>
                                <?php if ($user['is_active'] === 1): ?>
                                    <span class="badge text-bg-success">
                                        <?= e(__('users.status.active')) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge text-bg-secondary">
                                        <?= e(__('users.status.inactive')) ?>
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td><?= e(users_local_date($user['created_at'])) ?></td>

                            <td class="text-end users-action-column">
                                <div class="users-action-wrap">
                                    <a href="<?= e(base_url('/users.php?edit_id=' . $user['id'])) ?>"
                                       class="users-icon-btn users-edit-icon-btn"
                                       title="<?= e(__('users.action.edit')) ?>"
                                       aria-label="<?= e(__('users.action.edit')) ?>">

                                        <svg viewBox="0 0 24 24"
                                             fill="none"
                                             stroke="currentColor"
                                             stroke-linecap="round"
                                             stroke-linejoin="round"
                                             aria-hidden="true">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </a>

                                    <form method="post"
                                          action="<?= e(base_url('/users.php')) ?>"
                                          onsubmit="return confirm('<?= e(__('users.confirm.delete')) ?>');">
                                        <?= csrf_field() ?>

                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">

                                        <button type="submit"
                                                class="users-icon-btn users-delete-icon-btn"
                                                title="<?= e(__('users.action.delete')) ?>"
                                                aria-label="<?= e(__('users.action.delete')) ?>"
                                                <?= $currentUserId === $user['id'] ? 'disabled' : '' ?>>

                                            <svg viewBox="0 0 24 24"
                                                 fill="none"
                                                 stroke="currentColor"
                                                 stroke-linecap="round"
                                                 stroke-linejoin="round"
                                                 aria-hidden="true">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4h8v2"></path>
                                                <path d="M19 6l-1 14H6L5 6"></path>
                                                <path d="M10 11v5"></path>
                                                <path d="M14 11v5"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <?php if ($isEditing): ?>
                            <tr class="users-edit-row">
                                <td colspan="7">
                                    <div class="users-edit-box">
                                        <h3 class="h6 mb-3 users-edit-title">
                                            <?= e(__('users.edit.title')) ?>: <?= e($user['username']) ?>
                                        </h3>

                                        <?php if (!empty($rowEditErrors['user'])): ?>
                                            <div class="alert alert-danger">
                                                <?= e($rowEditErrors['user']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <form method="post" action="<?= e(base_url('/users.php?edit_id=' . $user['id'])) ?>" novalidate>
                                            <?= csrf_field() ?>

                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="id" value="<?= e((string) $user['id']) ?>">

                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-4 col-lg-2">
                                                    <label class="form-label"><?= e(__('users.field.username')) ?></label>
                                                    <input type="text"
                                                           name="username"
                                                           maxlength="50"
                                                           class="form-control<?= field_invalid($rowEditErrors, 'username') ?>"
                                                           value="<?= e((string) ($_POST['username'] ?? $user['username'])) ?>">
                                                    <?php show_field_error($rowEditErrors, 'username'); ?>
                                                </div>

                                                <div class="col-md-4 col-lg-3">
                                                    <label class="form-label"><?= e(__('users.field.name')) ?></label>
                                                    <input type="text"
                                                           name="name"
                                                           maxlength="120"
                                                           class="form-control<?= field_invalid($rowEditErrors, 'name') ?>"
                                                           value="<?= e((string) ($_POST['name'] ?? $user['name'])) ?>">
                                                    <?php show_field_error($rowEditErrors, 'name'); ?>
                                                </div>

                                                <div class="col-md-4 col-lg-2">
                                                    <label class="form-label"><?= e(__('users.field.role')) ?></label>

                                                    <?php $editRole = (string) ($_POST['role'] ?? $user['role']); ?>

                                                    <select name="role"
                                                            class="form-select<?= field_invalid($rowEditErrors, 'role') ?>"
                                                            <?= $currentUserId === $user['id'] ? 'disabled' : '' ?>>
                                                        <option value="admin" <?= $editRole === 'admin' ? 'selected' : '' ?>>
                                                            <?= e(__('role.admin')) ?>
                                                        </option>

                                                        <option value="manager" <?= $editRole === 'manager' ? 'selected' : '' ?>>
                                                            <?= e(__('role.manager')) ?>
                                                        </option>

                                                        <option value="receptionist" <?= $editRole === 'receptionist' ? 'selected' : '' ?>>
                                                            <?= e(__('role.receptionist')) ?>
                                                        </option>
                                                    </select>

                                                    <?php if ($currentUserId === $user['id']): ?>
                                                        <input type="hidden" name="role" value="<?= e($user['role']) ?>">
                                                    <?php endif; ?>

                                                    <?php show_field_error($rowEditErrors, 'role'); ?>
                                                </div>

                                                <div class="col-md-4 col-lg-2">
                                                    <label class="form-label"><?= e(__('users.field.status')) ?></label>

                                                    <?php $editActive = (string) ($_POST['is_active'] ?? $user['is_active']); ?>

                                                    <select name="is_active"
                                                            class="form-select"
                                                            <?= $currentUserId === $user['id'] ? 'disabled' : '' ?>>
                                                        <option value="1" <?= $editActive === '1' ? 'selected' : '' ?>>
                                                            <?= e(__('users.status.active')) ?>
                                                        </option>

                                                        <option value="0" <?= $editActive === '0' ? 'selected' : '' ?>>
                                                            <?= e(__('users.status.inactive')) ?>
                                                        </option>
                                                    </select>

                                                    <?php if ($currentUserId === $user['id']): ?>
                                                        <input type="hidden" name="is_active" value="1">
                                                    <?php endif; ?>
                                                </div>

                                                <div class="col-md-4 col-lg-2">
                                                    <label class="form-label"><?= e(__('users.field.new_password')) ?></label>
                                                    <input type="password"
                                                           name="password"
                                                           class="form-control<?= field_invalid($rowEditErrors, 'password') ?>"
                                                           placeholder="<?= e(__('users.placeholder.optional')) ?>">
                                                    <?php show_field_error($rowEditErrors, 'password'); ?>
                                                </div>

                                                <div class="col-md-4 col-lg-2">
                                                    <label class="form-label"><?= e(__('users.field.confirm_password')) ?></label>
                                                    <input type="password"
                                                           name="password_confirm"
                                                           class="form-control<?= field_invalid($rowEditErrors, 'password_confirm') ?>"
                                                           placeholder="<?= e(__('users.placeholder.optional')) ?>">
                                                    <?php show_field_error($rowEditErrors, 'password_confirm'); ?>
                                                </div>

                                                <div class="col-md-4 col-lg-2 d-flex gap-2">
                                                    <button type="submit" class="btn btn-reception-primary users-equal-btn">
                                                        <?= e(__('users.action.update')) ?>
                                                    </button>

                                                    <a href="<?= e(base_url('/users.php')) ?>"
                                                       class="btn btn-outline-secondary users-equal-btn">
                                                        <?= e(__('users.action.cancel')) ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>

                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>
    </section>

<?php endif; ?>

<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';