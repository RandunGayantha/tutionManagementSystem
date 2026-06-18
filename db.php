<?php
// ============================================
// db.php - Database Connection (MSSQL)
// ============================================
define('DB_HOST', 'DESKTOP-J4BHA1A\MSSQLSERVER01'); // <-- Updated with your exact server name
define('DB_NAME', 'tuition_db');

function getDB() {
    $connectionInfo = array(
        "Database"      => DB_NAME,
        "CharacterSet"  => "UTF-8",
        "TrustServerCertificate" => true
    );

    $conn = sqlsrv_connect(DB_HOST, $connectionInfo);

    if ($conn === false) {
        $errors = sqlsrv_errors();
        die("<div style='color:red;padding:20px;font-family:sans-serif;'>
            <h3>Database Connection Failed</h3>
            <p>" . $errors[0]['message'] . "</p>
            <p>Make sure SQL Server is running and database <strong>" . DB_NAME . "</strong> exists.</p>
        </div>");
    }

    return $conn;
}
?>