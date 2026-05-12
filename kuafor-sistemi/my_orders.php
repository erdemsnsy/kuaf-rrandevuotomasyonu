<?php
require_once 'config/database.php';
require_once 'config/product_catalog.php';
check_role('user');
ensure_product_store_schema($pdo);

$userId = (int) $_SESSION['user_id'];

$ordersStmt = $pdo->prepare("
    SELECT * FROM product_orders
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();

$itemsStmt = $pdo->prepare("
    SELECT * FROM product_order_items
    WHERE order_id = ?
    ORDER BY id ASC
");

require_once 'views/header.php';
?>

<section class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
    <h1 class="mb-0">Siparişlerim</h1>
    <a href="<?= APP_URL ?>/products.php" class="btn btn-outline-primary">Ürünlere Git</a>
</section>

<?php if (count($orders) === 0): ?>
    <div class="alert alert-info">Henüz ürün siparişiniz bulunmuyor.</div>
<?php else: ?>
    <?php foreach ($orders as $order): ?>
        <?php
            $itemsStmt->execute([$order['id']]);
            $orderItems = $itemsStmt->fetchAll();
        ?>
        <div class="card card-custom mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <div>
                        <h5 class="mb-0">Sipariş #<?= (int)$order['id'] ?></h5>
                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></small>
                    </div>
                    <?php $s = get_status_label($order['status']); ?>
                    <span class="badge bg-<?= $s['color'] ?>"><?= $s['label'] ?></span>
                </div>

                <div class="mb-3">
                    <strong>Teslimat:</strong>
                    <?= htmlspecialchars($order['full_name']) ?>,
                    <?= htmlspecialchars($order['phone']) ?>,
                    <?= htmlspecialchars($order['district']) ?> / <?= htmlspecialchars($order['city']) ?>,
                    <?= htmlspecialchars($order['address']) ?>
                </div>

                <div class="row g-3">
                    <?php foreach ($orderItems as $item): ?>
                        <div class="col-lg-6">
                            <div class="border rounded p-3 h-100">
                                <div class="d-flex gap-3">
                                    <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/160x120?text=Urun') ?>"
                                         alt="<?= htmlspecialchars($item['product_name']) ?>"
                                         style="width: 90px; height: 90px; object-fit: cover;"
                                         class="rounded">
                                    <div>
                                        <div class="fw-semibold"><?= htmlspecialchars($item['product_name']) ?></div>
                                        <div class="small text-muted">Marka: <?= htmlspecialchars($item['brand']) ?></div>
                                        <div class="small text-muted"><?= htmlspecialchars($item['category']) ?></div>
                                        <div class="small mt-1"><?= htmlspecialchars($item['description']) ?></div>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2 small">
                                    <span>Adet: <?= (int)$item['quantity'] ?></span>
                                    <span>Birim: ₺<?= number_format($item['unit_price'], 2, ',', '.') ?></span>
                                    <strong>Toplam: ₺<?= number_format($item['line_total'], 2, ',', '.') ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="text-end mt-3">
                    <strong>Genel Tutar: ₺<?= number_format($order['total_amount'], 2, ',', '.') ?></strong>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once 'views/footer.php'; ?>
