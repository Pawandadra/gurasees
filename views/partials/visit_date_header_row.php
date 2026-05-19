<?php

declare(strict_types=1);

/** @var int $colspan */
/** @var string $dateKey */
?>
<tr class="visit-records-date-row">
    <td colspan="<?= (int) $colspan ?>" class="visit-records-date-header">
        <?= e(Visit::formatDateGroupLabel($dateKey)) ?>
    </td>
</tr>
