<?php
require_once 'config/database.php';
check_role('user');

$userId = (int) $_SESSION['user_id'];

$upcomingStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM appointments
    WHERE user_id = ? AND appointment_date >= CURDATE() AND status != 'cancelled'
");
$upcomingStmt->execute([$userId]);
$upcomingCount = (int) ($upcomingStmt->fetch()['total'] ?? 0);

$ordersStmt = $pdo->prepare("
    SELECT COUNT(*) AS total
    FROM product_orders
    WHERE user_id = ?
");
$ordersStmt->execute([$userId]);
$orderCount = (int) ($ordersStmt->fetch()['total'] ?? 0);

require_once 'views/header.php';
?>

<section class="mb-4">
    <h1 class="mb-2">Profilim</h1>
    <p class="text-muted mb-0">Randevu ve sipariş işlemlerinizi tek noktadan yönetin.</p>
</section>

<div class="row g-4">
    <div class="col-md-6">
        <div class="card card-custom h-100">
            <div class="card-body d-flex flex-column">
                <h4 class="mb-2">Randevularım</h4>
                <p class="text-muted mb-3">Gelecek ve geçmiş randevularınızı görüntüleyin, değerlendirme ekleyin.</p>
                <div class="mb-3">
                    <span class="badge bg-primary">Aktif Randevu: <?= $upcomingCount ?></span>
                </div>
                <a href="<?= APP_URL ?>/user_dashboard.php" class="btn btn-outline-primary mt-auto">Randevu Ekranına Git</a>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card card-custom h-100">
            <div class="card-body d-flex flex-column">
                <h4 class="mb-2">Siparişlerim</h4>
                <p class="text-muted mb-3">Satın aldığınız ürünleri, teslimat detaylarını ve sipariş geçmişinizi görüntüleyin.</p>
                <div class="mb-3">
                    <span class="badge bg-success">Toplam Sipariş: <?= $orderCount ?></span>
                </div>
                <a href="<?= APP_URL ?>/my_orders.php" class="btn btn-outline-primary mt-auto">Sipariş Geçmişine Git</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
