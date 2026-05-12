<?php
require_once 'config/database.php';
require_once 'config/product_catalog.php';

try {
    // 1. Tablo Yapılarını Hazırla
    ensure_product_store_schema($pdo);
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (id INT AUTO_INCREMENT PRIMARY KEY, barber_id INT NOT NULL, user_id INT NOT NULL, rating INT NOT NULL DEFAULT 5, review TEXT, barber_reply TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE, FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE)");
    
    // Eksik sütunları ekle
    try {
        $pdo->exec("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS rating INT NULL");
        $pdo->exec("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS review TEXT NULL");
        $pdo->exec("ALTER TABLE appointments ADD COLUMN IF NOT EXISTS barber_reply TEXT NULL");
    } catch (Exception $e) {}

    try {
        $pdo->exec("ALTER TABLE barbers ADD COLUMN IF NOT EXISTS closed_day VARCHAR(20) NULL AFTER closing_time");
    } catch (Exception $e) {}

    // 2. Kullanıcıları Temizle ve Ekle
    $password = password_hash('password123', PASSWORD_BCRYPT);
    $users = [
        ['Admin Yılmaz', 'admin@example.com', $password, 'admin'],
        ['Ali Kuaför', 'ali@example.com', $password, 'barber_owner'],
        ['Veli Kuaför', 'veli@example.com', $password, 'barber_owner'],
        ['Mehmet Stil', 'mehmet@example.com', $password, 'barber_owner'],
        ['Ayşe Güzellik', 'ayse@example.com', $password, 'barber_owner'],
        ['Karizma Erkek', 'karizma@example.com', $password, 'barber_owner'],
        ['Ahmet Kullanıcı', 'ahmet@example.com', $password, 'user']
    ];

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE services");
    $pdo->exec("TRUNCATE TABLE barbers");
    $pdo->exec("DELETE FROM users WHERE email LIKE '%@example.com' OR email IN ('mehmet@example.com', 'ayse@example.com', 'karizma@example.com')");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    foreach ($users as $u) { $stmt->execute($u); }

    // 3. Kuaförleri Ekle (Artık kapalı gün bilgisiyle)
    $barbers = [
        ['ali@example.com', 'Ali Saç Tasarım', 'Kadıköy, İstanbul', 'Modern saç kesiminin adresi.', '09:00:00', '20:00:00', 'Sunday'],
        ['veli@example.com', 'Veli Erkek Kuaförü', 'Beşiktaş, İstanbul', 'Klasik ve modern tıraş teknikleri.', '08:30:00', '21:00:00', 'Sunday'],
        ['mehmet@example.com', 'Mehmet Stil Studio', 'Şişli, İstanbul', 'Tarzınızı biz belirleyelim.', '10:00:00', '22:00:00', 'Monday'],
        ['ayse@example.com', 'Ayşe Bayan Kuaförü', 'Nişantaşı, İstanbul', 'Güzelliğinize değer katın.', '09:00:00', '19:00:00', 'Sunday'],
        ['karizma@example.com', 'Karizma Men Care', 'Levent, İstanbul', 'Premium erkek bakım hizmetleri.', '08:00:00', '20:00:00', 'Sunday']
    ];

    $b_stmt = $pdo->prepare("INSERT INTO barbers (user_id, shop_name, address, description, opening_time, closing_time, closed_day) VALUES ((SELECT id FROM users WHERE email = ?), ?, ?, ?, ?, ?, ?)");
    foreach ($barbers as $b) { $b_stmt->execute($b); }

    // 4. Hizmetleri Ekle
    $services = [
        ['Ali Saç Tasarım', 'Modern Saç Kesimi', 350.00, 45],
        ['Ali Saç Tasarım', 'Sakal Tasarımı', 200.00, 30],
        ['Ali Saç Tasarım', 'Saç Boyama', 750.00, 90],
        ['Veli Erkek Kuaförü', 'Klasik Tıraş', 250.00, 30],
        ['Veli Erkek Kuaförü', 'Saç Yıkama & Fon', 150.00, 20],
        ['Mehmet Stil Studio', 'VIP Saç Bakımı', 500.00, 60],
        ['Mehmet Stil Studio', 'Damat Tıraşı', 1200.00, 120],
        ['Ayşe Bayan Kuaförü', 'Fön Çekimi', 150.00, 30],
        ['Ayşe Bayan Kuaförü', 'Saç Kesimi & Şekillendirme', 600.00, 60],
        ['Ayşe Bayan Kuaförü', 'Manikür & Pedikür', 450.00, 60],
        ['Ayşe Bayan Kuaförü', 'Ombre / Sombre', 2500.00, 180],
        ['Karizma Men Care', 'Cilt Bakımı', 400.00, 45],
        ['Karizma Men Care', 'Saç Kesimi', 450.00, 40],
        ['Karizma Men Care', 'Kulak Burun Ağda', 100.00, 15]
    ];

    $s_stmt = $pdo->prepare("INSERT INTO services (barber_id, service_name, price, duration) VALUES ((SELECT id FROM barbers WHERE shop_name = ?), ?, ?, ?)");
    foreach ($services as $s) { $s_stmt->execute($s); }

    echo "<div style='font-family:sans-serif; padding:20px; border:2px solid #007bff; border-radius:10px; background:#f0f7ff; color:#004085;'>";
    echo "<h2>✅ RANDEVU SİSTEMİ DÜZELTİLDİ!</h2>";
    echo "<p>Eksik 'Kapalı Gün' sütunu eklendi ve tüm veriler güncellendi.</p>";
    echo "<hr>";
    echo "<a href='index.php' style='display:inline-block; padding:10px 20px; background:#007bff; color:white; text-decoration:none; border-radius:5px;'>Randevu Almayı Dene</a>";
    echo "</div>";

} catch (PDOException $e) { echo "Kritik Hata: " . $e->getMessage(); }
?>
