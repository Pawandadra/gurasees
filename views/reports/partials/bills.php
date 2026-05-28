<?php

declare(strict_types=1);

/** @var array<string, mixed> $reportData */

$rows = $reportData['rows'] ?? [];
$rowsTotal = (int) ($reportData['rows_total'] ?? count($rows));
$bySupplier = $reportData['by_supplier'] ?? [];

ob_start();
?>
<section class="reception-card mb-3">
    <h2 class="reception-card-title h6 mb-3"><?= e(__('report.bills.summary')) ?></h2>
    <div class="row g-2 g-md-3">
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.bills'); $value = (string) (int) ($reportData['bill_count'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.grand_total'); $value = e(StockBill::formatAmount((float) ($reportData['total_amount'] ?? 0))); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.suppliers'); $value = (string) (int) ($reportData['supplier_count'] ?? 0); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
        <div class="col-6 col-md-4 col-lg-3">
            <?php $label = __('report.metric.avg_bill'); $value = e(StockBill::formatAmount((float) ($reportData['avg_amount'] ?? 0))); $variant = ''; require BASE_PATH . '/views/reports/partials/metric.php'; ?>
        </div>
    </div>
</section>

<div class="row g-3">
    <div class="col-lg-6">
        <section class="reception-card h-100">
            <h3 class="reception-card-title h6 mb-3"><?= e(__('report.bills.by_supplier')) ?></h3>
            <?php if ($bySupplier === []): ?>
                <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm reception-table mb-0">
                        <thead>
                        <tr>
                            <th><?= e(__('report.col.supplier')) ?></th>
                            <th class="text-end"><?= e(__('report.col.count')) ?></th>
                            <th class="text-end"><?= e(__('report.col.total')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bySupplier as $row): ?>
                            <tr>
                                <td><?= table_cell((string) ($row['supplier'] ?? '')) ?></td>
                                <td class="text-end"><?= (int) ($row['bill_count'] ?? 0) ?></td>
                                <td class="text-end text-nowrap"><?= e(StockBill::formatAmount((float) ($row['total_amount'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<section class="reception-card mt-3 report-detail-card">
    <?php
    $detailTitle = __('report.bills.detail');
    $detailCount = $rowsTotal;
    require BASE_PATH . '/views/reports/partials/detail_header.php';
    ?>

    <?php if ($rows === []): ?>
        <p class="text-muted small mb-0"><?= e(__('report.empty')) ?></p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover reception-table mb-0 report-detail-table">
                <thead>
                <tr>
                    <th><?= e(__('report.col.bill_number')) ?></th>
                    <th><?= e(__('report.col.register_number')) ?></th>
                    <th><?= e(__('report.col.supplier')) ?></th>
                    <th><?= e(__('report.col.item_names')) ?></th>
                    <th><?= e(__('report.col.purchase_date')) ?></th>
                    <th><?= e(__('report.col.delivery_date')) ?></th>
                    <th class="text-end"><?= e(__('report.col.total')) ?></th>
                    <th><?= e(__('report.col.submitted_by')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td class="text-nowrap"><?= table_cell((string) ($row['bill_number'] ?? '')) ?></td>
                        <td class="text-nowrap"><?= table_cell((string) ($row['register_number'] ?? '')) ?></td>
                        <td><?= table_cell((string) ($row['supplier'] ?? '')) ?></td>
                        <td class="text-truncate" style="max-width: 22rem;"><?= table_cell((string) ($row['items_summary'] ?? '')) ?></td>
                        <td class="text-nowrap small"><?= e(StockBill::formatDate((string) ($row['bill_date'] ?? ''))) ?></td>
                        <td class="text-nowrap small"><?= e(StockBill::formatDate((string) ($row['delivery_date'] ?? null))) ?></td>
                        <td class="text-end text-nowrap"><?= e(StockBill::formatAmount((float) ($row['amount'] ?? 0))) ?></td>
                        <td><?= table_cell((string) ($row['submitted_by_name'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        $rowsShown = count($rows);
        require BASE_PATH . '/views/reports/partials/detail_truncated.php';
        ?>
    <?php endif; ?>
</section>
<?php
echo ob_get_clean();

