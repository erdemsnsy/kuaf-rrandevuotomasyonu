<?php
require_once 'config/database.php';

$stmt = $pdo->query("
    SELECT b.*, u.name as owner_name, IFNULL(AVG(a.rating), 0) as avg_rating 
    FROM barbers b 
    JOIN users u ON b.user_id = u.id
    LEFT JOIN appointments a ON b.id = a.barber_id
    GROUP BY b.id
");
$barbers = $stmt->fetchAll();

require_once 'views/header.php';
?>

<div class="row mb-5 hero-section rounded text-center animate-fade-in-down">
    <div class="col-12 py-5" style="position: relative; z-index: 1;">
        <h1 class="display-3 fw-bold mb-4">Size En Uygun Kuaförü Bulun</h1>
        <p class="lead fs-4 opacity-75">Hızlı ve kolay bir şekilde randevunuzu alın, zamanınız size kalsın.</p>
        <div class="mt-4">
            <a href="#populer" class="btn btn-light btn-lg text-primary rounded-pill px-4 py-2 font-weight-bold shadow-sm">Keşfetmeye Başla</a>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-4 mt-5 animate-fade-in-up" id="populer">
    <h2 class="fw-bold m-0">Popüler Kuaförler</h2>
    <span class="badge bg-primary rounded-pill px-3 py-2">En Çok Tercih Edilenler</span>
</div>
<div class="row">
    <?php foreach ($barbers as $barber): ?>
    <div class="col-md-4 mb-4">
        <div class="card card-custom h-100">
            <div class="card-body">
                <h5 class="card-title text-primary"><?= htmlspecialchars($barber['shop_name']) ?></h5>
                <div class="mb-2 text-warning small">
                    <?php 
                        $rating = round($barber['avg_rating']); 
                        if($rating > 0) {
                            for($i=1; $i<=5; $i++) echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                        } else {
                            echo '<span class="text-muted"><i class="far fa-star"></i> Henüz yorum yok</span>';
                        }
                    ?>
                </div>
                <p class="card-text text-muted small"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($barber['address']) ?></p>
                <p class="card-text"><?= htmlspecialchars($barber['description'] ?? 'Açıklama bulunmuyor.') ?></p>
                <p class="card-text small">
                    <i class="far fa-clock me-1"></i> Çalışma Saatleri: <br>
                    <strong><?= substr($barber['opening_time'], 0, 5) ?> - <?= substr($barber['closing_time'], 0, 5) ?></strong>
                </p>
                <a href="barber.php?id=<?= $barber['id'] ?>" class="btn btn-outline-primary btn-custom w-100 mt-2">Profili & Hizmetleri Gör</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (count($barbers) == 0): ?>
        <div class="col-12"><div class="alert alert-warning">Henüz sisteme kayıtlı kuaför bulunmuyor.</div></div>
    <?php endif; ?>
</div>

<?php require_once 'views/footer.php'; ?>
