<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$productId = (int) ($_GET['id'] ?? 0);

$stmt = $conn->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
      WHERE p.id = ?"
);
$stmt->bind_param('i', $productId);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    http_response_code(404);
    $pageTitle = 'Piece not found';
    include __DIR__ . '/includes/header.php';
    ?>
    <div class="container py-5">
        <div class="empty-state">
            <?= icon('search') ?>
            <h1 class="h3">We can't find that piece</h1>
            <p class="text-muted-warm mb-4">It may have sold out or moved. Browse the current collection instead.</p>
            <a class="btn btn-ink" href="shop.php">Back to the shop</a>
        </div>
    </div>
    <?php
    include __DIR__ . '/includes/footer.php';
    exit;
}

$sizes = array_values(array_filter(array_map('trim', explode(',', (string) $product['sizes']))));
$onSale = $product['sale_price'] !== null && $product['sale_price'] !== '';
$price = product_price($product);
$stock = (int) $product['stock'];

$relatedStmt = $conn->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
      WHERE p.category_id = ? AND p.id <> ?
      ORDER BY p.featured DESC, p.created_at DESC
      LIMIT 3"
);
$relatedStmt->bind_param('ii', $product['category_id'], $productId);
$relatedStmt->execute();
$related = $relatedStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = $product['name'];
$pageDescription = mb_substr(strip_tags((string) $product['description']), 0, 150);

include __DIR__ . '/includes/header.php';
?>

<div class="container py-4 py-lg-5">
    <nav aria-label="Breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <li class="breadcrumb-item"><a href="shop.php?category=<?= e($product['category_slug']) ?>"><?= e($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4 g-lg-5">
        <div class="col-lg-6">
            <div class="product-gallery">
                <img src="<?= e(product_image($product['image'])) ?>" alt="<?= e($product['name']) ?>">
                <?php if ($onSale): ?>
                    <span class="product-badge sale">Sale</span>
                <?php endif; ?>
                <span class="zoom-hint"><?= icon('eye') ?> Studio image</span>
            </div>

            <div class="row g-3 mt-1">
                <div class="col-4">
                    <div class="ratio-threadline ratio ratio-1x1">
                        <img src="<?= e(product_image($product['image'])) ?>" class="w-100 h-100" style="object-fit:cover" alt="<?= e($product['name']) ?> detail">
                    </div>
                </div>
                <div class="col-8">
                    <div class="panel h-100 d-flex flex-column justify-content-center">
                        <p class="eyebrow mb-1">Made with</p>
                        <p class="mb-1"><strong><?= e($product['material'] ?? 'Natural fibres') ?></strong></p>
                        <p class="small text-muted-warm mb-0"><?= e($product['care'] ?? 'Wash cold, dry flat, wear often.') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <p class="eyebrow mb-2"><?= e($product['category_name']) ?> · <?= e($product['material'] ?? 'Threadline studio') ?></p>
            <h1 class="product-title"><?= e($product['name']) ?></h1>

            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                <div class="product-price-lg">
                    <?php if ($onSale): ?>
                        <span class="was"><?= money($product['price']) ?></span><span class="text-accent"><?= money($product['sale_price']) ?></span>
                    <?php else: ?>
                        <?= money($product['price']) ?>
                    <?php endif; ?>
                </div>
                <div class="stock-line">
                    <?php if ($stock <= 0): ?>
                        <span class="stock-dot out"></span> Sold out — join the waitlist
                    <?php elseif ($stock <= 10): ?>
                        <span class="stock-dot low"></span> Only <?= $stock ?> left in stock
                    <?php else: ?>
                        <span class="stock-dot"></span> In stock, ships within 2 days
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-muted-warm"><?= e($product['description']) ?></p>

            <form action="cart.php" method="post" class="js-add-to-cart mt-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">

                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="form-label mb-0">Size</span>
                    <button class="btn btn-link btn-sm p-0 text-muted-warm text-decoration-none" type="button"
                            data-bs-toggle="modal" data-bs-target="#sizeGuide">
                        <?= icon('info') ?> Size guide
                    </button>
                </div>
                <div class="size-grid mb-4" role="radiogroup" aria-label="Choose a size">
                    <?php foreach ($sizes as $index => $size): ?>
                        <div class="size-option">
                            <input type="radio" id="size-<?= e($size) ?>" name="size" value="<?= e($size) ?>"
                                   <?= $index === 0 ? 'checked' : '' ?> required>
                            <label for="size-<?= e($size) ?>"><?= e($size) ?></label>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex flex-wrap align-items-end gap-3">
                    <div>
                        <span class="form-label d-block">Quantity</span>
                        <div class="qty-stepper">
                            <button type="button" data-step="-1" aria-label="Decrease quantity"><?= icon('minus') ?></button>
                            <input type="number" name="qty" value="1" min="1" max="<?= max(1, min(10, $stock)) ?>" aria-label="Quantity">
                            <button type="button" data-step="1" aria-label="Increase quantity"><?= icon('plus') ?></button>
                        </div>
                    </div>
                    <button class="btn btn-ink btn-lg flex-grow-1" type="submit" <?= $stock <= 0 ? 'disabled' : '' ?>>
                        <?= icon('bag') ?> <?= $stock > 0 ? 'Add to bag — ' . money($price) : 'Sold out' ?>
                    </button>
                </div>

                <div class="meta-row mt-4">
                    <span><?= icon('truck') ?> Free delivery over <?= money(FREE_SHIPPING_THRESHOLD) ?></span>
                    <span><?= icon('refresh') ?> 30-day free returns</span>
                    <span><?= icon('shield') ?> Secure checkout</span>
                </div>
            </form>

            <div class="accordion accordion-threadline mt-4" id="productDetails">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#detailFit">
                            Fit &amp; sizing
                        </button>
                    </h2>
                    <div id="detailFit" class="accordion-collapse collapse show" data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            Relaxed through the body with a clean finish. If you are between sizes, take the smaller size for a closer fit.
                            Model is 178 cm and wears a size M.
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#detailCare">
                            Materials &amp; care
                        </button>
                    </h2>
                    <div id="detailCare" class="accordion-collapse collapse" data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            <p class="mb-1"><strong><?= e($product['material'] ?? 'Natural fibres') ?></strong></p>
                            <p class="mb-0"><?= e($product['care'] ?? 'Wash cold, dry flat, wear often.') ?> Made in a small, audited workshop in Porto, Portugal.</p>
                        </div>
                    </div>
                </div>
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#detailDelivery">
                            Delivery &amp; returns
                        </button>
                    </h2>
                    <div id="detailDelivery" class="accordion-collapse collapse" data-bs-parent="#productDetails">
                        <div class="accordion-body">
                            Orders leave our studio within 2 working days. Delivery is <?= money(SHIPPING_FLAT_RATE) ?>, free over
                            <?= money(FREE_SHIPPING_THRESHOLD) ?>. Returns are free for 30 days — just keep the tags on.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if ($related): ?>
        <section class="section pb-0">
            <div class="section-heading">
                <div>
                    <p class="kicker mb-2">Wear it with</p>
                    <h2>You may also like</h2>
                </div>
                <a class="link-arrow" href="shop.php?category=<?= e($product['category_slug']) ?>">All <?= e($product['category_name']) ?> <?= icon('arrow-right') ?></a>
            </div>
            <div class="row row-cols-2 row-cols-lg-3 g-3 g-lg-4">
                <?php foreach ($related as $item): ?>
                    <div class="col"><?php product_card($item); ?></div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<!-- Size guide modal -->
<div class="modal fade" id="sizeGuide" tabindex="-1" aria-labelledby="sizeGuideTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="sizeGuideTitle">Size guide</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted-warm small">Measurements are body measurements in centimetres, not garment measurements.</p>
                <div class="table-responsive">
                    <table class="table table-sm table-threadline">
                        <thead>
                            <tr><th>Size</th><th>Bust</th><th>Waist</th><th>Hip</th><th>UK</th><th>US</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>XS</td><td>80–84</td><td>62–66</td><td>88–92</td><td>6</td><td>2</td></tr>
                            <tr><td>S</td><td>85–89</td><td>67–71</td><td>93–97</td><td>8</td><td>4</td></tr>
                            <tr><td>M</td><td>90–94</td><td>72–76</td><td>98–102</td><td>10</td><td>6</td></tr>
                            <tr><td>L</td><td>95–101</td><td>77–83</td><td>103–109</td><td>12</td><td>8</td></tr>
                            <tr><td>XL</td><td>102–108</td><td>84–90</td><td>110–116</td><td>14</td><td>10</td></tr>
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted-warm mb-0">Still unsure? <a href="contact.php">Message the studio</a> and we will help you choose.</p>
            </div>
            <div class="modal-footer">
                <button class="btn btn-ink" type="button" data-bs-dismiss="modal">Got it</button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
