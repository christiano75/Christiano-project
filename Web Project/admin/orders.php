<?php
/**
 * Admin — order list and status updates.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$statuses = ['pending', 'paid', 'shipped', 'completed', 'cancelled'];

if (is_post()) {
    verify_csrf();
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $status = (string) ($_POST['status'] ?? '');

    if ($orderId > 0 && in_array($status, $statuses, true)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $status, $orderId);
        $stmt->execute();
        set_flash('success', 'Order status updated to ' . $status . '.');
    } else {
        set_flash('danger', 'That status update was not valid.');
    }
    redirect('orders.php' . (!empty($_POST['return_filter']) ? '?status=' . urlencode((string) $_POST['return_filter']) : ''));
}

$filter = (string) ($_GET['status'] ?? '');
if (!in_array($filter, $statuses, true)) {
    $filter = '';
}

if ($filter !== '') {
    $stmt = $conn->prepare(
        "SELECT o.*, u.full_name AS customer, COUNT(oi.id) AS item_count
           FROM orders o
           JOIN users u ON u.id = o.user_id
           LEFT JOIN order_items oi ON oi.order_id = o.id
          WHERE o.status = ?
          GROUP BY o.id
          ORDER BY o.created_at DESC"
    );
    $stmt->bind_param('s', $filter);
    $stmt->execute();
    $orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $orders = $conn->query(
        "SELECT o.*, u.full_name AS customer, COUNT(oi.id) AS item_count
           FROM orders o
           JOIN users u ON u.id = o.user_id
           LEFT JOIN order_items oi ON oi.order_id = o.id
          GROUP BY o.id
          ORDER BY o.created_at DESC"
    )->fetch_all(MYSQLI_ASSOC);
}

$counts = [];
foreach ($conn->query("SELECT status, COUNT(*) AS total FROM orders GROUP BY status")->fetch_all(MYSQLI_ASSOC) as $row) {
    $counts[$row['status']] = (int) $row['total'];
}

$pageTitle = 'Admin — Orders';
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'orders';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">Fulfilment</p>
            <h1>Orders</h1>
            <p class="text-muted-warm mb-0"><?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?><?= $filter ? ' · ' . e($filter) : '' ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-warm" href="index.php"><?= icon('grid') ?> Dashboard</a>
        </div>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a class="chip <?= $filter === '' ? 'active' : '' ?>" href="orders.php">All</a>
        <?php foreach ($statuses as $status): ?>
            <a class="chip" href="orders.php?status=<?= e($status) ?>">
                <?= ucfirst($status) ?> <span class="text-muted-warm"><?= $counts[$status] ?? 0 ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-threadline">
                <thead>
                    <tr>
                        <th scope="col">Reference</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Placed</th>
                        <th scope="col">Items</th>
                        <th scope="col">Total</th>
                        <th scope="col">Payment</th>
                        <th scope="col">Status</th>
                        <th scope="col">Update</th>
                        <th scope="col"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="fw-semibold"><?= e($order['reference']) ?></td>
                            <td>
                                <p class="mb-0"><?= e($order['customer']) ?></p>
                                <p class="mb-0 small text-muted-warm"><?= e($order['email']) ?></p>
                            </td>
                            <td class="text-muted-warm"><?= e(date('j M Y', strtotime($order['created_at']))) ?></td>
                            <td><?= (int) $order['item_count'] ?></td>
                            <td><?= money($order['total']) ?></td>
                            <td class="text-capitalize"><?= e($order['payment_method']) ?></td>
                            <td><?= status_badge($order['status']) ?></td>
                            <td>
                                <form method="post" action="orders.php" class="d-flex gap-1">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="return_filter" value="<?= e($filter) ?>">
                                    <label class="visually-hidden" for="status-<?= (int) $order['id'] ?>">Status for <?= e($order['reference']) ?></label>
                                    <select class="form-select form-select-sm" id="status-<?= (int) $order['id'] ?>" name="status" data-autosubmit>
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= e($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= ucfirst($status) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-warm" type="submit">Set</button>
                                </form>
                            </td>
                            <td class="text-end">
                                <a class="btn btn-sm btn-outline-ink" href="order-detail.php?id=<?= (int) $order['id'] ?>">Open</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orders): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted-warm">No orders with that status yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
