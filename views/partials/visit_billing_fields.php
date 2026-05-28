<?php

declare(strict_types=1);

/** @var list<array{id: int, name: string}> $catalogMedicines */
/** @var array<string, string> $visitOld */
/** @var list<array{medicine_id: int, quantity: int}> $visitMedicineLines */
/** @var array<string, string> $visitErrors */
/** @var array<string, string> $visitBilling */

$catalogMedicines = $catalogMedicines ?? [];
$visitMedicineLines = $visitMedicineLines ?? [];
$visitErrors = $visitErrors ?? [];
$visitBilling = $visitBilling ?? Visit::billingDefaults();
$visitOld = $visitOld ?? [];
?>
<div class="visit-billing-layout"
     id="visitBilling"
     data-medicines="<?= e(json_encode($catalogMedicines, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-gst-visit="<?= e($visitBilling['gst_visit_percent']) ?>"
     data-gst-medicine="<?= e($visitBilling['gst_medicine_percent']) ?>"
     data-gst-courier="<?= e($visitBilling['gst_courier_percent']) ?>"
     data-label-empty="<?= e(__('visit.medicine.search_empty')) ?>"
     data-label-qty="<?= e(__('visit.medicine.field.quantity')) ?>"
     data-label-remove="<?= e(__('visit.medicine.remove_line')) ?>"
     data-label-courier="<?= e(__('visit.courier.add_to_list')) ?>"
     data-currency="₹">

    <?php if (isset($visitErrors['medicines'])): ?>
        <div class="alert alert-danger py-2 small mb-3"><?= e($visitErrors['medicines']) ?></div>
    <?php endif; ?>

    <div class="row g-3 g-md-4 align-items-stretch patient-visit-billing-row">
        <div class="col-md-7 patient-visit-medicines-col">
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
                        <div class="table-responsive visit-medicine-cart-table-wrap">
                        <table class="table table-sm visit-medicine-table mb-0 d-none" id="visitMedicineTable">
                            <thead>
                            <tr>
                                <th scope="col"><?= e(__('visit.medicine.table.name')) ?></th>
                                <th scope="col" class="text-center visit-medicine-th-qty"><?= e(__('visit.medicine.field.quantity')) ?></th>
                                <th scope="col" class="text-center visit-medicine-th-courier"
                                    id="visitCourierToggleAll"
                                    role="button"
                                    tabindex="0"
                                    title="<?= e(__('visit.courier.column')) ?>">
                                    <?= e(__('visit.courier.column')) ?>
                                </th>
                                <th scope="col" class="visit-medicine-th-action"><span class="visually-hidden"><?= e(__('visit.medicine.table.action')) ?></span></th>
                            </tr>
                            </thead>
                            <tbody id="visitMedicineCart"></tbody>
                        </table>
                        </div>
                        <p class="visit-medicine-cart-empty text-muted small mb-0" id="visitMedicineCartEmpty">
                            <?= e(__('visit.medicine.cart_empty')) ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-md-5 patient-visit-charges-col">
            <div class="visit-summary-panel">
                <h3 class="visit-form-panel-title mb-3"><?= e(__('visit.form.charges_summary')) ?></h3>

                <dl class="visit-billing-summary mb-3" id="visitBillingSummary">
                    <div class="visit-billing-summary-row visit-billing-visit-charge-input-row">
                        <dt>
                            <label for="visit_charge" class="form-label mb-0"><?= e(__('visit.field.visit_charge')) ?></label>
                        </dt>
                        <dd>
                            <div class="input-group input-group-sm visit-visit-charge-input justify-content-end">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control text-end<?= field_invalid($visitErrors, 'visit_charge') ?>"
                                       id="visit_charge" name="visit_charge" min="0" step="0.01" required
                                       value="<?= e((string) ($visitOld['visit_charge'] ?? ($visitBilling['visit_charge'] ?? '0'))) ?>">
                            </div>
                            <?php show_field_error($visitErrors, 'visit_charge'); ?>
                        </dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-summary-row--detail">
                        <dt id="summaryVisitGstLabel"><?= e(__('visit.field.visit_gst', ['percent' => $visitBilling['gst_visit_percent']])) ?></dt>
                        <dd id="summaryVisitGst">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-base-row visit-billing-summary-row--detail">
                        <dt><?= e(__('visit.field.visit_charge')) ?> (<?= e(__('payment.field.without_gst_col')) ?>)</dt>
                        <dd id="summaryVisitBase">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-medicine-total-row">
                        <dt>
                            <label for="medicine_total" class="form-label mb-0"><?= e(__('visit.field.medicine_total')) ?></label>
                        </dt>
                        <dd>
                            <div class="input-group input-group-sm visit-medicine-total-input justify-content-end">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control text-end<?= field_invalid($visitErrors, 'medicine_total') ?>"
                                       id="medicine_total" name="medicine_total" min="0" step="0.01"
                                       value="<?= e((string) ($visitOld['medicine_total'] ?? '')) ?>">
                            </div>
                            <?php show_field_error($visitErrors, 'medicine_total'); ?>
                        </dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-summary-row--detail">
                        <dt id="summaryMedicineGstLabel"><?= e(__('visit.field.medicine_gst', ['percent' => $visitBilling['gst_medicine_percent']])) ?></dt>
                        <dd id="summaryMedicineGst">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-base-row visit-billing-summary-row--detail">
                        <dt><?= e(__('visit.field.medicine_total')) ?> (<?= e(__('payment.field.without_gst_col')) ?>)</dt>
                        <dd id="summaryMedicineBase">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-courier-row visit-billing-courier-total-row d-none">
                        <dt>
                            <label for="courier_charge" class="form-label mb-0"><?= e(__('visit.field.courier_charge')) ?></label>
                        </dt>
                        <dd>
                            <div class="input-group input-group-sm visit-courier-charge-input justify-content-end">
                                <span class="input-group-text">₹</span>
                                <input type="number" class="form-control text-end<?= field_invalid($visitErrors, 'courier_charge') ?>"
                                       id="courier_charge" name="courier_charge" min="0" step="0.01"
                                       value="<?= e((string) ($visitOld['courier_charge'] ?? '')) ?>">
                            </div>
                            <?php show_field_error($visitErrors, 'courier_charge'); ?>
                        </dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-courier-row visit-billing-summary-row--detail d-none">
                        <dt id="summaryCourierGstLabel"><?= e(__('visit.field.courier_gst', ['percent' => $visitBilling['gst_courier_percent']])) ?></dt>
                        <dd id="summaryCourierGst">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-courier-row visit-billing-base-row visit-billing-summary-row--detail d-none">
                        <dt><?= e(__('visit.field.courier_charge')) ?> (<?= e(__('payment.field.without_gst_col')) ?>)</dt>
                        <dd id="summaryCourierBase">₹0.00</dd>
                    </div>
                    <div class="visit-billing-summary-row visit-billing-summary-total">
                        <dt><?= e(__('visit.field.grand_total')) ?></dt>
                        <dd id="summaryGrandTotal">₹0.00</dd>
                    </div>
                </dl>

                <?php require BASE_PATH . '/views/partials/visit_payment_fields.php'; ?>
            </div>
        </div>
    </div>

    <div id="visitMedicineHiddenInputs" class="visually-hidden" aria-hidden="true"></div>
    <span id="summaryVisitCharge" class="visually-hidden" aria-hidden="true">₹0.00</span>
</div>
