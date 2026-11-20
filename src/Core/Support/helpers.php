<?php

declare(strict_types=1);

function e(null|string|int|float $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Format money with 3 decimal places to match DECIMAL(10,3) / PDF Pepsi $6.885. */
function money(null|string|int|float $value): string
{
    return number_format((float) $value, 3, '.', ',');
}

function csp_nonce(): string
{
    static $nonce = null;
    if (!is_string($nonce)) {
        $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
    }

    return $nonce;
}

/** Encode data for safe embedding inside a <script> tag. */
function json_for_script(mixed $value): string
{
    return json_encode(
        $value,
        JSON_THROW_ON_ERROR
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
        | JSON_UNESCAPED_UNICODE,
    );
}

/** True when the value contains HTML/script markers that should be rejected. */
function contains_html(string $value): bool
{
    return $value !== strip_tags($value) || preg_match('/[<>]/u', $value) === 1;
}

function url(string $path = '/'): string
{
    return App\Core\Http\Url::to($path);
}

/** A versioned URL lets immutable browser caching remain safe after deploys. */
function asset_url(string $path): string
{
    $absolute = dirname(__DIR__, 3) . '/public/' . ltrim($path, '/');
    $version = is_file($absolute) ? (string) filemtime($absolute) : '1';

    return url('/' . ltrim($path, '/')) . '?v=' . rawurlencode($version);
}

function product_image_directory(): string
{
    return dirname(__DIR__, 3) . '/public/assets/products';
}

function product_image_path(string $slug): ?string
{
    $dir = product_image_directory();
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        $path = $dir . '/' . $slug . '.' . $ext;
        if (is_file($path)) {
            return $path;
        }
    }

    return null;
}

function product_image(string $slug): ?string
{
    $path = product_image_path($slug);
    if ($path === null) {
        return null;
    }

    return asset_url('assets/products/' . rawurlencode(basename($path)));
}

/** @param array<string, mixed>|null $file */
function store_product_image(string $slug, ?array $file): ?string
{
    if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return 'Image upload failed.';
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return 'Image upload failed.';
    }

    if ((int) ($file['size'] ?? 0) > 2_000_000) {
        return 'Image must be 2MB or smaller.';
    }

    $mimeMap = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
    ];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    if (!isset($mimeMap[$mime])) {
        return 'Use a PNG, JPG, or WebP image.';
    }

    $dir = product_image_directory();
    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        return 'Could not save product image.';
    }

    delete_product_image($slug);

    $destPng = $dir . '/' . $slug . '.png';
    if (extension_loaded('gd')) {
        $image = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmp),
            'image/png' => @imagecreatefrompng($tmp),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($tmp) : false,
            default => false,
        };

        if ($image !== false) {
            if (function_exists('imagealphablending') && function_exists('imagesavealpha')) {
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            $ok = imagepng($image, $destPng, 6);
            imagedestroy($image);
            if ($ok) {
                return null;
            }
        }
    }

    $fallback = $dir . '/' . $slug . '.' . $mimeMap[$mime];
    if (!move_uploaded_file($tmp, $fallback)) {
        return 'Could not save product image.';
    }

    return null;
}

function delete_product_image(string $slug): void
{
    $dir = product_image_directory();
    foreach (['png', 'jpg', 'jpeg', 'webp'] as $ext) {
        $path = $dir . '/' . $slug . '.' . $ext;
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function machine_image(): string
{
    return asset_url('assets/vending_machine.png');
}

function brand_logo(): string
{
    return asset_url('assets/vendhub-brand.png');
}

function brand_mark(): string
{
    return asset_url('assets/vendhub-brand.png');
}

function product_category(\App\Model\Product|string $productOrSlug): string
{
    if ($productOrSlug instanceof \App\Model\Product) {
        return $productOrSlug->category !== '' ? $productOrSlug->category : 'Drink';
    }

    // Legacy slug fallback for old call sites.
    return in_array($productOrSlug, ['kitkat', 'lays', 'oreo'], true) ? 'Snack' : 'Drink';
}

function icon(string $name, int $size = 16): string
{
    $icons = [
        'cart' => '<path d="M3 5h2l1.2 8.2a2 2 0 0 0 2 1.8h7.4a2 2 0 0 0 2-1.6L19 8H7"/><circle cx="9" cy="19" r="1.2"/><circle cx="16" cy="19" r="1.2"/>',
        'login' => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/>',
        'user' => '<circle cx="12" cy="8" r="3.2"/><path d="M5 19a7 7 0 0 1 14 0"/>',
        'user-plus' => '<circle cx="10" cy="8" r="3"/><path d="M3 19a7 7 0 0 1 12-4.5"/><path d="M17 11v6"/><path d="M14 14h6"/>',
        'plus' => '<path d="M12 5v14"/><path d="M5 12h14"/>',
        'check' => '<path d="m5 12 4.5 4.5L19 7"/>',
        'trash' => '<path d="M4 7h16"/><path d="M9 7V5h6v2"/><path d="m7 7 1 12h8l1-12"/>',
        'edit' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'save' => '<path d="M5 5h11l3 3v11H5z"/><path d="M8 5v5h7V5"/><path d="M8 19v-6h8v6"/>',
        'ban' => '<circle cx="12" cy="12" r="8"/><path d="m7 7 10 10"/>',
        'box' => '<path d="M3 8.5 12 4l9 4.5-9 4.5z"/><path d="M3 8.5v7L12 20l9-4.5v-7"/><path d="M12 13v7"/>',
        'coins' => '<circle cx="9" cy="10" r="5"/><path d="M14.5 7.2a5 5 0 1 1 0 9.6"/><path d="M9 8v4"/><path d="M7.5 10h3"/>',
        'calendar' => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4"/><path d="M16 3v4"/><path d="M4 10h16"/>',
        'search' => '<circle cx="11" cy="11" r="6"/><path d="m16 16 4 4"/>',
        'download' => '<path d="M12 4v10"/><path d="m8 10 4 4 4-4"/><path d="M5 18h14"/>',
        'upload' => '<path d="M12 20V10"/><path d="m8 14 4-4 4 4"/><path d="M5 6h14"/>',
        'chevron-up' => '<path d="m6 14 6-6 6 6"/>',
        'chevron-down' => '<path d="m6 10 6 6 6-6"/>',
        'more' => '<circle cx="6" cy="12" r="1.3"/><circle cx="12" cy="12" r="1.3"/><circle cx="18" cy="12" r="1.3"/>',
        'mail' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m4 7 8 6 8-6"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 2.8 7.4 7 9 4.2-1.6 7-4.5 7-9V6z"/>',
        'lock' => '<rect x="5" y="10" width="14" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="m11 18-6-6 6-6"/>',
        'tag' => '<path d="M20 12 12 4H5v7l8 8z"/><circle cx="8.5" cy="8.5" r="1.2"/>',
        'x' => '<path d="M6 6l12 12"/><path d="M18 6 6 18"/>',
        'home' => '<path d="M4 11.5 12 4l8 7.5"/><path d="M6.5 10.5V20h11V10.5"/>',
        'list' => '<path d="M8 7h12"/><path d="M8 12h12"/><path d="M8 17h12"/><circle cx="4.5" cy="7" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="17" r="1"/>',
        'layout' => '<rect x="3" y="3" width="8" height="8" rx="1.5"/><rect x="13" y="3" width="8" height="5" rx="1.5"/><rect x="13" y="10" width="8" height="11" rx="1.5"/><rect x="3" y="13" width="8" height="8" rx="1.5"/>',
        'bell' => '<path d="M6 9a6 6 0 0 1 12 0c0 7 3 7 3 9H3c0-2 3-2 3-9"/><path d="M10 20a2 2 0 0 0 4 0"/>',
        'menu' => '<path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/>',
        'panel-left' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="m16 9-3 3 3 3"/>',
        'panel-right' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16"/><path d="m13 9 3 3-3 3"/>',
    ];

    $path = $icons[$name] ?? '';
    if ($path === '') {
        return '';
    }

    return '<svg class="btn-icon" viewBox="0 0 24 24" width="' . $size . '" height="' . $size . '" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
