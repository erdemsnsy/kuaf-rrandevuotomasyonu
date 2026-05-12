<?php
require_once 'config/database.php';
require_once 'config/product_catalog.php';

// Only admins can access
check_role('admin');

$page = $_GET['page'] ?? 'stats';
$message = '';

// Handle Product Actions
if (isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $category = $_POST['category'];
    $price = (float)$_POST['price'];
    $desc = $_POST['description'];
    
    // Handle Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "public/uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = APP_URL . "/public/uploads/" . $file_name;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO products (name, brand, category, price, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $brand, $category, $price, $desc, $image_url]);
    $message = "Ürün başarıyla eklendi.";
}

if (isset($_POST['update_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $category = $_POST['category'];
    $price = (float)$_POST['price'];
    $desc = $_POST['description'];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "public/uploads/";
        $file_ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = uniqid() . "." . $file_ext;
        $target_file = $target_dir . $file_name;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = APP_URL . "/public/uploads/" . $file_name;
            $stmt = $pdo->prepare("UPDATE products SET name=?, brand=?, category=?, price=?, description=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $brand, $category, $price, $desc, $image_url, $id]);
        }
    } else {
        $stmt = $pdo->prepare("UPDATE products SET name=?, brand=?, category=?, price=?, description=? WHERE id=?");
        $stmt->execute([$name, $brand, $category, $price, $desc, $id]);
    }
    $message = "Ürün güncellendi.";
}

if (isset($_GET['delete_product'])) {
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$_GET['delete_product']]);
    $message = "Ürün silindi.";
}

// Handle User Actions
if (isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $stmt = $pdo->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
    $stmt->execute([$name, $email, $role, $id]);
    $message = "Kullanıcı bilgileri güncellendi.";
}

if (isset($_GET['delete_user'])) {
    if ($_GET['delete_user'] != $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$_GET['delete_user']]);
        $message = "Kullanıcı silindi.";
    } else {
        $message = "Kendi hesabınızı silemezsiniz!";
    }
}

if (isset($_POST['update_order_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE product_orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
    $message = "Sipariş durumu güncellendi.";
}

// Data Fetching
if ($page === 'stats') {
    $total_revenue = $pdo->query("SELECT SUM(total_amount) FROM product_orders WHERE status = 'teslim edildi' OR status = 'completed'")->fetchColumn() ?: 0;
    $total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $total_barbers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'barber'")->fetchColumn();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM product_orders")->fetchColumn();
    
    $recent_orders = $pdo->query("SELECT * FROM product_orders ORDER BY created_at DESC LIMIT 10")->fetchAll();
} elseif ($page === 'users') {
    $users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
} elseif ($page === 'products') {
    $products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
} elseif ($page === 'orders') {
    $orders = $pdo->query("SELECT * FROM product_orders ORDER BY created_at DESC")->fetchAll();
}

require_once 'views/header.php';
?>

<div class="container-fluid py-4" style="margin-top: 80px;">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-2">
            <div class="list-group shadow-sm border-0 mb-4 sticky-top" style="top: 100px;">
                <a href="?page=stats" class="list-group-item list-group-item-action <?= $page==='stats'?'active':'' ?>">
                    <i class="fas fa-chart-line me-2"></i>Dashboard
                </a>
                <a href="?page=users" class="list-group-item list-group-item-action <?= $page==='users'?'active':'' ?>">
                    <i class="fas fa-users me-2"></i>Kullanıcılar
                </a>
                <a href="?page=products" class="list-group-item list-group-item-action <?= $page==='products'?'active':'' ?>">
                    <i class="fas fa-box me-2"></i>Ürün Yönetimi
                </a>
                <a href="?page=orders" class="list-group-item list-group-item-action <?= $page==='orders'?'active':'' ?>">
                    <i class="fas fa-shopping-cart me-2"></i>Siparişler
                </a>
                <a href="<?= APP_URL ?>/index.php" class="list-group-item list-group-item-action">
                    <i class="fas fa-external-link-alt me-2"></i>Siteye Dön
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-10">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($page === 'stats'): ?>
                <h3 class="mb-4">Sistem Özeti</h3>
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-primary text-white">
                            <div class="card-body py-4 text-center">
                                <h6 class="text-uppercase mb-2 opacity-75 small">Toplam Ciro</h6>
                                <h2 class="mb-0">₺<?= number_format($total_revenue, 2, ',', '.') ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-success text-white">
                            <div class="card-body py-4 text-center">
                                <h6 class="text-uppercase mb-2 opacity-75 small">Kayıtlı Kullanıcı</h6>
                                <h2 class="mb-0"><?= $total_users ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-info text-white">
                            <div class="card-body py-4 text-center">
                                <h6 class="text-uppercase mb-2 opacity-75 small">Kuaför Sayısı</h6>
                                <h2 class="mb-0"><?= $total_barbers ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm bg-warning text-white">
                            <div class="card-body py-4 text-center">
                                <h6 class="text-uppercase mb-2 opacity-75 small">Sipariş Sayısı</h6>
                                <h2 class="mb-0"><?= $total_orders ?></h2>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Son Siparişler</h5>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Sipariş ID</th>
                                        <th>Müşteri</th>
                                        <th>Tutar</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_orders as $o): ?>
                                    <tr>
                                        <td>#<?= $o['id'] ?></td>
                                        <td><?= htmlspecialchars($o['full_name']) ?></td>
                                        <td>₺<?= number_format($o['total_amount'], 2, ',', '.') ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <select name="status" class="form-select form-select-sm py-0" style="width: auto;" onchange="this.form.submit()">
                                                    <option value="hazirlaniyor" <?= $o['status']==='hazirlaniyor'?'selected':'' ?>>Hazırlanıyor</option>
                                                    <option value="kargoda" <?= $o['status']==='kargoda'?'selected':'' ?>>Kargoda</option>
                                                    <option value="teslim edildi" <?= $o['status']==='teslim edildi'?'selected':'' ?>>Teslim Edildi</option>
                                                    <option value="completed" <?= $o['status']==='completed'?'selected':'' ?>>Tamamlandı</option>
                                                </select>
                                                <input type="hidden" name="update_order_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($page === 'orders'): ?>
                <h3 class="mb-4">Tüm Siparişler</h3>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Müşteri</th>
                                        <th>İletişim</th>
                                        <th>Tutar</th>
                                        <th>Tarih</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($orders as $o): ?>
                                    <tr>
                                        <td>#<?= $o['id'] ?></td>
                                        <td><?= htmlspecialchars($o['full_name']) ?></td>
                                        <td class="small"><?= htmlspecialchars($o['phone']) ?><br><?= htmlspecialchars($o['city']) ?></td>
                                        <td>₺<?= number_format($o['total_amount'], 2, ',', '.') ?></td>
                                        <td><?= date('d.m.Y H:i', strtotime($o['created_at'])) ?></td>
                                        <td>
                                            <form method="POST" class="d-flex gap-1">
                                                <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                                <select name="status" class="form-select form-select-sm py-1" onchange="this.form.submit()">
                                                    <option value="hazirlaniyor" <?= $o['status']==='hazirlaniyor'?'selected':'' ?>>Hazırlanıyor</option>
                                                    <option value="kargoda" <?= $o['status']==='kargoda'?'selected':'' ?>>Kargoda</option>
                                                    <option value="teslim edildi" <?= $o['status']==='teslim edildi'?'selected':'' ?>>Teslim Edildi</option>
                                                    <option value="completed" <?= $o['status']==='completed'?'selected':'' ?>>Tamamlandı</option>
                                                </select>
                                                <input type="hidden" name="update_order_status" value="1">
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($page === 'users'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Kullanıcı Yönetimi</h3>
                </div>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>İsim</th>
                                        <th>Email</th>
                                        <th>Rol</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($users as $u): ?>
                                    <tr>
                                        <td><?= $u['id'] ?></td>
                                        <td><?= htmlspecialchars($u['name']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?= $u['role']==='admin'?'bg-danger':($u['role']==='barber'?'bg-info':'bg-secondary') ?>">
                                                <?= $u['role'] ?>
                                            </span>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editUser<?= $u['id'] ?>">Düzenle</button>
                                            <a href="?page=users&delete_user=<?= $u['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Emin misiniz?')">Sil</a>
                                        </td>
                                    </tr>

                                    <!-- Edit User Modal -->
                                    <div class="modal fade" id="editUser<?= $u['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <form method="POST" class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Kullanıcı Düzenle</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label">İsim</label>
                                                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($u['name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Email</label>
                                                        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($u['email']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">Rol</label>
                                                        <select name="role" class="form-select">
                                                            <option value="user" <?= $u['role']==='user'?'selected':'' ?>>Üye (User)</option>
                                                            <option value="barber" <?= $u['role']==='barber'?'selected':'' ?>>Kuaför Sahibi (Barber)</option>
                                                            <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                                    <button type="submit" name="update_user" class="btn btn-primary">Güncelle</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            <?php elseif ($page === 'products'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="mb-0">Ürün Yönetimi</h3>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProduct">Yeni Ürün Ekle</button>
                </div>

                <div class="row g-3">
                    <?php foreach($products as $p): ?>
                        <div class="col-md-4">
                            <div class="card card-custom h-100 border-0 shadow-sm">
                                <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://via.placeholder.com/300x200?text=Urun') ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <h5 class="card-title mb-1"><?= htmlspecialchars($p['name']) ?></h5>
                                        <span class="text-primary fw-bold">₺<?= number_format($p['price'], 2, ',', '.') ?></span>
                                    </div>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($p['brand']) ?> - <?= htmlspecialchars($p['category']) ?></p>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary flex-grow-1" data-bs-toggle="modal" data-bs-target="#editProduct<?= $p['id'] ?>">Düzenle</button>
                                        <a href="?page=products&delete_product=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Emin misiniz?')">Sil</a>
                                    </div>
                                </div>
                            </div>

                            <!-- Edit Product Modal -->
                            <div class="modal fade" id="editProduct<?= $p['id'] ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <form method="POST" enctype="multipart/form-data" class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Ürünü Güncelle</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <div class="mb-3">
                                                <label class="form-label">Ürün Adı</label>
                                                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Marka</label>
                                                    <input type="text" name="brand" class="form-control" value="<?= htmlspecialchars($p['brand']) ?>" required>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Kategori</label>
                                                    <input type="text" name="category" class="form-control" value="<?= htmlspecialchars($p['category']) ?>" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Fiyat (TL)</label>
                                                <input type="number" step="0.01" name="price" class="form-control" value="<?= $p['price'] ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Açıklama</label>
                                                <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($p['description']) ?></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Ürün Görseli (Boş bırakırsanız değişmez)</label>
                                                <input type="file" name="image" class="form-control" accept="image/*">
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                            <button type="submit" name="update_product" class="btn btn-primary">Güncelle</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Add Product Modal -->
                <div class="modal fade" id="addProduct" tabindex="-1">
                    <div class="modal-dialog">
                        <form method="POST" enctype="multipart/form-data" class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Yeni Ürün Ekle</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Ürün Adı</label>
                                    <input type="text" name="name" class="form-control" placeholder="Örn: Saç Bakım Yağı" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Marka</label>
                                        <input type="text" name="brand" class="form-control" placeholder="Örn: L'Oreal" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Kategori</label>
                                        <input type="text" name="category" class="form-control" placeholder="Örn: Bakım" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Fiyat (TL)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Açıklama</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Ürün Görseli</label>
                                    <input type="file" name="image" class="form-control" accept="image/*" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Vazgeç</button>
                                <button type="submit" name="add_product" class="btn btn-primary">Ürünü Ekle</button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
