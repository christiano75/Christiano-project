<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

require_login();

$items = cart_items($conn);
if (!$items) {
    set_flash('warning', 'Your bag is empty — add a piece before checking out.');
    redirect('cart.php');
}
$summary = cart_summary($items);

$userStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userStmt->bind_param('i', $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

$errors = [];
$form = [
    'full_name' => $user['full_name'] ?? '',
    'email' => $user['email'] ?? '',
    'phone' => $user['phone'] ?? '',
    'address' => $user['address'] ?? '',
    'city' => $user['city'] ?? '',
    'postcode' => $user['postcode'] ?? '',
    'payment_method' => 'card',
];

if (is_post()) {
    verify_csrf();

    foreach (['full_name', 'email', 'phone', 'address', 'city', 'postcode', 'payment_method'] as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (mb_strlen($form['full_name']) < 3) {
        $errors['full_name'] = 'Please enter your full name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $form['phone'])) {
        $errors['phone'] = 'Enter a valid phone number.';
    }
    if (mb_strlen($form['address']) < 5) {
        $errors['address'] = 'Enter your street address.';
    }
    if ($form['city'] === '') {
        $errors['city'] = 'Enter your city.';
    }
    if (!preg_match('/^\d{4,6}$/', $form['postcode'])) {
        $errors['postcode'] = 'Postcode should be 4–6 digits.';
    }
    if ($form['payment_method'] !== 'card') {
        $errors['payment_method'] = 'Only card payments are accepted.';
    }

    if ($form['payment_method'] === 'card') {
        $cardNumber = preg_replace('/\s+/', '', (string) ($_POST['card_number'] ?? ''));
        $cardExpiry = trim((string) ($_POST['card_expiry'] ?? ''));
        $cardCvc = trim((string) ($_POST['card_cvc'] ?? ''));

        if (!preg_match('/^\d{13,19}$/', (string) $cardNumber)) {
            $errors['card_number'] = 'Enter a valid card number (13–19 digits).';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $cardExpiry)) {
            $errors['card_expiry'] = 'Use MM/YY, for example 08/29.';
        }
        if (!preg_match('/^\d{3,4}$/', $cardCvc)) {
            $errors['card_cvc'] = 'CVC must be 3 or 4 digits.';
        }
    }

    // Final stock check before touching the database
    foreach ($items as $item) {
        if ($item['qty'] > (int) $item['product']['stock']) {
            $errors['stock'] = 'Only ' . (int) $item['product']['stock'] . ' of ' . $item['product']['name'] . ' left. Please update your bag.';
            break;
        }
    }

    if (!$errors) {
        try {
            $conn->begin_transaction();

            do {
                $reference = 'TL-' . random_int(100000, 999999);
                $check = $conn->prepare("SELECT id FROM orders WHERE reference = ?");
                $check->bind_param('s', $reference);
                $check->execute();
                $exists = (bool) $check->get_result()->fetch_assoc();
            } while ($exists);

            $orderStmt = $conn->prepare(
                "INSERT INTO orders
                    (user_id, reference, full_name, email, phone, address, city, postcode,
                     subtotal, shipping, total, payment_method, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'paid')"
            );
            $orderStmt->bind_param(
                'isssssssddds',
                $_SESSION['user_id'],
                $reference,
                $form['full_name'],
                $form['email'],
                $form['phone'],
                $form['address'],
                $form['city'],
                $form['postcode'],
                $summary['subtotal'],
                $summary['shipping'],
                $summary['total'],
                $form['payment_method']
            );
            $orderStmt->execute();
            $orderId = (int) $conn->insert_id;

            $itemStmt = $conn->prepare(
                "INSERT INTO order_items (order_id, product_id, product_name, size, unit_price, quantity)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stockStmt = $conn->prepare("UPDATE products SET stock = GREATEST(stock - ?, 0) WHERE id = ?");

            foreach ($items as $item) {
                $productId = (int) $item['product']['id'];
                $itemStmt->bind_param(
                    'iissdi',
                    $orderId,
                    $productId,
                    $item['product']['name'],
                    $item['size'],
                    $item['unit_price'],
                    $item['qty']
                );
                $itemStmt->execute();

                $stockStmt->bind_param('ii', $item['qty'], $productId);
                $stockStmt->execute();
            }

            $conn->commit();
            cart_clear();
            $_SESSION['last_order'] = $reference;

            set_flash('success', 'Thank you — your order ' . $reference . ' is confirmed.');
            redirect('order-confirmation.php?ref=' . urlencode($reference));
        } catch (Throwable $exception) {
            $conn->rollback();
            $errors['general'] = 'We could not place your order. Please try again.';
        }
    }

    if ($errors) {
        set_flash('danger', 'Please check the highlighted fields and try again.');
    }
}

$pageTitle = 'Checkout';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php">Your bag</a></li>
            <li class="breadcrumb-item active" aria-current="page">Checkout</li>
        </ol>
    </nav>

    <header class="page-header pt-2">
        <p class="kicker">Secure checkout</p>
        <h1 class="mb-2">Where should we send it?</h1>
        <p>You are signed in as <?= e($user['full_name']) ?>. Card details are for demonstration only and are never saved.</p>
    </header>

    <?php if (isset($errors['general'])): ?>
        <div class="alert alert-danger"><?= icon('info') ?> <?= e($errors['general']) ?></div>
    <?php endif; ?>
    <?php if (isset($errors['stock'])): ?>
        <div class="alert alert-warning"><?= icon('info') ?> <?= e($errors['stock']) ?> <a href="cart.php" class="alert-link">Review your bag</a></div>
    <?php endif; ?>

    <form method="post" action="checkout.php" id="checkoutForm" novalidate>
        <?= csrf_field() ?>
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <section class="panel mb-3">
                    <h2 class="panel-title">1 · Contact details</h2>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="full_name">Full name</label>
                            <input class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" type="text" value="<?= e($form['full_name']) ?>" autocomplete="name" required>
                            <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback d-block"><?= e($errors['full_name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone">Phone number</label>
                            <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" autocomplete="tel" required>
                            <?php if (isset($errors['phone'])): ?><div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="panel mb-3">
                    <h2 class="panel-title">2 · Delivery address</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="address">Street address</label>
                            <input class="form-control <?= isset($errors['address']) ? 'is-invalid' : '' ?>" id="address" name="address" type="text" value="<?= e($form['address']) ?>" autocomplete="street-address" required>
                            <?php if (isset($errors['address'])): ?><div class="invalid-feedback d-block"><?= e($errors['address']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label" for="city">City</label>
                            <input class="form-control <?= isset($errors['city']) ? 'is-invalid' : '' ?>" id="city" name="city" type="text" value="<?= e($form['city']) ?>" autocomplete="address-level2" required>
                            <?php if (isset($errors['city'])): ?><div class="invalid-feedback d-block"><?= e($errors['city']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="postcode">Postcode</label>
                            <input class="form-control <?= isset($errors['postcode']) ? 'is-invalid' : '' ?>" id="postcode" name="postcode" type="text" value="<?= e($form['postcode']) ?>" autocomplete="postal-code" required>
                            <?php if (isset($errors['postcode'])): ?><div class="invalid-feedback d-block"><?= e($errors['postcode']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <h2 class="panel-title">3 · Payment</h2>
                    <div class="d-flex flex-wrap gap-3 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="payment_method" id="payCard" value="card" checked>
                            <label class="form-check-label" for="payCard"><?= icon('card') ?> Card</label>
                        </div>
                    </div>

                    <?php if (isset($errors['payment_method'])): ?>
                        <div class="text-danger small mb-2"><?= e($errors['payment_method']) ?></div>
                    <?php endif; ?>

                    <div id="cardFields">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="card_number">Card number</label>
                                <input class="form-control <?= isset($errors['card_number']) ? 'is-invalid' : '' ?>" id="card_number" name="card_number" type="text" inputmode="numeric" placeholder="4242 4242 4242 4242" autocomplete="cc-number">
                                <?php if (isset($errors['card_number'])): ?><div class="invalid-feedback d-block"><?= e($errors['card_number']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="card_expiry">Expiry</label>
                                <input class="form-control <?= isset($errors['card_expiry']) ? 'is-invalid' : '' ?>" id="card_expiry" name="card_expiry" type="text" inputmode="numeric" placeholder="MM/YY" autocomplete="cc-exp">
                                <?php if (isset($errors['card_expiry'])): ?><div class="invalid-feedback d-block"><?= e($errors['card_expiry']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-6">
                                <label class="form-label" for="card_cvc">CVC</label>
                                <input class="form-control <?= isset($errors['card_cvc']) ? 'is-invalid' : '' ?>" id="card_cvc" name="card_cvc" type="text" inputmode="numeric" placeholder="123" autocomplete="cc-csc">
                                <?php if (isset($errors['card_cvc'])): ?><div class="invalid-feedback d-block"><?= e($errors['card_cvc']) ?></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4">
                    <a class="link-arrow" href="cart.php"><?= icon('arrow-right') ?> Back to bag</a>
                    <button class="btn btn-ink btn-lg" type="submit"><?= icon('lock') ?> Place order — <?= money($summary['total']) ?></button>
                </div>
            </div>

            <div class="col-lg-5">
                <aside class="summary-card">
                    <h2 class="h5 mb-3">Your order</h2>
                    <?php foreach ($items as $item): ?>
                        <div class="order-line">
                            <img src="<?= e(product_image($item['product']['image'])) ?>" alt="<?= e($item['product']['name']) ?>">
                            <div class="flex-grow-1">
                                <p class="name"><?= e($item['product']['name']) ?></p>
                                <p class="meta">Size <?= e($item['size']) ?> · Qty <?= (int) $item['qty'] ?></p>
                            </div>
                            <span class="small fw-semibold"><?= money($item['line_total']) ?></span>
                        </div>
                    <?php endforeach; ?>

                    <div class="summary-row mt-3"><span>Subtotal</span><span><?= money($summary['subtotal']) ?></span></div>
                    <div class="summary-row"><span>Delivery</span><span><?= $summary['shipping'] > 0 ? money($summary['shipping']) : 'Free' ?></span></div>
                    <div class="summary-row total"><span>Total</span><span><?= money($summary['total']) ?></span></div>

                    <p class="shipping-note mt-3 mb-0">
                        <?= $summary['free_remaining'] > 0
                            ? 'Add ' . money($summary['free_remaining']) . ' more for free delivery.'
                            : 'Your order qualifies for free delivery.' ?>
                    </p>
                </aside>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
