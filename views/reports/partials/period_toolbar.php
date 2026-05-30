<?php

declare(strict_types=1);

/** @var array{report: string, period: string, date_from: string, date_to: string} $filters */
/** @var array<string, scalar|null> $queryFilters */
/** @var bool $hasCustomDates */

$queryFilters = $queryFilters ?? report_query_filters($filters);
$hasCustomDates = $hasCustomDates ?? report_has_custom_dates($filters);
?>
<section class="reception-card reception-form report-toolbar-card mb-3">
    <form method="get" action="<?= e(base_url('/reports.php')) ?>" class="report-filters-form">
        <input type="hidden" name="report" value="<?= e($filters['report']) ?>">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <h2 class="reception-card-title h6 mb-0"><?= e(__('report.period.label')) ?></h2>
            <div class="d-flex flex-wrap align-items-center gap-2">
            <?php if (!$hasCustomDates): ?>
                <div class="gender-toggle-group payment-period-toggle report-period-toggle" role="group"
                     aria-label="<?= e(__('report.period.label')) ?>">
                    <?php foreach (Report::periodOptions() as $periodKey => $periodLabel): ?>
                        <?php
                        $periodQuery = $queryFilters;
                        $periodQuery['period'] = $periodKey;
                        $periodQuery['report'] = $filters['report'];
                        unset($periodQuery['date_from'], $periodQuery['date_to']);
                        ?>
                        <a href="<?= e(report_url($periodQuery)) ?>"
                           class="btn<?= $filters['period'] === $periodKey ? ' active' : '' ?>"><?= e($periodLabel) ?></a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <a href="<?= e(report_export_url($filters)) ?>"
               class="btn btn-sm btn-outline-secondary text-nowrap report-export-btn">
                <?= e(__('report.export_csv')) ?>
            </a>
            </div>
        </div>
        <div class="row g-2 align-items-end report-date-range-row">
            <div class="col-6 col-md-4 col-lg-3">
                <label for="report_date_from" class="form-label"><?= e(__('report.date_from')) ?></label>
                <input type="date" class="form-control form-control-sm" id="report_date_from" name="date_from"
                       value="<?= e($filters['date_from']) ?>">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label for="report_date_to" class="form-label"><?= e(__('report.date_to')) ?></label>
                <input type="date" class="form-control form-control-sm" id="report_date_to" name="date_to"
                       value="<?= e($filters['date_to']) ?>">
            </div>
            <?php if (in_array($filters['report'], [Report::TYPE_VISITS, Report::TYPE_COURIER], true)): ?>
                <?php
                $reportDeliveryMethodOptions = $filters['report'] === Report::TYPE_COURIER
                    ? Visit::remoteDeliveryMethodOptions()
                    : Visit::deliveryMethodOptions();
                ?>
                <div class="col-6 col-md-4 col-lg-3 report-filter-delivery-method">
                    <label for="report_delivery_method" class="form-label"><?= e(__('visit.form.delivery_method')) ?></label>
                    <select class="form-select form-select-sm" id="report_delivery_method" name="delivery_method">
                        <option value=""><?= e(__('report.filter.delivery_all')) ?></option>
                        <?php foreach ($reportDeliveryMethodOptions as $value => $labelKey): ?>
                            <option value="<?= e($value) ?>"<?= ($filters['delivery_method'] ?? '') === $value ? ' selected' : '' ?>>
                                <?= e(__($labelKey)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="col-auto report-filter-actions d-flex flex-wrap gap-2">
                <button type="submit" class="btn btn-sm btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                <?php if ($hasCustomDates): ?>
                    <a href="<?= e(report_url(['report' => $filters['report'], 'period' => $filters['period']])) ?>"
                       class="btn btn-sm btn-outline-secondary"><?= e(__('report.clear_dates')) ?></a>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($hasCustomDates): ?>
            <p class="text-muted small mb-0 mt-2"><?= e(__('report.custom_range_hint')) ?></p>
        <?php else: ?>
            <p class="text-muted small mb-0 mt-2">
                <?= e(__('report.period.showing', ['period' => Report::periodLabel($filters['period'])])) ?>
            </p>
        <?php endif; ?>
    </form>
</section>
