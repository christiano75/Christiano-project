<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$form = ['name' => '', 'email' => '', 'subject' => '', 'message' => ''];
$errors = [];

if (is_post()) {
    verify_csrf();

    foreach (array_keys($form) as $field) {
        $form[$field] = trim((string) ($_POST[$field] ?? ''));
    }

    if (mb_strlen($form['name']) < 2) {
        $errors['name'] = 'Please tell us your name.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (mb_strlen($form['subject']) < 3) {
        $errors['subject'] = 'Add a short subject.';
    }
    if (mb_strlen($form['message']) < 10) {
        $errors['message'] = 'Please write at least 10 characters.';
    }

    if (!$errors) {
        $stmt = $conn->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('ssss', $form['name'], $form['email'], $form['subject'], $form['message']);
        $stmt->execute();

        set_flash('success', 'Thank you — your message is with the studio. We reply within one working day.');
        redirect('contact.php');
    }

    set_flash('danger', 'Please check the highlighted fields and try again.');
}

$pageTitle = 'Contact the studio';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Contact</li>
        </ol>
    </nav>

    <header class="page-header pt-2">
        <p class="kicker">We are here</p>
        <h1>Talk to the studio.</h1>
        <p>Sizing, returns, restocks or simply a note about what you would like us to make next — it all lands with a real person.</p>
    </header>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-7">
            <div class="panel">
                <h2 class="panel-title">Send a message</h2>
                <form method="post" action="contact.php" id="contactForm" novalidate>
                    <?= csrf_field() ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name">Your name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                   id="name" name="name" type="text" value="<?= e($form['name']) ?>" autocomplete="name" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="email">Email address</label>
                            <input class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>"
                                   id="email" name="email" type="email" value="<?= e($form['email']) ?>" autocomplete="email" required>
                            <?php if (isset($errors['email'])): ?><div class="invalid-feedback d-block"><?= e($errors['email']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="subject">Subject</label>
                            <input class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>"
                                   id="subject" name="subject" type="text" value="<?= e($form['subject']) ?>" required>
                            <?php if (isset($errors['subject'])): ?><div class="invalid-feedback d-block"><?= e($errors['subject']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="message">Message</label>
                            <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>"
                                      id="message" name="message" rows="6" required><?= e($form['message']) ?></textarea>
                            <?php if (isset($errors['message'])): ?><div class="invalid-feedback d-block"><?= e($errors['message']) ?></div><?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-4">
                        <p class="small text-muted-warm mb-0">We reply within one working day, Mon–Fri.</p>
                        <button class="btn btn-ink" type="submit"><?= icon('mail') ?> Send message</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="panel mb-3">
                <h2 class="panel-title">The studio</h2>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex gap-3 mb-3">
                        <span class="stat-icon"><?= icon('pin') ?></span>
                        <div>
                            <p class="mb-0 fw-semibold">Threadline Studio</p>
                            <p class="small text-muted-warm mb-0">Victoria, Mah&eacute;</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-3">
                        <span class="stat-icon"><?= icon('phone') ?></span>
                        <div>
                            <p class="mb-0 fw-semibold">2568283</p>
                            <p class="small text-muted-warm mb-0">Mon–Fri, 9am–6pm MYT</p>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <span class="stat-icon"><?= icon('mail') ?></span>
                        <div>
                            <p class="mb-0 fw-semibold">houareauchristiano@gmail.com</p>
                            <p class="small text-muted-warm mb-0">Replies within one working day</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="panel mb-3">
                <h2 class="panel-title">Quick answers</h2>
                <div class="accordion accordion-threadline" id="contactFaq">
                    <div class="accordion-item pt-0">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">How do I choose a size?</button>
                        </h3>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#contactFaq">
                            <div class="accordion-body">Every product page has a size guide with body measurements. If you are between sizes, size down for a closer fit or message us with your measurements.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">How do returns work?</button>
                        </h3>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#contactFaq">
                            <div class="accordion-body">Returns are free for 30 days. Keep the tags on, pack the piece in its original mailer and reply to your order email to arrange collection.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">When will sold-out pieces return?</button>
                        </h3>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#contactFaq">
                            <div class="accordion-body">We make in small runs about eight weeks apart. Send us a note with the size you need and we will let you know before it goes live.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="panel bg-sand border-0">
                <p class="eyebrow mb-1">Visit</p>
                <p class="mb-0 small">Our Victoria, Mah&eacute; studio is open for fittings by appointment on Thursdays. Email to book.</p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
