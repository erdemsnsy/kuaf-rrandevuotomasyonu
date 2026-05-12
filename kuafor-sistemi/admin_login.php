<?php
require_once 'config/database.php';

if (auth() && $_SESSION['role'] === 'admin') {
    redirect('/admin_dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        redirect('/admin_dashboard.php');
    } else {
        $error = "Geçersiz admin bilgileri veya yetkisiz erişim.";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Girişi | Hair & Care</title>
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
    <style>
        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            transition: all 0.3s ease;
        }
        .admin-login-card {
            width: 100%;
            max-width: 400px;
            padding: 40px;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(0,0,0,0.05);
        }
        body.dark-mode .admin-login-card {
            border: 1px solid rgba(255,255,255,0.1);
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark' ? 'dark-mode' : '' ?>">
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark-mode');
        }
    </script>
    <div class="admin-login-card text-center">
        <h2 class="mb-4"><i class="fas fa-user-shield me-2 text-primary"></i>Admin Girişi</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger small py-2"><?= $error ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3 text-start">
                <label class="form-label small opacity-75">Admin Email</label>
                <input type="email" name="email" class="form-control" placeholder="admin@haircare.com" required>
            </div>
            <div class="mb-4 text-start">
                <label class="form-label small opacity-75">Şifre</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-3 mb-3 shadow-sm">Sisteme Giriş Yap</button>
            <a href="<?= APP_URL ?>/index.php" class="text-muted small text-decoration-none"><i class="fas fa-arrow-left me-1"></i>Siteye Dön</a>
        </form>
    </div>
</body>
</html>
