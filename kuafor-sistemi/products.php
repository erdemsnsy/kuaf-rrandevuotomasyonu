<?php
require_once 'config/database.php';
require_once 'config/product_catalog.php';
require_once 'views/header.php';

$selectedCategory = trim($_GET['category'] ?? '');
$search = trim($_GET['q'] ?? '');
$sort = trim($_GET['sort'] ?? 'newest');

$products = get_product_catalog($pdo, [
    'category' => $selectedCategory,
    'search' => $search,
    'sort' => $sort
]);
$categories = get_product_categories($pdo);
$cartCount = array_sum($_SESSION['cart'] ?? []);
$addedToCart = isset($_GET['added']);
$authRequired = isset($_GET['auth_required']);
$isLoggedIn = (bool) auth();
?>

<section class="hero-section text-center animate-fade-in-down">
    <div class="container">
        <h1 class="display-5 fw-bold animate-fade-in-down">Kuaför Ürün Mağazası</h1>
        <p class="lead mb-0 animate-fade-in-up">Salonunuz için profesyonel ürünleri seçin, sepetinize ekleyin ve hızlıca satın alın.</p>
    </div>
</section>

<section class="mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0">Popüler Ürünler</h2>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= APP_URL ?>/cart.php" class="btn btn-sm btn-outline-primary">Sepete Git (<?= (int)$cartCount ?>)</a>
        </div>
    </div>

    <?php if ($addedToCart): ?>
        <div class="alert alert-success py-2">Ürün sepete eklendi. Dilerseniz alışverişe devam edebilirsiniz.</div>
    <?php endif; ?>

    <form method="GET" action="<?= APP_URL ?>/products.php" class="card card-custom p-3 mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-lg-5">
                <label class="form-label small mb-1">Hızlı Arama</label>
                <input type="text" name="q" class="form-control" placeholder="Ürün adı, marka veya açıklama..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1">Kategori</label>
                <select name="category" class="form-select">
                    <option value="">Tüm Kategoriler</option>
                    <?php foreach ($categories as $categoryRow): ?>
                        <?php $category = $categoryRow['category']; ?>
                        <option value="<?= htmlspecialchars($category) ?>" <?= $selectedCategory === $category ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <label class="form-label small mb-1">Fiyata Göre Sırala</label>
                <select name="sort" class="form-select">
                    <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>En Yeni</option>
                    <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Fiyat (Artan)</option>
                    <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Fiyat (Azalan)</option>
                    <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>İsim (A-Z)</option>
                    <option value="name_desc" <?= $sort === 'name_desc' ? 'selected' : '' ?>>İsim (Z-A)</option>
                </select>
            </div>
            <div class="col-lg-1 d-grid">
                <button class="btn btn-primary" type="submit">Uygula</button>
            </div>
        </div>
    </form>

    <div class="row g-4">
        <?php foreach ($products as $product): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-custom product-card h-100">
                    <img src="<?= htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/600x380?text=Urun') ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         class="product-image">
                    <div class="card-body d-flex flex-column">
                        <span class="product-category mb-3"><?= htmlspecialchars($product['category']) ?></span>
                        <h5 class="card-title mb-2"><?= htmlspecialchars($product['name']) ?></h5>
                        <p class="text-muted small mb-1">Marka: <strong><?= htmlspecialchars($product['brand']) ?></strong></p>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($product['description']) ?></p>
                        <p class="small mb-3 <?= ((int)$product['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                            Stok: <?= (int)$product['stock'] ?> adet
                        </p>
                        <h4 class="text-primary mb-3">₺<?= number_format($product['price'], 2, ',', '.') ?></h4>

                        <form method="POST" action="<?= APP_URL ?>/cart.php" class="mt-auto">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <div class="quantity-box mb-3" data-product-id="<?= $product['id'] ?>">
                                <button class="btn btn-light quantity-btn" type="button" onclick="changeQuantity(<?= $product['id'] ?>, -1)">-</button>
                                <input
                                    type="number"
                                    class="form-control quantity-input text-center"
                                    id="qty-<?= $product['id'] ?>"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                >
                                <button class="btn btn-light quantity-btn" type="button" onclick="changeQuantity(<?= $product['id'] ?>, 1)">+</button>
                            </div>

                            <?php if ($isLoggedIn): ?>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" type="submit" name="action" value="add" <?= ((int)$product['stock'] < 1) ? 'disabled' : '' ?>>
                                        Sepete Ekle
                                    </button>
                                    <button class="btn btn-primary" type="submit" name="action" value="buy_now" <?= ((int)$product['stock'] < 1) ? 'disabled' : '' ?>>
                                        Satın Al
                                    </button>
                                    <a class="btn btn-sm btn-link text-decoration-none" href="<?= APP_URL ?>/product_detail.php?id=<?= (int)$product['id'] ?>">Detayı Gör</a>
                                </div>
                            <?php else: ?>
                                <div class="d-grid gap-2">
                                    <button class="btn btn-outline-primary" type="button" onclick="showAuthError()">
                                        Sepete Ekle
                                    </button>
                                    <button class="btn btn-primary" type="button" onclick="showAuthError()">
                                        Satın Al
                                    </button>
                                    <a class="btn btn-sm btn-link text-decoration-none" href="<?= APP_URL ?>/product_detail.php?id=<?= (int)$product['id'] ?>">Detayı Gör</a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (count($products) === 0): ?>
            <div class="col-12">
                <div class="alert alert-secondary">Arama ve filtreye uygun ürün bulunamadı.</div>
            </div>
        <?php endif; ?>
    </div>
</section>

<div id="authToast" class="auth-toast" role="status" aria-live="polite" aria-atomic="true">
    <div class="auth-toast-title">İşlem için giriş gerekli</div>
    <div class="auth-toast-message">Lütfen Giriş Yap ya da Kayıt Ol.</div>
</div>

<style>
.product-card .card-body {
    padding: 24px;
}

.product-image {
    width: 100%;
    height: 210px;
    object-fit: cover;
}

.product-category {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4338ca;
    background: rgba(79, 70, 229, 0.1);
    width: fit-content;
}

.quantity-box {
    display: grid;
    grid-template-columns: 44px 1fr 44px;
    gap: 8px;
    align-items: center;
}

.quantity-btn {
    border-radius: 12px !important;
    font-weight: 700;
    border: 1px solid #d1d5db;
    padding: 8px;
}

.quantity-input {
    border-radius: 12px;
    padding: 10px;
    font-weight: 600;
}

.quantity-input::-webkit-outer-spin-button,
.quantity-input::-webkit-inner-spin-button {
    -webkit-appearance: none;
    margin: 0;
}

.quantity-input[type=number] {
    -moz-appearance: textfield;
}

.auth-toast {
    position: fixed;
    top: 100px;
    right: 24px;
    width: min(320px, calc(100vw - 32px));
    background: #ffffff;
    border: 1px solid rgba(79, 70, 229, 0.2);
    border-left: 4px solid #4f46e5;
    border-radius: 14px;
    box-shadow: 0 12px 30px rgba(15, 23, 42, 0.15);
    padding: 12px 14px;
    z-index: 2000;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
    transition: opacity 0.25s ease, transform 0.25s ease;
}

.auth-toast.show {
    opacity: 1;
    transform: translateY(0);
}

.auth-toast-title {
    font-weight: 700;
    color: #312e81;
    margin-bottom: 2px;
    font-size: 0.95rem;
}

.auth-toast-message {
    color: #4b5563;
    font-size: 0.88rem;
}
</style>

<script>
var authRequiredFromRedirect = <?= $authRequired ? 'true' : 'false' ?>;
var authToastTimeout;

function getQuantity(productId) {
    var input = document.getElementById('qty-' + productId);
    var qty = parseInt(input.value, 10);
    if (isNaN(qty) || qty < 1) {
        qty = 1;
        input.value = 1;
    }
    return qty;
}

function changeQuantity(productId, delta) {
    var input = document.getElementById('qty-' + productId);
    var current = getQuantity(productId);
    var next = current + delta;
    input.value = next < 1 ? 1 : next;
}

function showAuthError() {
    var toast = document.getElementById('authToast');
    if (!toast) return;
    toast.classList.add('show');
    clearTimeout(authToastTimeout);
    authToastTimeout = setTimeout(function () {
        toast.classList.remove('show');
    }, 2600);
}

if (authRequiredFromRedirect) {
    window.addEventListener('load', function () {
        showAuthError();
    });
}

</script>

<?php require_once 'views/footer.php'; ?>
