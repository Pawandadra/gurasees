<?php

declare(strict_types=1);

/** @var string $role */
/** @var string|null $activeNav */

$activeNav = $activeNav ?? nav_active_id();
$navItems = nav_items($role);
?>
<aside class="app-sidebar" id="appSidebar" aria-label="<?= e(__('nav.menu')) ?>">
    <nav class="app-sidebar-nav app-sidebar-nav--stack">
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

        <div class="app-sidebar-footer">
            <div class="app-sidebar-footer-row">
                <a href="<?= e(base_url('/profile.php')) ?>"
                   class="app-sidebar-link app-sidebar-footer-link<?= $activeNav === 'profile' ? ' active' : '' ?>"
                   <?= $activeNav === 'profile' ? ' aria-current="page"' : '' ?>>
                    <span class="app-sidebar-icon" aria-hidden="true">
                        <?php require BASE_PATH . '/views/partials/icons/users.php'; ?>
                    </span>
                    <span class="app-sidebar-label"><?= e(__('nav.profile')) ?></span>
                </a>

                <form method="post" action="<?= e(base_url('/logout.php')) ?>" class="app-sidebar-logout-form">
                    <?= csrf_field() ?>
                    <button type="button"
                            class="app-sidebar-link app-sidebar-link--button app-sidebar-footer-link confirm-action-trigger"
                            data-confirm-title="<?= e(__('nav.logout_confirm_title')) ?>"
                            data-confirm="<?= e(__('nav.logout_confirm_message')) ?>"
                            data-confirm-label="<?= e(__('nav.logout')) ?>"
                            data-confirm-variant="danger">
                        <span class="app-sidebar-icon" aria-hidden="true">
                            <?php require BASE_PATH . '/views/partials/icons/cancel.php'; ?>
                        </span>
                        <span class="app-sidebar-label"><?= e(__('nav.logout')) ?></span>
                    </button>
                </form>
            </div>
        </div>
    </nav>
</aside>
<div class="app-sidebar-backdrop" id="sidebarBackdrop" hidden></div>
