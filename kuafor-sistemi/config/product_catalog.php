<?php

function ensure_product_store_schema($pdo) {
    static $initialized = false;
    if ($initialized) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            barber_user_id INT NULL,
            name VARCHAR(150) NOT NULL,
            brand VARCHAR(120) NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            ingredients TEXT,
            usage_instructions TEXT,
            image_url TEXT,
            price DECIMAL(10,2) NOT NULL,
            stock INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_products_category (category),
            INDEX idx_products_price (price),
            INDEX idx_products_name (name)
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            full_name VARCHAR(120) NOT NULL,
            phone VARCHAR(30) NOT NULL,
            city VARCHAR(100) NOT NULL,
            district VARCHAR(100) NOT NULL,
            address TEXT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('hazirlaniyor', 'kargoda', 'tamamlandi') DEFAULT 'hazirlaniyor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS product_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NULL,
            product_name VARCHAR(150) NOT NULL,
            brand VARCHAR(120) NOT NULL,
            category VARCHAR(100) NOT NULL,
            description TEXT,
            ingredients TEXT,
            usage_instructions TEXT,
            image_url TEXT,
            unit_price DECIMAL(10,2) NOT NULL,
            quantity INT NOT NULL,
            line_total DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES product_orders(id) ON DELETE CASCADE
        )
    ");

    $countStmt = $pdo->query("SELECT COUNT(*) AS total FROM products");
    $productCount = (int) ($countStmt->fetch()['total'] ?? 0);

    if ($productCount === 0) {
        $seedProducts = [
            ['Profesyonel Saç Spreyi', 'HairPro', 'Saç Bakım', 'Güçlü tutuş sağlar, saçta kalıntı bırakmaz.', 'Alkol denat, panthenol, bitkisel proteinler.', 'Kuru saça 20 cm mesafeden sıkın.', 'https://images.unsplash.com/photo-1522335789203-aabd1fc54bc9?auto=format&fit=crop&w=900&q=80', 189.90, 50],
            ['Nemlendirici Yüz Maskesi', 'DermaGlow', 'Cilt Bakım', 'Cildi canlandırır ve yoğun nem desteği sunar.', 'Hyalüronik asit, aloe vera, E vitamini.', 'Temiz cilde haftada 2 kez uygulayın.', 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=900&q=80', 149.90, 40],
            ['Keratin Şampuan', 'SalonCare', 'Saç Bakım', 'Yıpranmış saçları güçlendirmeye yardımcı olur.', 'Keratin kompleksi, biotin, argan yağı.', 'Islak saça masaj yaparak uygulayın ve durulayın.', 'https://images.unsplash.com/photo-1556228578-8c89e6adf883?auto=format&fit=crop&w=900&q=80', 219.90, 35],
            ['Sakal Bakım Yağı', 'Gentleman Lab', 'Erkek Bakım', 'Sakalları yumuşatır, parlak ve düzenli görünüm sağlar.', 'Jojoba yağı, badem yağı, E vitamini.', 'Avuç içine birkaç damla alıp sakala yedirin.', 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=900&q=80', 129.90, 60],
            ['Canlandırıcı Yüz Temizleyici', 'SkinPure', 'Cilt Bakım', 'Günlük kullanım için nazik ve etkili temizleme.', 'Niasinamid, yeşil çay özü, gliserin.', 'Sabah ve akşam nemli cilde uygulayın.', 'https://images.unsplash.com/photo-1608248597279-f99d160bfcbc?auto=format&fit=crop&w=900&q=80', 99.90, 70],
            ['Isı Koruyucu Saç Kremi', 'ThermoShield', 'Saç Bakım', 'Fön ve maşa öncesi saçları ısıya karşı korur.', 'İpek proteini, keratin, pantenol.', 'Şekillendirme öncesi nemli saça sürün.', 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=900&q=80', 174.90, 45],
        ];

        $insertStmt = $pdo->prepare("
            INSERT INTO products
            (barber_user_id, name, brand, category, description, ingredients, usage_instructions, image_url, price, stock, is_active)
            VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($seedProducts as $seed) {
            $insertStmt->execute($seed);
        }
    }

    $initialized = true;
}

function get_product_catalog($pdo, $filters = []) {
    ensure_product_store_schema($pdo);

    $where = ["is_active = 1"];
    $params = [];

    if (!empty($filters['category'])) {
        $where[] = "category = ?";
        $params[] = $filters['category'];
    }

    if (!empty($filters['search'])) {
        $where[] = "(name LIKE ? OR brand LIKE ? OR description LIKE ?)";
        $searchTerm = '%' . $filters['search'] . '%';
        $params[] = $searchTerm;
        $params[] = $searchTerm;
        $params[] = $searchTerm;
    }

    $sortMap = [
        'price_asc' => 'price ASC',
        'price_desc' => 'price DESC',
        'name_asc' => 'name ASC',
        'name_desc' => 'name DESC',
        'newest' => 'created_at DESC'
    ];
    $sortKey = $filters['sort'] ?? 'newest';
    $orderBy = $sortMap[$sortKey] ?? $sortMap['newest'];

    $sql = "SELECT * FROM products WHERE " . implode(' AND ', $where) . " ORDER BY {$orderBy}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    $products = [];
    foreach ($rows as $row) {
        $products[(int) $row['id']] = $row;
    }
    return $products;
}

function get_product_by_id($pdo, $productId) {
    ensure_product_store_schema($pdo);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([(int) $productId]);
    return $stmt->fetch();
}

function get_product_categories($pdo) {
    ensure_product_store_schema($pdo);
    $stmt = $pdo->query("SELECT DISTINCT category FROM products WHERE is_active = 1 ORDER BY category ASC");
    return $stmt->fetchAll();
}

