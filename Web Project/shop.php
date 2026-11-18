<?php


declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$categorySlug = trim((string) ($_GET['category'] ?? ''));
$search = trim((string) ($_GET['q'] ?? ''));
$sort = (string) ($_GET['sort'] ?? 'new');
$maxPrice = (int) ($_GET['max'] ?? 250);
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;

$categories = $conn->query(
    "SELECT c.*, COUNT(p.id) AS product_count
       FROM categories c
       LEFT JOIN products p ON p.category_id = c.id
      GROUP BY c.id
      ORDER BY c.name"
)->fetch_all(MYSQLI_ASSOC);

$activeCategory = null;
foreach ($categories as $category) {
    if ($category['slug'] === $categorySlug) {
        $activeCategory = $category;
        break;
    }
}
if ($activeCategory === null) {
    $categorySlug = '';
}

$where = [];
$params = [];
$types = '';

if ($categorySlug !== '') {
    $where[] = 'c.slug = ?';
    $types .= 's';
    $params[] = $categorySlug;
}
if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR c.name LIKE ?)';
    $like = '%' . $search . '%';
    $types .= 'sss';
    array_push($params, $like, $like, $like);
}
if ($maxPrice > 0) {
    $where[] = 'COALESCE(p.sale_price, p.price) <= ?';
    $types .= 'd';
    $params[] = $maxPrice;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sortSql = match ($sort) {
    'price_asc' => 'ORDER BY COALESCE(p.sale_price, p.price) ASC, p.name ASC',
    'price_desc' => 'ORDER BY COALESCE(p.sale_price, p.price) DESC, p.name ASC',
    'name' => 'ORDER BY p.name ASC',
    default => 'ORDER BY p.created_at DESC, p.id DESC',
};

$countStmt = $conn->prepare(
    "SELECT COUNT(*) AS total
       FROM products p
       JOIN categories c ON c.id = p.category_id
       $whereSql"
);
if ($params) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalProducts = (int) $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = max(1, (int) ceil($totalProducts / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $conn->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug
       FROM products p
       JOIN categories c ON c.id = p.category_id
       $whereSql
       $sortSql
       LIMIT ? OFFSET ?"
);
$listParams = $params;
$listTypes = $types . 'ii';
$listParams[] = $perPage;
$listParams[] = $offset;
$listStmt->bind_param($listTypes, ...$listParams);
$listStmt->execute();
$products = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$pageTitle = $activeCategory ? $activeCategory['name'] . ' — Shop' : 'Shop all pieces';
$pageDescription = $activeCategory['description'] ?? 'Browse the full Threadline collection of considered everyday clothing.';

$currentParams = array_filter([
    'category' => $categorySlug,
    'q' => $search,
    'sort' => $sort !== 'new' ? $sort : '',
    'max' => $maxPrice !== 250 ? $maxPrice : '',
], static fn ($value): bool => $value !== '' && $value !== null);

include __DIR__ . '/includes/header.php';
?>

<div class="container">
    <nav aria-label="Breadcrumb" class="pt-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="shop.php">Shop</a></li>
            <?php if ($activeCategory): ?>
                <li class="breadcrumb-item active" aria-current="page"><?= e($activeCategory['name']) ?></li>
            <?php endif; ?>
        </ol>
    </nav>

    <header class="page-header pt-2">
        <p class="kicker"><?= $activeCategory ? 'Collection' : 'The full collection' ?></p>
        <h1><?= e($activeCategory['name'] ?? 'Every piece we make') ?></h1>
        <p><?= e($activeCategory['description'] ?? 'Thoughtful basics in natural fibres — designed to be worn together, and for years.') ?></p>
    </header>

    <div class="row g-4 pb-5">
        <!-- Filters -->
        <aside class="col-lg-3">
            <div class="filter-card">
                <h2 class="filter-title">Category</h2>
                <ul class="filter-list">
                    <li>
                        <a class="<?= $categorySlug === '' ? 'active' : '' ?>"
                           href="shop.php?<?= e(http_build_query(array_merge($currentParams, ['category' => '', 'page' => 1]))) ?>">
                            All pieces <span class="count"><?= array_sum(array_column($categories, 'product_count')) ?></span>
                        </a>
                    </li>
                    <?php foreach ($categories as $category):
                        $query = array_merge($currentParams, ['category' => $category['slug'], 'page' => 1]);
                        ?>
                        <li>
                            <a class="<?= $categorySlug === $category['slug'] ? 'active' : '' ?>"
                               href="shop.php?<?= e(http_build_query($query)) ?>">
                                <?= e($category['name']) ?> <span class="count"><?= (int) $category['product_count'] ?></span>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <form class="filter-card" method="get" action="shop.php">
                <?php if ($categorySlug !== ''): ?>
                    <input type="hidden" name="category" value="<?= e($categorySlug) ?>">
                <?php endif; ?>
                <?php if ($search !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                <?php endif; ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
                <h2 class="filter-title">Max price</h2>
                <label for="maxPrice" class="d-flex justify-content-between small mb-2">
                    <span class="text-muted-warm">Up to</span>
                    <strong id="maxPriceLabel"><?= money($maxPrice) ?></strong>
                </label>
                <input type="range" class="form-range" id="maxPrice" name="max" min="20" max="250" step="10"
                       value="<?= e((string) $maxPrice) ?>">
                <div class="d-flex justify-content-between small text-muted-warm mb-3">
                    <span><?= money(20) ?></span><span><?= money(250) ?></span>
                </div>
                <button class="btn btn-outline-ink w-100" type="submit">Apply filter</button>
                <?php if ($currentParams): ?>
                    <a class="btn btn-link w-100 mt-2 text-muted-warm text-decoration-none small" href="shop.php">Reset all</a>
                <?php endif; ?>
            </form>

            <div class="filter-card">
                <h2 class="filter-title">Need a hand?</h2>
                <p class="small text-muted-warm mb-3">Not sure about sizing? Our studio replies within one working day.</p>
                <a class="btn btn-outline-warm w-100" href="contact.php"><?= icon('message') ?> Ask the studio</a>
            </div>
        </aside>

        <!-- Results -->
        <div class="col-lg-9">
            <div class="shop-toolbar">
                <p class="result-count mb-0">
                    <?= $totalProducts ?> <?= $totalProducts === 1 ? 'piece' : 'pieces' ?>
                    <?php if ($search !== ''): ?>
                        for “<strong><?= e($search) ?></strong>”
                    <?php endif; ?>
                    <?php if ($activeCategory): ?>
                        in <?= e($activeCategory['name']) ?>
                    <?php endif; ?>
                </p>
                <form method="get" action="shop.php" class="d-flex align-items-center gap-2">
                    <?php foreach ($currentParams as $key => $value): ?>
                        <?php if ($key !== 'sort'): ?>
                            <input type="hidden" name="<?= e($key) ?>" value="<?= e((string) $value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <label for="sort" class="small text-nowrap mb-0 text-muted-warm">Sort by</label>
                    <select class="form-select form-select-sm" id="sort" name="sort" data-autosubmit>
                        <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: low to high</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: high to low</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name: A–Z</option>
                    </select>
                </form>
            </div>

            <?php if ($search !== '' || $activeCategory || $maxPrice < 250): ?>
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <?php if ($search !== ''): ?>
                        <a class="chip" href="shop.php?<?= e(http_build_query(array_merge($currentParams, ['q' => '', 'page' => 1]))) ?>">
                            Search: <?= e($search) ?> <?= icon('close') ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($activeCategory): ?>
                        <a class="chip" href="shop.php?<?= e(http_build_query(array_merge($currentParams, ['category' => '', 'page' => 1]))) ?>">
                            <?= e($activeCategory['name']) ?> <?= icon('close') ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($maxPrice < 250): ?>
                        <a class="chip" href="shop.php?<?= e(http_build_query(array_merge($currentParams, ['max' => 250, 'page' => 1]))) ?>">
                            Under <?= money($maxPrice) ?> <?= icon('close') ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!$products): ?>
                <div class="empty-state">
                    <?= icon('search') ?>
                    <h2 class="h4">Nothing matched that search</h2>
                    <p class="text-muted-warm mb-4">Try a different keyword, or browse the full collection.</p>
                    <a class="btn btn-ink" href="shop.php">Browse everything</a>
                </div>
            <?php else: ?>
                <div class="row row-cols-2 row-cols-md-3 g-3 g-lg-4">
                    <?php foreach ($products as $product): ?>
                        <div class="col"><?php product_card($product); ?></div>
                    <?php endforeach; ?>
                </div>

                <div class="d-flex justify-content-center mt-5">
                    <?= paginate_links($page, $totalPages, $currentParams) ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
