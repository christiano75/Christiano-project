<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$pageTitle = 'Our story';
$pageDescription = 'How Threadline makes clothes: responsible fibres, small runs and partners we know by name.';
include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Our story</li>
        </ol>
    </nav>

    <header class="page-header pt-2">
        <p class="kicker">Since 2019</p>
        <h1>Fewer, better things.</h1>
        <p>Threadline began with a simple frustration: clothes that looked good online but fell apart after a season. We wanted the opposite — a small wardrobe of pieces you reach for without thinking.</p>
    </header>

    <section class="row g-4 g-lg-5 align-items-center mb-5">
        <div class="col-lg-6">
            <div class="product-gallery" style="aspect-ratio: 5 / 4">
                <img src="assets/img/products/wool-coat.jpg" alt="Wool blend coat from the Threadline collection">
            </div>
        </div>
        <div class="col-lg-6">
            <p class="kicker mb-2">Our approach</p>
            <h2 class="h1 mb-3" style="font-size: clamp(1.9rem, 3.4vw, 2.8rem)">Designed to be worn, not replaced.</h2>
            <p class="text-muted-warm">We work with six workshops in Portugal, Malaysia and India — all audited, all paid above the local living wage. We order in small quantities, keep colours across seasons and repair anything that needs it.</p>
            <p class="text-muted-warm">The result is a collection that changes slowly. When something works, we make it again rather than chasing a trend.</p>
            <div class="row g-3 mt-2">
                <div class="col-4">
                    <p class="h3 mb-0">6</p>
                    <p class="small text-muted-warm mb-0">partner workshops</p>
                </div>
                <div class="col-4">
                    <p class="h3 mb-0">92%</p>
                    <p class="small text-muted-warm mb-0">natural or recycled fibres</p>
                </div>
                <div class="col-4">
                    <p class="h3 mb-0">2,140</p>
                    <p class="small text-muted-warm mb-0">reviews, 4.8 average</p>
                </div>
            </div>
        </div>
    </section>

    <section id="values" class="section pt-0">
        <div class="section-heading">
            <div>
                <p class="kicker mb-2">What we stand for</p>
                <h2>Three things we refuse to compromise</h2>
            </div>
        </div>
        <div class="row g-3 g-lg-4">
            <div class="col-md-4">
                <div class="panel h-100">
                    <span class="stat-icon"><?= icon('leaf') ?></span>
                    <h3 class="h5 mt-3">Better fibres</h3>
                    <p class="text-muted-warm small mb-0">Organic cotton, European linen, recycled wool and vegetable-tanned leather. No virgin polyester in the main collection.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel h-100">
                    <span class="stat-icon"><?= icon('users') ?></span>
                    <h3 class="h5 mt-3">People first</h3>
                    <p class="text-muted-warm small mb-0">Long-term partners, published factory list and wages we can stand behind. We visit every workshop at least once a year.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="panel h-100">
                    <span class="stat-icon"><?= icon('refresh') ?></span>
                    <h3 class="h5 mt-3">Made to be mended</h3>
                    <p class="text-muted-warm small mb-0">Free repairs for the life of the garment. Send us a photo and we will tell you what is possible before you post it.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="split-banner mb-5">
        <div class="container-fluid px-0">
            <div class="row g-0 align-items-stretch">
                <div class="col-lg-6">
                    <div class="split-copy">
                        <p class="kicker mb-3">How it is made</p>
                        <h2 class="mb-4">From fibre to front door, in six steps.</h2>
                        <ol class="ps-3 mb-0" style="color:rgba(246,241,232,.75)">
                            <li class="mb-2">Fibres sourced from certified mills in Europe and India.</li>
                            <li class="mb-2">Patterns tested on real bodies, not just mannequins.</li>
                            <li class="mb-2">Small production runs, six weeks apart.</li>
                            <li class="mb-2">Every piece checked by hand in the studio.</li>
                            <li class="mb-2">Packed plastic-free in recycled paper.</li>
                            <li>Repaired, resold or recycled at end of life.</li>
                        </ol>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="split-art h-100">
                        <img src="assets/img/products/straight-jeans.jpg" alt="Straight leg jeans">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="care" class="row g-4 g-lg-5">
        <div class="col-lg-5">
            <p class="kicker mb-2">Care guide</p>
            <h2 class="mb-3">Make it last longer.</h2>
            <p class="text-muted-warm">Washing less and drying flat adds years to a garment. These are the rules we follow in the studio.</p>
            <a class="link-arrow mt-2" href="contact.php">Ask us about care <?= icon('arrow-right') ?></a>
        </div>
        <div class="col-lg-7">
            <div class="accordion accordion-threadline" id="careGuide">
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#care1">Linen &amp; cotton</button>
                    </h3>
                    <div id="care1" class="accordion-collapse collapse show" data-bs-parent="#careGuide">
                        <div class="accordion-body">Wash cold on a gentle cycle with similar colours. Skip the dryer — line dry in shade and warm-iron while slightly damp. Linen softens every wash.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#care2">Wool &amp; knitwear</button>
                    </h3>
                    <div id="care2" class="accordion-collapse collapse" data-bs-parent="#careGuide">
                        <div class="accordion-body">Hand wash cold with a pH-neutral wool detergent. Press water out in a towel, never wring, and dry flat. Store folded, not hung.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#care3">Denim</button>
                    </h3>
                    <div id="care3" class="accordion-collapse collapse" data-bs-parent="#careGuide">
                        <div class="accordion-body">Wash inside out, cold, and only when needed. Hang to dry. For raw denim, wait at least six weeks before the first wash.</div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h3 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#care4">Leather</button>
                    </h3>
                    <div id="care4" class="accordion-collapse collapse" data-bs-parent="#careGuide">
                        <div class="accordion-body">Keep leather away from prolonged rain and direct heat. Wipe with a dry cloth and condition twice a year with a neutral balm.</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="panel text-center mt-5">
        <p class="kicker mb-2">Ready when you are</p>
        <h2 class="h3">Start with one piece you will wear weekly.</h2>
        <p class="text-muted-warm mb-4">Free delivery over <?= money(FREE_SHIPPING_THRESHOLD) ?> and 30-day returns on everything.</p>
        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a class="btn btn-ink" href="shop.php">Shop the collection</a>
            <a class="btn btn-outline-ink" href="contact.php">Talk to the studio</a>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
