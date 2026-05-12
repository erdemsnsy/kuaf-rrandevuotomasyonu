<?php
$notifications = [];
$unread_count = 0;
if (auth()) {
    global $pdo;
    $stmt_n = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 15");
    $stmt_n->execute([auth()['user_id']]);
    $notifications = $stmt_n->fetchAll();
    foreach ($notifications as $n) {
        if (!$n['is_read']) $unread_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hair &amp; Care | Randevu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= APP_URL ?>/public/css/style.css" rel="stylesheet">
    <script>
        // Check for saved theme preference or use system default
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'dark' || (!savedTheme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark-mode');
            }
        })();
    </script>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">
    <script>
        // Synchronize body class with localStorage immediately
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
    <nav class="navbar navbar-expand-lg fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?= APP_URL ?>/index.php">
                <img src="<?= APP_URL ?>/img/Hair Care Logo PNG.png" alt="Hair Care" height="80" class="me-3">
                <span>Hair <span class="text-secondary">&amp;</span> Care</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if (auth()): ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/index.php">Ana Sayfa</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/products.php">Ürünler</a></li>
                        <li class="nav-item dropdown me-2">
                            <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" onclick="markNotificationsRead()">
                                Bildirimler
                                <?php if ($unread_count > 0): ?>
                                    <span class="position-absolute top-10 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" id="notif-badge" style="width: 10px; height: 10px; margin-top: 10px; margin-left: -5px;">
                                        <span class="visually-hidden">Yeni Bildirimler</span>
                                    </span>
                                <?php endif; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="notificationDropdown" style="width: 320px; max-height: 400px; overflow-y: auto;">
                                <li><h6 class="dropdown-header fw-bold">Bildirimler</h6></li>
                                <?php if (count($notifications) > 0): ?>
                                    <?php foreach ($notifications as $n): ?>
                                        <li>
                                            <div class="dropdown-item border-bottom <?= $n['is_read'] ? 'opacity-75' : 'bg-light' ?>" style="white-space: normal; font-size: 0.85rem;">
                                                <div class="mb-1"><?= htmlspecialchars($n['message']) ?></div>
                                                <small class="text-muted"><i class="far fa-clock me-1"></i><?= date('d.m.Y H:i', strtotime($n['created_at'])) ?></small>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <li><span class="dropdown-item text-muted text-center py-3">Bildiriminiz Yok</span></li>
                                <?php endif; ?>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <?php 
                                $dashLink = APP_URL . '/user_dashboard.php';
                                if ($_SESSION['role'] === 'admin') $dashLink = APP_URL . '/admin_dashboard.php';
                                elseif ($_SESSION['role'] === 'barber_owner') $dashLink = APP_URL . '/barber_dashboard.php';
                            ?>
                            <a class="nav-link" href="<?= $dashLink ?>">Hesabım</a>
                        </li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/logout.php">Çıkış Yap</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/index.php">Ana Sayfa</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/products.php">Ürünler</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/login.php">Giriş Yap</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= APP_URL ?>/register.php">Kayıt Ol</a></li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3">
                        <button class="nav-link btn btn-link" id="themeToggle" title="Tema Değiştir">
                            <i class="fas fa-moon dark-icon"></i>
                            <i class="fas fa-sun light-icon d-none"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container py-4" style="margin-top: 110px;">
    
    <script>
    function markNotificationsRead() {
        var badge = document.getElementById('notif-badge');
        if (badge) {
            badge.style.display = 'none';
            fetch('<?= APP_URL ?>/mark_notifications_read.php')
                .then(response => response.text())
                .then(data => { console.log('Bildirimler okundu olarak işaretlendi.'); })
                .catch(error => console.error('Hata:', error));
        }
    }

    const themeToggle = document.getElementById('themeToggle');
    const body = document.body;
    const moonIcon = themeToggle.querySelector('.dark-icon');
    const sunIcon = themeToggle.querySelector('.light-icon');

    function updateIcons(isDark) {
        if (isDark) {
            moonIcon.classList.add('d-none');
            sunIcon.classList.remove('d-none');
        } else {
            moonIcon.classList.remove('d-none');
            sunIcon.classList.add('d-none');
        }
    }

    // Initialize icons
    updateIcons(body.classList.contains('dark-mode'));

    themeToggle.addEventListener('click', () => {
        const isDark = body.classList.toggle('dark-mode');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
        updateIcons(isDark);
        
        // Also update the HTML tag for consistency
        if (isDark) {
            document.documentElement.classList.add('dark-mode');
        } else {
            document.documentElement.classList.remove('dark-mode');
        }
    });
    </script>
