<?php

declare(strict_types=1);

/** @return array{ok: true, relative: string, original: string, mime: string, size: int}|array{ok: false, error: string} */
function stock_bill_store_upload(array $file, int $billId): array
{
    if ($billId < 1) {
        return ['ok' => false, 'error' => __('stock.error.upload')];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => __('validation.required')];
    }

    $error = (int) ($file['error'] ?? UPLOAD_ERR_OK);
    if ($error !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => __('stock.error.upload')];
    }

    $size = (int) ($file['size'] ?? 0);
    $maxBytes = 2 * 1024 * 1024;
    if ($size < 1 || $size > $maxBytes) {
        return ['ok' => false, 'error' => __('stock.error.file_size')];
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => __('stock.error.upload')];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp) ?: '';
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => __('stock.error.file_type')];
    }

    $dir = stock_bill_upload_dir($billId);
    if (!is_dir($dir) && !mkdir($dir, 0750, true) && !is_dir($dir)) {
        return ['ok' => false, 'error' => __('stock.error.upload')];
    }

    $stored = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $absolute = $dir . '/' . $stored;
    if (!move_uploaded_file($tmp, $absolute)) {
        return ['ok' => false, 'error' => __('stock.error.upload')];
    }

    @chmod($absolute, 0640);

    $original = basename((string) ($file['name'] ?? 'attachment'));
    $original = preg_replace('/[^\w.\- ]+/u', '_', $original) ?? 'attachment';
    $original = mb_substr(trim($original), 0, 255);
    if ($original === '') {
        $original = 'attachment.' . $allowed[$mime];
    }

    return [
        'ok' => true,
        'relative' => stock_bill_relative_path($billId, $stored),
        'original' => $original,
        'mime' => $mime,
        'size' => $size,
    ];
}

function stock_bill_upload_base(): string
{
    return BASE_PATH . '/storage/uploads/stock';
}

function stock_bill_upload_dir(int $billId): string
{
    return stock_bill_upload_base() . '/' . $billId;
}

function stock_bill_relative_path(int $billId, string $storedName): string
{
    return $billId . '/' . $storedName;
}

function stock_bill_absolute_path(string $relative): ?string
{
    $relative = str_replace(['\\', '..'], ['/', ''], trim($relative));
    if ($relative === '' || !preg_match('/^\d+\/[A-Za-z0-9._-]+$/', $relative)) {
        return null;
    }

    $absolute = stock_bill_upload_base() . '/' . $relative;

    return is_readable($absolute) ? $absolute : null;
}

function stock_bill_delete_file(?string $relative): void
{
    if ($relative === null || $relative === '') {
        return;
    }

    $absolute = stock_bill_absolute_path($relative);
    if ($absolute !== null && is_file($absolute)) {
        @unlink($absolute);
    }

    $parts = explode('/', str_replace('\\', '/', $relative), 2);
    if (count($parts) === 2) {
        stock_bill_delete_bill_files((int) $parts[0]);
    }
}

function stock_bill_delete_bill_files(int $billId): void
{
    if ($billId < 1) {
        return;
    }

    $dir = stock_bill_upload_dir($billId);
    if (!is_dir($dir)) {
        return;
    }

    foreach (glob($dir . '/*') ?: [] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
