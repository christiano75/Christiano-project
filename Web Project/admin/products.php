<?php
/**
 * Admin — product list with search, feature toggle and delete.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

require_admin();

if (is_post()) {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $productId = (int) ($_POST['product_id'] ?? 0);

    if ($productId > 0 && $action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        set_flash('info', 'Product deleted. Past orders keep their record of it.');
    } elseif ($productId > 0 && $action === 'feature') {
        $stmt = $conn->prepare("UPDATE products SET featured = 1 - featured WHERE id = ?");
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        set_flash('success', 'Homepage feature updated.');
    }

    redirect('products.php');
}

$search = trim((string) ($_GET['q'] ?? ''));

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        "SELECT p.*, c.name AS category_name
           FROM products p
           JOIN categories c ON c.id = p.category_id
          WHERE p.name LIKE ? OR c.name LIKE ?
          ORDER BY p.created_at DESC"
    );
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $products = $conn->query(
        "SELECT p.*, c.name AS category_name
           FROM products p
           JOIN categories c ON c.id = p.category_id
          ORDER BY p.created_at DESC"
    )->fetch_all(MYSQLI_ASSOC);
}

$pageTitle = 'Admin — Products';
$base = '../';
include __DIR__ . '/../includes/header.php';
$adminPage = 'products';
?>

<div class="container admin-shell">
    <div class="admin-head">
        <div>
            <p class="kicker mb-2">Catalogue</p>
            <h1>Products</h1>
            <p class="text-muted-warm mb-0"><?= count($products) ?> <?= count($products) === 1 ? 'piece' : 'pieces' ?> in the collection</p>
        </div>
        <a class="btn btn-ink" href="product-form.php"><?= icon('plus') ?> New product</a>
    </div>

    <?php include __DIR__ . '/nav.php'; ?>

    <div class="panel">
        <div class="shop-toolbar">
            <form class="d-flex gap-2" method="get" action="products.php">
                <label class="visually-hidden" for="q">Search products</label>
                <input class="form-control" id="q" name="q" type="search" value="<?= e($search) ?>" placeholder="Search by name or category">
                <button class="btn btn-outline-ink" type="submit">Search</button>
                <?php if ($search !== ''): ?>
                    <a class="btn btn-outline-warm" href="products.php">Clear</a>
                <?php endif; ?>
            </form>
            <span class="small text-muted-warm">Click “Feature” to pin a piece to the homepage.</span>
        </div>

        <div class="table-responsive">
            <table class="table table-threadline">
                <thead>
                    <tr>
                        <th scope="col">Product</th>
                        <th scope="col">Category</th>
                        <th scope="col">Price</th>
                        <th scope="col">Stock</th>
                        <th scope="col">Featured</th>
                        <th scope="col" class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img class="table-thumb" src="../<?= e(product_image($product['image'])) ?>" alt="">
                                    <div>
                                        <p class="mb-0 fw-semibold"><?= e($product['name']) ?></p>
                                        <p class="mb-0 small text-muted-warm"><?= e($product['sizes']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td><?= e($product['category_name']) ?></td>
                            <td>
                                <?= money(product_price($product)) ?>
                                <?php if ($product['sale_price'] !== null): ?>
                                    <br><span class="small text-muted-warm text-decoration-line-through"><?= money($product['price']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $stock = (int) $product['stock']; ?>
                                <span class="badge rounded-pill <?= $stock === 0 ? 'text-bg-danger' : ($stock <= 10 ? 'text-bg-warning' : 'text-bg-success') ?>">
                                    <?= $stock ?>
                                </span>
                            </td>
                            <td><?= $product['featured'] ? '<span class="badge text-bg-dark">Featured</span>' : '<span class="small text-muted-warm">—</span>' ?></td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <form method="post" action="products.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="feature">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <button class="btn btn-sm btn-outline-warm" type="submit" title="Toggle homepage feature">
                                            <?= icon('star') ?>
                                        </button>
                                    </form>
                                    <a class="btn btn-sm btn-outline-warm" href="../product.php?id=<?= (int) $product['id'] ?>" title="View on storefront"><?= icon('eye') ?></a>
                                    <a class="btn btn-sm btn-outline-ink" href="product-form.php?id=<?= (int) $product['id'] ?>"><?= icon('edit') ?> Edit</a>
                                    <form method="post" action="products.php" class="d-inline"
                                          onsubmit="return confirm('Delete “<?= e($product['name']) ?>”? This cannot be undone.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit" title="Delete product"><?= icon('trash') ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$products): ?>
                        <tr><td colspan="6" class="text-center py-4 text-muted-warm">No products matched that search.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
