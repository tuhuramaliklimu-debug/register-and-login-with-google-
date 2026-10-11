<?php
// ─────────────────────────────────────────────
//  dashboard.php  –  Dashboard (Protected Page)
//  Save this file in: C:/xampp/htdocs/form/
// ─────────────────────────────────────────────

// Start the session FIRST – before reading $_SESSION.
session_start();

// ── Protect this page ──
// If 'user_id' is NOT in the session, the user is not logged in.
// Send them to the login page immediately.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Include the database connection.
require "db.php";

// ── Fetch fresh user data from the database ──
// We use the session's user_id to find the right row.
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// If the user somehow doesn't exist (e.g., deleted), log them out.
if (!$user) {
    header("Location: logout.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – User Auth</title>
    <meta name="description" content="Your personal dashboard.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card dashboard-card">

    <!-- Header row: greeting on left, logout button on right -->
    <div class="dashboard-header">
        <div>
            <h1>👋 Hello, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p class="subtitle" style="text-align:left; margin-bottom:0;">
                You are successfully logged in.
            </p>
        </div>
        <!-- Logout link styled as a button -->
        <a href="logout.php" class="logout-btn">Log Out</a>
    </div>

    <!-- Display the user's details in a simple grid -->
    <div class="info-grid">

        <div class="info-box">
            <div class="info-label">Full Name</div>
            <div class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></div>
        </div>

        <div class="info-box">
            <div class="info-label">Username</div>
            <div class="info-value">@<?php echo htmlspecialchars($user['username']); ?></div>
        </div>

        <div class="info-box">
            <div class="info-label">Email Address</div>
            <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
        </div>

        <div class="info-box">
            <div class="info-label">Member Since</div>
            <!--
                date() formats the timestamp.
                "M j, Y" produces something like "Oct 11, 2026".
                strtotime() converts the database date string to a Unix timestamp.
            -->
            <div class="info-value">
                <?php echo date("M j, Y", strtotime($user['created_at'])); ?>
            </div>
        </div>

    </div>

</div>

</body>
</html>
