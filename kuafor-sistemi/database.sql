CREATE DATABASE IF NOT EXISTS kuafor_sistemi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kuafor_sistemi;

-- Kullanıcılar Tablosu (Tüm roller)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'barber_owner', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Kuaförler/Mağaza Profilleri
CREATE TABLE IF NOT EXISTS barbers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    shop_name VARCHAR(150) NOT NULL,
    address TEXT,
    description TEXT,
    opening_time TIME DEFAULT '09:00:00',
    closing_time TIME DEFAULT '20:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Kuaförler tarafından sunulan hizmetler
CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    barber_id INT NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    duration INT DEFAULT 30, -- in minutes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE
);

-- Randevular
CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    barber_id INT NOT NULL,
    service_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    user_note TEXT,
    rating INT NULL,
    review TEXT NULL,
    barber_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE,
    UNIQUE KEY unique_appointment (barber_id, appointment_date, appointment_time)
);

-- Ödemeler
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method ENUM('cash', 'card') NOT NULL,
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
);

-- Bildirimler
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, -- Both users and barbers
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Test Verilerini Ekle (Tüm şifreler: password123)
INSERT INTO users (name, email, password, role) VALUES 
('Admin Yılmaz', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ali Kuaför', 'ali@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber_owner'),
('Veli Kuaför', 'veli@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'barber_owner'),
('Ahmet Kullanıcı', 'ahmet@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO barbers (user_id, shop_name, address, description, opening_time, closing_time) VALUES 
(2, 'Ali Saç Tasarım', 'Kadıköy, İstanbul', 'En iyi saç kesimi', '08:00:00', '21:00:00'),
(3, 'Veli Erkek Kuaförü', 'Beşiktaş, İstanbul', 'Modern saç ve sakal tasarımı', '09:00:00', '20:00:00');

INSERT INTO services (barber_id, service_name, price, duration) VALUES 
(1, 'Saç Kesimi', 300.00, 30),
(1, 'Sakal Kesimi', 200.00, 20),
(1, 'Saç/Sakal Kombin', 500.00, 50),
(2, 'Saç Kesimi', 350.00, 30),
(2, 'Keratin Bakımı', 800.00, 60);

INSERT INTO appointments (user_id, barber_id, service_id, appointment_date, appointment_time, status, user_note) VALUES
(4, 1, 3, CURRENT_DATE, '14:00:00', 'confirmed', 'Lütfen tam vaktinde alınız.');

INSERT INTO payments (appointment_id, amount, payment_method, status) VALUES
(1, 500.00, 'card', 'success');

INSERT INTO notifications (user_id, message) VALUES
(2, 'Yeni bir randevunuz var! Ahmet Kullanıcı - 14:00');
