<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var list<array{id: int, label: string, in_use: bool}> $symptoms */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var bool $dbError */

$pageTitle = __('symptom.manage.title');
$errors = $errors ?? [];

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('symptom.manage.title')) ?></h1>

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
        <h2 class="reception-card-title h6 mb-3"><?= e(__('symptom.add.title')) ?></h2>
        <p class="text-muted small mb-3"><?= e(__('symptom.add.hint')) ?></p>

        <form method="post" action="<?= e(base_url('/symptoms.php')) ?>" class="symptom-add-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <div class="row g-2 align-items-end symptom-add-fields">
                <div class="col-md-8 col-lg-6">
                    <label for="symptom_label" class="form-label"><?= e(__('symptom.field.label')) ?></label>
                    <input type="text" class="form-control<?= field_invalid($errors, 'label') ?>"
                           id="symptom_label" name="label" maxlength="120" required
                           placeholder="<?= e(__('symptom.add.placeholder')) ?>"
                           value="<?= e((string) ($_POST['label'] ?? '')) ?>">
                    <?php show_field_error($errors, 'label'); ?>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-reception-primary text-nowrap"><?= e(__('symptom.add.submit')) ?></button>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('symptom.list.title')) ?></h2>

        <?php if ($symptoms === []): ?>
            <p class="text-muted mb-0"><?= e(__('symptom.list.empty')) ?></p>
        <?php else: ?>
            <ul class="symptom-manage-list list-unstyled mb-0">
                <?php foreach ($symptoms as $symptom): ?>
                    <li class="symptom-manage-item">
                        <span class="symptom-manage-label"><?= e($symptom['label']) ?></span>
                        <?php if (!empty($symptom['in_use'])): ?>
                            <span class="symptom-in-use-hint text-muted small"
                                  title="<?= e(__('symptom.delete.in_use_hint')) ?>">
                                <?= e(__('symptom.delete.in_use_hint')) ?>
                            </span>
                        <?php else: ?>
                            <form method="post" action="<?= e(base_url('/symptoms.php')) ?>" class="symptom-remove-form patient-action-delete-form">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="id" value="<?= (int) $symptom['id'] ?>">
                                <button type="button"
                                        class="patient-action-btn patient-action-delete confirm-action-trigger"
                                        data-confirm-title="<?= e(__('symptom.delete.confirm_title')) ?>"
                                        data-confirm="<?= e(__('symptom.delete.confirm', ['label' => $symptom['label']])) ?>"
                                        data-confirm-label="<?= e(__('symptom.action.remove')) ?>"
                                        title="<?= e(__('symptom.action.remove')) ?>"
                                        aria-label="<?= e(__('symptom.action.remove') . ': ' . $symptom['label']) ?>">
                                    <?php require BASE_PATH . '/views/partials/icons/delete.php'; ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
