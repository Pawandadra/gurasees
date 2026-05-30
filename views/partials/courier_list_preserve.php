<?php

declare(strict_types=1);

/** @var string $sort */
/** @var string $dir */
/** @var array{q: string, status: string, delivery_method: string} $listFilters */

$sort = $sort ?? 'date';
$dir = $dir ?? 'desc';
$listFilters = $listFilters ?? ['q' => '', 'status' => '', 'delivery_method' => ''];
?>
<input type="hidden" name="sort" value="<?= e($sort) ?>">
<input type="hidden" name="dir" value="<?= e($dir) ?>">
<input type="hidden" name="q" value="<?= e($listFilters['q']) ?>">
<input type="hidden" name="status" value="<?= e($listFilters['status']) ?>">
<input type="hidden" name="delivery_method" value="<?= e($listFilters['delivery_method']) ?>">
