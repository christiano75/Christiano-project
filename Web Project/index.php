<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Considered clothing, made to last';
$pageDescription = 'Threadline is a considered wardrobe of everyday essentials — responsible fabrics, honest prices and pieces made to last.';

$featured = $conn->query(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
      WHERE p.featured = 1
      ORDER BY p.created_at DESC
      LIMIT 8"
)->fetch_all(MYSQLI_ASSOC);

$categories = $conn->query(
    "SELECT c.*, COUNT(p.id) AS product_count
       FROM categories c
       LEFT JOIN products p ON p.category_id = c.id
      GROUP BY c.id
      ORDER BY c.id"
)->fetch_all(MYSQLI_ASSOC);

$categoryImages = [
    'women' => 'wrap-dress.jpg',
    'men' => 'linen-trousers.jpg',
    'accessories' => 'leather-tote.jpg',
    'footwear' => 'canvas-sneakers.jpg',
];

include __DIR__ . '/includes/header.php';
?>

<!-- ============================== HERO ============================== -->
<section id="heroCarousel" class="carousel slide hero-carousel" data-bs-ride="carousel" data-bs-interval="6500">
    <div class="carousel-indicators">
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Slide 1"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1" aria-label="Slide 2"></button>
        <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2" aria-label="Slide 3"></button>
    </div>

    <div class="carousel-inner">
        <div class="carousel-item active">
            <div class="hero-slide hero-1">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="hero-copy">
                                <p class="kicker">Autumn / Winter 2026</p>
                                <h1>Pieces that feel like you.</h1>
                                <p class="lead">Natural fibres, quiet colours and cuts that work for real life. Made in small runs with partners we know by name.</p>
                                <div class="d-flex flex-wrap gap-2 mt-4">
                                    <a class="btn btn-ink btn-lg" href="shop.php">Shop the collection</a>
                                    <a class="btn btn-outline-ink btn-lg" href="about.php">Our story</a>
                                </div>
                                <p class="mt-4 mb-0 small text-muted-warm">Free delivery over <?= money(FREE_SHIPPING_THRESHOLD) ?> · 30-day returns</p>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="hero-art">
                                <div class="hero-art-inner">
                                    <img src="assets/img/products/wrap-dress.jpg" alt="Midi wrap dress in terracotta">
                                </div>
                                <span class="hero-tag">New in · Midi Wrap Dress</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-item">
            <div class="hero-slide hero-2">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6 order-lg-2">
                            <div class="hero-copy">
                                <p class="kicker">The everyday edit</p>
                                <h1>Built for the slow Sunday.</h1>
                                <p class="lead">Soft shirting, wide-leg linen and a cap that goes with everything. Clothes that ask nothing of you.</p>
                                <div class="d-flex flex-wrap gap-2 mt-4">
                                    <a class="btn btn-ink btn-lg" href="shop.php?category=men">Shop men</a>
                                    <a class="btn btn-outline-ink btn-lg" href="shop.php?category=accessories">Shop accessories</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 order-lg-1">
                            <div class="hero-art">
                                <div class="hero-art-inner">
                                    <img src="assets/img/products/linen-trousers.jpg" alt="Wide leg linen trousers">
                                </div>
                                <span class="hero-tag">Natural fibres only</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="carousel-item">
            <div class="hero-slide hero-3">
                <div class="container">
                    <div class="row align-items-center">
                        <div class="col-lg-6">
                            <div class="hero-copy">
                                <p class="kicker">Small things, considered</p>
                                <h1>Finish the look.</h1>
                                <p class="lead">A vegetable-tanned tote, a recycled wool beanie, a pair of canvas sneakers. The details that carry a wardrobe.</p>
                                <div class="d-flex flex-wrap gap-2 mt-4">
                                    <a class="btn btn-ink btn-lg" href="shop.php?category=accessories">Shop accessories</a>
                                    <a class="btn btn-outline-ink btn-lg" href="shop.php?category=footwear">Shop footwear</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="hero-art">
                                <div class="hero-art-inner">
                                    <img src="assets/img/products/leather-tote.jpg" alt="Leather tote bag">
                                </div>
                                <span class="hero-tag">Limited run · 120 made</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Next</span>
    </button>
</section>

<!-- ============================== VALUES ============================== -->
<section class="value-strip">
    <div class="container">
        <div class="row gy-4">
            <div class="col-6 col-lg-3">
                <div class="value-item"><?= icon('truck') ?><div><h3>Free delivery</h3><p>On every order over <?= money(FREE_SHIPPING_THRESHOLD) ?>.</p></div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="value-item"><?= icon('refresh') ?><div><h3>30-day returns</h3><p>Change your mind? Send it back, free.</p></div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="value-item"><?= icon('leaf') ?><div><h3>Responsible fibres</h3><p>Organic cotton, linen and recycled wool.</p></div></div>
            </div>
            <div class="col-6 col-lg-3">
                <div class="value-item"><?= icon('shield') ?><div><h3>Secure checkout</h3><p>Your details stay private, always.</p></div></div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== CATEGORIES ============================== -->
<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="kicker mb-2">Find your fit</p>
                <h2>Shop by category</h2>
            </div>
            <a class="link-arrow" href="shop.php">View everything <?= icon('arrow-right') ?></a>
        </div>

        <div class="row g-3 g-lg-4">
            <?php foreach ($categories as $category): ?>
                <div class="col-6 col-lg-3">
                    <a class="category-card reveal" href="shop.php?category=<?= e($category['slug']) ?>">
                        <img src="assets/img/products/<?= e($categoryImages[$category['slug']] ?? 'linen-shirt.jpg') ?>"
                             alt="<?= e($category['name']) ?> collection" loading="lazy">
                        <span class="category-arrow"><?= icon('arrow-right') ?></span>
                        <div class="category-card-body">
                            <h3><?= e($category['name']) ?></h3>
                            <span><?= (int) $category['product_count'] ?> pieces</span>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================== FEATURED ============================== -->
<section class="section pt-0">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="kicker mb-2">The short list</p>
                <h2>Most-loved pieces</h2>
                <p>A tight edit of the styles our community keeps coming back to.</p>
            </div>
            <a class="link-arrow" href="shop.php?sort=new">Shop new arrivals <?= icon('arrow-right') ?></a>
        </div>

        <div class="row row-cols-2 row-cols-lg-4 g-3 g-lg-4">
            <?php foreach ($featured as $product): ?>
                <div class="col"><?php product_card($product); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ============================== EDITORIAL ============================== -->
<section class="split-banner">
    <div class="container-fluid px-0">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-6">
                <div class="split-copy">
                    <p class="kicker mb-3">The outerwear edit</p>
                    <h2 class="mb-3">One coat. Everything else optional.</h2>
                    <p class="mb-4" style="max-width: 26rem">
                        Cut from a recycled wool blend and finished with deep pockets and dropped shoulders,
                        the Wool Blend Coat is the layer that makes the rest of your wardrobe work harder.
                    </p>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <a class="btn btn-accent" href="product.php?id=5">Shop the coat</a>
                        <a class="link-arrow text-cream" style="color:#f6f1e8;border-color:#f6f1e8" href="shop.php?category=women">All women's <?= icon('arrow-right') ?></a>
                    </div>
                    <div class="row mt-4 pt-3" style="border-top:1px solid rgba(246,241,232,.15)">
                        <div class="col-4"><div class="h5 mb-0 text-white">60%</div><div class="small" style="color:rgba(246,241,232,.6)">recycled wool</div></div>
                        <div class="col-4"><div class="h5 mb-0 text-white">8</div><div class="small" style="color:rgba(246,241,232,.6)">pairs left</div></div>
                        <div class="col-4"><div class="h5 mb-0 text-white">30</div><div class="small" style="color:rgba(246,241,232,.6)">day returns</div></div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="split-art h-100">
                    <img src="assets/img/products/wool-coat.jpg" alt="Wool blend coat in camel">
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============================== REVIEWS ============================== -->
<section class="section">
    <div class="container">
        <div class="section-heading">
            <div>
                <p class="kicker mb-2">Word of mouth</p>
                <h2>Worn and loved</h2>
            </div>
            <p class="small text-muted-warm mb-0">4.8 average from 2,140 reviews</p>
        </div>

        <div class="row g-3 g-lg-4">
            <?php
            $reviews = [
                ['The linen shirt has become my uniform. It has been washed a hundred times and still looks new.', 'Amira S.', 'Linen Camp Shirt', 5],
                ['I bought the coat for a trip and wore it every single day. Warm without being bulky.', 'Grace L.', 'Wool Blend Coat', 5],
                ['Sizing advice on the product page was spot on. The jeans fit exactly as described.', 'Daniel T.', 'Straight Leg Jeans', 4],
            ];
            foreach ($reviews as [$quote, $name, $item, $stars]): ?>
                <div class="col-md-4">
                    <figure class="testimonial reveal mb-0">
                        <div class="stars"><?= str_repeat('★', $stars) . str_repeat('☆', 5 - $stars) ?></div>
                        <blockquote>“<?= e($quote) ?>”</blockquote>
                        <figcaption><?= e($name) ?> · <span class="text-muted-warm"><?= e($item) ?></span></figcaption>
                    </figure>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
