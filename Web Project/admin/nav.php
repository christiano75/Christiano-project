<?php
/** Admin sub-navigation. Expects $adminPage to be set by the parent page. */
$adminPage = $adminPage ?? '';
$adminTabs = [
    'dashboard' => ['index.php', 'grid', 'Dashboard'],
    'products' => ['products.php', 'tag', 'Products'],
    'orders' => ['orders.php', 'box', 'Orders'],
    'messages' => ['messages.php', 'message', 'Messages'],
    'users' => ['users.php', 'users', 'Customers'],
];
?>
<div class="admin-tabs">
    <?php foreach ($adminTabs as $key => [$href, $iconName, $label]): ?>
        <a class="<?= $adminPage === $key ? 'active' : '' ?>" href="<?= $href ?>">
            <?= icon($iconName) ?> <?= $label ?>
        </a>
    <?php endforeach; ?>
</div>
