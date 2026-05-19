<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $courierRows */
/** @var bool $dbError */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('courier.list.title')) ?></h1>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success reception-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('courier.list.pending', ['count' => count($courierRows)])) ?></h2>
        </div>

        <?php if ($courierRows === []): ?>
            <p class="text-muted mb-0"><?= e(__('courier.list.empty')) ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover reception-table mb-0 courier-pending-table">
                    <thead>
                    <tr>
                        <th scope="col"><?= e(__('visit.field.datetime')) ?></th>
                        <th scope="col"><?= e(__('patient.field.id')) ?></th>
                        <th scope="col"><?= e(__('patient.field.name')) ?></th>
                        <th scope="col"><?= e(__('patient.field.phone')) ?></th>
                        <th scope="col"><?= e(__('patient.field.delivery_address')) ?></th>
                        <th scope="col"><?= e(__('courier.field.medicines')) ?></th>
                        <th scope="col" class="col-actions"><?= e(__('patient.field.actions')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($courierRows as $row): ?>
                        <?php $lines = $row['courier_lines'] ?? []; ?>
                        <tr>
                            <td class="text-nowrap"><?= e(Visit::formatVisitedAt((string) $row['visited_at'])) ?></td>
                            <td><span class="patient-code"><?= e((string) $row['patient_code']) ?></span></td>
                            <td><?= e((string) $row['patient_name']) ?></td>
                            <td class="text-nowrap"><?= e(phone_format_display((string) $row['phone'])) ?></td>
                            <td class="courier-delivery-address"><?= table_cell((string) $row['delivery_display']) ?></td>
                            <td class="small visit-history-medicines"><?= table_cell(Courier::formatMedicineSummary($lines)) ?></td>
                            <td class="col-actions">
                                <form method="post" action="<?= e(base_url('/courier.php')) ?>" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="dispatch">
                                    <input type="hidden" name="visit_id" value="<?= (int) $row['visit_id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-reception-primary"
                                            onclick="return confirm('<?= e(__('courier.dispatch.confirm')) ?>')">
                                        <?= e(__('courier.dispatch.submit')) ?>
                                    </button>
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
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
