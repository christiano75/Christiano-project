<?php
/**
 * Admin — customer accounts and their order totals.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$users = $conn->query(
    "SELECT u.*, COUNT(o.id) AS order_count, COALESCE(SUM(o.total), 0) AS spend
       FROM users u
       LEFT JOIN orders o ON o.user_id = u.id AND o.status <> 'cancelled'
      GROUP BY u.id
      ORDER BY u.created_at DESC"
)->fetch_all(MYSQLI_ASSOC);

$pageTitle = 'Admin — Customers';
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'users';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">People</p>
            <h1>Customers</h1>
            <p class="text-muted-warm mb-0"><?= count($users) ?> registered <?= count($users) === 1 ? 'account' : 'accounts' ?></p>
        </div>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="panel">
        <div class="table-responsive">
            <table class="table table-threadline">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Email</th>
                        <th scope="col">Role</th>
                        <th scope="col">Orders</th>
                        <th scope="col">Spend</th>
                        <th scope="col">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <p class="mb-0 fw-semibold"><?= e($user['full_name']) ?></p>
                                <p class="mb-0 small text-muted-warm"><?= e($user['phone'] ?: 'No phone') ?></p>
                            </td>
                            <td><?= e($user['email']) ?></td>
                            <td>
                                <span class="badge <?= $user['role'] === 'admin' ? 'text-bg-dark' : 'text-bg-light border' ?> text-uppercase">
                                    <?= e($user['role']) ?>
                                </span>
                            </td>
                            <td><?= (int) $user['order_count'] ?></td>
                            <td><?= money($user['spend']) ?></td>
                            <td class="text-muted-warm"><?= e(date('j M Y', strtotime($user['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
