<?php
require_once 'config/database.php';

$id = $_GET['id'] ?? null;
if (!$id) redirect('/index.php');

$stmt = $pdo->prepare("SELECT * FROM barbers WHERE id = ?");
$stmt->execute([$id]);
$barber = $stmt->fetch();

if (!$barber) redirect('/index.php');

$stmt_services = $pdo->prepare("SELECT * FROM services WHERE barber_id = ?");
$stmt_services->execute([$id]);
$services = $stmt_services->fetchAll();

$user = auth();

// Handle Rating Submit
if ($user && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_barber_review'])) {
    $rating = $_POST['rating'];
    $review = $_POST['review'] ?? '';
    // Artık herkes yorum atabilir (Giriş yapması yeterli)
    $pdo->prepare("INSERT INTO reviews (barber_id, user_id, rating, review) VALUES (?, ?, ?, ?)")->execute([$id, $user['user_id'], $rating, $review]);
    
    // Yorum bildirimi
    $stmt_owner = $pdo->prepare("SELECT user_id FROM barbers WHERE id = ?");
    $stmt_owner->execute([$id]);
    $owner = $stmt_owner->fetch();
    if ($owner) {
        $msg = $user['name'] . " size bir yorum yaptı: " . substr(str_replace(["\r", "\n"], ' ', $review), 0, 50);
        if (strlen($review) > 50) $msg .= '...';
        $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)")->execute([$owner['user_id'], $msg]);
    }

    redirect("/barber.php?id=$id");
}

// Fetch Reviews
$stmt_reviews = $pdo->prepare("
    SELECT r.id, r.rating, r.review, r.barber_reply, r.created_at as review_date, u.name as customer_name 
    FROM reviews r 
    JOIN users u ON r.user_id = u.id 
    WHERE r.barber_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt_reviews->execute([$id]);
$reviews = $stmt_reviews->fetchAll();

require_once 'views/header.php';
?>

<div id="barberCarousel" class="carousel slide mb-5 rounded shadow-lg overflow-hidden animate-fade-in-down" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php for($i=0; $i<6; $i++): ?>
            <button type="button" data-bs-target="#barberCarousel" data-bs-slide-to="<?= $i ?>" class="<?= $i==0 ? 'active' : '' ?>" aria-current="<?= $i==0 ? 'true' : 'false' ?>"></button>
        <?php endfor; ?>
    </div>
    <div class="carousel-inner">
        <?php for($i=1; $i<=6; $i++): ?>
            <div class="carousel-item <?= $i==1 ? 'active' : '' ?>">
                <img src="img/barber_interior_<?= $i ?>.png" class="d-block w-100" style="height: 450px; object-fit: cover;" alt="Kuaför İç Mekan <?= $i ?>">
                <div class="carousel-caption d-none d-md-block" style="background: rgba(0,0,0,0.5); border-radius: 15px; padding: 10px 20px;">
                    <h5 class="fw-bold"><?= htmlspecialchars($barber['shop_name']) ?></h5>
                    <p>Modern ve konforlu salonumuzda size en iyi hizmeti sunuyoruz.</p>
                </div>
            </div>
        <?php endfor; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#barberCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        <span class="visually-hidden">Geri</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#barberCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
        <span class="visually-hidden">İleri</span>
    </button>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card card-custom">
            <div class="card-body">
                <h3 class="card-title text-primary"><?= htmlspecialchars($barber['shop_name']) ?></h3>
                <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($barber['address']) ?></p>
                <hr>
                <p><?= nl2br(htmlspecialchars($barber['description'])) ?></p>
                <p class="mb-3">
                    <strong><i class="far fa-clock me-2"></i>Çalışma Saatleri:</strong><br>
                    <?= substr($barber['opening_time'], 0, 5) ?> - <?= substr($barber['closing_time'], 0, 5) ?>
                </p>
                <div class="d-grid mt-2">
                    <a href="https://www.google.com/maps/search/?api=1&query=<?= urlencode($barber['shop_name'] . ' ' . $barber['address']) ?>" target="_blank" class="btn btn-outline-danger">
                        <i class="fas fa-map-marked-alt me-2"></i>Haritada Gör
                    </a>
                </div>
                </div>
            </div>
    
    <!-- Yorumlar Kartı -->
        <div class="card card-custom mt-4">
            <div class="card-body">
                <h5 class="card-title text-primary mb-3"><i class="fas fa-star me-2"></i>Değerlendirmeler</h5>
                
                <!-- Yorum Ekleme Formu -->
                <?php if($user): ?>
                    <form method="POST" class="mb-4 pb-3 border-bottom">
                        <h6 class="fw-bold fs-6">Puanınız</h6>
                        <div class="btn-group mb-2 w-100" role="group">
                            <?php for($i=1; $i<=5; $i++): ?>
                                <input type="radio" class="btn-check" name="rating" id="rate<?= $i ?>" value="<?= $i ?>" required>
                                <label class="btn btn-sm btn-outline-warning" for="rate<?= $i ?>"><?= $i ?></label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="review" class="form-control form-control-sm mb-2" rows="2" placeholder="Kuaför deneyiminizi anlatın..." required></textarea>
                        <button type="submit" name="submit_barber_review" class="btn btn-sm btn-warning w-100 fw-bold text-dark rounded-pill shadow-sm">Gönder</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning small py-2 mb-3">Yorum yapabilmek için <a href="login.php" class="alert-link">giriş yapmalısınız</a>.</div>
                <?php endif; ?>

                <!-- Yorum Listesi -->
                <div class="review-list" style="max-height: 400px; overflow-y: auto; padding-right: 5px;">
                    <?php if(count($reviews) > 0): ?>
                        <?php foreach($reviews as $r): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="small"><i class="fas fa-user-circle me-1"></i><?= htmlspecialchars($r['customer_name']) ?></strong>
                                    <span class="text-warning small" style="font-size: 0.8rem;">
                                        <?php for($i=1; $i<=5; $i++) echo $i <= $r['rating'] ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>'; ?>
                                    </span>
                                </div>
                                <?php if(!empty($r['review'])): ?>
                                    <p class="mb-1 mt-1 small fst-italic">"<?= htmlspecialchars($r['review']) ?>"</p>
                                <?php endif; ?>
                                <?php if(!empty($r['barber_reply'])): ?>
                                    <div class="bg-light p-2 mt-1 rounded border-start border-2 border-primary" style="font-size: 0.8rem;">
                                        <strong class="text-primary">Kuaför Yanıtı:</strong><br><?= htmlspecialchars($r['barber_reply']) ?>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted mt-1 d-block" style="font-size: 0.75rem;"><?= date('d.m.Y', strtotime($r['review_date'])) ?></small>
                            </div>
                            <hr class="text-muted opacity-25">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="far fa-comment-dots fa-2x text-muted mb-2 opacity-50"></i>
                            <p class="text-muted small mb-0">Henüz bir değerlendirme bulunmuyor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <h4 class="mb-3">Hizmetler ve Fiyatlar</h4>
        <?php if (count($services) > 0): ?>
            <div class="list-group">
                <?php foreach ($services as $service): ?>
                <div class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-2 border rounded card-custom">
                    <div>
                        <h5 class="mb-1"><?= htmlspecialchars($service['service_name']) ?></h5>
                        <small class="text-muted"><i class="far fa-clock me-1"></i><?= $service['duration'] ?> Dakika</small>
                    </div>
                    <div class="text-end">
                        <h4 class="text-success mb-1"><?= number_format($service['price'], 2) ?> TL</h4>
                        <a href="book.php?service_id=<?= $service['id'] ?>" class="btn btn-primary btn-sm btn-custom text-white">Randevu Al</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">Bu kuaför henüz hizmet eklememiş.</div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
