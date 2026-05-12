<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kuafor_sistemi');
define('APP_URL', 'http://localhost/kuafor-sistemi-zip/kuafor-sistemi');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Helpers
function redirect($url) {
    header("Location: " . APP_URL . $url);
    exit;
}

function auth() {
    return isset($_SESSION['user_id']) ? $_SESSION : false;
}

function check_role($role) {
    $user = auth();
    if (!$user || $user['role'] !== $role) {
        redirect('/login.php');
    }
}

function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function get_status_label($status) {
    $map = [
        'hazirlaniyor' => ['label' => 'Hazırlanıyor', 'color' => 'primary'],
        'kargoda' => ['label' => 'Kargoda', 'color' => 'info'],
        'teslim edildi' => ['label' => 'Teslim Edildi', 'color' => 'success'],
        'completed' => ['label' => 'Tamamlandı', 'color' => 'success'],
        'tamamlandi' => ['label' => 'Tamamlandı', 'color' => 'success']
    ];
    return $map[$status] ?? ['label' => ucfirst($status), 'color' => 'secondary'];
}
?>
