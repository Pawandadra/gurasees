<?php

declare(strict_types=1);

/** @var array<string, mixed> $patient */
/** @var list<string> $symptomLabels */
/** @var list<array<string, mixed>> $visits */
/** @var array<string, string> $visitErrors */
/** @var array<string, string> $visitOld */
/** @var string|null $successMessage */
/** @var string $code */
/** @var string $sort */
/** @var string $dir */
/** @var string $return */
/** @var array{q: string, gender: string, page: int} $listFilters */

$return = $return ?? 'dashboard';
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
$successMessage = $successMessage ?? null;

ob_start();
?>
<div class="page-header-bar mb-4">
    <?php $url = $backUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0"><?= e(__('patient.view.title')) ?></h1>
    <a href="<?= e($editUrl) ?>" class="btn btn-reception-primary btn-sm ms-auto"><?= e(__('patient.action.edit')) ?></a>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<section class="reception-card patient-profile-card mb-4">
    <h2 class="reception-card-title h6 mb-2"><?= e(__('patient.profile.title')) ?></h2>
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
                <?php $paymentSummary = Patient::formatPaymentSummary($patient); ?>
                <?php if ($paymentSummary !== null): ?>
                    <div class="patient-detail-item">
                        <dt><?= e(__('patient.field.payment')) ?></dt>
                        <dd class="small"><?= e($paymentSummary) ?></dd>
                    </div>
                <?php endif; ?>
                <?php if ($symptomLabels !== []): ?>
                    <div class="patient-detail-item patient-symptoms-item">
                        <dt><?= e(__('patient.field.symptoms')) ?></dt>
                        <dd><?= e(implode(', ', $symptomLabels)) ?></dd>
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
    <h2 class="reception-card-title h6 mb-1"><?= e(__('visit.records.title')) ?></h2>
    <p class="text-muted small mb-4"><?= e(__('visit.form.intro')) ?></p>

    <form method="post" action="<?= e(base_url('/patient_view.php')) ?>" class="visit-log-form">
        <?= csrf_field() ?>
        <input type="hidden" name="code" value="<?= e($code) ?>">
        <input type="hidden" name="action" value="add_visit">

        <?php if (isset($visitErrors['_form'])): ?>
            <div class="alert alert-danger"><?= e($visitErrors['_form']) ?></div>
        <?php endif; ?>

        <fieldset class="visit-form-section mb-4">
            <legend class="visit-form-section-title"><?= e(__('visit.form.details')) ?></legend>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <label for="visited_at" class="form-label"><?= e(__('visit.field.datetime')) ?></label>
                    <input type="datetime-local" class="form-control<?= field_invalid($visitErrors, 'visited_at') ?>"
                           id="visited_at" name="visited_at" value="<?= e($visitOld['visited_at']) ?>" required>
                    <?php show_field_error($visitErrors, 'visited_at'); ?>
                </div>
                <div class="col-md-6 col-lg-4">
                    <label for="visit_charge" class="form-label"><?= e(__('visit.field.visit_charge')) ?></label>
                    <div class="input-group">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control<?= field_invalid($visitErrors, 'visit_charge') ?>"
                               id="visit_charge" name="visit_charge" min="0" step="0.01" required
                               value="<?= e((string) ($visitOld['visit_charge'] ?? ($visitBilling['visit_charge'] ?? '0'))) ?>">
                    </div>
                    <?php show_field_error($visitErrors, 'visit_charge'); ?>
                </div>
                <div class="col-lg-4">
                    <label for="visit_notes" class="form-label">
                        <?= e(__('visit.field.notes')) ?>
                        <span class="text-muted fw-normal small">(<?= e(__('patient.field.delivery_optional')) ?>)</span>
                    </label>
                    <input type="text" class="form-control" id="visit_notes" name="notes"
                           value="<?= e($visitOld['notes']) ?>" maxlength="500"
                           placeholder="<?= e(__('visit.field.notes_placeholder')) ?>">
                </div>
            </div>
        </fieldset>

        <?php
        $catalogMedicines = $catalogMedicines ?? [];
        $visitMedicineLines = $visitOld['medicines'] ?? [];
        $visitBilling = $visitBilling ?? Visit::billingDefaults();
        require BASE_PATH . '/views/partials/visit_billing_fields.php';
        ?>
    </form>

    <div class="visit-history-block">
        <h3 class="visit-form-section-title mb-3"><?= e(__('visit.form.previous')) ?></h3>

        <?php if ($visits === []): ?>
            <p class="text-muted mb-0"><?= e(__('visit.records.empty')) ?></p>
        <?php else: ?>
            <div class="table-responsive visit-history-table-wrap">
                <table class="table table-hover reception-table visit-history-table mb-0">
                    <thead>
                    <tr>
                        <th scope="col"><?= e(__('visit.field.medicines')) ?></th>
                        <th scope="col" class="text-end"><?= e(__('visit.field.grand_total')) ?></th>
                        <th scope="col"><?= e(__('payment.field.method')) ?></th>
                        <th scope="col"><?= e(__('payment.field.status')) ?></th>
                        <th scope="col"><?= e(__('visit.field.notes')) ?></th>
                        <th scope="col"><?= e(__('visit.field.time')) ?></th>
                        <th scope="col"><?= e(__('visit.field.recorded_by')) ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $lastDateKey = null;
                    $dateHeaderColspan = 7;
                    foreach ($visits as $visit):
                        $dateKey = Visit::visitedDateKey((string) $visit['visited_at']);
                        if ($dateKey !== $lastDateKey) {
                            $lastDateKey = $dateKey;
                            $colspan = $dateHeaderColspan;
                            require BASE_PATH . '/views/partials/visit_date_header_row.php';
                        }
                        $lines = $visit['medicine_lines'] ?? [];
                        $grandTotal = (float) ($visit['grand_total'] ?? 0);
                        ?>
                        <tr>
                            <td class="small visit-history-medicines"><?= table_cell(Visit::formatMedicineSummary($lines)) ?></td>
                            <td class="text-end fw-semibold text-nowrap">
                                <?= $grandTotal > 0
                                    ? e(Medicine::formatPriceDisplay($grandTotal))
                                    : table_na() ?>
                            </td>
                            <td class="small text-nowrap">
                                <?= !empty($visit['payment_method'])
                                    ? e(PaymentSettings::methodLabel((string) $visit['payment_method']))
                                    : table_na() ?>
                            </td>
                            <td class="small text-nowrap">
                                <?= !empty($visit['payment_status'])
                                    ? e(PaymentSettings::statusLabel((string) $visit['payment_status']))
                                    : table_na() ?>
                            </td>
                            <td class="small"><?= table_cell($visit['notes'] ?? '') ?></td>
                            <td class="text-nowrap small"><?= e(Visit::formatVisitedTime((string) $visit['visited_at'])) ?></td>
                            <td class="small text-nowrap"><?= table_cell($visit['recorded_by_name'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$pageScripts = ['assets/js/visit-charges.js', 'assets/js/visit-payment-fields.js'];
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
