<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $stockRows */
/** @var bool $dbError */
/** @var string|null $successMessage */
/** @var string|null $errorMessage */
/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, date_from: string, date_to: string, page: int} $listFilters */
/** @var array<string, scalar|null> $sortFilterQuery */
/** @var bool $hasFilters */
/** @var int $totalBills */
/** @var int $totalPages */
/** @var int $page */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
/** @var bool $canSeeAll */

$sort = $sort ?? 'bill_date';
$dir = $dir ?? 'desc';
$listFilters = $listFilters ?? ['q' => '', 'date_from' => '', 'date_to' => '', 'page' => 1];
$sortFilterQuery = $sortFilterQuery ?? [];
$errors = $errors ?? [];
$old = $old ?? ['bill_number' => '', 'register_number' => '', 'supplier' => '', 'bill_date' => '', 'delivery_date' => '', 'items' => [['name' => '', 'quantity' => '1', 'amount' => '']]];
$canSeeAll = $canSeeAll ?? false;
$listPath = '/stock.php';

$stockColumns = [
    'bill_number' => __('stock.field.bill_number'),
    'register_number' => __('stock.field.register_number'),
    'supplier' => __('stock.field.supplier'),
    'items_summary' => __('stock.field.items_summary'),
    'bill_date' => __('stock.field.bill_date'),
    'delivery_date' => __('stock.field.delivery_date'),
    'amount' => __('stock.field.total_amount'),
    'submitted' => __('stock.field.submitted_by'),
];

ob_start();
?>
<div class="stock-page">
    <h1 class="reception-page-title mb-4"><?= e(__('stock.list.title')) ?></h1>

    <?php if ($dbError): ?>
        <div class="alert alert-warning"><?= e(__('reception.error.database')) ?></div>
    <?php else: ?>

        <?php if ($successMessage !== null): ?>
            <div class="alert alert-success"><?= e($successMessage) ?></div>
        <?php endif; ?>

        <?php if (!empty($errorMessage)): ?>
            <div class="alert alert-danger"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <?php if (!$canSeeAll): ?>
            <p class="text-muted small mb-3"><?= e(__('stock.list.visibility_own')) ?></p>
        <?php endif; ?>

        <section class="reception-card reception-form mb-4" id="stockAddBill">
            <h2 class="reception-card-title h6 mb-3"><?= e(__('stock.add.title')) ?></h2>

            <?php if (isset($errors['_form'])): ?>
                <div class="alert alert-danger py-2"><?= e($errors['_form']) ?></div>
            <?php endif; ?>

            <form method="post" action="<?= e(base_url($listPath)) ?>" enctype="multipart/form-data" class="stock-add-form" novalidate
                  data-msg-required="<?= e(__('validation.required')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create">

                <div class="row g-3 stock-bill-header-fields">
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="bill_number" class="form-label"><?= e(__('stock.field.bill_number')) ?></label>
                        <input type="text" class="form-control<?= field_invalid($errors, 'bill_number') ?>"
                               id="bill_number" name="bill_number" maxlength="64" required
                               value="<?= e((string) $old['bill_number']) ?>">
                        <?php show_field_error($errors, 'bill_number'); ?>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="register_number" class="form-label"><?= e(__('stock.field.register_number')) ?></label>
                        <input type="text" class="form-control<?= field_invalid($errors, 'register_number') ?>"
                               id="register_number" name="register_number" maxlength="64" required
                               value="<?= e((string) $old['register_number']) ?>">
                        <?php show_field_error($errors, 'register_number'); ?>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="bill_date" class="form-label"><?= e(__('stock.field.bill_date')) ?></label>
                        <input type="date" class="form-control<?= field_invalid($errors, 'bill_date') ?>"
                               id="bill_date" name="bill_date" required
                               value="<?= e((string) $old['bill_date']) ?>">
                        <?php show_field_error($errors, 'bill_date'); ?>
                    </div>
                    <div class="col-6 col-md-4 col-lg-2">
                        <label for="delivery_date" class="form-label">
                            <?= e(__('stock.field.delivery_date')) ?>
                            <span class="text-muted fw-normal small d-none d-xl-inline">(<?= e(__('patient.field.delivery_optional')) ?>)</span>
                        </label>
                        <input type="date" class="form-control" id="delivery_date" name="delivery_date"
                               value="<?= e((string) $old['delivery_date']) ?>">
                    </div>
                    <div class="col-12 col-md-8 col-lg-4">
                        <label for="supplier" class="form-label"><?= e(__('stock.field.supplier')) ?></label>
                        <input type="text" class="form-control<?= field_invalid($errors, 'supplier') ?>"
                               id="supplier" name="supplier" maxlength="255" required
                               value="<?= e((string) $old['supplier']) ?>">
                        <?php show_field_error($errors, 'supplier'); ?>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label"><?= e(__('stock.field.items')) ?></label>
                    <?php if (isset($errors['items'])): ?>
                        <div class="invalid-feedback d-block mb-2"><?= e($errors['items']) ?></div>
                    <?php endif; ?>
                    <div class="stock-item-header row g-2 small text-muted mb-1 d-none d-md-flex">
                        <div class="col-md-5"><?= e(__('stock.field.item_name')) ?></div>
                        <div class="col-md-2"><?= e(__('stock.field.item_quantity')) ?></div>
                        <div class="col-md-4"><?= e(__('stock.field.item_amount')) ?></div>
                        <div class="col-md-1"></div>
                    </div>
                    <div id="stockItemList">
                        <?php foreach ($old['items'] as $item): ?>
                            <?php
                            $itemName = is_array($item) ? (string) ($item['name'] ?? '') : (string) $item;
                            $itemQuantity = is_array($item) ? (string) ($item['quantity'] ?? '1') : '1';
                            if ($itemQuantity === '') {
                                $itemQuantity = '1';
                            }
                            $itemAmount = is_array($item) ? (string) ($item['amount'] ?? '') : '';
                            ?>
                            <div class="stock-item-row row g-2 mb-2 align-items-center">
                                <div class="col-md-5">
                                    <input type="text" class="form-control" name="item_names[]" maxlength="255"
                                           placeholder="<?= e(__('stock.field.item_name')) ?>"
                                           value="<?= e($itemName) ?>">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="item_quantities[]"
                                           min="1" step="1"
                                           value="<?= e($itemQuantity) ?>">
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control stock-item-amount" name="item_amounts[]"
                                               min="0.01" step="0.01" placeholder="0.00"
                                               value="<?= e($itemAmount) ?>">
                                    </div>
                                </div>
                                <div class="col-md-1 text-end">
                                    <button type="button" class="btn btn-outline-secondary stock-remove-item w-100"
                                            title="<?= e(__('stock.action.remove_item')) ?>">×</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="stockAddItemBtn"
                                data-remove-label="<?= e(__('stock.action.remove_item')) ?>"
                                data-name-placeholder="<?= e(__('stock.field.item_name')) ?>">
                            <?= e(__('stock.action.add_item')) ?>
                        </button>
                        <div class="stock-bill-total ms-md-auto">
                            <span class="text-muted"><?= e(__('stock.field.total_amount')) ?>:</span>
                            <strong class="stock-bill-total-value ms-1" id="stockBillTotal">₹0.00</strong>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label for="bill_file" class="form-label"><?= e(__('stock.field.file')) ?></label>
                    <input type="file" class="form-control<?= field_invalid($errors, 'file') ?>"
                           id="bill_file" name="bill_file" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                    <div class="form-text"><?= e(__('stock.field.file_hint')) ?></div>
                    <?php show_field_error($errors, 'file'); ?>
                </div>

                <div class="mt-3">
                    <button type="button" class="btn btn-reception-primary confirm-action-trigger"
                            data-confirm-title="<?= e(__('stock.add.confirm_title')) ?>"
                            data-confirm="<?= e(__('stock.add.confirm_message')) ?>"
                            data-confirm-label="<?= e(__('stock.add.submit')) ?>"
                            data-confirm-variant="primary">
                        <?= e(__('stock.add.submit')) ?>
                    </button>
                </div>
            </form>
        </section>

        <section class="reception-card reception-form mb-4">
            <h2 class="reception-card-title h6 mb-3"><?= e(__('stock.list.filters')) ?></h2>
            <form method="get" action="<?= e(base_url($listPath)) ?>" class="patient-list-filters">
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <input type="hidden" name="dir" value="<?= e($dir) ?>">
                <div class="patient-list-filters-row stock-list-filters-row">
                    <div class="patient-list-filter-search">
                        <label for="stock_filter_q" class="form-label"><?= e(__('stock.list.search')) ?></label>
                        <input type="search" class="form-control" id="stock_filter_q" name="q"
                               value="<?= e($listFilters['q']) ?>"
                               placeholder="<?= e(__('stock.list.search_placeholder')) ?>" autocomplete="off">
                    </div>
                    <div class="patient-list-filter-date">
                        <span class="form-label d-block"><?= e(__('stock.list.date_range')) ?></span>
                        <div class="patient-list-range-inputs">
                            <input type="date" class="form-control" name="date_from"
                                   value="<?= e($listFilters['date_from']) ?>"
                                   aria-label="<?= e(__('patients.list.date_from')) ?>">
                            <span class="patient-list-range-sep" aria-hidden="true">–</span>
                            <input type="date" class="form-control" name="date_to"
                                   value="<?= e($listFilters['date_to']) ?>"
                                   aria-label="<?= e(__('patients.list.date_to')) ?>">
                        </div>
                    </div>
                    <div class="patient-list-filter-actions">
                        <button type="submit" class="btn btn-reception-primary"><?= e(__('patients.list.apply')) ?></button>
                        <?php if ($hasFilters): ?>
                            <a href="<?= e(stock_list_url($sort, $dir)) ?>" class="btn btn-outline-secondary">
                                <?= e(__('patients.list.clear')) ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </section>

        <section class="reception-card">
            <h2 class="reception-card-title h6 mb-3"><?= e(__('stock.list.results', ['count' => $totalBills])) ?></h2>

            <?php if ($stockRows === []): ?>
                <p class="text-muted mb-0">
                    <?= e($hasFilters ? __('stock.list.empty') : __('stock.list.empty_all')) ?>
                </p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover reception-table reception-table-sortable mb-0">
                        <thead>
                        <tr>
                            <?php foreach ($stockColumns as $colKey => $colLabel): ?>
                                <?php if ($colKey === 'submitted' && !$canSeeAll) {
                                    continue;
                                } ?>
                                <th scope="col"<?= stock_sort_th_attr($colKey, $sort, $dir) ?>>
                                    <a href="<?= e(stock_sort_url($colKey, $sort, $dir, $listFilters)) ?>"
                                       class="reception-sort-link<?= $sort === $colKey ? ' active' : '' ?>">
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
                        <?php foreach ($stockRows as $row):
                            $viewUrl = stock_view_url((int) $row['id'], $listFilters, $sort, $dir);
                            ?>
                            <tr class="reception-table-row-link" data-href="<?= e($viewUrl) ?>" tabindex="0" role="link"
                                aria-label="<?= e(__('stock.action.view')) ?>">
                                <td><?= table_cell($row['bill_number']) ?></td>
                                <td><?= table_cell($row['register_number']) ?></td>
                                <td><?= table_cell($row['supplier']) ?></td>
                                <td class="text-truncate" style="max-width: 34rem;"><?= table_cell($row['items_summary']) ?></td>
                                <td><?= e(StockBill::formatDate($row['bill_date'])) ?></td>
                                <td><?= e(StockBill::formatDate($row['delivery_date'])) ?></td>
                                <td class="text-start text-nowrap" style="max-width: 7rem;"><?= e(StockBill::formatAmount((float) $row['amount'])) ?></td>
                                <?php if ($canSeeAll): ?>
                                    <td><?= table_cell($row['submitted_by_name']) ?></td>
                                <?php endif; ?>
                                <td class="text-end text-nowrap col-actions">
                                    <div class="patient-actions justify-content-end">
                                        <a href="<?= e(stock_view_url((int) $row['id'], $listFilters, $sort, $dir)) ?>"
                                           class="patient-action-btn patient-action-view"
                                           title="<?= e(__('stock.action.view')) ?>" aria-label="<?= e(__('stock.action.view')) ?>">
                                            <?php require BASE_PATH . '/views/partials/icons/view.php'; ?>
                                        </a>
                                        <?php if ($canSeeAll): ?>
                                            <form method="post" action="<?= e(base_url('/stock_delete.php')) ?>" class="patient-action-delete-form d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e((string) $row['id']) ?>">
                                                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                                                <input type="hidden" name="dir" value="<?= e($dir) ?>">
                                                <?php foreach (stock_list_query_filters($listFilters) as $key => $value): ?>
                                                    <input type="hidden" name="<?= e((string) $key) ?>" value="<?= e((string) $value) ?>">
                                                <?php endforeach; ?>
                                                <button type="button"
                                                        class="patient-action-btn patient-action-delete confirm-action-trigger"
                                                        data-confirm-title="<?= e(__('stock.delete.confirm_title')) ?>"
                                                        data-confirm="<?= e(__('stock.delete.confirm')) ?>"
                                                        data-confirm-label="<?= e(__('stock.action.delete')) ?>"
                                                        data-confirm-variant="danger"
                                                        title="<?= e(__('stock.action.delete')) ?>" aria-label="<?= e(__('stock.action.delete')) ?>">
                                                    <?php require BASE_PATH . '/views/partials/icons/delete.php'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav class="patient-list-pagination mt-3" aria-label="<?= e(__('stock.list.title')) ?>">
                        <ul class="pagination pagination-sm mb-0">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= e(stock_list_url($sort, $dir, array_merge($listFilters, ['page' => $page - 1]))) ?>">
                                        <?= e(__('patients.list.prev')) ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                                <li class="page-item<?= $p === $page ? ' active' : '' ?>">
                                    <a class="page-link" href="<?= e(stock_list_url($sort, $dir, array_merge($listFilters, ['page' => $p]))) ?>">
                                        <?= e((string) $p) ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="<?= e(stock_list_url($sort, $dir, array_merge($listFilters, ['page' => $page + 1]))) ?>">
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
</div>
<?php
$content = ob_get_clean();
$pageScripts = ['assets/js/stock-items.js', 'assets/js/stock-form-validate.js'];
require BASE_PATH . '/views/layouts/dashboard.php';
