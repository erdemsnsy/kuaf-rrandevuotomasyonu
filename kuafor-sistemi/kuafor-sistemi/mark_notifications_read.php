<?php
require_once 'config/database.php';
if (auth()) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")->execute([auth()['user_id']]);
    echo "OK";
}
