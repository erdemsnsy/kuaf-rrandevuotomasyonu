<?php
require_once 'config/database.php';

try {
    // Add columns if they don't exist
    $pdo->exec("ALTER TABLE appointments ADD COLUMN rating INT NULL");
    $pdo->exec("ALTER TABLE appointments ADD COLUMN review TEXT NULL");
    $pdo->exec("ALTER TABLE appointments ADD COLUMN barber_reply TEXT NULL");
    echo "Columns added successfully";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "Columns already exist.";
        // Just add barber_reply if not exists
        try{
            $pdo->exec("ALTER TABLE appointments ADD COLUMN barber_reply TEXT NULL");
            echo " Barber reply column added.";
        }catch(PDOException $e2){}
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
