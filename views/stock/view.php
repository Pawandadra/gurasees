<?php

declare(strict_types=1);

/** @var array<string, mixed> $bill */
/** @var string|null $successMessage */
/** @var string $backUrl */

$bill = $bill ?? [];
$backUrl = $backUrl ?? stock_list_url('bill_date', 'desc');
$hasFile = !empty($bill['file_stored_name']);
$isPdf = str_contains((string) ($bill['file_mime'] ?? ''), 'pdf');
$fileUrl = stock_file_url((int) $bill['id']);

ob_start();
?>
<div class="page-header-bar page-header-bar--inline mb-4">
    <?php $url = $backUrl; require BASE_PATH . '/views/partials/page_back.php'; ?>
    <h1 class="reception-page-title mb-0">
        <?= e(__('stock.view.title', ['bill' => $bill['bill_number']])) ?>
    </h1>
</div>

<?php if ($successMessage !== null): ?>
    <div class="alert alert-success"><?= e($successMessage) ?></div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-7">
        <section class="reception-card">
            <dl class="row mb-0 stock-detail-dl">
                <dt class="col-sm-4"><?= e(__('stock.field.bill_number')) ?></dt>
                <dd class="col-sm-8"><?= e($bill['bill_number']) ?></dd>

                <dt class="col-sm-4"><?= e(__('stock.field.register_number')) ?></dt>
                <dd class="col-sm-8"><?= e($bill['register_number']) ?></dd>

                <dt class="col-sm-4"><?= e(__('stock.field.delivery_date')) ?></dt>
                <dd class="col-sm-8"><?= e(StockBill::formatDate($bill['delivery_date'])) ?></dd>

                <dt class="col-sm-4"><?= e(__('stock.field.bill_date')) ?></dt>
                <dd class="col-sm-8"><?= e(StockBill::formatDate($bill['bill_date'])) ?></dd>

                <dt class="col-sm-4"><?= e(__('stock.field.supplier')) ?></dt>
                <dd class="col-sm-8"><?= e($bill['supplier']) ?></dd>

                <dt class="col-sm-4"><?= e(__('stock.field.submitted_by')) ?></dt>
                <dd class="col-sm-8"><?= e($bill['submitted_by_name']) ?></dd>

                <dt class="col-sm-4"><?= e(__('stock.field.submitted_at')) ?></dt>
                <dd class="col-sm-8"><?= e($bill['created_at']) ?></dd>
            </dl>
        </section>

        <section class="reception-card mt-4">
            <h2 class="reception-card-title h6 mb-3"><?= e(__('stock.field.items')) ?></h2>
            <?php if (($bill['items'] ?? []) === []): ?>
                <p class="text-muted mb-0"><?= table_na() ?></p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0 stock-items-table">
                        <thead>
                        <tr>
                            <th scope="col"><?= e(__('stock.field.item_name')) ?></th>
                            <th scope="col" class="text-end"><?= e(__('stock.field.item_quantity')) ?></th>
                            <th scope="col" class="text-end"><?= e(__('stock.field.item_amount')) ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($bill['items'] as $item): ?>
                            <tr>
                                <td><?= e($item['item_name']) ?></td>
                                <td class="text-end"><?= e(StockBill::formatQuantity((float) $item['quantity'])) ?></td>
                                <td class="text-end"><?= e(StockBill::formatAmount((float) $item['amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <th scope="row" colspan="2"><?= e(__('stock.field.total_amount')) ?></th>
                            <td class="text-end fw-semibold"><?= e(StockBill::formatAmount((float) $bill['amount'])) ?></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="reception-card stock-attachment-card">
            <h2 class="reception-card-title h6 mb-3"><?= e(__('stock.field.file')) ?></h2>
            <?php if (!$hasFile): ?>
                <p class="text-muted mb-0"><?= e(__('stock.error.file_not_found')) ?></p>
            <?php else: ?>
                <p class="small text-muted mb-2"><?= e($bill['file_original_name']) ?></p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php if (!$isPdf && str_starts_with((string) ($bill['file_mime'] ?? ''), 'image/')): ?>
                        <a href="<?= e($fileUrl) ?>" class="btn btn-sm btn-reception-primary" target="_blank" rel="noopener">
                            <?= e(__('stock.action.open')) ?>
                        </a>
                    <?php endif; ?>
                    <a href="<?= e($fileUrl) ?>" class="btn btn-sm btn-outline-secondary" download>
                        <?= e(__('stock.action.download')) ?>
                    </a>
                </div>
                <?php if (!$isPdf && str_starts_with((string) ($bill['file_mime'] ?? ''), 'image/')): ?>
                    <div class="stock-attachment-preview">
                        <img src="<?= e($fileUrl) ?>" alt="<?= e($bill['file_original_name']) ?>" class="img-fluid rounded border">
                    </div>
                <?php elseif ($isPdf): ?>
                    <div id="stockPdfViewer" class="stock-pdf-viewer rounded border"
                         data-url="<?= e($fileUrl) ?>"
                         role="region"
                         aria-label="<?= e($bill['file_original_name']) ?>"></div>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php
$content = ob_get_clean();
$pageScripts = $isPdf && $hasFile ? ['assets/js/stock-pdf-viewer.js'] : [];
require BASE_PATH . '/views/layouts/dashboard.php';
