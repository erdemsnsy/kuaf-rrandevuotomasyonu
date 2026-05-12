<?php
require_once 'config/database.php';
require_once 'config/product_catalog.php';

$productId = (int) ($_GET['id'] ?? 0);
$product = get_product_by_id($pdo, $productId);

if (!$product) {
    redirect('/products.php');
}

$isLoggedIn = (bool) auth();
require_once 'views/header.php';
?>

<section class="mb-4">
    <a href="<?= APP_URL ?>/products.php" class="btn btn-sm btn-outline-primary mb-3">Ürünlere Dön</a>
    <div class="card card-custom">
        <div class="card-body">
            <div class="row g-4 align-items-start">
                <div class="col-md-5">
                    <img src="<?= htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/600x400?text=Urun') ?>"
                         alt="<?= htmlspecialchars($product['name']) ?>"
                         class="img-fluid rounded-4 shadow-sm">
                </div>
                <div class="col-md-7">
                    <span class="badge bg-light text-dark mb-2"><?= htmlspecialchars($product['category']) ?></span>
                    <h2 class="mb-2"><?= htmlspecialchars($product['name']) ?></h2>
                    <p class="text-muted mb-2">Marka: <strong><?= htmlspecialchars($product['brand']) ?></strong></p>
                    <p class="mb-3"><?= htmlspecialchars($product['description']) ?></p>
                    <h4 class="text-primary mb-3">₺<?= number_format($product['price'], 2, ',', '.') ?></h4>
                    <p class="small mb-2 <?= ((int)$product['stock'] > 0) ? 'text-success' : 'text-danger' ?>">
                        Stok Durumu: <?= (int)$product['stock'] ?> adet
                    </p>
                    <div class="mb-3">
                        <h6>İçerik Bilgisi</h6>
                        <p class="text-muted mb-0"><?= htmlspecialchars($product['ingredients'] ?: 'İçerik bilgisi girilmemiş.') ?></p>
                    </div>
                    <div class="mb-4">
                        <h6>Kullanım Şekli</h6>
                        <p class="text-muted mb-0"><?= htmlspecialchars($product['usage_instructions'] ?: 'Kullanım bilgisi girilmemiş.') ?></p>
                    </div>

                    <form method="POST" action="<?= APP_URL ?>/cart.php" class="d-flex gap-2 align-items-center flex-wrap">
                        <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
                        <input type="number" name="quantity" class="form-control" value="1" min="1" max="<?= max(1, (int)$product['stock']) ?>" style="max-width: 110px;">
                        <?php if ($isLoggedIn): ?>
                            <button class="btn btn-outline-primary" type="submit" name="action" value="add" <?= ((int)$product['stock'] < 1) ? 'disabled' : '' ?>>Sepete Ekle</button>
                            <button class="btn btn-primary" type="submit" name="action" value="buy_now" <?= ((int)$product['stock'] < 1) ? 'disabled' : '' ?>>Satın Al</button>
                        <?php else: ?>
                            <button class="btn btn-outline-primary" type="button" onclick="alert('Lütfen giriş yapın veya kayıt olun.')">Sepete Ekle</button>
                            <button class="btn btn-primary" type="button" onclick="alert('Lütfen giriş yapın veya kayıt olun.')">Satın Al</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'views/footer.php'; ?>
