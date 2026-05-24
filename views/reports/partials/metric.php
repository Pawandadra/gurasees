<?php

declare(strict_types=1);

/** @var string $label */
/** @var string $value */
/** @var string $variant */

$variant = $variant ?? '';
$cardClass = 'payment-summary-card report-metric-card';
if ($variant !== '') {
    $cardClass .= ' payment-summary-' . $variant;
}
?>
<div class="<?= e($cardClass) ?>">
    <p class="payment-summary-label mb-1"><?= e($label) ?></p>
    <p class="payment-summary-value mb-0"><?= e($value) ?></p>
</div>
