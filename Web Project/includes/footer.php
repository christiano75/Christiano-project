<?php $base = $base ?? ''; ?>
</main>

<footer class="site-footer mt-5">
    <div class="container">
        <div class="newsletter-panel">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <p class="kicker mb-2">The Threadline list</p>
                    <h2 class="h3 mb-1">Good clothes, better inbox.</h2>
                    <p class="mb-0 text-muted-warm">Early access to new drops, restocks and studio stories. One thoughtful email a week.</p>
                </div>
                <div class="col-lg-6">
                    <form class="newsletter-form" id="newsletterForm" novalidate>
                        <label class="visually-hidden" for="newsletterEmail">Email address</label>
                        <div class="input-group">
                            <input type="email" class="form-control" id="newsletterEmail" name="newsletter_email"
                                   placeholder="you@example.com" required>
                            <button class="btn btn-ink" type="submit">Join the list</button>
                        </div>
                        <div class="form-text text-muted-warm">No spam. Unsubscribe whenever you like.</div>
                        <div class="small mt-2 d-none" id="newsletterFeedback" role="status"></div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row gy-4 footer-links">
            <div class="col-lg-4">
                <a class="footer-brand" href="<?= $base ?>index.php">
                    <span class="brand-mark">T</span><span class="brand-word"><?= SITE_NAME ?></span>
                </a>
                <p class="mt-3 mb-3 text-muted-warm" style="max-width: 22rem">
                    A considered wardrobe of everyday essentials. Natural fibres, fair partners and pieces
                    designed to be worn for years, not seasons.
                </p>
                <div class="d-flex gap-2">
                    <a class="social-link" href="#" aria-label="Threadline on Instagram"><?= icon('instagram') ?></a>
                    <a class="social-link" href="#" aria-label="Threadline on Facebook"><?= icon('facebook') ?></a>
                    <a class="social-link" href="<?= $base ?>contact.php" aria-label="Email Threadline"><?= icon('mail') ?></a>
                </div>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="footer-heading">Shop</h3>
                <ul class="footer-list">
                    <li><a href="<?= $base ?>shop.php">All pieces</a></li>
                    <li><a href="<?= $base ?>shop.php?category=women">Women</a></li>
                    <li><a href="<?= $base ?>shop.php?category=men">Men</a></li>
                    <li><a href="<?= $base ?>shop.php?category=accessories">Accessories</a></li>
                    <li><a href="<?= $base ?>shop.php?category=footwear">Footwear</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-2">
                <h3 class="footer-heading">Help</h3>
                <ul class="footer-list">
                    <li><a href="<?= $base ?>contact.php">Contact us</a></li>
                    <li><a href="<?= $base ?>about.php">Our story</a></li>
                    <li><a href="<?= $base ?>account.php">Order tracking</a></li>
                    <li><a href="<?= $base ?>cart.php">Your bag</a></li>
                    <li><a href="<?= $base ?>register.php">Create account</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h3 class="footer-heading">Studio</h3>
                <ul class="footer-list">
                    <li><?= icon('pin') ?><span>18 Jalan Tun Perak<br>50050 Kuala Lumpur, Malaysia</span></li>
                    <li><?= icon('phone') ?><a href="tel:2568283">2568283</a></li>
                    <li><?= icon('mail') ?><a href="mailto:houareauchristiano@gmail.com">houareauchristiano@gmail.com</a></li>
                    <li><?= icon('clock') ?><span>Mon–Fri, 9am–6pm MYT</span></li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. A student project built with PHP, MySQL &amp; Bootstrap 5.</p>
            <p class="mb-0 d-flex flex-wrap gap-3">
                <a href="<?= $base ?>about.php#care">Care guide</a>
                <a href="<?= $base ?>about.php#values">Sustainability</a>
                <span class="text-muted-warm">Visa · Mastercard · FPX</span>
            </p>
        </div>
    </div>
</footer>

<button class="back-to-top" id="backToTop" type="button" aria-label="Back to top"><?= icon('arrow-right') ?></button>

<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<script src="<?= $base ?>assets/vendor/bootstrap.bundle.min.js"></script>
<script src="<?= $base ?>assets/js/main.js?v=1.3"></script>
</body>
</html>
