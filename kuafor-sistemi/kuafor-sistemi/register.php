<?php
require_once 'config/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? 'user';

    if ($password !== $password_confirm) {
        $error = "Şifreler uyuşmuyor!";
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Bu e-posta adresi zaten kullanılıyor!";
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $hash, $role])) {
                $user_id = $pdo->lastInsertId();
                if ($role == 'barber_owner') {
                    // Create an empty barber profile
                    $stmt2 = $pdo->prepare("INSERT INTO barbers (user_id, shop_name) VALUES (?, ?)");
                    $stmt2->execute([$user_id, $name . ' Kuaför']);
                }
                redirect('/login.php');
            } else {
                $error = "Kayıt işlemi başarısız!";
            }
        }
    }
}
?>
<?php require_once 'views/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card card-custom mt-4 animate-fade-in-down border-0 shadow-lg">
            <div class="card-body p-5">
                <div class="text-center mb-4">
                    <div class="bg-success text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm" style="width: 60px; height: 60px;">
                        <i class="fas fa-user-plus fs-4"></i>
                    </div>
                    <h3 class="fw-bold">Yeni Hesap Oluştur</h3>
                    <p class="text-muted">Aramıza katılmak için formu doldurun</p>
                </div>
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center"><i class="fas fa-exclamation-triangle me-2"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">Ad Soyad</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" name="name" class="form-control" placeholder="Örn: Ahmet Yılmaz" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark">E-posta Adresi</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="ornek@email.com" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-dark">Şifre</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-key"></i></span>
                                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold text-dark">Şifre Tekrar</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-check"></i></span>
                                <input type="password" name="password_confirm" class="form-control" placeholder="••••••••" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold text-dark">Kayıt Türü</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-briefcase"></i></span>
                            <select name="role" class="form-select">
                                <option value="user">Müşteri (Randevu Almak İstiyorum)</option>
                                <option value="barber_owner">Kuaför İşletme Sahibi (Mekan Eklemek İstiyorum)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100 mt-2 mb-3 shadow-sm" style="border-radius: 50px;">Kayıt Ol <i class="fas fa-paper-plane ms-1"></i></button>
                    <div class="text-center mt-3">
                        <span class="text-muted">Zaten hesabın var mı?</span> <a href="login.php" class="text-primary fw-bold text-decoration-none">Hemen Giriş Yap</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once 'views/footer.php'; ?>
