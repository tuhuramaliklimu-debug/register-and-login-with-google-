<?php
// ─────────────────────────────────────────────────────────────
//  google-callback.php  –  Google OAuth 2.0 Callback Handler
//  Google redirects here after the user approves (or denies) login.
//  Uses plain PHP + cURL — NO Composer/vendor required.
// ─────────────────────────────────────────────────────────────

session_start();
require 'db.php';
require 'google-oauth.php';

// ── Step 1: Check for errors from Google ──
// If the user clicked "Cancel" on the Google consent screen, Google sends ?error=access_denied
if (isset($_GET['error'])) {
    $msg = urlencode('Google sign-in was cancelled: ' . htmlspecialchars($_GET['error']));
    header("Location: login.php?error=$msg");
    exit;
}

// ── Step 2: Verify the CSRF state token ──
// We stored a random token in $_SESSION['oauth_state'] before redirecting to Google.
// Google echoes it back in ?state=. If they don't match, something is wrong.
if (
    empty($_GET['state']) ||
    empty($_SESSION['oauth_state']) ||
    !hash_equals($_SESSION['oauth_state'], $_GET['state'])
) {
    $msg = urlencode('Invalid OAuth state. Please try signing in again.');
    header("Location: login.php?error=$msg");
    exit;
}
// Clear the used state token so it cannot be replayed.
unset($_SESSION['oauth_state']);

// ── Step 3: Make sure we received an authorization code ──
if (empty($_GET['code'])) {
    $msg = urlencode('No authorization code received from Google.');
    header("Location: login.php?error=$msg");
    exit;
}

// ── Step 4: Exchange the authorization code for an access token ──
$token_data = google_exchange_code($_GET['code']);

if (empty($token_data['access_token'])) {
    $detail = $token_data['error_description'] ?? ($token_data['error'] ?? 'unknown error');
    $msg = urlencode('Failed to get access token from Google: ' . $detail);
    header("Location: login.php?error=$msg");
    exit;
}

// ── Step 5: Use the access token to fetch the user's Google profile ──
$google_user = google_get_user($token_data['access_token']);

if (!$google_user || empty($google_user['email'])) {
    $msg = urlencode('Could not retrieve your Google profile. Please try again.');
    header("Location: login.php?error=$msg");
    exit;
}

// ── Step 6: Find or create the user in our database ──
$email     = $google_user['email'];
$full_name = $google_user['name']    ?? $email;
$google_id = $google_user['sub']     ?? '';   // Google's permanent unique user ID

// Check if this email already exists.
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    // ── Existing user: update google_id if not already stored ──
    if (empty($user['google_id'])) {
        $upd = $pdo->prepare("UPDATE users SET google_id = :gid WHERE id = :id");
        $upd->execute([':gid' => $google_id, ':id' => $user['id']]);
    }
} else {
    // ── New user: auto-create an account ──
    // Derive a base username from the email prefix (letters/numbers only).
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', strstr($email, '@', true)));
    $username = $base;
    $suffix   = 1;
    // Keep trying until we find a unique username.
    while (true) {
        $chk = $pdo->prepare("SELECT id FROM users WHERE username = :u LIMIT 1");
        $chk->execute([':u' => $username]);
        if ($chk->rowCount() === 0) break;
        $username = $base . $suffix++;
    }

    // Insert — password is NULL because Google users don't use a password.
    $ins = $pdo->prepare(
        "INSERT INTO users (full_name, email, username, password, google_id)
         VALUES (:full_name, :email, :username, NULL, :google_id)"
    );
    $ins->execute([
        ':full_name' => $full_name,
        ':email'     => $email,
        ':username'  => $username,
        ':google_id' => $google_id,
    ]);

    // Fetch the newly created user row.
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// ── Step 7: Log the user in ──
$_SESSION['user_id']   = $user['id'];
$_SESSION['user_name'] = $user['full_name'];

header("Location: dashboard.php");
exit;