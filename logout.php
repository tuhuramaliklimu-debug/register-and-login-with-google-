<?php
// ─────────────────────────────────────────────
//  logout.php  –  Logout
//  Save this file in: C:/xampp/htdocs/form/
// ─────────────────────────────────────────────

// Start the session so we can access and destroy it.
session_start();

// ── Step 1: Remove all data stored in $_SESSION ──
// This clears the user's login info from the server.
session_unset();

// ── Step 2: Destroy the session completely ──
// This deletes the session file on the server and
// tells the browser to forget the session cookie.
session_destroy();

// ── Step 3: Send the user back to the login page ──
header("Location: login.php");
exit;
?>
