<?php

declare(strict_types=1);

/** @var string $phoneIso */

$selected = phone_country($phoneIso) ?? phone_country('IN');
if ($selected === null) {
    return;
}
?>
<div class="phone-country-picker">
    <input type="hidden" name="phone_iso" value="<?= e($selected['iso']) ?>">
    <button type="button" class="phone-country-trigger"
            aria-haspopup="listbox" aria-expanded="false" aria-controls="phone_iso_listbox"
            aria-label="<?= e(__('patient.field.phone_country')) ?>">
        <span class="phone-country-value"><?= e(phone_compact_label($selected)) ?></span>
    </button>
    <div class="phone-country-menu" id="phone_iso_listbox" role="listbox" hidden>
        <div class="phone-country-menu-inner">
            <?php foreach (phone_countries() as $country): ?>
                <button type="button" class="phone-country-option" role="option"
                        data-iso="<?= e($country['iso']) ?>"
                        data-compact="<?= e(phone_compact_label($country)) ?>"
                        data-search="<?= e(phone_search_text($country)) ?>"
                        aria-selected="<?= $selected['iso'] === $country['iso'] ? 'true' : 'false' ?>">
                    <span class="phone-country-option-code"><?= e(phone_compact_label($country)) ?></span>
                    <span class="phone-country-option-name"><?= e($country['name']) ?></span>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</div>
