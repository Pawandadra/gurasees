<?php

declare(strict_types=1);

/** @var array<string, mixed> $package */
/** @var string $listUrl */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

$visitId = (int) $package['visit_id'];
$lines = $package['courier_lines'] ?? [];
$courierStatus = (string) ($package['courier_status'] ?? Courier::STATUS_PENDING);
$deliveryMethod = (string) ($package['delivery_method'] ?? Visit::DELIVERY_COURIER);
$deliveryMethodLabel = (string) ($package['delivery_method_label'] ?? '');

ob_start();
?>
<div class="page-header-bar mb-4">
    <?php $url = $listUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0"><?= e(__('courier.view.title')) ?></h1>
    <div class="ms-auto">
        <?php require BASE_PATH . '/views/partials/courier_actions.php'; ?>
    </div>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= e($errorMessage) ?></div>
<?php endif; ?>

<section class="reception-card mb-4 courier-package-details">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('courier.view.package')) ?></h2>
    <div class="row patient-detail-grid g-3">
        <div class="col-md-6">
            <dl class="patient-detail-list mb-0">
                <div class="patient-detail-item">
                    <dt><?= e(__('courier.field.status')) ?></dt>
                    <dd>
                        <span class="courier-status-badge courier-status-<?= e($courierStatus) ?>">
                            <?= e(Courier::statusLabel($courierStatus)) ?>
                        </span>
                    </dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('visit.form.delivery_method')) ?></dt>
                    <dd>
                        <span class="visit-delivery-badge visit-delivery-badge--<?= e($deliveryMethod) ?>">
                            <?= e($deliveryMethodLabel) ?>
                        </span>
                    </dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('visit.field.datetime')) ?></dt>
                    <dd><?= e(Visit::formatVisitedAt((string) $package['visited_at'])) ?></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.id')) ?></dt>
                    <dd><span class="patient-code"><?= e((string) $package['patient_code']) ?></span></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.name')) ?></dt>
                    <dd><?= e((string) $package['patient_name']) ?></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.phone')) ?></dt>
                    <dd><?= e(phone_format_display((string) $package['phone'])) ?></dd>
                </div>
            </dl>
        </div>
        <div class="col-md-6">
            <dl class="patient-detail-list mb-0">
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.delivery_address')) ?></dt>
                    <dd><?= table_cell((string) $package['delivery_display']) ?></dd>
                </div>
                <?php if (trim((string) ($package['notes'] ?? '')) !== ''): ?>
                    <div class="patient-detail-item">
                        <dt><?= e(__('visit.field.notes')) ?></dt>
                        <dd><?= e((string) $package['notes']) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
    </div>
</section>

<section class="reception-card">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('courier.view.medicines')) ?></h2>
    <?php if ($lines === []): ?>
        <p class="text-muted mb-0"><?= table_na() ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table reception-table mb-0">
                <thead>
                <tr>
                    <th scope="col"><?= e(__('medicine.field.name')) ?></th>
                    <th scope="col" class="text-end"><?= e(__('visit.medicine.field.quantity')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($lines as $line): ?>
                    <tr>
                        <td><?= e($line['name']) ?></td>
                        <td class="text-end"><?= (int) $line['quantity'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
