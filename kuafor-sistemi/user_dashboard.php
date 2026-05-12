<?php
require_once 'config/database.php';
check_role('user');

$user_id = $_SESSION['user_id'];

// Handle Appointment Cancellation
if (isset($_GET['cancel_app'])) {
    $app_id = $_GET['cancel_app'];
    $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND user_id = ?")->execute([$app_id, $user_id]);
    redirect('/user_dashboard.php');
}

// Handle Reviews
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    $r_app_id = $_POST['appointment_id'];
    $rating = $_POST['rating'];
    $review = $_POST['review'] ?? '';
    $pdo->prepare("UPDATE appointments SET rating = ?, review = ? WHERE id = ? AND user_id = ?")->execute([$rating, $review, $r_app_id, $user_id]);
    redirect('/user_dashboard.php');
}

// Get Upcoming Appointments
$stmt_up = $pdo->prepare("
    SELECT a.*, b.shop_name, s.service_name, p.amount 
    FROM appointments a 
    JOIN barbers b ON a.barber_id = b.id 
    JOIN services s ON a.service_id = s.id 
    LEFT JOIN payments p ON a.id = p.appointment_id
    WHERE a.user_id = ? AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$stmt_up->execute([$user_id]);
$upcoming = $stmt_up->fetchAll();

// Get Past Appointments
$stmt_past = $pdo->prepare("
    SELECT a.*, b.shop_name, s.service_name, p.amount 
    FROM appointments a 
    JOIN barbers b ON a.barber_id = b.id 
    JOIN services s ON a.service_id = s.id 
    LEFT JOIN payments p ON a.id = p.appointment_id
    WHERE a.user_id = ? AND a.appointment_date < CURDATE()
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$stmt_past->execute([$user_id]);
$past = $stmt_past->fetchAll();

// Get Orders
require_once 'config/product_catalog.php';
ensure_product_store_schema($pdo);
$stmt_orders = $pdo->prepare("
    SELECT * FROM product_orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll();

$stmt_items = $pdo->prepare("
    SELECT * FROM product_order_items 
    WHERE order_id = ?
");

require_once 'views/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="card card-custom mb-4">
            <div class="card-body text-center">
                <i class="fas fa-user-circle fa-4x text-primary mb-3"></i>
                <h5 class="card-title"><?= htmlspecialchars($_SESSION['name']) ?></h5>
                <p class="text-muted small">Müşteri Profili</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="upcoming-tab" data-bs-toggle="tab" data-bs-target="#upcoming" type="button">Gelecek Randevular</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="past-tab" data-bs-toggle="tab" data-bs-target="#past" type="button">Geçmiş Randevular</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="orders-tab" data-bs-toggle="tab" data-bs-target="#orders" type="button">Siparişlerim</button>
            </li>
        </ul>
        
        <div class="tab-content" id="myTabContent">
            <!-- Upcoming -->
            <div class="tab-pane fade show active" id="upcoming" role="tabpanel">
                <?php if (count($upcoming) > 0): ?>
                    <div class="row">
                        <?php foreach($upcoming as $app): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card card-custom border-primary">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($app['shop_name']) ?></h5>
                                    <p class="mb-1 text-muted"><i class="fas fa-cut me-2"></i><?= htmlspecialchars($app['service_name']) ?></p>
                                    <p class="mb-1"><strong>Tarih:</strong> <?= date('d.m.Y', strtotime($app['appointment_date'])) ?> <?= substr($app['appointment_time'],0,5) ?></p>
                                    <p class="mb-1"><strong>Tutar:</strong> ₺<?= number_format($app['amount'] ?? 0, 2) ?></p>
                                    <p class="mb-0"><strong>Durum:</strong> 
                                        <?php
                                            $badgeClass = 'bg-secondary';
                                            if($app['status'] == 'pending') $badgeClass = 'bg-warning text-dark';
                                            if($app['status'] == 'confirmed') $badgeClass = 'bg-success';
                                            if($app['status'] == 'cancelled') $badgeClass = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($app['status'] == 'cancelled' ? 'İptal Edildi' : $app['status']) ?></span>
                                    </p>
                                    <?php if($app['status'] != 'cancelled'): ?>
                                    <div class="mt-3 text-end">
                                        <a href="?cancel_app=<?= $app['id'] ?>" class="btn btn-sm btn-outline-danger shadow-sm rounded-pill px-3" onclick="return confirm('Randevunuzu iptal etmek istediğinize emin misiniz?')">İptal Et</a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info border-0 rounded">Yaklaşan bir randevunuz bulunmamaktadır.</div>
                <?php endif; ?>
            </div>
            
            <!-- Past -->
            <div class="tab-pane fade" id="past" role="tabpanel">
                <?php if (count($past) > 0): ?>
                    <div class="table-responsive bg-white rounded shadow-sm">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Tarih</th>
                                    <th>Kuaför</th>
                                    <th>Hizmet</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                    <th class="text-end">Değerlendirme</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($past as $app): ?>
                                <tr>
                                    <td><?= date('d.m.Y', strtotime($app['appointment_date'])) ?> <?= substr($app['appointment_time'],0,5) ?></td>
                                    <td><?= htmlspecialchars($app['shop_name']) ?></td>
                                    <td><?= htmlspecialchars($app['service_name']) ?></td>
                                    <td>₺<?= number_format($app['amount'] ?? 0, 2) ?></td>
                                    <td><?= ucfirst($app['status']) ?></td>
                                    <td class="text-end">
                                        <?php if($app['status'] == 'confirmed' && empty($app['rating'])): ?>
                                            <button class="btn btn-sm btn-outline-warning shadow-sm rounded-pill px-3" onclick="openReviewModal(<?= $app['id'] ?>, '<?= htmlspecialchars(addslashes($app['shop_name'])) ?>')"><i class="fas fa-star me-1"></i>Değerlendir</button>
                                        <?php elseif(!empty($app['rating'])): ?>
                                            <div class="text-warning small">
                                                <?php for($i=1; $i<=5; $i++) echo $i <= $app['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary border-0 rounded">Geçmiş randevunuz bulunmamaktadır.</div>
                <?php endif; ?>
            </div>

            <!-- Orders -->
            <div class="tab-pane fade" id="orders" role="tabpanel">
                <?php if (count($orders) > 0): ?>
                    <?php foreach ($orders as $order): 
                        $stmt_items->execute([$order['id']]);
                        $orderItems = $stmt_items->fetchAll();
                    ?>
                        <div class="card card-custom mb-3 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0 fw-bold">Sipariş #<?= $order['id'] ?></h6>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    <?php $s = get_status_label($order['status']); ?>
                                    <span class="badge bg-<?= $s['color'] ?> rounded-pill"><?= $s['label'] ?></span>
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($orderItems as $item): ?>
                                        <div class="col-12">
                                            <div class="d-flex align-items-center gap-3 p-2 bg-light rounded">
                                                <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/60') ?>" 
                                                     class="rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                                <div class="flex-grow-1">
                                                    <div class="small fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <div class="x-small text-muted"><?= htmlspecialchars($item['brand']) ?> | <?= (int)$item['quantity'] ?> Adet</div>
                                                </div>
                                                <div class="text-end">
                                                    <div class="small fw-bold text-primary">₺<?= number_format($item['line_total'], 2) ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="mt-3 pt-3 border-top d-flex justify-content-between align-items-center">
                                    <span class="small text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($order['district']) ?> / <?= htmlspecialchars($order['city']) ?></span>
                                    <div class="fw-bold text-dark">Toplam: ₺<?= number_format($order['total_amount'], 2) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info border-0 rounded">Henüz bir ürün siparişiniz bulunmamaktadır.</div>
                    <div class="text-center mt-3">
                        <a href="products.php" class="btn btn-primary rounded-pill px-4 shadow-sm">Ürünleri İncele</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" method="POST">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold" id="reviewShopName">Mekanı Değerlendir</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="appointment_id" id="review_app_id">
                <div class="mb-3 text-center">
                    <label class="form-label fw-semibold d-block">Puanınız</label>
                    <div class="btn-group" role="group">
                        <?php for($i=1; $i<=5; $i++): ?>
                            <input type="radio" class="btn-check" name="rating" id="rate<?= $i ?>" value="<?= $i ?>" required>
                            <label class="btn btn-outline-warning" for="rate<?= $i ?>"><i class="fas fa-star"></i> <?= $i ?></label>
                        <?php endfor; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Yorumunuz (Opsiyonel)</label>
                    <textarea name="review" class="form-control" rows="3" placeholder="Hizmetten memnun kaldınız mı?"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">İptal</button>
                <button type="submit" name="submit_review" class="btn btn-warning text-dark rounded-pill px-4 fw-bold">Gönder</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReviewModal(appId, shopName) {
    document.getElementById('review_app_id').value = appId;
    document.getElementById('reviewShopName').innerText = shopName + ' - Değerlendir';
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}
</script>

<?php require_once 'views/footer.php'; ?>
