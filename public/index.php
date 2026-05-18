<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if (auth_check()) {
    auth_redirect_dashboard();
}

redirect(base_url('/login.php'));
