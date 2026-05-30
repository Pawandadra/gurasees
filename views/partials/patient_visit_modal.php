<?php

declare(strict_types=1);

/** @var array<string, string> $visitErrors */
/** @var array<string, mixed> $visitOld */
/** @var string $code */
/** @var list<array{id: int, name: string}> $catalogMedicines */
/** @var array<string, string> $visitBilling */

$visitErrors = $visitErrors ?? [];
$visitOld = $visitOld ?? ['visited_at' => '', 'notes' => ''];
$catalogMedicines = $catalogMedicines ?? [];
$visitBilling = $visitBilling ?? Visit::billingDefaults();
$successMessage = $successMessage ?? null;
$editVisitId = isset($editVisitId) ? (int) $editVisitId : 0;
$isEditVisit = $editVisitId > 0;
$openVisitModal = !$isEditVisit && ($visitErrors !== [] || $successMessage === null);
$openVisitModal = $openVisitModal || ($isEditVisit && $visitErrors !== []);
?>
<div class="modal fade" id="patientVisitModal" tabindex="-1" aria-labelledby="patientVisitModalLabel" aria-hidden="true"
     data-open="<?= $openVisitModal ? '1' : '0' ?>"
     data-edit="<?= $isEditVisit ? '1' : '0' ?>">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down patient-visit-modal-dialog">
        <div class="modal-content">
            <form method="post" action="<?= e(base_url('/patient_view.php')) ?>" class="visit-log-form" id="patientVisitForm"
                  data-visit-confirm="<?= e(__('visit.add.confirm_message')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="code" value="<?= e($code) ?>">
                <input type="hidden" name="action" value="<?= $isEditVisit ? 'update_visit' : 'add_visit' ?>" id="patientVisitFormAction">
                <?php if ($isEditVisit): ?>
                    <input type="hidden" name="visit_id" value="<?= e((string) $editVisitId) ?>" id="patientVisitIdField">
                <?php endif; ?>

                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="patientVisitModalLabel">
                        <?= e($isEditVisit ? __('visit.edit.title') : __('visit.add.title')) ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?= e(__('action.cancel')) ?>"></button>
                </div>

                <div class="modal-body patient-visit-modal-body pt-2">
                    <?php if (isset($visitErrors['_form'])): ?>
                        <div class="alert alert-danger py-2"><?= e($visitErrors['_form']) ?></div>
                    <?php endif; ?>

                    <div class="visit-form-panel patient-visit-details-panel mb-3">
                        <h3 class="visit-form-panel-title mb-2"><?= e(__('visit.form.details')) ?></h3>
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-5">
                                <label for="visited_at" class="form-label"><?= e(__('visit.field.datetime')) ?></label>
                                <input type="datetime-local" class="form-control<?= field_invalid($visitErrors, 'visited_at') ?>"
                                       id="visited_at" name="visited_at" value="<?= e($visitOld['visited_at']) ?>" required>
                                <?php show_field_error($visitErrors, 'visited_at'); ?>
                            </div>
                            <div class="col-sm-6 col-lg-7">
                                <label for="visit_notes" class="form-label">
                                    <?= e(__('visit.field.notes')) ?>
                                    <span class="text-muted fw-normal small">(<?= e(__('patient.field.delivery_optional')) ?>)</span>
                                </label>
                                <input type="text" class="form-control" id="visit_notes" name="notes"
                                       value="<?= e($visitOld['notes']) ?>" maxlength="500"
                                       placeholder="<?= e(__('visit.field.notes_placeholder')) ?>">
                            </div>
                        </div>
                    </div>

                    <?php $deliveryMethod = Visit::parseDeliveryMethod($visitOld); ?>
                    <div class="visit-form-panel patient-visit-delivery-panel mb-3">
                        <h3 class="visit-form-panel-title mb-2"><?= e(__('visit.form.delivery_method')) ?></h3>
                        <div class="visit-delivery-method-group gender-toggle-group" id="visitDeliveryMethodGroup" role="group"
                             aria-label="<?= e(__('visit.form.delivery_method')) ?>"
                             data-initial="<?= e($deliveryMethod) ?>">
                            <?php foreach (Visit::deliveryMethodOptions() as $value => $labelKey): ?>
                                <input type="radio" class="btn-check" name="delivery_method"
                                       id="visit_delivery_<?= e($value) ?>" value="<?= e($value) ?>"
                                       autocomplete="off"<?= $deliveryMethod === $value ? ' checked' : '' ?>>
                                <label class="btn" for="visit_delivery_<?= e($value) ?>"><?= e(__($labelKey)) ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php
                    $visitMedicineLines = $visitOld['medicines'] ?? [];
                    require BASE_PATH . '/views/partials/visit_billing_fields.php';
                    ?>
                </div>

                <div class="modal-footer patient-visit-modal-footer border-0 pt-0">
                    <div class="patient-visit-footer-total" aria-live="polite">
                        <span class="patient-visit-footer-total-label"><?= e(__('visit.field.grand_total')) ?></span>
                        <span class="patient-visit-footer-total-value" id="patientVisitFooterTotal">₹0.00</span>
                    </div>
                    <div class="patient-visit-footer-actions">
                        <button type="button" class="btn btn-outline-secondary" id="patientVisitCancelBtn" data-bs-dismiss="modal">
                            <?= e(__('action.cancel')) ?>
                        </button>
                        <button type="button" class="btn btn-reception-primary confirm-action-trigger" id="patientVisitSubmitBtn"
                                data-confirm-title="<?= e($isEditVisit ? __('visit.edit.confirm_title') : __('visit.add.confirm_title')) ?>"
                                data-confirm="<?= e($isEditVisit ? __('visit.edit.confirm_message') : __('visit.add.confirm_message')) ?>"
                                data-confirm-label="<?= e($isEditVisit ? __('visit.edit.submit') : __('visit.add.submit')) ?>"
                                data-confirm-variant="primary">
                            <?= e($isEditVisit ? __('visit.edit.submit') : __('visit.add.submit')) ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
