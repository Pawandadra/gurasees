<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var string $sort */
/** @var string $dir */

$pageTitle = __('reception.dashboard.title');
$errors = $errors ?? [];
$old = $old ?? patient_form_defaults();
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$phoneIso = (string) ($old['phone_iso'] ?? 'IN');
$phoneLocal = (string) ($old['phone_local'] ?? '');
$sort = $sort ?? 'date';
$dir = $dir ?? 'desc';
$showPhonePlaceholder = true;

$recentColumns = [
    'id' => __('patient.field.id'),
    'name' => __('patient.field.name'),
    'age' => __('patient.field.age'),
    'gender' => __('patient.field.gender'),
    'phone' => __('patient.field.phone'),
    'address' => __('patient.field.address'),
    'date' => __('patient.field.date'),
];

try {
    $recentPatients = Patient::recent(8, $sort, $dir);
    $dbError = false;
} catch (Throwable) {
    $recentPatients = [];
    $dbError = true;
}

ob_start();
?>
<h1 class="reception-page-title mb-4"><?= e(__('reception.dashboard.title')) ?></h1>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success reception-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger"><?= e($errorMessage) ?></div>
    <?php endif; ?>

    <?php if (isset($errors['_form'])): ?>
        <div class="alert alert-danger"><?= e($errors['_form']) ?></div>
    <?php endif; ?>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('patient.register.title')) ?></h2>

        <form method="post" action="<?= e(base_url('/dashboard.php')) ?>" novalidate>
            <?= csrf_field() ?>

            <?php require BASE_PATH . '/views/partials/patient_form_row1.php'; ?>
            <?php require BASE_PATH . '/views/partials/patient_address_row.php'; ?>
            <?php
            if (!class_exists('PaymentSettings', false)) {
                load_model('PaymentSettings');
            }
            if (PaymentSettings::isEnabled()) {
                require BASE_PATH . '/views/partials/patient_payment_row.php';
            }
            ?>
            <?php require BASE_PATH . '/views/partials/patient_symptoms_fields.php'; ?>

            <div class="mt-3">
                <button type="submit" class="btn btn-reception-primary"><?= e(__('patient.register.submit')) ?></button>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('reception.dashboard.recent')) ?></h2>
        <?php if ($recentPatients === []): ?>
            <p class="text-muted mb-0"><?= e(__('reception.dashboard.empty')) ?></p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover reception-table reception-table-sortable mb-0">
                    <thead>
                    <tr>
                        <?php foreach ($recentColumns as $colKey => $colLabel): ?>
                            <th scope="col"<?= patient_sort_th_attr($colKey, $sort, $dir) ?><?= $colKey === 'address' ? ' class="col-address"' : '' ?>>
                                <a href="<?= e(patient_sort_url($colKey, $sort, $dir)) ?>"
                                   class="reception-sort-link<?= $sort === $colKey ? ' active' : '' ?>"
                                   title="<?= e(__('reception.sort.sort_by', ['column' => $colLabel])) ?>">
                                    <?= e($colLabel) ?>
                                    <?php if ($sort === $colKey): ?>
                                        <span class="reception-sort-icon" aria-hidden="true"><?= $dir === 'asc' ? '▲' : '▼' ?></span>
                                    <?php endif; ?>
                                </a>
                            </th>
                        <?php endforeach; ?>
                        <th scope="col" class="col-actions"><?= e(__('patient.field.actions')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentPatients as $row): ?>
                        <tr>
                            <td><span class="patient-code"><?= e($row['patient_code']) ?></span></td>
                            <td><?= e($row['name']) ?></td>
                            <td><?= e((string) $row['age']) ?></td>
                            <td><?= e(Patient::genderLabel((string) $row['gender'])) ?></td>
                            <td><?= e(phone_format_display((string) $row['phone'])) ?></td>
                            <td class="col-address" title="<?= e((string) $row['address']) ?>"><?= e((string) $row['address']) ?></td>
                            <td><?= e(Patient::formatRegisteredAt((string) $row['created_at'])) ?></td>
                            <td class="col-actions">
                                <?php
                                $patientCode = (string) $row['patient_code'];
                                require BASE_PATH . '/views/partials/patient_actions.php';
                                ?>
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
$activeNav = 'dashboard';
load_model('PaymentSettings');
$pageScripts = ['assets/js/phone-country.js', 'assets/js/delivery-address.js'];
if (PaymentSettings::isEnabled()) {
    $pageScripts[] = 'assets/js/payment-fields.js';
}
require BASE_PATH . '/views/layouts/dashboard.php';
