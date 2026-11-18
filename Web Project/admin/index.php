<?php
/**
 * Admin dashboard — store stats, low stock and the latest orders.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$stats = [];

$stats['revenue'] = (float) $conn->query(
    "SELECT COALESCE(SUM(total), 0) AS v FROM orders WHERE status IN ('paid', 'shipped', 'completed')"
)->fetch_assoc()['v'];

$stats['orders'] = (int) $conn->query("SELECT COUNT(*) AS v FROM orders")->fetch_assoc()['v'];
$stats['customers'] = (int) $conn->query("SELECT COUNT(*) AS v FROM users WHERE role = 'customer'")->fetch_assoc()['v'];
$stats['products'] = (int) $conn->query("SELECT COUNT(*) AS v FROM products")->fetch_assoc()['v'];
$stats['units'] = (int) $conn->query("SELECT COALESCE(SUM(quantity), 0) AS v FROM order_items")->fetch_assoc()['v'];
$stats['unread'] = (int) $conn->query("SELECT COUNT(*) AS v FROM messages WHERE is_read = 0")->fetch_assoc()['v'];

$lowStock = $conn->query(
    "SELECT id, name, stock, image FROM products WHERE stock <= 10 ORDER BY stock ASC LIMIT 5"
)->fetch_all(MYSQLI_ASSOC);

$recentOrders = $conn->query(
    "SELECT o.*, u.full_name AS customer, COUNT(oi.id) AS item_count
       FROM orders o
       JOIN users u ON u.id = o.user_id
       LEFT JOIN order_items oi ON oi.order_id = o.id
      GROUP BY o.id
      ORDER BY o.created_at DESC
      LIMIT 6"
)->fetch_all(MYSQLI_ASSOC);

$topProducts = $conn->query(
    "SELECT product_name, SUM(quantity) AS units, SUM(unit_price * quantity) AS revenue
       FROM order_items
      GROUP BY product_name
      ORDER BY units DESC
      LIMIT 4"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin dashboard';
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'dashboard';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">Threadline studio</p>
            <h1>Dashboard</h1>
            <p class="text-muted-warm mb-0">Signed in as <?= e(current_user_name()) ?> · <?= e(date('l, j F Y')) ?></p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-warm" href="../index.php"><?= icon('eye') ?> View storefront</a>
            <a class="btn btn-ink" href="product-form.php"><?= icon('plus') ?> New product</a>
        </div>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Revenue</p>
                        <p class="stat-value mb-1"><?= money($stats['revenue']) ?></p>
                        <p class="stat-delta mb-0">Paid, shipped &amp; completed</p>
                    </div>
                    <span class="stat-icon"><?= icon('chart') ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Orders</p>
                        <p class="stat-value mb-1"><?= $stats['orders'] ?></p>
                        <p class="stat-delta mb-0"><?= $stats['units'] ?> units sold</p>
                    </div>
                    <span class="stat-icon"><?= icon('box') ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Customers</p>
                        <p class="stat-value mb-1"><?= $stats['customers'] ?></p>
                        <p class="stat-delta mb-0">Registered accounts</p>
                    </div>
                    <span class="stat-icon"><?= icon('users') ?></span>
                </div>
            </div>
        </div>
        <div class="col-6 col-xl-3">
            <div class="stat-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Products</p>
                        <p class="stat-value mb-1"><?= $stats['products'] ?></p>
                        <p class="stat-delta mb-0"><?= count($lowStock) ?> low on stock</p>
                    </div>
                    <span class="stat-icon"><?= icon('tag') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="panel">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h2 class="panel-title mb-0">Recent orders</h2>
                    <a class="link-arrow" href="orders.php">All orders <?= icon('arrow-right') ?></a>
                </div>
                <div class="table-responsive">
                    <table class="table table-threadline">
                        <thead>
                            <tr><th>Reference</th><th>Customer</th><th>Placed</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($order['reference']) ?></td>
                                    <td><?= e($order['customer']) ?></td>
                                    <td class="text-muted-warm"><?= e(date('j M', strtotime($order['created_at']))) ?></td>
                                    <td><?= (int) $order['item_count'] ?></td>
                                    <td><?= money($order['total']) ?></td>
                                    <td><?= status_badge($order['status']) ?></td>
                                    <td class="text-end"><a class="btn btn-sm btn-outline-warm" href="order-detail.php?id=<?= (int) $order['id'] ?>">Open</a></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="panel mt-4">
                <h2 class="panel-title">Best sellers</h2>
                <div class="row g-3">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center gap-3 border rounded p-3 h-100" style="border-color:var(--line)!important">
                                <span class="h4 mb-0 text-muted-warm" style="font-family:var(--font-display)">0<?= $index + 1 ?></span>
                                <div class="flex-grow-1">
                                    <p class="mb-0 fw-semibold small"><?= e($product['product_name']) ?></p>
                                    <p class="mb-0 small text-muted-warm"><?= (int) $product['units'] ?> sold · <?= money($product['revenue']) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="panel mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="panel-title mb-0">Low stock</h2>
                    <a class="small text-muted-warm text-decoration-none" href="products.php">Manage</a>
                </div>
                <?php if (!$lowStock): ?>
                    <p class="small text-muted-warm mb-0">Everything is comfortably stocked.</p>
                <?php else: ?>
                    <?php foreach ($lowStock as $product): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <a class="small fw-semibold text-decoration-none" href="product-form.php?id=<?= (int) $product['id'] ?>"><?= e($product['name']) ?></a>
                                <span class="small <?= (int) $product['stock'] === 0 ? 'text-danger' : 'text-muted-warm' ?>">
                                    <?= (int) $product['stock'] === 0 ? 'Sold out' : (int) $product['stock'] . ' left' ?>
                                </span>
                            </div>
                            <div class="progress progress-thin" role="progressbar" aria-label="<?= e($product['name']) ?> stock"
                                 aria-valuenow="<?= (int) $product['stock'] ?>" aria-valuemin="0" aria-valuemax="40">
                                <div class="progress-bar" style="width: <?= min(100, (int) $product['stock'] / 40 * 100) ?>%; background: <?= (int) $product['stock'] <= 3 ? 'var(--accent)' : 'var(--olive)' ?>"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <p class="small text-muted-warm mb-0">Bars show stock against a target of 40 units.</p>
                <?php endif; ?>
            </div>

            <div class="panel mb-4">
                <h2 class="panel-title">Inbox</h2>
                <p class="display-font mb-1" style="font-size:2.2rem"><?= $stats['unread'] ?></p>
                <p class="small text-muted-warm">unread <?= $stats['unread'] === 1 ? 'message' : 'messages' ?> from customers.</p>
                <a class="btn btn-outline-ink w-100" href="messages.php"><?= icon('message') ?> Open inbox</a>
            </div>

            <div class="panel">
                <h2 class="panel-title">Quick actions</h2>
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-warm text-start" href="product-form.php"><?= icon('plus') ?> Add a product</a>
                    <a class="btn btn-outline-warm text-start" href="orders.php"><?= icon('box') ?> Update order status</a>
                    <a class="btn btn-outline-warm text-start" href="users.php"><?= icon('users') ?> View customers</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
