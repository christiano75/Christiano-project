<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_login();

$reference = trim((string) ($_GET['ref'] ?? ($_SESSION['last_order'] ?? '')));

$orderStmt = $conn->prepare("SELECT * FROM orders WHERE reference = ? AND user_id = ?");
$orderStmt->bind_param('si', $reference, $_SESSION['user_id']);
$orderStmt->execute();
$order = $orderStmt->get_result()->fetch_assoc();

if (!$order) {
    set_flash('warning', 'We could not find that order in your account.');
    redirect('account.php');
}

$itemStmt = $conn->prepare(
    "SELECT oi.*, p.image
       FROM order_items oi
       LEFT JOIN products p ON p.id = oi.product_id
      WHERE oi.order_id = ?
      ORDER BY oi.id"
);
$itemStmt->bind_param('i', $order['id']);
$itemStmt->execute();
$orderItems = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Order ' . $order['reference'];
include __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="panel text-center mb-4">
                <div class="d-inline-grid place-items-center rounded-circle bg-success-subtle text-success mb-3"
                     style="width:64px;height:64px;display:inline-grid!important">
                    <?= icon('check', 'fs-2') ?>
                </div>
                <p class="kicker mb-2">Order confirmed</p>
                <h1 class="h2 mb-2">Thank you, <?= e(explode(' ', $order['full_name'])[0]) ?>.</h1>
                <p class="text-muted-warm mb-3">
                    Your order <strong><?= e($order['reference']) ?></strong> is in. We have sent a receipt to
                    <strong><?= e($order['email']) ?></strong>.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-3 small text-muted-warm">
                    <span><?= icon('box') ?> Status: <?= status_badge($order['status']) ?></span>
                    <span><?= icon('clock') ?> Placed <?= e(date('j M Y, g:ia', strtotime($order['created_at']))) ?></span>
                    <span><?= icon('card') ?> <?= e(ucfirst($order['payment_method'])) ?></span>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="panel h-100">
                        <h2 class="panel-title">Items</h2>
                        <?php foreach ($orderItems as $item): ?>
                            <div class="order-line">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?= e(product_image($item['image'])) ?>" alt="<?= e($item['product_name']) ?>">
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <p class="name"><?= e($item['product_name']) ?></p>
                                    <p class="meta">Size <?= e($item['size']) ?> · Qty <?= (int) $item['quantity'] ?> · <?= money($item['unit_price']) ?> each</p>
                                </div>
                                <span class="small fw-semibold"><?= money($item['unit_price'] * $item['quantity']) ?></span>
                            </div>
                        <?php endforeach; ?>

                        <div class="summary-row mt-3"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
                        <div class="summary-row"><span>Delivery</span><span><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Free' ?></span></div>
                        <div class="summary-row total"><span>Total paid</span><span><?= money($order['total']) ?></span></div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="panel mb-3">
                        <h2 class="panel-title">Delivery to</h2>
                        <address class="mb-0 small">
                            <strong><?= e($order['full_name']) ?></strong><br>
                            <?= e($order['address']) ?><br>
                            <?= e($order['postcode']) ?> <?= e($order['city']) ?><br>
                            <?= e($order['phone']) ?>
                        </address>
                    </div>
                    <div class="panel">
                        <h2 class="panel-title">What happens next</h2>
                        <ol class="small text-muted-warm ps-3 mb-3">
                            <li class="mb-2">We pack your pieces within 2 working days.</li>
                            <li class="mb-2">You will receive tracking by email.</li>
                            <li>Free returns for 30 days if anything is not right.</li>
                        </ol>
                        <a class="btn btn-ink w-100" href="account.php">View my orders</a>
                        <a class="btn btn-link w-100 text-muted-warm text-decoration-none small" href="shop.php">Continue shopping</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
