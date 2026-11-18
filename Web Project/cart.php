<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

/**
 * Fetch a single product (used when adding to the bag).
 */
function find_product(mysqli $conn, int $id): ?array
{
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return $row ?: null;
}

if (is_post()) {
    verify_csrf();

    $action = (string) ($_POST['action'] ?? '');
    $wantsJson = isset($_POST['ajax']);

    if ($action === 'add') {
        $productId = (int) ($_POST['product_id'] ?? 0);
        $qty = max(1, min(10, (int) ($_POST['qty'] ?? 1)));
        $size = trim((string) ($_POST['size'] ?? ''));
        $product = find_product($conn, $productId);

        if (!$product || (int) $product['stock'] <= 0) {
            $message = 'Sorry — that piece is no longer available.';
            $ok = false;
        } else {
            $allowed = array_map('trim', explode(',', (string) $product['sizes']));
            if (!in_array($size, $allowed, true)) {
                $size = $allowed[0] ?? 'One Size';
            }
            cart_add($productId, $qty, $size);
            $message = $product['name'] . ' (' . $size . ') added to your bag.';
            $ok = true;
        }

        if ($wantsJson) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => $ok, 'count' => cart_count(), 'message' => $message]);
            exit;
        }
        set_flash($ok ? 'success' : 'danger', $message);
        redirect('cart.php');
    }

    if ($action === 'update') {
        $quantities = $_POST['qty'] ?? [];
        cart_update(is_array($quantities) ? $quantities : []);
        set_flash('success', 'Your bag has been updated.');
        redirect('cart.php');
    }

    if ($action === 'remove') {
        $key = (string) ($_POST['remove_key'] ?? '');
        if ($key !== '') {
            cart_remove($key);
            set_flash('info', 'Item removed from your bag.');
        }
        redirect('cart.php');
    }

    if ($action === 'clear') {
        cart_clear();
        set_flash('info', 'Your bag is now empty.');
        redirect('cart.php');
    }

    redirect('cart.php');
}

$items = cart_items($conn);
$summary = cart_summary($items);
$itemCount = array_sum(array_column($items, 'qty'));

$pageTitle = 'Your bag';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Your bag</li>
        </ol>
    </nav>

    <header class="page-header pt-2 d-flex flex-wrap align-items-end justify-content-between gap-3">
        <div>
            <p class="kicker">Almost yours</p>
            <h1 class="mb-0">Your bag<?= $itemCount ? ' (' . $itemCount . ')' : '' ?></h1>
        </div>
        <?php if ($items): ?>
            <a class="link-arrow" href="shop.php">Keep shopping <?= icon('arrow-right') ?></a>
        <?php endif; ?>
    </header>

    <?php if (!$items): ?>
        <div class="empty-state mb-5">
            <?= icon('bag') ?>
            <h2 class="h4">Your bag is empty</h2>
            <p class="text-muted-warm mb-4">Start with the pieces everyone comes back for.</p>
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a class="btn btn-ink" href="shop.php">Shop all pieces</a>
                <a class="btn btn-outline-ink" href="shop.php?sort=price_asc">Shop under <?= money(100) ?></a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4 g-lg-5">
            <div class="col-lg-8">
                <form method="post" action="cart.php">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">

                    <div class="d-flex justify-content-between align-items-center pb-2 border-bottom">
                        <span class="eyebrow"><?= $itemCount ?> <?= $itemCount === 1 ? 'piece' : 'pieces' ?></span>
                        <span class="eyebrow d-none d-md-block">Line total</span>
                    </div>

                    <?php foreach ($items as $item): ?>
                        <?php $product = $item['product']; ?>
                        <div class="cart-row" data-price="<?= e((string) $item['unit_price']) ?>">
                            <div class="cart-thumb">
                                <a href="product.php?id=<?= (int) $product['id'] ?>">
                                    <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                                </a>
                            </div>

                            <div>
                                <p class="cart-name"><a href="product.php?id=<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a></p>
                                <p class="cart-options mb-2">
                                    <?= e($product['category_name']) ?> · Size <?= e($item['size']) ?> ·
                                    <?= money($item['unit_price']) ?> each
                                </p>
                                <div class="qty-stepper">
                                    <button type="button" data-step="-1" aria-label="Decrease quantity of <?= e($product['name']) ?>"><?= icon('minus') ?></button>
                                    <input class="cart-qty" type="number" name="qty[<?= e($item['key']) ?>]"
                                           value="<?= (int) $item['qty'] ?>" min="1" max="20"
                                           aria-label="Quantity of <?= e($product['name']) ?>" data-key="<?= e($item['key']) ?>">
                                    <button type="button" data-step="1" aria-label="Increase quantity of <?= e($product['name']) ?>"><?= icon('plus') ?></button>
                                </div>
                            </div>

                            <div class="line-total"><?= money($item['line_total']) ?></div>

                            <button class="btn btn-icon btn-sm text-muted-warm" type="submit" name="remove_key"
                                    value="<?= e($item['key']) ?>"
                                    form="removeForm"
                                    aria-label="Remove <?= e($product['name']) ?> from bag">
                                <?= icon('trash') ?>
                            </button>
                        </div>
                    <?php endforeach; ?>

                    <div class="d-flex flex-wrap justify-content-between gap-2 mt-4">
                        <a class="btn btn-outline-warm" href="shop.php"><?= icon('arrow-right') ?> Continue shopping</a>
                        <button class="btn btn-outline-ink" type="submit">Update bag</button>
                    </div>
                </form>

                <form id="removeForm" method="post" action="cart.php" class="d-none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                </form>

                <div class="alert alert-info d-flex align-items-start gap-2 mt-4 mb-0">
                    <?= icon('leaf', 'mt-1') ?>
                    <div class="small">Every order is packed plastic-free in recycled paper. Returns are free for 30 days.</div>
                </div>
            </div>

            <div class="col-lg-4">
                <aside class="summary-card">
                    <h2 class="h5 mb-3">Order summary</h2>

                    <div class="summary-row"><span>Subtotal (<?= $itemCount ?> items)</span><span id="cartSubtotal"><?= money($summary['subtotal']) ?></span></div>
                    <div class="summary-row"><span>Delivery</span><span id="cartShipping"><?= $summary['shipping'] > 0 ? money($summary['shipping']) : 'Free' ?></span></div>
                    <div class="summary-row total"><span>Total</span><span id="cartTotal"><?= money($summary['total']) ?></span></div>

                    <div class="mt-3">
                        <div class="progress progress-thin" role="progressbar" aria-label="Progress towards free delivery"
                             aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $summary['free_progress'] ?>">
                            <div class="progress-bar" id="freeProgress" style="width: <?= $summary['free_progress'] ?>%"></div>
                        </div>
                        <p class="shipping-note mt-2 mb-0" id="freeMessage">
                            <?= $summary['free_remaining'] > 0
                                ? 'You are ' . money($summary['free_remaining']) . ' away from free delivery.'
                                : 'Free delivery unlocked — nice one.' ?>
                        </p>
                    </div>

                    <a class="btn btn-ink w-100 btn-lg mt-4" href="checkout.php">
                        <?= icon('lock') ?> Checkout
                    </a>

                    <div class="d-flex align-items-center justify-content-center gap-2 mt-3 payment-note">
                        <?= icon('card') ?> Secure demo checkout · Visa · Mastercard
                    </div>

                    <hr class="my-4">

                    <ul class="list-unstyled small text-muted-warm mb-0">
                        <li class="d-flex gap-2 mb-2"><?= icon('truck') ?><span>Dispatched within 2 working days</span></li>
                        <li class="d-flex gap-2 mb-2"><?= icon('refresh') ?><span>Free 30-day returns</span></li>
                        <li class="d-flex gap-2"><?= icon('message') ?><span>Studio support, <?= e('Mon–Fri') ?></span></li>
                    </ul>
                </aside>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
