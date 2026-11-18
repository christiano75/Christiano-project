<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_login();

$userId = (int) $_SESSION['user_id'];

$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param('i', $userId);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$ordersStmt = $conn->prepare(
    "SELECT o.*, COUNT(oi.id) AS item_count
       FROM orders o
       LEFT JOIN order_items oi ON oi.order_id = o.id
      WHERE o.user_id = ?
      GROUP BY o.id
      ORDER BY o.created_at DESC"
);
$ordersStmt->bind_param('i', $userId);
$ordersStmt->execute();
$orders = $ordersStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$totalSpent = array_sum(array_column($orders, 'total'));
$latestOrder = $orders[0] ?? null;

$pageTitle = 'My account';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">My account</li>
        </ol>
    </nav>

    <header class="page-header pt-2 d-flex flex-wrap align-items-end justify-content-between gap-3">
        <div>
            <p class="kicker">Members</p>
            <h1 class="mb-1">Hello, <?= e(explode(' ', $user['full_name'])[0]) ?>.</h1>
            <p>Member since <?= e(date('F Y', strtotime($user['created_at']))) ?> · <?= count($orders) ?> <?= count($orders) === 1 ? 'order' : 'orders' ?> so far</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <?php if (is_admin()): ?>
                <a class="btn btn-outline-ink" href="admin/index.php"><?= icon('grid') ?> Admin dashboard</a>
            <?php endif; ?>
            <a class="btn btn-outline-warm" href="logout.php"><?= icon('logout') ?> Sign out</a>
        </div>
    </header>

    <div class="row g-4">
        <div class="col-md-4">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Orders placed</p>
                        <p class="stat-value mb-0"><?= count($orders) ?></p>
                    </div>
                    <span class="stat-icon"><?= icon('box') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Lifetime spend</p>
                        <p class="stat-value mb-0"><?= money($totalSpent) ?></p>
                    </div>
                    <span class="stat-icon"><?= icon('card') ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card mb-3">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="stat-label mb-0">Latest order</p>
                        <p class="stat-value mb-0" style="font-size:1.3rem"><?= $latestOrder ? e($latestOrder['reference']) : '—' ?></p>
                    </div>
                    <span class="stat-icon"><?= icon('clock') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-lg-8">
            <div class="panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="panel-title mb-0">Order history</h2>
                    <a class="link-arrow" href="shop.php">Shop again <?= icon('arrow-right') ?></a>
                </div>

                <?php if (!$orders): ?>
                    <div class="empty-state border-0 py-4">
                        <?= icon('box') ?>
                        <h3 class="h5">No orders yet</h3>
                        <p class="text-muted-warm mb-3">Once you place an order it will appear here with live status.</p>
                        <a class="btn btn-ink" href="shop.php">Browse the collection</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-threadline align-middle">
                            <thead>
                                <tr>
                                    <th scope="col">Reference</th>
                                    <th scope="col">Date</th>
                                    <th scope="col">Items</th>
                                    <th scope="col">Total</th>
                                    <th scope="col">Status</th>
                                    <th scope="col" class="text-end">View</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($order['reference']) ?></td>
                                        <td class="text-muted-warm"><?= e(date('j M Y', strtotime($order['created_at']))) ?></td>
                                        <td><?= (int) $order['item_count'] ?></td>
                                        <td><?= money($order['total']) ?></td>
                                        <td><?= status_badge($order['status']) ?></td>
                                        <td class="text-end">
                                            <a class="btn btn-sm btn-outline-warm" href="order-confirmation.php?ref=<?= urlencode($order['reference']) ?>">Details</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="panel mb-3">
                <h2 class="panel-title">Account details</h2>
                <p class="mb-1 fw-semibold"><?= e($user['full_name']) ?></p>
                <p class="small text-muted-warm mb-3"><?= e($user['email']) ?></p>
                <ul class="list-unstyled small mb-3">
                    <li class="d-flex gap-2 mb-2"><?= icon('phone') ?><span><?= e($user['phone'] ?: 'No phone saved') ?></span></li>
                    <li class="d-flex gap-2"><?= icon('pin') ?><span><?= e($user['address'] ?: 'No address saved yet') ?><?= $user['city'] ? '<br>' . e($user['postcode']) . ' ' . e($user['city']) : '' ?></span></li>
                </ul>
                <a class="btn btn-outline-warm w-100" href="checkout.php">Go to checkout</a>
            </div>

            <div class="panel">
                <h2 class="panel-title">Need help?</h2>
                <p class="small text-muted-warm">Questions about a fit, a return or an order? The studio answers within one working day.</p>
                <a class="btn btn-ink w-100" href="contact.php"><?= icon('message') ?> Contact the studio</a>
                <p class="small text-muted-warm mt-3 mb-0">Signed in as <?= e($user['role']) ?>.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
