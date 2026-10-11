<?php
// ─────────────────────────────────────────────
//  db.php  –  Database Connection
//  Save this file in: C:/xampp/htdocs/form/
// ─────────────────────────────────────────────

// These are the settings PHP needs to connect to MySQL.
$host     = "localhost";   // MySQL server address (always localhost in XAMPP)
$db_name  = "user_auth";  // The name of our database
$username = "root";        // Default MySQL username in XAMPP
$password = "";            // Default MySQL password in XAMPP (empty)

// Try to create a PDO connection.
// PDO is a safe way to talk to the database.
// We wrap it in try/catch so if something goes wrong, we show a clear message.
try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8",
        $username,
        $password
    );

    // This tells PDO to throw an error (exception) instead of silently failing.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

} catch (PDOException $e) {
    // If the connection fails, stop everything and show the error.
    die("Connection failed: " . $e->getMessage());
}
// After this file runs, $pdo is ready to use everywhere we include this file.
?>
