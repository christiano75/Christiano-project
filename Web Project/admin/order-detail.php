<?php
/**
 * Admin — single order view.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$orderId = (int) ($_GET['id'] ?? 0);
$statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

$stmt = $conn->prepare(
    "SELECT o.*, u.full_name AS customer, u.id AS customer_id
       FROM orders o
       JOIN users u ON u.id = o.user_id
      WHERE o.id = ?"
);
$stmt->bind_param('i', $orderId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    set_flash('warning', 'That order could not be found.');
    redirect('orders.php');
}

if (is_post()) {
    verify_csrf();
    $status = (string) ($_POST['status'] ?? '');
    if (in_array($status, $statuses, true)) {
        $update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $update->bind_param('si', $status, $orderId);
        $update->execute();
        set_flash('success', 'Order status updated to ' . $status . '.');
        redirect('order-detail.php?id=' . $orderId);
    }
    set_flash('danger', 'That status update was not valid.');
    redirect('order-detail.php?id=' . $orderId);
}

$itemStmt = $conn->prepare(
    "SELECT oi.*, p.image
       FROM order_items oi
       LEFT JOIN products p ON p.id = oi.product_id
      WHERE oi.order_id = ?
      ORDER BY oi.id"
);
$itemStmt->bind_param('i', $orderId);
$itemStmt->execute();
$items = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin — Order ' . $order['reference'];
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'orders';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">Order</p>
            <h1><?= e($order['reference']) ?></h1>
            <p class="text-muted-warm mb-0">
                Placed <?= e(date('j F Y, g:ia', strtotime($order['created_at']))) ?> ·
                <?= e($order['customer']) ?> · <?= status_badge($order['status']) ?>
            </p>
        </div>
        <a class="btn btn-outline-ink" href="orders.php"><?= icon('arrow-right') ?> All orders</a>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="panel mb-4">
                <h2 class="panel-title">Items</h2>
                <div class="table-responsive">
                    <table class="table table-threadline">
                        <thead>
                            <tr><th>Product</th><th>Size</th><th>Unit price</th><th>Qty</th><th class="text-end">Line total</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php if (!empty($item['image'])): ?>
                                                <img class="table-thumb" src="../<?= e(product_image($item['image'])) ?>" alt="">
                                            <?php endif; ?>
                                            <span class="fw-semibold"><?= e($item['product_name']) ?></span>
                                        </div>
                                    </td>
                                    <td><?= e($item['size']) ?></td>
                                    <td><?= money($item['unit_price']) ?></td>
                                    <td><?= (int) $item['quantity'] ?></td>
                                    <td class="text-end"><?= money($item['unit_price'] * $item['quantity']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="summary-row mt-3"><span>Subtotal</span><span><?= money($order['subtotal']) ?></span></div>
                <div class="summary-row"><span>Delivery</span><span><?= $order['shipping'] > 0 ? money($order['shipping']) : 'Free' ?></span></div>
                <div class="summary-row total"><span>Total</span><span><?= money($order['total']) ?></span></div>
            </div>

            <div class="panel">
                <h2 class="panel-title">Update status</h2>
                <form method="post" action="order-detail.php?id=<?= $orderId ?>" class="d-flex flex-wrap gap-2">
                    <?= csrf_field() ?>
                    <label class="visually-hidden" for="status">Order status</label>
                    <select class="form-select w-auto" id="status" name="status">
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-ink" type="submit">Update order</button>
                </form>
                <p class="small text-muted-warm mt-3 mb-0">Customers see this status in their account and on the confirmation page.</p>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel mb-4">
                <h2 class="panel-title">Customer</h2>
                <p class="mb-1 fw-semibold"><?= e($order['full_name']) ?></p>
                <p class="small text-muted-warm mb-3"><?= e($order['email']) ?><br><?= e($order['phone']) ?></p>
                <a class="btn btn-outline-warm btn-sm w-100" href="mailto:<?= e($order['email']) ?>"><?= icon('mail') ?> Email customer</a>
            </div>

            <div class="panel mb-4">
                <h2 class="panel-title">Delivery address</h2>
                <address class="small mb-0">
                    <?= e($order['address']) ?><br>
                    <?= e($order['postcode']) ?> <?= e($order['city']) ?>
                </address>
            </div>

            <div class="panel">
                <h2 class="panel-title">Payment</h2>
                <p class="mb-1 text-capitalize"><?= e($order['payment_method']) ?></p>
                <p class="small text-muted-warm mb-0"><?= $order['payment_method'] === 'card' ? 'Paid by card (demo).' : 'See the original order payment details.' ?></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
