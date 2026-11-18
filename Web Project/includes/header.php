<?php
/**
 * Shared site header: <head>, announcement bar, navigation and flash messages.
 *
 * Pages may set: $pageTitle, $pageDescription, $bodyClass, $base (e.g. '../' for /admin).
 */

$pageTitle = $pageTitle ?? SITE_NAME;
$pageDescription = $pageDescription ?? 'Threadline is a considered wardrobe of everyday essentials — responsible fabrics, honest prices and pieces made to last.';
$bodyClass = $bodyClass ?? '';
$base = $base ?? '';
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$shopNavCategory = $current === 'shop.php' ? ($categorySlug ?? '') : null;
$cartCount = cart_count();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($pageTitle) ?> · <?= SITE_NAME ?></title>
    <link rel="icon" href="<?= $base ?>assets/img/favicon.svg" type="image/svg+xml">
    <link rel="stylesheet" href="<?= $base ?>assets/vendor/bootstrap.min.css">
    <link rel="stylesheet" href="<?= $base ?>assets/css/style.css?v=1.2">
</head>
<body class="<?= e($bodyClass) ?>">
<a class="skip-link" href="#main">Skip to content</a>

<div class="announcement-bar text-center">
    <span class="d-none d-md-inline">Free delivery on orders over <?= money(FREE_SHIPPING_THRESHOLD) ?></span>
    <span class="d-md-none">Free delivery over <?= money(FREE_SHIPPING_THRESHOLD) ?></span>
    <span class="announcement-sep">·</span> Easy 30-day returns <span class="announcement-sep">·</span> New season, consciously made
</div>

<nav class="navbar navbar-expand-xl site-nav sticky-top" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand" href="<?= $base ?>index.php">
            <span class="brand-mark">T</span>
            <span class="brand-word"><?= SITE_NAME ?></span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto mb-3 mb-lg-0">
                <li class="nav-item"><a class="nav-link<?= $shopNavCategory === '' ? ' active' : '' ?>"<?= $shopNavCategory === '' ? ' aria-current="page"' : '' ?> href="<?= $base ?>shop.php">Shop all</a></li>
                <li class="nav-item"><a class="nav-link<?= $shopNavCategory === 'women' ? ' active' : '' ?>"<?= $shopNavCategory === 'women' ? ' aria-current="page"' : '' ?> href="<?= $base ?>shop.php?category=women">Women</a></li>
                <li class="nav-item"><a class="nav-link<?= $shopNavCategory === 'men' ? ' active' : '' ?>"<?= $shopNavCategory === 'men' ? ' aria-current="page"' : '' ?> href="<?= $base ?>shop.php?category=men">Men</a></li>
                <li class="nav-item"><a class="nav-link<?= $shopNavCategory === 'accessories' ? ' active' : '' ?>"<?= $shopNavCategory === 'accessories' ? ' aria-current="page"' : '' ?> href="<?= $base ?>shop.php?category=accessories">Accessories</a></li>
                <li class="nav-item"><a class="nav-link<?= $shopNavCategory === 'footwear' ? ' active' : '' ?>"<?= $shopNavCategory === 'footwear' ? ' aria-current="page"' : '' ?> href="<?= $base ?>shop.php?category=footwear">Footwear</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'about.php' ? 'active' : '' ?>" href="<?= $base ?>about.php">Our story</a></li>
                <li class="nav-item"><a class="nav-link <?= $current === 'contact.php' ? 'active' : '' ?>" href="<?= $base ?>contact.php">Contact</a></li>
            </ul>

            <form class="nav-search me-xl-3 mb-3 mb-xl-0" action="<?= $base ?>shop.php" method="get" role="search">
                <label for="navSearch" class="visually-hidden">Search products</label>
                <span class="nav-search-icon"><?= icon('search') ?></span>
                <input id="navSearch" class="form-control" type="search" name="q" placeholder="Search the collection"
                       value="<?= e($_GET['q'] ?? '') ?>" autocomplete="off">
            </form>

            <div class="d-flex align-items-center gap-2 nav-actions">
                <?php if (is_logged_in()): ?>
                    <div class="dropdown">
                        <button class="btn btn-icon dropdown-toggle nav-account-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="My account">
                            <?= icon('user') ?><span class="d-none d-xl-inline nav-account-name"><?= e(explode(' ', current_user_name())[0]) ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            <li><span class="dropdown-item-text small text-muted">Signed in as<br><strong><?= e(current_user_name()) ?></strong></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= $base ?>account.php"><?= icon('user') ?>My account</a></li>
                            <?php if (is_admin()): ?>
                                <li><a class="dropdown-item" href="<?= $base ?>admin/index.php"><?= icon('grid') ?>Admin dashboard</a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="<?= $base ?>logout.php"><?= icon('logout') ?>Sign out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="btn btn-icon d-lg-none d-xl-inline-flex" href="<?= $base ?>login.php" aria-label="Sign in"><?= icon('user') ?></a>
                    <a class="btn btn-outline-ink btn-sm d-none d-md-inline-flex" href="<?= $base ?>login.php">Sign in</a>
                <?php endif; ?>

                <a class="btn btn-icon cart-button" href="<?= $base ?>cart.php" aria-label="Shopping bag">
                    <?= icon('bag') ?>
                    <span class="cart-count" id="cartCount" aria-live="polite"><?= $cartCount ?></span>
                </a>
            </div>
        </div>
    </div>
</nav>

<main id="main">
<?php if (!empty($_SESSION['welcome_toast'])): ?>
    <div id="welcomeToast" class="d-none" data-message="<?= e($_SESSION['welcome_toast']) ?>"></div>
    <?php unset($_SESSION['welcome_toast']); ?>
<?php endif; ?>
<?php $flashes = get_flashes(); ?>
<?php if ($flashes): ?>
    <div class="container mt-4">
        <?php foreach ($flashes as $flash): ?>
            <?php
            $type = in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true) ? $flash['type'] : 'info';
            $iconName = $type === 'success' ? 'check' : ($type === 'danger' ? 'close' : 'info');
            ?>
            <div class="alert alert-<?= $type ?> alert-dismissible fade show d-flex align-items-start gap-2" role="alert">
                <?= icon($iconName, 'flex-shrink-0 mt-1') ?>
                <div><?= $flash['message'] ?></div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
