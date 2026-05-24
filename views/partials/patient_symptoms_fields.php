<?php

declare(strict_types=1);

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$selected = array_map('intval', (array) ($old['symptoms'] ?? []));

try {
    $symptomOptions = Symptom::listActive();
} catch (Throwable) {
    $symptomOptions = [];
}

$symptomCatalog = array_map(
    static fn(array $row): array => ['id' => (int) $row['id'], 'name' => (string) $row['label']],
    $symptomOptions
);

$selectedSet = array_flip($selected);
$initialSymptoms = [];
foreach ($symptomOptions as $symptom) {
    $sid = (int) $symptom['id'];
    if (isset($selectedSet[$sid])) {
        $initialSymptoms[] = ['symptom_id' => $sid];
    }
}
?>
<div class="patient-symptoms-section mt-3"
     id="patientSymptomsPicker"
     data-symptoms="<?= e(json_encode($symptomCatalog, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>"
     data-label-empty="<?= e(__('patient.symptom.search_empty')) ?>"
     data-label-remove="<?= e(__('patient.symptom.remove')) ?>"
     data-label-selected="<?= e(__('patient.symptom.selected_list')) ?>"
     data-initial="<?= e(json_encode($initialSymptoms, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) ?>">

    <span class="form-label d-block mb-2"><?= e(__('patient.field.symptoms')) ?></span>

    <?php if ($symptomOptions === []): ?>
        <p class="text-muted small mb-0"><?= e(__('symptom.list.empty_reception')) ?></p>
    <?php else: ?>
        <div class="patient-symptom-picker-body">
            <div class="patient-symptom-search-col">
                <div class="visit-medicine-search patient-symptom-search" id="patientSymptomSearchWrap">
                    <label for="patientSymptomSearchInput" class="form-label visually-hidden">
                        <?= e(__('patient.symptom.search')) ?>
                    </label>
                    <div class="input-group">
                        <span class="input-group-text visit-search-icon" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                        </span>
                        <input type="search" class="form-control visit-medicine-search-input" id="patientSymptomSearchInput"
                               autocomplete="off" placeholder="<?= e(__('patient.symptom.search_placeholder')) ?>"
                               aria-controls="patientSymptomSearchResults" aria-expanded="false" aria-autocomplete="list">
                    </div>
                    <div class="visit-medicine-search-results patient-symptom-results-panel" id="patientSymptomSearchResults" role="listbox" hidden></div>
                </div>
            </div>

            <div class="patient-symptom-list-col">
                <span class="form-label small text-muted mb-2 d-block"><?= e(__('patient.symptom.selected_list')) ?></span>
                <ul class="list-group patient-symptom-list mb-0 d-none" id="patientSymptomList"></ul>
                <p class="patient-symptom-list-empty text-muted small mb-0" id="patientSymptomListEmpty">
                    <?= e(__('patient.symptom.list_empty')) ?>
                </p>
            </div>
        </div>

        <div id="patientSymptomHiddenInputs" class="visually-hidden" aria-hidden="true"></div>
    <?php endif; ?>
</div>
