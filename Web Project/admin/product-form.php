<?php
/**
 * Admin — create or edit a product.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

$productId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetch_all(MYSQLI_ASSOC);
$images = array_map('basename', glob(__DIR__ . '/../assets/img/products/*.jpg') ?: []);

$product = [
    'id' => 0,
    'category_id' => $categories[0]['id'] ?? 1,
    'name' => '',
    'description' => '',
    'material' => '',
    'care' => '',
    'price' => '',
    'sale_price' => '',
    'sizes' => 'S,M,L,XL',
    'stock' => 0,
    'image' => $images[0] ?? 'linen-shirt.jpg',
    'featured' => 0,
];

if ($productId > 0) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if (!$found) {
        set_flash('warning', 'That product no longer exists.');
        redirect('products.php');
    }
    $product = $found;
}

$errors = [];

if (is_post()) {
    verify_csrf();

    foreach (['name', 'description', 'material', 'care', 'sizes', 'image'] as $field) {
        $product[$field] = trim((string) ($_POST[$field] ?? ''));
    }
    $product['category_id'] = (int) ($_POST['category_id'] ?? 0);
    $product['price'] = (string) ($_POST['price'] ?? '');
    $product['sale_price'] = trim((string) ($_POST['sale_price'] ?? ''));
    $product['stock'] = (int) ($_POST['stock'] ?? 0);
    $product['featured'] = isset($_POST['featured']) ? 1 : 0;

    if (mb_strlen($product['name']) < 3) {
        $errors['name'] = 'Give the product a name (3+ characters).';
    }
    if (mb_strlen($product['description']) < 20) {
        $errors['description'] = 'Add a description of at least 20 characters.';
    }
    if (!in_array($product['image'], $images, true)) {
        $errors['image'] = 'Choose a product image.';
    }
    if (!is_numeric($product['price']) || (float) $product['price'] <= 0) {
        $errors['price'] = 'Price must be greater than 0.';
    }
    if ($product['sale_price'] !== '' && (!is_numeric($product['sale_price']) || (float) $product['sale_price'] <= 0)) {
        $errors['sale_price'] = 'Sale price must be a number, or leave it empty.';
    } elseif ($product['sale_price'] !== '' && (float) $product['sale_price'] >= (float) $product['price']) {
        $errors['sale_price'] = 'Sale price should be lower than the regular price.';
    }
    if ($product['stock'] < 0) {
        $errors['stock'] = 'Stock cannot be negative.';
    }
    if ($product['sizes'] === '') {
        $errors['sizes'] = 'List at least one size.';
    }

    $categoryIds = array_column($categories, 'id');
    if (!in_array($product['category_id'], array_map('intval', $categoryIds), true)) {
        $errors['category_id'] = 'Choose a category.';
    }

    if (!$errors) {
        $baseSlug = slugify($product['name']) ?: 'product';
        $slug = $baseSlug;
        $suffix = 2;
        while (true) {
            $check = $conn->prepare("SELECT id FROM products WHERE slug = ? AND id <> ?");
            $check->bind_param('si', $slug, $productId);
            $check->execute();
            if (!$check->get_result()->fetch_assoc()) {
                break;
            }
            $slug = $baseSlug . '-' . $suffix++;
        }

        $salePrice = $product['sale_price'] === '' ? null : (float) $product['sale_price'];
        $price = (float) $product['price'];

        if ($productId > 0) {
            $stmt = $conn->prepare(
                "UPDATE products
                    SET category_id = ?, name = ?, slug = ?, description = ?, material = ?, care = ?,
                        price = ?, sale_price = ?, sizes = ?, stock = ?, image = ?, featured = ?
                  WHERE id = ?"
            );
            $stmt->bind_param(
                'isssssddsisii',
                $product['category_id'],
                $product['name'],
                $slug,
                $product['description'],
                $product['material'],
                $product['care'],
                $price,
                $salePrice,
                $product['sizes'],
                $product['stock'],
                $product['image'],
                $product['featured'],
                $productId
            );
            $stmt->execute();
            set_flash('success', '“' . e($product['name']) . '” has been updated.');
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO products
                    (category_id, name, slug, description, material, care, price, sale_price, sizes, stock, image, featured)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                'isssssddsisi',
                $product['category_id'],
                $product['name'],
                $slug,
                $product['description'],
                $product['material'],
                $product['care'],
                $price,
                $salePrice,
                $product['sizes'],
                $product['stock'],
                $product['image'],
                $product['featured']
            );
            $stmt->execute();
            set_flash('success', '“' . e($product['name']) . '” has been added to the collection.');
        }

        redirect('products.php');
    }

    set_flash('danger', 'Please check the highlighted fields and try again.');
}

$pageTitle = $productId > 0 ? 'Admin — Edit product' : 'Admin — New product';
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'products';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">Catalogue</p>
            <h1><?= $productId > 0 ? 'Edit product' : 'New product' ?></h1>
            <p class="text-muted-warm mb-0"><?= $productId > 0 ? e($product['name']) : 'Add a piece to the storefront' ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($productId > 0): ?>
                <a class="btn btn-outline-warm" href="../product.php?id=<?= $productId ?>"><?= icon('eye') ?> View</a>
            <?php endif; ?>
            <a class="btn btn-outline-ink" href="products.php"><?= icon('arrow-right') ?> Back to products</a>
        </div>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <form method="post" action="product-form.php<?= $productId > 0 ? '?id=' . $productId : '' ?>" id="productForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $productId ?>">

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="panel mb-4">
                    <h2 class="panel-title">The piece</h2>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" for="name">Product name</label>
                            <input class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name"
                                   value="<?= e($product['name']) ?>" required>
                            <?php if (isset($errors['name'])): ?><div class="invalid-feedback d-block"><?= e($errors['name']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="description">Description</label>
                            <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" id="description" name="description" rows="5" required><?= e($product['description']) ?></textarea>
                            <?php if (isset($errors['description'])): ?><div class="invalid-feedback d-block"><?= e($errors['description']) ?></div><?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="material">Material</label>
                            <input class="form-control" id="material" name="material" value="<?= e($product['material'] ?? '') ?>" placeholder="100% organic cotton">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="care">Care instructions</label>
                            <input class="form-control" id="care" name="care" value="<?= e($product['care'] ?? '') ?>" placeholder="Machine wash cold. Dry flat.">
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="sizes">Sizes (comma separated)</label>
                            <input class="form-control <?= isset($errors['sizes']) ? 'is-invalid' : '' ?>" id="sizes" name="sizes"
                                   value="<?= e($product['sizes']) ?>" placeholder="XS,S,M,L,XL">
                            <?php if (isset($errors['sizes'])): ?><div class="invalid-feedback d-block"><?= e($errors['sizes']) ?></div><?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="panel mb-4">
                    <h2 class="panel-title">Organise</h2>
                    <div class="mb-3">
                        <label class="form-label" for="category_id">Category</label>
                        <select class="form-select <?= isset($errors['category_id']) ? 'is-invalid' : '' ?>" id="category_id" name="category_id">
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= (int) $category['id'] ?>" <?= (int) $product['category_id'] === (int) $category['id'] ? 'selected' : '' ?>>
                                    <?= e($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['category_id'])): ?><div class="invalid-feedback d-block"><?= e($errors['category_id']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="price">Price (<?= CURRENCY ?>)</label>
                        <input class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>" id="price" name="price" type="number" step="0.01" min="0" value="<?= e((string) $product['price']) ?>" required>
                        <?php if (isset($errors['price'])): ?><div class="invalid-feedback d-block"><?= e($errors['price']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="sale_price">Sale price <span class="fw-normal text-muted-warm">(optional)</span></label>
                        <input class="form-control <?= isset($errors['sale_price']) ? 'is-invalid' : '' ?>" id="sale_price" name="sale_price" type="number" step="0.01" min="0" value="<?= e((string) $product['sale_price']) ?>">
                        <?php if (isset($errors['sale_price'])): ?><div class="invalid-feedback d-block"><?= e($errors['sale_price']) ?></div><?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="stock">Stock</label>
                        <input class="form-control <?= isset($errors['stock']) ? 'is-invalid' : '' ?>" id="stock" name="stock" type="number" min="0" value="<?= (int) $product['stock'] ?>" required>
                        <?php if (isset($errors['stock'])): ?><div class="invalid-feedback d-block"><?= e($errors['stock']) ?></div><?php endif; ?>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="1" id="featured" name="featured" <?= $product['featured'] ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="featured">Feature on the homepage</label>
                    </div>
                </div>

                <div class="panel mb-4">
                    <h2 class="panel-title">Image</h2>
                    <div class="ratio-threadline ratio ratio-4x3 mb-3">
                        <img id="imagePreview" src="../<?= e(product_image($product['image'])) ?>" alt="Selected product image" style="object-fit: cover">
                    </div>
                    <label class="form-label" for="image">Studio image</label>
                    <select class="form-select <?= isset($errors['image']) ? 'is-invalid' : '' ?>" id="image" name="image" data-image-preview>
                        <?php foreach ($images as $image): ?>
                            <option value="<?= e($image) ?>" <?= $product['image'] === $image ? 'selected' : '' ?>><?= e($image) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['image'])): ?><div class="invalid-feedback d-block"><?= e($errors['image']) ?></div><?php endif; ?>
                    <p class="form-text mt-2 mb-0">Images live in <code>assets/img/products/</code>.</p>
                </div>

                <button class="btn btn-ink w-100 btn-lg" type="submit">
                    <?= icon('check') ?> <?= $productId > 0 ? 'Save changes' : 'Create product' ?>
                </button>
            </div>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
