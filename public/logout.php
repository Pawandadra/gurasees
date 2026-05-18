<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('/login.php'));
}

csrf_require();
auth_logout();
redirect(base_url('/login.php'));
