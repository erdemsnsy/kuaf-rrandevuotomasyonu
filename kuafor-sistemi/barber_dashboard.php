<?php
require_once 'config/database.php';
check_role('barber_owner');

$user_id = $_SESSION['user_id'];

$stmt_b = $pdo->prepare("SELECT * FROM barbers WHERE user_id = ?");
$stmt_b->execute([$user_id]);
$barber = $stmt_b->fetch();

if (!$barber) {
    die("Kuaför profili bulunamadı.");
}

$barber_id = $barber['id'];
$msg = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $shop_name = $_POST['shop_name'];
    $address = $_POST['address'];
    $desc = $_POST['description'];
    $open = $_POST['opening_time'];
    $close = $_POST['closing_time'];
    $closed_day = $_POST['closed_day'];
    
    $u_stmt = $pdo->prepare("UPDATE barbers SET shop_name=?, address=?, description=?, opening_time=?, closing_time=?, closed_day=? WHERE id=?");
    $u_stmt->execute([$shop_name, $address, $desc, $open, $close, $closed_day, $barber_id]);
    $msg = "Profil güncellendi.";
    $barber = $pdo->prepare("SELECT * FROM barbers WHERE id = ?")->execute([$barber_id]); // Refresh
    redirect('/barber_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_service'])) {
    $s_name = $_POST['service_name'];
    $price = $_POST['price'];
    $dur = $_POST['duration'];
    $i_stmt = $pdo->prepare("INSERT INTO services (barber_id, service_name, price, duration) VALUES (?, ?, ?, ?)");
    $i_stmt->execute([$barber_id, $s_name, $price, $dur]);
    $msg = "Hizmet eklendi.";
}

if (isset($_GET['delete_service'])) {
    $del = $_GET['delete_service'];
    $d_stmt = $pdo->prepare("DELETE FROM services WHERE id = ? AND barber_id = ?");
    $d_stmt->execute([$del, $barber_id]);
    redirect('/barber_dashboard.php');
}

// Randevu İptal İşlemi
if (isset($_GET['cancel_app'])) {
    $app_id = $_GET['cancel_app'];
    $app_q = $pdo->prepare("SELECT user_id, appointment_date, appointment_time FROM appointments WHERE id = ? AND barber_id = ?");
    $app_q->execute([$app_id, $barber_id]);
    $app = $app_q->fetch();
    if ($app) {
        $notif_msg = "Randevunuz kuaför tarafından iptal edildi: " . date('d.m.Y', strtotime($app['appointment_date'])) . " " . substr($app['appointment_time'], 0, 5);
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$app['user_id'], $notif_msg]);
    }
    
    $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND barber_id = ?")->execute([$app_id, $barber_id]);
    redirect('/barber_dashboard.php');
}

// Randevu Onaylama İşlemi
if (isset($_GET['confirm_app'])) {
    $app_id = $_GET['confirm_app'];
    $pdo->prepare("UPDATE appointments SET status = 'confirmed' WHERE id = ? AND barber_id = ?")->execute([$app_id, $barber_id]);
    redirect('/barber_dashboard.php');
}

// Randevu Silme İşlemi
if (isset($_GET['delete_app'])) {
    $app_id = $_GET['delete_app'];
    
    $app_q = $pdo->prepare("SELECT user_id, appointment_date, appointment_time FROM appointments WHERE id = ? AND barber_id = ?");
    $app_q->execute([$app_id, $barber_id]);
    $app = $app_q->fetch();
    if ($app) {
        $notif_msg = "Randevunuz kuaför tarafından kalıcı olarak silindi: " . date('d.m.Y', strtotime($app['appointment_date'])) . " " . substr($app['appointment_time'], 0, 5);
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$app['user_id'], $notif_msg]);
    }

    $pdo->prepare("DELETE FROM appointments WHERE id = ? AND barber_id = ?")->execute([$app_id, $barber_id]);
    redirect('/barber_dashboard.php');
}

// Bildirimleri Okundu Olarak İşaretle
$pdo->query("UPDATE notifications SET is_read = 1 WHERE user_id = $user_id AND is_read = 0");

// Yorum Yanıtlama İşlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply_review'])) {
    $rep_r_id = $_POST['review_id'];
    $reply = $_POST['barber_reply'];
    $pdo->prepare("UPDATE reviews SET barber_reply = ? WHERE id = ? AND barber_id = ?")->execute([$reply, $rep_r_id, $barber_id]);
    $msg = "Yanıtlama başarılı.";
}

// Yorum Silme İşlemi
if (isset($_GET['delete_review'])) {
    $del_r_id = $_GET['delete_review'];
    $pdo->prepare("DELETE FROM reviews WHERE id = ? AND barber_id = ?")->execute([$del_r_id, $barber_id]);
    redirect('/barber_dashboard.php');
}

// Hizmetleri Getir
$services = $pdo->prepare("SELECT * FROM services WHERE barber_id = ?");
$services->execute([$barber_id]);
$services = $services->fetchAll();

// Randevuları Getir
$appointments = $pdo->prepare("
    SELECT a.*, u.name as customer_name, u.email, s.service_name 
    FROM appointments a 
    JOIN users u ON a.user_id = u.id 
    JOIN services s ON a.service_id = s.id 
    WHERE a.barber_id = ? 
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
$appointments->execute([$barber_id]);
$appointments = $appointments->fetchAll();

// Yorumları Getir
$reviews = $pdo->prepare("
    SELECT r.id, r.rating, r.review, r.barber_reply, u.name as customer_name, r.created_at as review_date 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.barber_id = ?
    ORDER BY r.created_at DESC
");
$reviews->execute([$barber_id]);
$reviews = $reviews->fetchAll();

// Ürün Siparişlerini Getir (Müşteri olarak)
$ordersStmt = $pdo->prepare("
    SELECT * FROM product_orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$ordersStmt->execute([$user_id]);
$orders = $ordersStmt->fetchAll();

$itemsStmt = $pdo->prepare("
    SELECT * FROM product_order_items 
    WHERE order_id = ? 
    ORDER BY id ASC
");

require_once 'views/header.php';
?>

<div class="row">
    <div class="col-md-3">
        <div class="list-group mb-4 shadow-sm border-0">
            <a href="#appointments" class="list-group-item list-group-item-action active" data-bs-toggle="list">Randevular</a>
            <a href="#services" class="list-group-item list-group-item-action" data-bs-toggle="list">Hizmetlerim</a>
            <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">Siparişlerim</a>
            <a href="#reviews" class="list-group-item list-group-item-action" data-bs-toggle="list">Değerlendirmeler</a>
            <a href="#settings" class="list-group-item list-group-item-action" data-bs-toggle="list">İşletme Ayarları</a>
        </div>
        <script>
            // Sayfa yüklendiğinde URL'deki hash'e göre ilgili sekmeyi aktif et
            document.addEventListener("DOMContentLoaded", function() {
                if (window.location.hash) {
                    var triggerEl = document.querySelector('a[href="' + window.location.hash + '"]');
                    if (triggerEl) {
                        var tab = new bootstrap.Tab(triggerEl);
                        tab.show();
                    }
                }
            });
        </script>
    </div>
    
    <div class="col-md-9">
        <?php if($msg): ?>
            <div class="alert alert-success"><?= $msg ?></div>
        <?php endif; ?>
        
        <div class="tab-content">
            <!-- Appointments -->
            <div class="tab-pane fade show active" id="appointments">
                <h4>Gelen Randevular</h4>
                <div class="table-responsive bg-white rounded shadow-sm mt-3">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih/Saat</th>
                                <th>Müşteri</th>
                                <th>Hizmet</th>
                                <th>Not</th>
                                <th>Durum</th>
                                <th class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $app): ?>
                            <tr>
                                <td><?= date('d.m.Y', strtotime($app['appointment_date'])) ?> <?= substr($app['appointment_time'],0,5) ?></td>
                                <td><?= htmlspecialchars($app['customer_name']) ?></td>
                                <td><?= htmlspecialchars($app['service_name']) ?></td>
                                <td><?= htmlspecialchars($app['user_note']) ?></td>
                                <td>
                                    <?php
                                        $badgeClass = 'bg-secondary';
                                        if($app['status'] == 'pending') $badgeClass = 'bg-warning text-dark';
                                        if($app['status'] == 'confirmed') $badgeClass = 'bg-success';
                                        if($app['status'] == 'cancelled') $badgeClass = 'bg-danger';
                                    ?>
                                    <span class="badge <?= $badgeClass ?>"><?= ucfirst($app['status'] == 'cancelled' ? 'İptal Edildi' : $app['status']) ?></span>
                                </td>
                                <td class="text-end">
                                    <?php if($app['status'] == 'pending'): ?>
                                        <a href="?confirm_app=<?= $app['id'] ?>" class="btn btn-sm btn-outline-success shadow-sm rounded-pill px-3 me-1">Onayla</a>
                                    <?php endif; ?>
                                    <?php if($app['status'] != 'cancelled'): ?>
                                        <a href="?cancel_app=<?= $app['id'] ?>" class="btn btn-sm btn-outline-warning shadow-sm rounded-pill px-3 me-1" onclick="return confirm('Bu randevuyu iptal etmek istediğinize emin misiniz?')">İptal Et</a>
                                    <?php endif; ?>
                                    <a href="?delete_app=<?= $app['id'] ?>" class="btn btn-sm btn-outline-danger shadow-sm rounded-pill px-2" title="Kalıcı Olarak Sil" onclick="return confirm('DİKKAT: Bu randevuyu tamamen SİLMEK istediğinize emin misiniz?')"><i class="fas fa-trash-alt"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Services -->
            <div class="tab-pane fade" id="services">
                <h4>Hizmetlerim</h4>
                <form class="card mb-4 p-3 border-0 shadow-sm" method="POST">
                    <h5>Yeni Hizmet Ekle</h5>
                    <div class="row">
                        <div class="col-md-4 mb-2">
                            <input type="text" name="service_name" class="form-control" placeholder="Hizmet Adı" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="number" step="0.01" name="price" class="form-control" placeholder="Fiyat (TL)" required>
                        </div>
                        <div class="col-md-3 mb-2">
                            <input type="number" name="duration" class="form-control" placeholder="Süre (Dakika)" required>
                        </div>
                        <div class="col-md-2 mb-2">
                            <button type="submit" name="add_service" class="btn btn-primary w-100">Ekle</button>
                        </div>
                    </div>
                </form>

                <ul class="list-group shadow-sm border-0">
                    <?php foreach($services as $s): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <strong><?= htmlspecialchars($s['service_name']) ?></strong> - ₺<?= $s['price'] ?> (<?= $s['duration'] ?> dk)
                        </div>
                        <a href="?delete_service=<?= $s['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Silmek istiyor musunuz?')"><i class="fas fa-trash"></i></a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Settings -->
            <div class="tab-pane fade" id="settings">
                <h4>İşletme Ayarları</h4>
                <form class="card p-4 border-0 shadow-sm" method="POST">
                    <div class="mb-3">
                        <label>İşletme Adı</label>
                        <input type="text" name="shop_name" class="form-control" value="<?= htmlspecialchars($barber['shop_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label>Adres</label>
                        <textarea name="address" class="form-control" rows="2" required><?= htmlspecialchars($barber['address']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Açıklama</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($barber['description']) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Açılış Saati</label>
                            <input type="time" name="opening_time" class="form-control" value="<?= $barber['opening_time'] ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Kapanış Saati</label>
                            <input type="time" name="closing_time" class="form-control" value="<?= $barber['closing_time'] ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Kapalı Olduğu Gün</label>
                            <select name="closed_day" class="form-select">
                                <option value="" <?= empty($barber['closed_day']) ? 'selected' : '' ?>>Yok (Haftanın Her Günü Açık)</option>
                                <option value="Monday" <?= $barber['closed_day'] == 'Monday' ? 'selected' : '' ?>>Pazartesi</option>
                                <option value="Tuesday" <?= $barber['closed_day'] == 'Tuesday' ? 'selected' : '' ?>>Salı</option>
                                <option value="Wednesday" <?= $barber['closed_day'] == 'Wednesday' ? 'selected' : '' ?>>Çarşamba</option>
                                <option value="Thursday" <?= $barber['closed_day'] == 'Thursday' ? 'selected' : '' ?>>Perşembe</option>
                                <option value="Friday" <?= $barber['closed_day'] == 'Friday' ? 'selected' : '' ?>>Cuma</option>
                                <option value="Saturday" <?= $barber['closed_day'] == 'Saturday' ? 'selected' : '' ?>>Cumartesi</option>
                                <option value="Sunday" <?= $barber['closed_day'] == 'Sunday' ? 'selected' : '' ?>>Pazar</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-success">Değişiklikleri Kaydet</button>
                </form>
            </div>

            <!-- Reviews -->
            <div class="tab-pane fade" id="reviews">
                <h4>Müşteri Değerlendirmeleri</h4>
                <?php if (count($reviews) > 0): ?>
                    <div class="list-group shadow-sm border-0">
                        <?php foreach($reviews as $r): ?>
                        <div class="list-group-item mb-2 rounded border">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($r['customer_name']) ?></strong>
                                <div>
                                    <span class="text-warning small me-2">
                                        <?php for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    </span>
                                    <a href="?delete_review=<?= $r['id'] ?>" class="text-danger small" title="Yorumu Sil" onclick="return confirm('Bu yorumu silmek istediğinize emin misiniz?')"><i class="fas fa-trash-alt"></i></a>
                                </div>
                            </div>
                            <?php if(!empty($r['review'])): ?>
                                <p class="mb-1 mt-2 fst-italic">"<?= htmlspecialchars($r['review']) ?>"</p>
                            <?php else: ?>
                                <p class="mb-1 mt-2 text-muted small">Yorum yapılmadı.</p>
                            <?php endif; ?>
                            <small class="text-muted d-block mb-3"><?= date('d.m.Y', strtotime($r['review_date'])) ?></small>
                            <?php if($r['barber_reply']): ?>
                                <div class="bg-light p-3 rounded ms-4 border-start border-3 border-primary">
                                    <strong><i class="fas fa-reply me-1"></i>Yanıtınız:</strong><br>
                                    <span class="text-secondary"><?= htmlspecialchars($r['barber_reply']) ?></span>
                                </div>
                            <?php else: ?>
                                <form method="POST" class="mt-2 d-flex ms-4">
                                    <input type="hidden" name="review_id" value="<?= $r['id'] ?>">
                                    <input type="text" name="barber_reply" class="form-control form-control-sm me-2 bg-light border-0" placeholder="Yoruma yanıt verin..." required>
                                    <button type="submit" name="reply_review" class="btn btn-sm btn-primary px-3 rounded-pill">Yanıtla</button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-secondary border-0 text-center py-4">
                        <i class="fas fa-star-half-alt fa-3x text-muted mb-3 d-block"></i>
                        Henüz değerlendirme bulunmuyor.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Orders -->
            <div class="tab-pane fade" id="orders">
                <h4>Ürün Siparişlerim</h4>
                <?php if (count($orders) === 0): ?>
                    <div class="alert alert-info">Henüz ürün siparişiniz bulunmuyor.</div>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            $itemsStmt->execute([$order['id']]);
                            $orderItems = $itemsStmt->fetchAll();
                        ?>
                        <div class="card card-custom mb-4 border-0 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                    <div>
                                        <h5 class="mb-0">Sipariş #<?= (int)$order['id'] ?></h5>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></small>
                                    </div>
                                    <?php $s = get_status_label($order['status']); ?>
                                    <span class="badge bg-<?= $s['color'] ?> rounded-pill"><?= $s['label'] ?></span>
                                </div>
                                <div class="mb-3 small">
                                    <i class="fas fa-truck me-1"></i> <strong>Teslimat Adresi:</strong>
                                    <?= htmlspecialchars($order['full_name']) ?>, <?= htmlspecialchars($order['phone']) ?>, <?= htmlspecialchars($order['district']) ?>/<?= htmlspecialchars($order['city']) ?>, <?= htmlspecialchars($order['address']) ?>
                                </div>
                                <div class="row g-2">
                                    <?php foreach ($orderItems as $item): ?>
                                        <div class="col-12">
                                            <div class="d-flex align-items-center gap-3 p-2 border rounded bg-light">
                                                <img src="<?= htmlspecialchars($item['image_url'] ?: 'https://via.placeholder.com/160x120?text=Urun') ?>" 
                                                     style="width: 50px; height: 50px; object-fit: cover;" class="rounded">
                                                <div class="flex-grow-1">
                                                    <div class="fw-semibold small"><?= htmlspecialchars($item['product_name']) ?></div>
                                                    <div class="text-muted" style="font-size: 0.75rem;"><?= (int)$item['quantity'] ?> Adet x ₺<?= number_format($item['unit_price'], 2, ',', '.') ?></div>
                                                </div>
                                                <div class="fw-bold small">₺<?= number_format($item['line_total'], 2, ',', '.') ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="text-end mt-3 border-top pt-2">
                                    <span class="text-muted me-2">Toplam:</span>
                                    <strong class="fs-5 text-primary">₺<?= number_format($order['total_amount'], 2, ',', '.') ?></strong>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
