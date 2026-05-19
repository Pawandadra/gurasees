<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $rows */
/** @var array<string, float> $totals */
/** @var array<string, string> $filters */

ob_start();
?>

<style>
    .ledger-summary-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 18px;
    }

    .ledger-stat-card {
        border: 1px solid rgba(12, 89, 71, 0.12);
        border-radius: 16px;
        background: #ffffff;
        padding: 16px;
        box-shadow: 0 8px 22px rgba(15, 79, 65, 0.07);
    }

    .ledger-stat-label {
        font-size: 13px;
        color: #66736f;
        margin-bottom: 6px;
    }

    .ledger-stat-value {
        font-size: 21px;
        font-weight: 800;
        color: #0b5d4b;
    }

    .ledger-table th {
        white-space: nowrap;
        font-size: 12px;
        text-transform: uppercase;
        color: #4f625d;
    }

    .ledger-table td {
        white-space: nowrap;
        vertical-align: middle;
    }

    .ledger-money {
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .ledger-type-badge {
        border-radius: 999px;
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 700;
        background: rgba(11, 93, 75, 0.09);
        color: #0b5d4b;
        border: 1px solid rgba(11, 93, 75, 0.15);
    }

    @media (max-width: 1200px) {
        .ledger-summary-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 700px) {
        .ledger-summary-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<h1 class="reception-page-title mb-4"><?= e(__('report.ledger.title')) ?></h1>

<div class="ledger-summary-grid">
    <div class="ledger-stat-card">
        <div class="ledger-stat-label"><?= e(__('report.ledger.col.registration_bill')) ?></div>
        <div class="ledger-stat-value">₹<?= e(LedgerReport::money($totals['registration_bill'] ?? 0)) ?></div>
    </div>

    <div class="ledger-stat-card">
        <div class="ledger-stat-label"><?= e(__('report.ledger.col.visit_bill')) ?></div>
        <div class="ledger-stat-value">₹<?= e(LedgerReport::money($totals['visit_bill'] ?? 0)) ?></div>
    </div>

    <div class="ledger-stat-card">
        <div class="ledger-stat-label"><?= e(__('report.ledger.col.pharmacy_bill')) ?></div>
        <div class="ledger-stat-value">₹<?= e(LedgerReport::money($totals['pharmacy_bill'] ?? 0)) ?></div>
    </div>

    <div class="ledger-stat-card">
        <div class="ledger-stat-label"><?= e(__('report.ledger.col.total_gst')) ?></div>
        <div class="ledger-stat-value">₹<?= e(LedgerReport::money($totals['total_gst'] ?? 0)) ?></div>
    </div>
</div>

<section class="reception-card reception-form mb-4">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <h2 class="reception-card-title h6 mb-1"><?= e(__('report.ledger.filters')) ?></h2>
            <p class="text-muted small mb-0"><?= e(__('report.ledger.hint')) ?></p>
        </div>

        <a class="btn btn-outline-secondary"
           href="<?= e(base_url('/reports.php?' . http_build_query(array_merge($filters, ['export' => 'csv'])))) ?>">
            <?= e(__('report.ledger.export_csv')) ?>
        </a>
    </div>

    <form method="get" action="<?= e(base_url('/reports.php')) ?>">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label"><?= e(__('patients.list.search')) ?></label>
                <input type="text"
                       name="q"
                       class="form-control"
                       placeholder="<?= e(__('patients.list.search_placeholder')) ?>"
                       value="<?= e($filters['q'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label"><?= e(__('patients.list.date_from')) ?></label>
                <input type="date" name="date_from" class="form-control" value="<?= e($filters['date_from'] ?? '') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label"><?= e(__('patients.list.date_to')) ?></label>
                <input type="date" name="date_to" class="form-control" value="<?= e($filters['date_to'] ?? '') ?>">
            </div>

            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-reception-primary">
                    <?= e(__('patients.list.apply')) ?>
                </button>
            </div>
        </div>
    </form>
</section>

<section class="reception-card">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="reception-card-title h6 mb-0"><?= e(__('report.ledger.records')) ?></h2>
        <p class="text-muted small mb-0"><?= e(__('report.ledger.count', ['count' => count($rows)])) ?></p>
    </div>

    <?php if ($rows === []): ?>
        <p class="text-muted mb-0"><?= e(__('report.ledger.empty')) ?></p>
    <?php else: ?>

        <div class="table-responsive">
            <table class="table table-hover ledger-table mb-0 align-middle">
                <thead>
                <tr>
                    <th><?= e(__('report.ledger.col.ledger_id')) ?></th>
                    <th><?= e(__('patient.field.id')) ?></th>
                    <th><?= e(__('patient.field.name')) ?></th>
                    <th><?= e(__('patient.field.phone')) ?></th>
                    <th><?= e(__('report.ledger.col.type')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.registration_bill')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.registration_gst')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.visit_bill')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.visit_gst')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.pharmacy_bill')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.medicine_gst')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.total_gst')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.total_amount')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.paid_amount')) ?></th>
                    <th class="ledger-money"><?= e(__('report.ledger.col.pending_amount')) ?></th>
                    <th><?= e(__('report.ledger.col.payment_mode')) ?></th>
                    <th><?= e(__('report.ledger.col.due_date')) ?></th>
                    <th><?= e(__('report.ledger.col.status')) ?></th>
                    <th><?= e(__('report.ledger.col.remarks')) ?></th>
                    <th><?= e(__('report.ledger.col.created_at')) ?></th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><strong><?= e((string) $row['ledger_id']) ?></strong></td>
                        <td><?= e((string) $row['patient_code']) ?></td>
                        <td><?= e((string) $row['patient_name']) ?></td>
                        <td><?= e((string) $row['phone']) ?></td>
                        <td>
                            <span class="ledger-type-badge">
                                <?= e(LedgerReport::typeLabel((string) $row['type'])) ?>
                            </span>
                        </td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['registration_bill'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['registration_gst'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['visit_bill'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['visit_gst'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['pharmacy_bill'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['medicine_gst'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['total_gst'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['total_amount'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['paid_amount'])) ?></td>
                        <td class="ledger-money">₹<?= e(LedgerReport::money($row['pending_amount'])) ?></td>
                        <td><?= e(LedgerReport::methodLabel($row['payment_method'] ?? null)) ?></td>
                        <td><?= e((string) $row['due_date']) ?></td>
                        <td><?= e(LedgerReport::statusLabel($row['status'] ?? null)) ?></td>
                        <td><?= e((string) $row['remarks']) ?></td>
                        <td><?= e((string) $row['created_at']) ?></td>
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
