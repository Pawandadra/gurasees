<?php

declare(strict_types=1);

/** @var list<array{id: int, name: string, unit_price: string}> $catalogMedicines */
/** @var list<array{medicine_id: int, quantity: int}> $visitMedicineLines */
/** @var array<string, string> $visitErrors */
/** @var array<string, string> $visitBilling */

$catalogMedicines = $catalogMedicines ?? [];
$visitMedicineLines = $visitMedicineLines ?? [];
$visitErrors = $visitErrors ?? [];
$visitBilling = $visitBilling ?? Visit::billingDefaults();
?>
<div class="visit-billing-layout"
     id="visitBilling"
     data-medicines="<?= e(json_encode($catalogMedicines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-gst-visit="<?= e($visitBilling['gst_visit_percent']) ?>"
     data-gst-medicine="<?= e($visitBilling['gst_medicine_percent']) ?>"
     data-label-empty="<?= e(__('visit.medicine.search_empty')) ?>"
     data-label-qty="<?= e(__('visit.medicine.field.quantity')) ?>"
     data-label-remove="<?= e(__('visit.medicine.remove_line')) ?>"
     data-currency="₹">

    <?php if (isset($visitErrors['medicines'])): ?>
        <div class="alert alert-danger py-2 small mb-3"><?= e($visitErrors['medicines']) ?></div>
    <?php endif; ?>

    <div class="row g-3 g-lg-4 align-items-start">
        <div class="col-lg-7">
            <div class="visit-form-panel h-100">
                <div class="visit-form-panel-header">
                    <h3 class="visit-form-panel-title mb-0"><?= e(__('visit.medicine.title')) ?></h3>
                    <p class="visit-form-panel-hint mb-0"><?= e(__('visit.medicine.search_hint')) ?></p>
                </div>

                <?php if ($catalogMedicines === []): ?>
                    <p class="text-muted small mb-0"><?= e(__('visit.medicine.none_available')) ?></p>
                <?php else: ?>
                    <div class="visit-medicine-search" id="visitMedicineSearchWrap">
                        <label for="visitMedicineSearchInput" class="form-label visually-hidden">
                            <?= e(__('visit.medicine.search')) ?>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text visit-search-icon" aria-hidden="true">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                            </span>
                            <input type="search" class="form-control visit-medicine-search-input" id="visitMedicineSearchInput"
                                   autocomplete="off" placeholder="<?= e(__('visit.medicine.search_placeholder')) ?>"
                                   aria-controls="visitMedicineSearchResults" aria-expanded="false" aria-autocomplete="list">
                        </div>
                        <div class="visit-medicine-search-results" id="visitMedicineSearchResults" role="listbox" hidden></div>
                    </div>

                    <div class="visit-medicine-cart-wrap mt-3"
                         data-initial="<?= e(json_encode($visitMedicineLines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">
                        <table class="table table-sm visit-medicine-table mb-0 d-none" id="visitMedicineTable">
                            <thead>
                            <tr>
                                <th scope="col"><?= e(__('visit.medicine.table.name')) ?></th>
                                <th scope="col" class="text-center visit-medicine-th-qty"><?= e(__('visit.medicine.field.quantity')) ?></th>
                                <th scope="col" class="text-end"><?= e(__('visit.medicine.table.amount')) ?></th>
                                <th scope="col" class="visit-medicine-th-action"><span class="visually-hidden"><?= e(__('visit.medicine.table.action')) ?></span></th>
                            </tr>
                            </thead>
                            <tbody id="visitMedicineCart"></tbody>
                        </table>
                        <p class="visit-medicine-cart-empty text-muted small mb-0" id="visitMedicineCartEmpty">
                            <?= e(__('visit.medicine.cart_empty')) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="visit-summary-panel">
                <h3 class="visit-form-panel-title mb-3"><?= e(__('visit.form.charges_summary')) ?></h3>

                <dl class="visit-billing-summary mb-4" id="visitBillingSummary">
                    <div class="visit-billing-summary-row">
                        <dt><?= e(__('visit.field.visit_charge')) ?></dt>
                        <dd id="summaryVisitCharge">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row">
                        <dt id="summaryVisitGstLabel"><?= e(__('visit.field.visit_gst', ['percent' => $visitBilling['gst_visit_percent']])) ?></dt>
                        <dd id="summaryVisitGst">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row">
                        <dt><?= e(__('visit.field.medicines_subtotal')) ?></dt>
                        <dd id="summaryMedicineSubtotal">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row">
                        <dt id="summaryMedicineGstLabel"><?= e(__('visit.field.medicine_gst', ['percent' => $visitBilling['gst_medicine_percent']])) ?></dt>
                        <dd id="summaryMedicineGst">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-summary-total">
                        <dt><?= e(__('visit.field.grand_total')) ?></dt>
                        <dd id="summaryGrandTotal">₹0.00</dd>
                    </div>
                </dl>

                <button type="submit" class="btn btn-reception-primary btn-lg w-100">
                    <?= e(__('visit.add.submit')) ?>
                </button>
            </div>
        </div>
    </div>

    <div id="visitMedicineHiddenInputs" class="visually-hidden" aria-hidden="true"></div>
</div>
