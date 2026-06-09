<?php

declare(strict_types=1);

/** @var array<string, mixed> $patient */
/** @var list<string> $symptomLabels */
/** @var list<array<string, mixed>> $profileHistory */
/** @var list<array<string, mixed>> $visits */
/** @var array<string, string> $visitErrors */
/** @var array<string, string> $visitOld */
/** @var string|null $successMessage */
/** @var string $code */
/** @var string $sort */
/** @var string $dir */
/** @var string $return */
/** @var array{q: string, gender: string, page: int} $listFilters */
/** @var float $totalBalance */

$return = $return ?? 'dashboard';
$totalBalance = (float) ($totalBalance ?? 0);
$listFilters = $return === 'visits'
    ? ($listFilters ?? visit_list_filters_from_request())
    : ($listFilters ?? patient_list_filters_from_request());
$backUrl = patient_return_url($return, $sort, $dir, $listFilters);
$actionExtra = match ($return) {
    'patients' => patient_build_list_query($sort, $dir, patient_list_query_filters($listFilters)),
    'visits' => patient_build_list_query($sort, $dir, visit_list_query_filters($listFilters)),
    default => [],
};
$editUrl = base_url('/patient_edit.php?' . http_build_query(['code' => $code]) . '&' . patient_action_query($sort, $dir, $actionExtra));
$visitErrors = $visitErrors ?? [];
$visitOld = $visitOld ?? ['visited_at' => '', 'notes' => ''];
$visits = $visits ?? [];
$profileHistory = $profileHistory ?? [];
$successMessage = $successMessage ?? null;
$errorMessage = $errorMessage ?? null;
$editVisitId = $editVisitId ?? 0;

ob_start();
?>
<div class="patient-view-page">
<div class="page-header-bar">
    <?php $url = $backUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0"><?= e(__('patient.view.title')) ?></h1>
    <div class="page-header-actions ms-auto d-flex align-items-center gap-2 flex-shrink-0">
        <div class="patient-view-total-balance<?= $totalBalance > 0 ? ' patient-view-total-balance--due' : '' ?>">
            <span class="patient-view-total-balance-label"><?= e(__('patient.view.total_balance')) ?></span>
            <span class="patient-view-total-balance-value">
                <?= e(PaymentSettings::formatAmountDisplay($totalBalance)) ?>
            </span>
        </div>
        <a href="<?= e($editUrl) ?>" class="btn btn-reception-primary btn-sm"><?= e(__('patient.action.edit')) ?></a>
    </div>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
    <div class="alert alert-danger"><?= e($errorMessage) ?></div>
<?php endif; ?>

<section class="reception-card patient-profile-card">
    <div class="row patient-detail-grid g-2 mb-0">
        <div class="col-md-6">
            <dl class="patient-detail-list mb-0">
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.id')) ?></dt>
                    <dd><span class="patient-code"><?= e((string) $patient['patient_code']) ?></span></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.name')) ?></dt>
                    <dd><?= e((string) $patient['name']) ?></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.age')) ?></dt>
                    <dd><?= e((string) $patient['age']) ?></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.gender')) ?></dt>
                    <dd><?= e(Patient::genderLabel((string) $patient['gender'])) ?></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.phone')) ?></dt>
                    <dd><?= e(phone_format_display((string) $patient['phone'])) ?></dd>
                </div>
                <?php if (trim((string) ($patient['additional_phone'] ?? '')) !== ''): ?>
                    <div class="patient-detail-item">
                        <dt><?= e(__('patient.field.additional_phone')) ?></dt>
                        <dd><?= e(phone_format_display((string) $patient['additional_phone'])) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </div>
        <div class="col-md-6">
            <dl class="patient-detail-list mb-0">
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.address')) ?></dt>
                    <dd><?= e((string) $patient['address']) ?></dd>
                </div>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.delivery_address')) ?></dt>
                    <dd><?= e(Patient::formatDeliveryAddress((string) $patient['address'], $patient['delivery_address'] ?? null)) ?></dd>
                </div>
                <?php if ($symptomLabels !== []): ?>
                    <div class="patient-detail-item patient-symptoms-item">
                        <dt><?= e(__('patient.field.symptoms')) ?></dt>
                        <dd>
                            <div class="patient-symptom-capsules" role="list">
                                <?php foreach ($symptomLabels as $symptomLabel): ?>
                                    <span class="patient-symptom-capsule" role="listitem"><?= e($symptomLabel) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </dd>
                    </div>
                <?php endif; ?>
                <?php if (trim((string) ($patient['remarks'] ?? '')) !== ''): ?>
                    <div class="patient-detail-item patient-remarks-item">
                        <dt><?= e(__('patient.field.remarks')) ?></dt>
                        <dd><?= e((string) $patient['remarks']) ?></dd>
                    </div>
                <?php endif; ?>
                <?php $paymentSummary = Patient::formatPaymentSummary($patient); ?>
                <?php if ($paymentSummary !== null): ?>
                    <div class="patient-detail-item">
                        <dt><?= e(__('patient.field.payment')) ?></dt>
                        <dd><?= e($paymentSummary) ?></dd>
                    </div>
                <?php endif; ?>
                <div class="patient-detail-item">
                    <dt><?= e(__('patient.field.date')) ?></dt>
                    <dd><?= e(date('d M Y', strtotime((string) $patient['created_at']))) ?></dd>
                </div>
            </dl>
        </div>
    </div>
</section>

<section class="reception-card visit-records-card">
    <div class="visit-records-card-head d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="reception-card-title h6 mb-0"><?= e(__('visit.records.title')) ?></h2>
        <button type="button" class="btn btn-reception-primary btn-sm" id="patientNewVisitBtn"
                data-bs-toggle="modal" data-bs-target="#patientVisitModal">
            <?= e(__('visit.add.new_button')) ?>
        </button>
    </div>

    <div class="visit-history-block visit-history-block--standalone">
        <?php if ($visits === []): ?>
            <p class="text-muted mb-0"><?= e(__('visit.records.empty')) ?></p>
        <?php else: ?>
            <?php require BASE_PATH . '/views/partials/patient_visit_history_table.php'; ?>
        <?php endif; ?>
    </div>
</section>

<?php require BASE_PATH . '/views/partials/patient_profile_history.php'; ?>

<?php
$catalogMedicines = $catalogMedicines ?? [];
$visitBilling = $visitBilling ?? Visit::billingDefaults();
require BASE_PATH . '/views/partials/visit_detail_modal.php';
require BASE_PATH . '/views/partials/patient_visit_modal.php';
?>
</div>
<?php
$pageScripts = [
    'assets/js/gst-inclusive.js',
    'assets/js/visit-charges.js',
    'assets/js/visit-payment-fields.js',
    'assets/js/patient-visit-modal.js',
    'assets/js/patient-visit-detail.js',
    'assets/js/form-enter-navigation.js',
];
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
