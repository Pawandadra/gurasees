<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $paymentRows */
/** @var array<string, int|float> $summary */
/** @var bool $dbError */
/** @var string|null $successMessage */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, status: string, type: string, date_from: string, date_to: string, page: int} $listFilters */
/** @var int $totalPages */
/** @var int $page */
/** @var int $perPage */
/** @var string $summaryPeriod */
/** @var array<string, scalar|null> $sortFilterQuery */
/** @var bool $hasFilters */
/** @var int $totalPayments */

$sort = $sort ?? 'date';
$dir = $dir ?? 'desc';
$listFilters = $listFilters ?? ['q' => '', 'status' => '', 'type' => '', 'date_from' => '', 'date_to' => ''];
$summaryPeriod = $summaryPeriod ?? Payment::PERIOD_TODAY;
$sortFilterQuery = $sortFilterQuery ?? [];
$hasFilters = $hasFilters ?? false;
$totalPayments = $totalPayments ?? 0;
$totalPages = $totalPages ?? 1;
$page = $page ?? 1;
$perPage = $perPage ?? 25;
$listPath = '/payments.php';
$paymentColumns = [
    'patient_id' => __('patient.field.id'),
    'patient' => __('patient.field.name'),
    'phone' => __('patient.field.phone'),
    'type' => __('payment.field.type'),
    'total' => __('payment.field.total'),
    'without_gst' => __('payment.field.without_gst_col'),
    'gst' => __('payment.field.gst_col'),
    'paid' => __('payment.field.paid'),
    'balance' => __('payment.field.balance'),
    'method' => __('payment.field.method'),
    'status' => __('payment.field.status'),
];

ob_start();
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <h1 class="reception-page-title mb-0"><?= e(__('payment.list.title')) ?></h1>
    <a href="<?= e(payment_settings_url($listFilters, $summaryPeriod, $sort, $dir)) ?>"
       class="btn btn-outline-secondary btn-sm text-nowrap">
        <?= e(__('payment.settings.configure')) ?>
    </a>
</div>

<?php if ($dbError): ?>
    <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
<?php else: ?>

    <?php if ($successMessage !== null): ?>
        <div class="alert alert-success"><?= e($successMessage) ?></div>
    <?php endif; ?>

    <section class="reception-card mb-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('payment.summary.title')) ?></h2>
            <div class="gender-toggle-group payment-period-toggle" role="group" aria-label="<?= e(__('payment.period.label')) ?>">
                <?php foreach (Payment::periodOptions() as $periodKey => $periodLabel): ?>
                    <a href="<?= e(payment_list_url($sort, $dir, $listFilters, $periodKey)) ?>"
                       class="btn<?= $summaryPeriod === $periodKey ? ' active' : '' ?>"><?= e($periodLabel) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="row g-3 payment-summary-row">
            <div class="col-6 col-md-6">
                <div class="payment-summary-card payment-summary-paid">
                    <p class="payment-summary-label mb-1"><?= e(__('payment.summary.paid')) ?></p>
                    <p class="payment-summary-value mb-0"><?= e(PaymentSettings::formatAmountDisplay((float) $summary['paid_total'])) ?></p>
                </div>
            </div>
            <div class="col-6 col-md-6">
                <div class="payment-summary-card payment-summary-pending">
                    <p class="payment-summary-label mb-1"><?= e(__('payment.summary.pending')) ?></p>
                    <p class="payment-summary-value mb-0"><?= e(PaymentSettings::formatAmountDisplay((float) $summary['pending_total'])) ?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="reception-card reception-form mb-4">
        <h2 class="reception-card-title h6 mb-3"><?= e(__('payment.list.filters')) ?></h2>
        <form method="get" action="<?= e(base_url($listPath)) ?>" class="patient-list-filters payment-list-filters">
            <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <input type="hidden" name="dir" value="<?= e($dir) ?>">
            <input type="hidden" name="period" value="<?= e($summaryPeriod) ?>">
            <div class="patient-list-filters-row payment-list-filters-row">
                <div class="patient-list-filter-search">
                    <label for="payment_filter_q" class="form-label"><?= e(__('payment.list.search')) ?></label>
                    <input type="search" class="form-control" id="payment_filter_q" name="q"
                           value="<?= e($listFilters['q']) ?>"
                           placeholder="<?= e(__('payment.list.search_placeholder')) ?>"
                           autocomplete="off">
                </div>
                <div class="patient-list-filter-gender">
                    <label for="payment_filter_status" class="form-label"><?= e(__('payment.field.status')) ?></label>
                    <select class="form-select" id="payment_filter_status" name="status">
                        <option value=""><?= e(__('payment.list.status_all')) ?></option>
                        <?php foreach (PaymentSettings::STATUSES as $status): ?>
                            <option value="<?= $status ?>"<?= $listFilters['status'] === $status ? ' selected' : '' ?>>
                                <?= e(PaymentSettings::statusLabel($status)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="patient-list-filter-gender">
                    <label for="payment_filter_type" class="form-label"><?= e(__('payment.field.type')) ?></label>
                    <select class="form-select" id="payment_filter_type" name="type">
                        <option value=""><?= e(__('payment.list.type_all')) ?></option>
                        <option value="registration"<?= $listFilters['type'] === Payment::TYPE_REGISTRATION ? ' selected' : '' ?>>
                            <?= e(__('payment.type.registration')) ?>
                        </option>
                        <option value="visit"<?= $listFilters['type'] === Payment::TYPE_VISIT ? ' selected' : '' ?>>
                            <?= e(__('payment.type.visit')) ?>
                        </option>
                    </select>
                </div>
                <div class="patient-list-filter-date">
                    <span class="form-label d-block"><?= e(__('payment.list.date_range')) ?></span>
                    <div class="patient-list-range-inputs">
                        <input type="date" class="form-control" id="payment_filter_date_from" name="date_from"
                               value="<?= e($listFilters['date_from']) ?>"
                               aria-label="<?= e(__('patients.list.date_from')) ?>">
                        <span class="patient-list-range-sep" aria-hidden="true">–</span>
                        <input type="date" class="form-control" id="payment_filter_date_to" name="date_to"
                               value="<?= e($listFilters['date_to']) ?>"
                               aria-label="<?= e(__('patients.list.date_to')) ?>">
                    </div>
                </div>
                <div class="patient-list-filter-actions">
                    <button type="submit" class="btn btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                    <?php if ($hasFilters): ?>
                        <a href="<?= e(payment_list_url($sort, $dir, [], $summaryPeriod)) ?>"
                           class="btn btn-outline-secondary"><?= e(__('patients.list.clear')) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </section>

    <section class="reception-card">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('payment.list.results', ['count' => $totalPayments])) ?></h2>
        </div>

        <?php if ($paymentRows === []): ?>
            <p class="text-muted mb-0">
                <?= e($hasFilters ? __('payment.list.empty') : __('payment.list.empty_all')) ?>
            </p>
        <?php else: ?>
            <?php require BASE_PATH . '/views/partials/payment_list_table.php'; ?>

            <?php if ($totalPages > 1): ?>
                <nav class="patient-list-pagination mt-3" aria-label="<?= e(__('payment.list.pagination')) ?>">
                    <ul class="pagination pagination-sm mb-0">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(payment_list_url($sort, $dir, array_merge($listFilters, ['page' => $page - 1]), $summaryPeriod)) ?>">
                                    <?= e(__('patients.list.prev')) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                        <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                            <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                <a class="page-link" href="<?= e(payment_list_url($sort, $dir, array_merge($listFilters, ['page' => $p]), $summaryPeriod)) ?>">
                                    <?= e((string) $p) ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= e(payment_list_url($sort, $dir, array_merge($listFilters, ['page' => $page + 1]), $summaryPeriod)) ?>">
                                    <?= e(__('patients.list.next')) ?>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

<?php endif; ?>
<?php
$content = ob_get_clean();
require BASE_PATH . '/views/layouts/dashboard.php';
