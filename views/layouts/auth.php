<?php

declare(strict_types=1);

$locale = locale();
?>
<!DOCTYPE html>
<html lang="<?= $locale === 'pa' ? 'pa' : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e(__('auth.login_title')) ?> — <?= e(__('app.name')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?= e(base_url('assets/css/app.css')) ?>" rel="stylesheet">
</head>
<body class="auth-body">
<div class="auth-lang position-absolute top-0 end-0 p-3">
    <div class="btn-group btn-group-sm">
        <a href="<?= e(lang_url('en')) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('lang.english')) ?></a>
        <a href="<?= e(lang_url('pa')) ?>" class="btn btn-outline-secondary btn-sm"><?= e(__('lang.punjabi')) ?></a>
    </div>
</div>

<main class="auth-main">
    <?= $content ?? '' ?>
</main>

<script src="<?= e(base_url('assets/js/login.js')) ?>" defer></script>
</body>
</html>
