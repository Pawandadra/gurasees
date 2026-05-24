<?php

declare(strict_types=1);

/** @var list<array<string, mixed>> $profileHistory */
$profileHistory = $profileHistory ?? [];
if ($profileHistory === []) {
    return;
}
?>
<section class="reception-card patient-profile-history-card">
    <h2 class="reception-card-title h6 mb-2"><?= e(__('patient.history.title')) ?></h2>
    <p class="patient-profile-history-hint"><?= e(__('patient.history.hint')) ?></p>

    <ol class="patient-profile-history-list">
        <?php foreach ($profileHistory as $entry): ?>
            <?php
            $changes = is_array($entry['changes'] ?? null) ? $entry['changes'] : [];
            $editor = trim((string) ($entry['edited_by_name'] ?? ''));
            ?>
            <li class="patient-profile-history-entry">
                <div class="patient-profile-history-entry-meta">
                    <time datetime="<?= e((string) ($entry['edited_at'] ?? '')) ?>">
                        <?= e(PatientProfileHistory::formatEditedAt((string) ($entry['edited_at'] ?? ''))) ?>
                    </time>
                    <?php if ($editor !== ''): ?>
                        <span class="patient-profile-history-editor">
                            <?= e(__('patient.history.edited_by', ['name' => $editor])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                <ul class="patient-profile-history-changes">
                    <?php foreach ($changes as $change): ?>
                        <li class="patient-profile-history-change">
                            <span class="patient-profile-history-change-label"><?= e($change['label']) ?></span>
                            <div class="patient-profile-history-diff">
                                <div class="patient-profile-history-diff-row patient-profile-history-diff-row--previous">
                                    <span class="patient-profile-history-diff-key"><?= e(__('patient.history.previous')) ?></span>
                                    <span class="patient-profile-history-diff-text"><?= e($change['previous']) ?></span>
                                </div>
                                <div class="patient-profile-history-diff-row patient-profile-history-diff-row--new">
                                    <span class="patient-profile-history-diff-key"><?= e(__('patient.history.new')) ?></span>
                                    <span class="patient-profile-history-diff-text"><?= e($change['new']) ?></span>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
        <?php endforeach; ?>
    </ol>
</section>
