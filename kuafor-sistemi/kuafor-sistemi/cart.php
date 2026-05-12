<?php
require_once 'config/database.php';
require_once 'config/product_catalog.php';

if (!auth()) {
    redirect('/products.php?auth_required=1');
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$catalog = get_product_catalog($pdo);
$cartError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));

    if ($action === 'add' && isset($catalog[$productId])) {
        $currentQty = (int) ($_SESSION['cart'][$productId] ?? 0);
        $maxStock = (int) $catalog[$productId]['stock'];
        $targetQty = min($currentQty + $quantity, $maxStock);
        if ($maxStock > 0) {
            $_SESSION['cart'][$productId] = $targetQty;
        }
        redirect('/products.php?added=1');
    }

    if ($action === 'buy_now' && isset($catalog[$productId])) {
        $maxStock = (int) $catalog[$productId]['stock'];
        if ($maxStock > 0) {
            $_SESSION['cart'] = [$productId => min($quantity, $maxStock)];
        }
        redirect('/cart.php');
    }

    if ($action === 'increase' && isset($_SESSION['cart'][$productId])) {
        if (isset($catalog[$productId])) {
            $maxStock = (int) $catalog[$productId]['stock'];
            $_SESSION['cart'][$productId] = min(((int) $_SESSION['cart'][$productId]) + 1, $maxStock);
        }
        redirect('/cart.php');
    }

    if ($action === 'decrease' && isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId]--;
        if ($_SESSION['cart'][$productId] < 1) {
            unset($_SESSION['cart'][$productId]);
        }
        redirect('/cart.php');
    }

    if ($action === 'remove' && isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
        redirect('/cart.php');
    }
}

$cartItems = [];
$total = 0;

foreach ($_SESSION['cart'] as $id => $qty) {
    if (!isset($catalog[$id])) {
        continue;
    }
    $product = $catalog[$id];
    if ((int) $product['stock'] < 1) {
        continue;
    }

    if ($qty > (int) $product['stock']) {
        $qty = (int) $product['stock'];
        $_SESSION['cart'][$id] = $qty;
        $cartError = 'Bazı ürünler için sepet adedi mevcut stok miktarına göre güncellendi.';
    }

    $lineTotal = $product['price'] * $qty;
    $total += $lineTotal;
    $cartItems[] = [
        'id' => $id,
        'name' => $product['name'],
        'description' => $product['description'],
        'category' => $product['category'],
        'price' => $product['price'],
        'quantity' => $qty,
        'line_total' => $lineTotal
    ];
}

require_once 'views/header.php';
?>

<section class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0">Sepetim</h1>
        <a href="<?= APP_URL ?>/products.php" class="btn btn-outline-primary">Ürünlere Dön</a>
    </div>
</section>

<?php if (empty($cartItems)): ?>
    <div class="alert alert-info">
        Sepetiniz boş. Ürün seçmek için ürünler sayfasına gidebilirsiniz.
    </div>
<?php else: ?>
    <?php if ($cartError): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($cartError) ?></div>
    <?php endif; ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <?php foreach ($cartItems as $item): ?>
                <div class="card card-custom mb-3">
                    <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div class="d-flex gap-3 align-items-start">
                            <img src="<?= htmlspecialchars($catalog[$item['id']]['image_url'] ?: 'https://via.placeholder.com/160x120?text=Urun') ?>"
                                 alt="<?= htmlspecialchars($item['name']) ?>"
                                 style="width: 88px; height: 88px; object-fit: cover;"
                                 class="rounded">
                            <div>
                                <span class="badge bg-light text-dark mb-2"><?= htmlspecialchars($item['category']) ?></span>
                                <h5 class="mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                <p class="text-muted small mb-1">Marka: <strong><?= htmlspecialchars($catalog[$item['id']]['brand']) ?></strong></p>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($item['description']) ?></p>
                                <div class="text-primary fw-semibold">Birim Fiyat: ₺<?= number_format($item['price'], 2, ',', '.') ?></div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold mb-2">Ara Toplam: ₺<?= number_format($item['line_total'], 2, ',', '.') ?></div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <form method="POST" action="<?= APP_URL ?>/cart.php">
                                    <input type="hidden" name="action" value="decrease">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit">-</button>
                                </form>
                                <span class="fw-semibold"><?= $item['quantity'] ?> adet</span>
                                <form method="POST" action="<?= APP_URL ?>/cart.php">
                                    <input type="hidden" name="action" value="increase">
                                    <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                    <button class="btn btn-sm btn-outline-secondary" type="submit" <?= ((int)$item['quantity'] >= (int)$catalog[$item['id']]['stock']) ? 'disabled' : '' ?>>+</button>
                                </form>
                            </div>
                            <form method="POST" action="<?= APP_URL ?>/cart.php">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">Sepetten Sil</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="col-lg-4">
            <div class="card card-custom">
                <div class="card-body">
                    <h5 class="mb-3">Sipariş Özeti</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Ürün Çeşidi</span>
                        <strong><?= count($cartItems) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Genel Toplam</span>
                        <strong>₺<?= number_format($total, 2, ',', '.') ?></strong>
                    </div>
                    <a href="<?= APP_URL ?>/checkout.php" class="btn btn-primary w-100 mt-3">Satın Al</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'views/footer.php'; ?>
