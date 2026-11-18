<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect('account.php');
}

$form = ['full_name' => '', 'email' => '', 'phone' => ''];
$errors = [];

if (is_post()) {
    verify_csrf();

    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');
    $terms = isset($_POST['terms']);

    if (mb_strlen($form['full_name']) < 3) {
        $errors['full_name'] = 'Please enter your full name (at least 3 characters).';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param('s', $form['email']);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            $errors['email'] = 'An account already exists with that email. Try signing in.';
        }
    }
    if ($form['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $form['phone'])) {
        $errors['phone'] = 'Enter a valid phone number.';
    }
    if (strlen($password) < 8) {
        $errors['password'] = 'Use at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors['confirm_password'] = $confirm === '' ? 'Please confirm your password.' : 'Passwords do not match.';
    }
    if (!$terms) {
        $errors['terms'] = 'Please accept the terms to continue.';
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';
        $stmt = $conn->prepare(
            "INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('sssss', $form['full_name'], $form['email'], $hash, $form['phone'], $role);
        $stmt->execute();

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $conn->insert_id;
        $_SESSION['user_name'] = $form['full_name'];
        $_SESSION['user_role'] = 'customer';
        $_SESSION['welcome_toast'] = 'Welcome to Threadline, ' . explode(' ', $form['full_name'])[0] . '.';

        set_flash('success', 'Your account is ready. Start with the pieces everyone comes back for.');
        redirect('account.php');
    }

    set_flash('danger', 'Please check the highlighted fields and try again.');
}

$pageTitle = 'Create an account';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <div class="auth-wrap">
        <div class="row g-4 align-items-stretch justify-content-center w-100">
            <div class="col-lg-5">
                <div class="auth-side h-100">
                    <div>
                        <p class="kicker mb-2">Join the studio</p>
                        <h2>Create an account in a minute.</h2>
                        <p class="mb-0">Faster checkout, order tracking and first look at every small run.</p>
                    </div>
                    <div class="mt-4">
                        <div class="d-flex gap-2 align-items-start mb-3" style="color:rgba(246,241,232,.85)">
                            <?= icon('leaf') ?>
                            <p class="small mb-0">We email once a week at most, and never share your details.</p>
                        </div>
                        <div class="d-flex gap-2 align-items-start" style="color:rgba(246,241,232,.85)">
                            <?= icon('refresh') ?>
                            <p class="small mb-0">Free returns for 30 days, even on sale pieces.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="auth-card">
                    <p class="kicker mb-2">New here</p>
                    <h1 class="h3 mb-4">Create your account</h1>

                    <form method="post" action="register.php" id="registerForm" novalidate>
                        <?= csrf_field() ?>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" for="full_name">Full name</label>
                                <input class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>"
                                       id="full_name" name="full_name" type="text" value="<?= e($form['full_name']) ?>"
                                       autocomplete="name" required>
                                <?php if (isset($errors['full_name'])): ?><div class="invalid-feedback d-block"><?= e($errors['full_name']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="email">Email address</label>
                                <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                       id="email" name="email" type="email" value="<?= e($form['email']) ?>"
                                       autocomplete="email" required>
                                <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="phone">Phone <span class="text-muted-warm fw-normal">(optional)</span></label>
                                <input class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>"
                                       id="phone" name="phone" type="tel" value="<?= e($form['phone']) ?>" autocomplete="tel">
                                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback d-block"><?= e($errors['phone']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="password">Password</label>
                                <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                                       id="password" name="password" type="password" autocomplete="new-password" required>
                                <div class="form-text">At least 8 characters.</div>
                                <?php if (isset($errors['password'])): ?><div class="invalid-feedback d-block"><?= e($errors['password']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" for="confirm_password">Confirm password</label>
                                <input class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                                       id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                                <?php if (isset($errors['confirm_password'])): ?><div class="invalid-feedback d-block"><?= e($errors['confirm_password']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input <?= isset($errors['terms']) ? 'is-invalid' : '' ?>"
                                           type="checkbox" value="1" id="terms" name="terms">
                                    <label class="form-check-label small" for="terms">
                                        I agree to the <a href="about.php#care">terms of sale</a> and the privacy policy.
                                    </label>
                                    <?php if (isset($errors['terms'])): ?><div class="invalid-feedback d-block"><?= e($errors['terms']) ?></div><?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-ink w-100 btn-lg mt-4" type="submit">Create account</button>
                    </form>

                    <p class="small text-muted-warm text-center mt-3 mb-0">
                        Already have an account? <a href="login.php">Sign in</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
