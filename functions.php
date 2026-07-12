<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $value): string
{
    return '₹' . number_format((float) $value, 0);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    $sessionToken = $_SESSION['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !is_string($sessionToken) || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
        http_response_code(419);
        exit('Your session expired. Please go back and try again.');
    }
}

function user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function logged_in(): bool
{
    return user() !== null;
}

function has_role(array|string $roles): bool
{
    return logged_in() && in_array(user()['role'], (array) $roles, true);
}

function require_login(array|string|null $roles = null): void
{
    if (!logged_in()) {
        flash('error', 'Please sign in to continue.');
        redirect('index.php?page=login');
    }
    if ($roles !== null && !has_role($roles)) {
        http_response_code(403);
        exit('You do not have permission to view this page.');
    }
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function query(string $sql, array $params = []): PDOStatement
{
    $statement = db()->prepare($sql);
    $statement->execute($params);
    return $statement;
}

function setting(string $key, string $default = ''): string
{
    static $settings = null;
    if ($settings === null) {
        try {
            $settings = query('SELECT setting_key, setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Throwable) {
            $settings = [];
        }
    }
    return (string) ($settings[$key] ?? $default);
}

function cart(): array
{
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int
{
    return array_sum(array_map(static fn ($item) => (int) $item['quantity'], cart()));
}

function cart_totals(): array
{
    $subtotal = 0.0;
    foreach (cart() as $item) {
        $subtotal += (float) $item['price'] * (int) $item['quantity'];
    }
    $discount = 0.0;
    if (!empty($_SESSION['coupon']) && $subtotal > 0) {
        $coupon = query('SELECT * FROM coupons WHERE code=? AND status=1 AND valid_until>=CURDATE() AND used_count<usage_limit', [$_SESSION['coupon']])->fetch();
        if ($coupon && $subtotal >= (float) $coupon['minimum_order']) {
            $discount = $coupon['type'] === 'percent'
                ? $subtotal * ((float) $coupon['value'] / 100)
                : (float) $coupon['value'];
            if ($coupon['max_discount'] !== null) {
                $discount = min($discount, (float) $coupon['max_discount']);
            }
            $discount = round(min($discount, $subtotal), 2);
            $_SESSION['discount'] = $discount;
        } else {
            unset($_SESSION['coupon'], $_SESSION['discount']);
        }
    }
    $delivery = $subtotal >= (float) setting('free_delivery_threshold', '499') || $subtotal === 0.0 ? 0.0 : (float) setting('delivery_fee', '49');
    $tax = round(($subtotal - $discount) * ((float) setting('tax_rate', '5') / 100), 2);
    return compact('subtotal', 'discount', 'delivery', 'tax') + [
        'total' => max(0, $subtotal - $discount + $delivery + $tax),
    ];
}

function order_number(): string
{
    return 'FF' . date('ymd') . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
}

function status_meta(string $status): array
{
    return match ($status) {
        'pending' => ['Pending', 'warning', 'bi-hourglass-split'],
        'confirmed' => ['Confirmed', 'info', 'bi-check-circle'],
        'preparing' => ['Preparing', 'primary', 'bi-fire'],
        'ready' => ['Ready', 'success', 'bi-bag-check'],
        'out_for_delivery' => ['On the way', 'primary', 'bi-scooter'],
        'delivered' => ['Delivered', 'success', 'bi-check2-all'],
        'cancelled' => ['Cancelled', 'danger', 'bi-x-circle'],
        default => [ucwords(str_replace('_', ' ', $status)), 'secondary', 'bi-circle'],
    };
}

function food_image(string $image): string
{
    if (str_starts_with($image, 'http')) {
        return $image;
    }
    return base_url($image ?: 'assets/images/food-placeholder.svg');
}

function store_uploaded_image(array $file, string $existing = ''): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $existing;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK || ($file['size'] ?? 0) > 2 * 1024 * 1024) {
        throw new RuntimeException('Image upload failed or exceeds the 2 MB limit.');
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Upload a JPG, PNG, WebP or GIF image.');
    }
    $directory = __DIR__ . '/uploads/menu';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create the upload directory.');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $extensions[$mime];
    if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }
    return 'uploads/menu/' . $name;
}

function page_title(string $title = ''): string
{
    return $title ? $title . ' · ' . APP_NAME : APP_NAME . ' · Flavours delivered fast';
}
