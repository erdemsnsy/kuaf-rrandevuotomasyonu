<?php
require_once 'config/database.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barber_id INT NOT NULL,
        user_id INT NOT NULL,
        rating INT NOT NULL DEFAULT 5,
        review TEXT,
        barber_reply TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (barber_id) REFERENCES barbers(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "Reviews table created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
