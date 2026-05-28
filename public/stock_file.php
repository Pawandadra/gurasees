<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
load_model('StockBill');

auth_require();
auth_require_role(['receptionist', 'manager', 'admin']);

$user = auth_user();
$viewerId = $user !== null ? (int) $user['id'] : 0;
$viewerRole = $user['role'] ?? '';

$id = filter_var($_GET['id'] ?? '', FILTER_VALIDATE_INT);
$id = $id !== false && $id > 0 ? (int) $id : 0;

$bill = $id > 0 ? StockBill::findById($id, $viewerId, $viewerRole) : null;
if ($bill === null || empty($bill['file_stored_name'])) {
    http_response_code(404);
    exit(__('stock.error.file_not_found'));
}

$path = stock_bill_absolute_path((string) $bill['file_stored_name']);
if ($path === null) {
    http_response_code(404);
    exit(__('stock.error.file_not_found'));
}

$mime = (string) ($bill['file_mime'] ?? 'application/octet-stream');
$name = (string) ($bill['file_original_name'] ?? 'bill-attachment');
$inline = str_starts_with($mime, 'image/') || $mime === 'application/pdf';

header('Content-Type: ' . $mime);
header('Content-Length: ' . (string) filesize($path));
header(
    'Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . str_replace('"', '', $name) . '"'
);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');

readfile($path);
exit;
