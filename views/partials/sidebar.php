<?php

declare(strict_types=1);

/** @var string $role */
/** @var string|null $activeNav */

$activeNav = $activeNav ?? nav_active_id();
$navItems = nav_items($role);
?>
<aside class="app-sidebar" id="appSidebar" aria-label="<?= e(__('nav.menu')) ?>">
    <nav class="app-sidebar-nav">
        <ul class="app-sidebar-list">
            <?php foreach ($navItems as $item): ?>
                <?php $active = nav_is_active($item['id'], $activeNav); ?>
                <li>
                    <a href="<?= e(base_url($item['url'])) ?>"
                       class="app-sidebar-link<?= $active ? ' active' : '' ?>"
                       <?= $active ? ' aria-current="page"' : '' ?>>
                        <span class="app-sidebar-icon" aria-hidden="true">
                            <?php
                            $iconFile = BASE_PATH . '/views/partials/icons/' . $item['icon'] . '.php';
                            if (is_readable($iconFile)) {
                                require $iconFile;
                            }
                            ?>
                        </span>
                        <span class="app-sidebar-label"><?= e($item['label']) ?></span>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>
</aside>
<div class="app-sidebar-backdrop" id="sidebarBackdrop" hidden></div>
