<?php
require_once 'config/database.php';

if (auth()) {
    redirect('/index.php');
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        redirect('/index.php');
    } else {
        $error = "E-posta veya şifre hatalı!";
    }
}
?>
<?php require_once 'views/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-custom mt-4 animate-fade-in-down border-0 shadow-lg">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 50px; height: 50px;">
                        <i class="fas fa-lock fs-5"></i>
                    </div>
                    <h4 class="fw-bold">Hoş Geldiniz</h4>
                    <p class="text-muted">Devam etmek için giriş yapın</p>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-1">E-posta Adresi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-1">Şifre</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 mt-2 mb-2 shadow-sm" style="border-radius: 50px;">Giriş Yap <i class="fas fa-sign-in-alt ms-1"></i></button>
                    <div class="text-center mt-2">
                        <span class="text-muted small">Hesabın yok mu?</span> <a href="register.php" class="text-primary fw-bold text-decoration-none small">Hemen Kayıt Ol</a>
                    </div>
                    <div class="text-center mt-3 pt-3 border-top">
                        <span class="text-muted small">Yönetici misiniz?</span> <a href="admin_login.php" class="text-primary fw-bold text-decoration-none small">Sistem Yöneticisi Girişi</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'views/footer.php'; ?>
