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
$cartItems = [];
$total = 0;
$success = '';
$error = '';

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $cardName = trim($_POST['card_name'] ?? '');
    $cardNumber = trim($_POST['card_number'] ?? '');
    $cardExpiry = trim($_POST['card_expiry'] ?? '');
    $cardCVV = trim($_POST['card_cvv'] ?? '');

    if (empty($cartItems)) {
        $error = 'Sepetiniz boş. Satın alma işlemi başlatılamadı.';
    } elseif ($fullName === '' || $phone === '' || $city === '' || $district === '' || $address === '' || $cardName === '' || $cardNumber === '' || $cardExpiry === '' || $cardCVV === '') {
        $error = 'Lütfen tüm teslimat ve ödeme alanlarını doldurun.';
    } else {
        try {
            $pdo->beginTransaction();

            $orderStmt = $pdo->prepare("
                INSERT INTO product_orders (user_id, full_name, phone, city, district, address, total_amount)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $orderStmt->execute([$_SESSION['user_id'], $fullName, $phone, $city, $district, $address, $total]);
            $orderId = (int) $pdo->lastInsertId();

            $itemStmt = $pdo->prepare("
                INSERT INTO product_order_items
                (order_id, product_id, product_name, brand, category, description, ingredients, usage_instructions, image_url, unit_price, quantity, line_total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stockStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

            foreach ($cartItems as $item) {
                $itemStmt->execute([
                    $orderId,
                    $item['id'],
                    $item['name'],
                    $catalog[$item['id']]['brand'],
                    $item['category'],
                    $item['description'],
                    $catalog[$item['id']]['ingredients'],
                    $catalog[$item['id']]['usage_instructions'],
                    $catalog[$item['id']]['image_url'],
                    $item['price'],
                    $item['quantity'],
                    $item['line_total']
                ]);

                $stockStmt->execute([$item['quantity'], $item['id'], $item['quantity']]);
                if ($stockStmt->rowCount() === 0) {
                    throw new Exception('Stok yetersiz olduğu için sipariş tamamlanamadı.');
                }
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            $success = 'Siparişiniz başarıyla oluşturuldu.';
            $cartItems = [];
            $total = 0;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

require_once 'views/header.php';
?>

<section class="mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h1 class="mb-0">Satın Al</h1>
        <a href="<?= APP_URL ?>/cart.php" class="btn btn-outline-primary">Sepete Dön</a>
    </div>
</section>

<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (empty($cartItems) && !$success): ?>
    <div class="alert alert-info">Sepetinizde ürün yok. Önce ürün ekleyiniz.</div>
<?php elseif (!empty($cartItems)): ?>
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card card-custom">
                <div class="card-body">
                    <h5 class="mb-3">Teslimat Bilgileri</h5>
                    <form method="POST" action="<?= APP_URL ?>/checkout.php">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" class="form-control" name="full_name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" class="form-control" name="phone" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Şehir</label>
                                <input type="text" class="form-control" name="city" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">İlçe</label>
                                <input type="text" class="form-control" name="district" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Açık Adres</label>
                            <textarea class="form-control" name="address" rows="3" required></textarea>
                        </div>
                        <hr class="my-4">
                        <h5 class="mb-3">Ödeme Bilgileri</h5>
                        <div class="mb-3">
                            <label class="form-label">Kart Üzerindeki İsim</label>
                            <input type="text" class="form-control" name="card_name" placeholder="Ad Soyad" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kart Numarası</label>
                            <input type="text" class="form-control" name="card_number" placeholder="0000 0000 0000 0000" maxlength="19" required>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Son Kullanma Tarihi</label>
                                <input type="text" class="form-control" name="card_expiry" placeholder="AA/YY" maxlength="5" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">CVV</label>
                                <input type="password" class="form-control" name="card_cvv" placeholder="***" maxlength="3" required>
                            </div>
                        </div>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="fas fa-lock me-2"></i>Ödemeniz 256-bit SSL ile güvenli bir şekilde şifrelenmektedir.
                        </div>
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold fs-5 shadow-sm">Siparişi Onayla ve Öde</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card card-custom">
                <div class="card-body">
                    <h5 class="mb-3">Ürün ve Fiyat Özeti</h5>
                    <?php foreach ($cartItems as $item): ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="d-flex gap-2">
                                    <img src="<?= htmlspecialchars($catalog[$item['id']]['image_url'] ?: 'https://via.placeholder.com/160x120?text=Urun') ?>"
                                         alt="<?= htmlspecialchars($item['name']) ?>"
                                         style="width: 70px; height: 70px; object-fit: cover;"
                                         class="rounded">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($item['name']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($item['category']) ?></div>
                                        <div class="small text-muted">Marka: <?= htmlspecialchars($catalog[$item['id']]['brand']) ?></div>
                                    </div>
                                </div>
                                <strong><?= (int)$item['quantity'] ?> adet</strong>
                            </div>
                            <div class="small text-muted mt-2"><?= htmlspecialchars($item['description']) ?></div>
                            <div class="d-flex justify-content-between mt-2">
                                <span>Birim: ₺<?= number_format($item['price'], 2, ',', '.') ?></span>
                                <strong>Toplam: ₺<?= number_format($item['line_total'], 2, ',', '.') ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex justify-content-between mt-2">
                        <span class="fw-semibold">Genel Toplam</span>
                        <span class="fw-bold text-primary">₺<?= number_format($total, 2, ',', '.') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'views/footer.php'; ?>
