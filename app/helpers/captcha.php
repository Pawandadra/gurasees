<?php

declare(strict_types=1);

/**
 * Image CAPTCHA (requires PHP GD extension).
 */
function captcha_refresh(): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $_SESSION['captcha_code'] = $code;
    $_SESSION['captcha_time'] = time();

    return $code;
}

function captcha_verify(string $input): bool
{
    $expected = $_SESSION['captcha_code'] ?? '';
    $time = (int) ($_SESSION['captcha_time'] ?? 0);
    unset($_SESSION['captcha_code'], $_SESSION['captcha_time']);

    if ($expected === '' || time() - $time > 600) {
        return false;
    }

    $input = preg_replace('/\s+/', '', $input);

    return hash_equals($expected, $input);
}

function captcha_output_image(): void
{
    if (!extension_loaded('gd')) {
        http_response_code(500);
        exit((bool) config('debug') ? 'GD extension required for CAPTCHA.' : production_error_message());
    }

    $code = captcha_refresh();
    $width = 160;
    $height = 50;

    $img = imagecreatetruecolor($width, $height);
    if ($img === false) {
        http_response_code(500);
        exit;
    }

    $bg = imagecolorallocate($img, 232, 244, 240);
    $textColor = imagecolorallocate($img, 13, 92, 77);
    $lineColor = imagecolorallocate($img, 180, 200, 195);
    $noiseColor = imagecolorallocate($img, 100, 140, 130);

    imagefill($img, 0, 0, $bg);

    for ($i = 0; $i < 5; $i++) {
        imageline(
            $img,
            random_int(0, $width),
            random_int(0, $height),
            random_int(0, $width),
            random_int(0, $height),
            $lineColor
        );
    }

    for ($i = 0; $i < 80; $i++) {
        imagesetpixel($img, random_int(0, $width - 1), random_int(0, $height - 1), $noiseColor);
    }

    $x = 18;
    for ($i = 0, $len = strlen($code); $i < $len; $i++) {
        imagestring(
            $img,
            5,
            $x + random_int(-2, 2),
            14 + random_int(-3, 3),
            $code[$i],
            $textColor
        );
        $x += 26;
    }

    header('Content-Type: image/png');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Pragma: no-cache');
    imagepng($img);
    imagedestroy($img);
    exit;
}
