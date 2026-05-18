<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var list<array{id: int, label: string}> $symptoms */
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
            <div class="row g-2 align-items-start">
                <div class="col-md-8 col-lg-6">
                    <label for="symptom_label" class="form-label"><?= e(__('symptom.field.label')) ?></label>
                    <input type="text" class="form-control<?= field_invalid($errors, 'label') ?>"
                           id="symptom_label" name="label" maxlength="120" required
                           placeholder="<?= e(__('symptom.add.placeholder')) ?>"
                           value="<?= e((string) ($_POST['label'] ?? '')) ?>">
                    <?php show_field_error($errors, 'label'); ?>
                </div>
                <div class="col-md-4 col-lg-auto d-flex align-items-end">
                    <button type="submit" class="btn btn-reception-primary"><?= e(__('symptom.add.submit')) ?></button>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('symptom.list.title')) ?></h2>

        <?php if ($symptoms === []): ?>
            <p class="text-muted mb-0"><?= e(__('symptom.list.empty')) ?></p>
        <?php else: ?>
            <p class="text-muted small mb-3"><?= e(__('symptom.list.preview_hint')) ?></p>
            <div class="symptoms-checkbox-row symptoms-preview-row mb-4" aria-hidden="true">
                <?php foreach ($symptoms as $symptom): ?>
                    <span class="symptom-tag"><?= e($symptom['label']) ?></span>
                <?php endforeach; ?>
            </div>

            <ul class="symptom-manage-list list-unstyled mb-0">
                <?php foreach ($symptoms as $symptom): ?>
                    <li class="symptom-manage-item">
                        <span class="symptom-manage-label"><?= e($symptom['label']) ?></span>
                        <form method="post" action="<?= e(base_url('/symptoms.php')) ?>" class="symptom-remove-form">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="id" value="<?= (int) $symptom['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    title="<?= e(__('symptom.delete.submit')) ?>"
                                    aria-label="<?= e(__('symptom.delete.submit') . ': ' . $symptom['label']) ?>">
                                <?= e(__('symptom.delete.submit')) ?>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
