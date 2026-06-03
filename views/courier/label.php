<?php

declare(strict_types=1);

/** @var array<string, mixed> $package */
/** @var array{name: string, phone: string, address: string} $sender */
/** @var string $labelDate */
/** @var string $listUrl */

$lines = $package['courier_lines'] ?? [];
$fromPhone = trim($sender['phone']);
$deliveryMethodLabel = (string) ($package['delivery_method_label'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e(__('courier.label.title')) ?> — <?= e((string) $package['patient_name']) ?></title>
    <link href="<?= e(base_url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="courier-label-body">
<div class="courier-label-page">
    <article class="courier-label-sheet">
        <p class="courier-label-date">
            <span class="courier-label-meta-key"><?= e(__('courier.label.date')) ?>:</span>
            <strong><?= e($labelDate) ?></strong>
        </p>

        <header class="courier-label-header courier-label-to-section">
            <h2 class="courier-label-section-title"><?= e(__('courier.label.to')) ?></h2>
            <p class="courier-label-party-name"><?= e((string) $package['patient_name']) ?></p>
            <p class="courier-label-party-phone">
                <?= e(__('patient.field.phone')) ?>:
                <strong><?= e(phone_format_display((string) $package['phone'])) ?></strong>
            </p>
            <?php if (trim((string) ($package['additional_phone'] ?? '')) !== ''): ?>
                <p class="courier-label-party-phone">
                    <?= e(__('patient.field.additional_phone')) ?>:
                    <strong><?= e(phone_format_display((string) $package['additional_phone'])) ?></strong>
                </p>
            <?php endif; ?>
            <p class="courier-label-party-address"><?= nl2br(e((string) $package['delivery_display'])) ?></p>
        </header>

        <section class="courier-label-section courier-label-from-section">
            <h2 class="courier-label-section-title"><?= e(__('courier.label.from')) ?></h2>
            <p class="courier-label-party-name"><?= e($sender['name']) ?></p>
            <?php if ($fromPhone !== ''): ?>
                <p class="courier-label-party-phone">
                    <?= e(__('patient.field.phone')) ?>:
                    <strong><?= e($fromPhone) ?></strong>
                </p>
            <?php endif; ?>
            <?php if (trim($sender['address']) !== ''): ?>
                <p class="courier-label-party-address"><?= nl2br(e($sender['address'])) ?></p>
            <?php endif; ?>
        </section>

        <section class="courier-label-section courier-label-medicines-section">
            <p class="courier-label-delivery-method">
                <span class="courier-label-meta-key"><?= e(__('visit.form.delivery_method')) ?>:</span>
                <strong><?= e($deliveryMethodLabel) ?></strong>
            </p>
            <?php if ($lines !== []): ?>
                <h2 class="courier-label-section-title"><?= e(__('courier.field.medicines')) ?></h2>
                <ul class="courier-label-medicines list-unstyled mb-0">
                    <?php foreach ($lines as $line): ?>
                        <li><?= e($line['name']) ?> <strong>×<?= (int) $line['quantity'] ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </article>

    <div class="courier-label-toolbar no-print">
        <button type="button" class="courier-label-btn courier-label-btn-print" id="courierLabelPrint">
            <?= e(__('courier.label.print')) ?>
        </button>
        <button type="button" class="courier-label-btn courier-label-btn-cancel" id="courierLabelCancel"
                data-back-url="<?= e($listUrl) ?>">
            <?= e(__('action.cancel')) ?>
        </button>
    </div>
</div>

<script src="<?= e(base_url('assets/js/courier-label.js')) ?>"></script>
</body>
</html>
