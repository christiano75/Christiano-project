<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'account.php');
}

$email = '';
$errors = [];

if (is_post()) {
    verify_csrf();

    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if ($password === '') {
        $errors['password'] = 'Enter your password.';
    }

    if (!$errors) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['welcome_toast'] = 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '.';

            $target = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($user['role'] === 'admin') {
                set_flash('success', 'You are signed in as an administrator.');
                redirect('admin/index.php');
            }

            set_flash('success', 'Good to see you again. Your bag and orders are right where you left them.');
            redirect($target && str_contains((string) $target, 'checkout') ? (string) $target : 'account.php');
        }

        $errors['general'] = 'That email and password combination does not match our records.';
    }
}

$pageTitle = 'Sign in';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <div class="auth-wrap">
        <div class="row g-4 align-items-stretch justify-content-center w-100">
            <div class="col-lg-5">
                <div class="auth-side h-100">
                    <div>
                        <p class="kicker mb-2">Members</p>
                        <h2>Your wardrobe, kept in one place.</h2>
                        <p class="mb-0">Track orders, save your details for faster checkout and hear about restocks before they sell out.</p>
                    </div>
                    <ul class="list-unstyled mt-4 mb-0 small" style="color:rgba(246,241,232,.8)">
                        <li class="d-flex gap-2 mb-2"><?= icon('check') ?><span>Order history and live status</span></li>
                        <li class="d-flex gap-2 mb-2"><?= icon('check') ?><span>Faster checkout with saved addresses</span></li>
                        <li class="d-flex gap-2"><?= icon('check') ?><span>Early access to small-run drops</span></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="auth-card h-100">
                    <p class="kicker mb-2">Welcome back</p>
                    <h1 class="h3 mb-4">Sign in to Threadline</h1>

                    <?php if (isset($errors['general'])): ?>
                        <div class="alert alert-danger"><?= e($errors['general']) ?></div>
                    <?php endif; ?>

                    <form method="post" action="login.php" id="loginForm" novalidate>
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   id="email" name="email" type="email" value="<?= e($email) ?>"
                                   autocomplete="email" required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between">
                                <label class="form-label" for="password">Password</label>
                                <a class="small text-muted-warm text-decoration-none" href="contact.php">Forgot?</a>
                            </div>
                            <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                   id="password" name="password" type="password" autocomplete="current-password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback d-block"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button class="btn btn-ink w-100 btn-lg" type="submit">Sign in</button>
                    </form>

                    <p class="small text-muted-warm text-center mt-3 mb-0">
                        New here? <a href="register.php">Create an account</a>
                    </p>

                    <div class="demo-credentials mt-4">
                        <p class="mb-1 fw-semibold">Demo accounts</p>
                        <p class="mb-1">Customer: <code>alex@example.com</code> / <code>Password123</code></p>
                        <p class="mb-0">Admin: <code>admin@threadline.test</code> / <code>Admin@12345</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
