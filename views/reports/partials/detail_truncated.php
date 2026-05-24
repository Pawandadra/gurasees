<?php

declare(strict_types=1);

/** @var int $rowsShown */
/** @var int $rowsTotal */

if ($rowsTotal <= $rowsShown) {
    return;
}
?>
<p class="text-muted small mb-0 mt-2">
    <?= e(__('report.detail.truncated', [
        'shown' => $rowsShown,
        'total' => $rowsTotal,
        'limit' => Report::DETAIL_LIMIT_UI,
    ])) ?>
</p>
