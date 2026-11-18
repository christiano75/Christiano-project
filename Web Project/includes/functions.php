<?php
/**
 * Shared helpers: escaping, flash messages, CSRF, cart, formatting.
 */

declare(strict_types=1);

/* ------------------------------------------------------------------
 |  Output & formatting
 * ------------------------------------------------------------------ */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $amount): string
{
    return CURRENCY . number_format((float) $amount, 2);
}

function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function old(string $key, string $default = ''): string
{
    return e($_POST[$key] ?? $default);
}

/* ------------------------------------------------------------------
 |  Flash messages
 * ------------------------------------------------------------------ */

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ------------------------------------------------------------------
 |  CSRF protection
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        set_flash('danger', 'Your session expired. Please try again.');
        redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
    }
}

/* ------------------------------------------------------------------
 |  Authentication state
 * ------------------------------------------------------------------ */

function current_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function current_user_name(): string
{
    return (string) ($_SESSION['user_name'] ?? '');
}

function is_logged_in(): bool
{
    return current_user_id() !== null;
}

function is_admin(): bool
{
    return ($_SESSION['user_role'] ?? '') === 'admin';
}

/* ------------------------------------------------------------------
 |  Shopping bag (stored in the session)
 * ------------------------------------------------------------------ */

function cart_key(int $productId, string $size): string
{
    $size = trim($size) !== '' ? trim($size) : 'One Size';
    return $productId . ':' . $size;
}

function cart_add(int $productId, int $qty, string $size): void
{
    $qty = max(1, $qty);
    $key = cart_key($productId, $size);
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['qty'] += $qty;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $productId,
            'size' => trim($size) !== '' ? trim($size) : 'One Size',
            'qty' => $qty,
        ];
    }
}

function cart_update(array $quantities): void
{
    foreach ($quantities as $key => $qty) {
        if (!isset($_SESSION['cart'][$key])) {
            continue;
        }
        $qty = (int) $qty;
        if ($qty < 1) {
            unset($_SESSION['cart'][$key]);
        } else {
            $_SESSION['cart'][$key]['qty'] = min($qty, 20);
        }
    }
}

function cart_remove(string $key): void
{
    unset($_SESSION['cart'][$key]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_count(): int
{
    $count = 0;
    foreach ($_SESSION['cart'] ?? [] as $line) {
        $count += (int) $line['qty'];
    }
    return $count;
}

function product_price(array $product): float
{
    return $product['sale_price'] !== null && $product['sale_price'] !== ''
        ? (float) $product['sale_price']
        : (float) $product['price'];
}

/**
 * Merge the session bag with fresh product data from the database.
 */
function cart_items(mysqli $conn): array
{
    $cart = $_SESSION['cart'] ?? [];
    if (!$cart) {
        return [];
    }

    $ids = array_values(array_unique(array_map(
        static fn (array $line): int => (int) $line['product_id'],
        $cart
    )));
    $idList = implode(',', $ids);

    $result = $conn->query(
        "SELECT p.*, c.name AS category_name, c.slug AS category_slug
           FROM products p
           JOIN categories c ON c.id = p.category_id
          WHERE p.id IN ($idList)"
    );

    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[(int) $row['id']] = $row;
    }

    $items = [];
    foreach ($cart as $key => $line) {
        $id = (int) $line['product_id'];
        if (!isset($products[$id])) {
            unset($_SESSION['cart'][$key]);
            continue;
        }
        $product = $products[$id];
        $qty = max(1, (int) $line['qty']);
        $unit = product_price($product);
        $items[] = [
            'key' => $key,
            'product' => $product,
            'size' => $line['size'],
            'qty' => $qty,
            'unit_price' => $unit,
            'line_total' => $unit * $qty,
        ];
    }
    return $items;
}

function cart_summary(array $items): array
{
    $subtotal = 0.0;
    foreach ($items as $item) {
        $subtotal += (float) $item['line_total'];
    }
    $shipping = ($subtotal <= 0 || $subtotal >= FREE_SHIPPING_THRESHOLD) ? 0.0 : SHIPPING_FLAT_RATE;

    return [
        'subtotal' => $subtotal,
        'shipping' => $shipping,
        'total' => $subtotal + $shipping,
        'free_remaining' => max(0.0, FREE_SHIPPING_THRESHOLD - $subtotal),
        'free_progress' => min(100, (int) round($subtotal / FREE_SHIPPING_THRESHOLD * 100)),
    ];
}

/* ------------------------------------------------------------------
 |  Storefront markup helpers
 * ------------------------------------------------------------------ */

function product_image(string $file): string
{
    return 'assets/img/products/' . rawurlencode($file);
}

function status_badge(string $status): string
{
    $map = [
        'pending' => 'warning',
        'paid' => 'info',
        'shipped' => 'primary',
        'completed' => 'success',
        'cancelled' => 'secondary',
    ];
    $class = $map[$status] ?? 'secondary';
    return '<span class="badge rounded-pill text-bg-' . $class . ' text-uppercase">' . e($status) . '</span>';
}

/**
 * Storefront product card used on the home, shop and related-product grids.
 */
function product_card(array $product): void
{
    $id = (int) $product['id'];
    $url = 'product.php?id=' . $id;
    $onSale = $product['sale_price'] !== null && $product['sale_price'] !== '';
    $stock = (int) $product['stock'];
    ?>
    <article class="product-card reveal">
        <div class="product-media">
            <a href="<?= e($url) ?>" class="d-block w-100 h-100" aria-label="View <?= e($product['name']) ?>">
                <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
            </a>
            <?php if ($onSale): ?>
                <span class="product-badge sale">Sale</span>
            <?php elseif ($stock > 0 && $stock <= 10): ?>
                <span class="product-badge low">Low stock</span>
            <?php endif; ?>
            <a class="product-quick" href="<?= e($url) ?>">
                <span class="btn btn-sm w-100"><?= $stock > 0 ? 'Choose options' : 'View piece' ?></span>
            </a>
        </div>
        <div class="product-body">
            <span class="product-meta"><?= e($product['category_name'] ?? '') ?></span>
            <h3 class="product-name"><a href="<?= e($url) ?>"><?= e($product['name']) ?></a></h3>
            <p class="product-price mb-0">
                <?php if ($onSale): ?>
                    <span class="was"><?= money($product['price']) ?></span><span class="now"><?= money($product['sale_price']) ?></span>
                <?php else: ?>
                    <?= money($product['price']) ?>
                <?php endif; ?>
            </p>
        </div>
    </article>
    <?php
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

function paginate_links(int $page, int $pages, array $params = []): string{
    if ($pages <= 1) {
        return '';
    }
    $html = '<nav aria-label="Pagination"><ul class="pagination pagination-threadline justify-content-center mb-0">';
    for ($i = 1; $i <= $pages; $i++) {
        $params['page'] = $i;
        $url = '?' . http_build_query($params);
        $active = $i === $page ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($url) . '">' . $i . '</a></li>';
    }
    return $html . '</ul></nav>';
}

/**
 * Inline SVG icon set (no icon-font dependency, works offline).
 */
function icon(string $name, string $class = ''): string
{
    $paths = [
        'bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'user' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20.5 20.5-4.2-4.2"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
        'truck' => '<rect x="1" y="3" width="15" height="13" rx="1"/><path d="M16 8h4l3 3v5h-7z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'refresh' => '<path d="M3 4v6h6"/><path d="M3.5 13a9 9 0 1 0 2-7.5L3 10"/><path d="M21 20v-6h-6"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/>',
        'leaf' => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.5 19 2c1 2 2 4.2 2 8 0 5.5-4.8 10-10 10Z"/><path d="M2 21c0-3 1.9-5.4 5.1-6C9.5 14.5 12 13 13 12"/>',
        'star' => '<path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8-6.2-3.2L5.8 21 7 14.2 2 9.3l6.9-1z"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8l1 1.1L12 21.2l7.8-7.7 1-1.1a5.5 5.5 0 0 0 0-7.8z"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'check' => '<path d="m20 6-11 11-5-5"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 6-10 7L2 6"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8.1 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
        'pin' => '<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/>',
        'box' => '<path d="M21 16V8a2 2 0 0 0-1-1.7l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.7l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'chart' => '<path d="M12 20V10M18 20V4M6 20v-4"/>',
        'tag' => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><path d="M7 7h.01"/>',
        'users' => '<path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.9"/><path d="M16 3.1a4 4 0 0 1 0 7.8"/>',
        'message' => '<path d="M21 11.5a8.4 8.4 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.4 8.4 0 0 1-3.8-.9L3 21l1.9-5.7a8.4 8.4 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.4 8.4 0 0 1 3.8-.9h.5a8.5 8.5 0 0 1 8 8z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M10 11v6M14 11v6"/>',
        'edit' => '<path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.1 2.1 0 0 1 3 3L12 15l-4 1 1-4z"/>',
        'eye' => '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
        'home' => '<path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>',
        'instagram' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.4A4 4 0 1 1 12.6 8 4 4 0 0 1 16 11.4z"/><path d="M17.5 6.5h.01"/>',
        'facebook' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        'lock' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'card' => '<rect x="1" y="4" width="22" height="16" rx="2"/><path d="M1 10h22"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'info' => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
    ];

    $body = $paths[$name] ?? $paths['info'];
    return '<svg class="icon ' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" '
        . 'stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
        . $body . '</svg>';
}
