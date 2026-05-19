<?php

declare(strict_types=1);

$headerSearchRole = auth_user()['role'] ?? '';
$headerRegisterUrl = $headerSearchRole === 'receptionist'
    ? base_url('/dashboard.php#patient-register')
    : '';
?>
<div class="header-patient-search" id="headerPatientSearch"
     data-search-url="<?= e(base_url('/patient_search.php')) ?>"
     data-empty-label="<?= e(__('search.patient.empty')) ?>"
     <?php if ($headerRegisterUrl !== ''): ?>
     data-register-url="<?= e($headerRegisterUrl) ?>"
     data-register-label="<?= e(__('search.patient.register')) ?>"
     <?php endif; ?>>
    <label class="visually-hidden" for="headerPatientSearchInput"><?= e(__('search.patient.label')) ?></label>
    <input type="search" class="form-control form-control-sm header-patient-search-input"
           id="headerPatientSearchInput" name="q" autocomplete="off"
           placeholder="<?= e(__('search.patient.placeholder')) ?>"
           aria-controls="headerPatientSearchResults" aria-expanded="false" aria-autocomplete="list">
    <div class="header-patient-search-results" id="headerPatientSearchResults" role="listbox" hidden></div>
</div>
