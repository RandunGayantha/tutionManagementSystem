<?php
// ============================================
// db.php - Database Connection
// ============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change if needed
define('DB_PASS', '');           // Your MySQL password
define('DB_NAME', 'tuition_db');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<div style='color:red;padding:20px;font-family:sans-serif;'>
            <h3>Database Connection Failed</h3>
            <p>" . $conn->connect_error . "</p>
            <p>Make sure XAMPP MySQL is running and database <strong>" . DB_NAME . "</strong> exists.</p>
        </div>");
    }
    $conn->set_charset("utf8");
    return $conn;
}
?>
