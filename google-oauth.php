<?php
// ─────────────────────────────────────────────────────────────
//  google-oauth.php  –  Google OAuth 2.0 Helper
//  Loads credentials from .env and provides helper functions.
// ─────────────────────────────────────────────────────────────

// ── Load the .env file into PHP constants ──
function load_env(string $path): void {
    if (!file_exists($path)) {
        die("Error: .env file not found at $path");
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        // Skip comments
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Load .env from the same directory as this file.
load_env(__DIR__ . '/.env');

define('GOOGLE_CLIENT_ID',     $_ENV['GOOGLE_CLIENT_ID']     ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');
define('GOOGLE_REDIRECT_URI',  $_ENV['GOOGLE_REDIRECT_URI']  ?? '');

// Google OAuth 2.0 endpoints
define('GOOGLE_AUTH_URL',  'https://accounts.google.com/o/oauth2/v2/auth');
define('GOOGLE_TOKEN_URL', 'https://oauth2.googleapis.com/token');
define('GOOGLE_USER_URL',  'https://www.googleapis.com/oauth2/v3/userinfo');

// ── Build the Google authorization URL ──
// The user is redirected here to log in with Google and grant permission.
function google_auth_url(): string {
    $params = [
        'client_id'     => GOOGLE_CLIENT_ID,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'response_type' => 'code',
        'scope'         => 'openid email profile',
        'access_type'   => 'online',
        'prompt'        => 'select_account',
        // CSRF protection: a random token we verify in the callback.
        'state'         => $_SESSION['oauth_state'] ?? '',
    ];
    return GOOGLE_AUTH_URL . '?' . http_build_query($params);
}

// ── Exchange the authorization code for an access token ──
// After the user approves, Google sends a short-lived "code" to our callback.
// We swap that code for an access_token using a server-to-server POST request.
function google_exchange_code(string $code): ?array {
    $post = [
        'code'          => $code,
        'client_id'     => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri'  => GOOGLE_REDIRECT_URI,
        'grant_type'    => 'authorization_code',
    ];

    $ch = curl_init(GOOGLE_TOKEN_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($post),
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return null;
    return json_decode($response, true);
}

// ── Fetch the Google user's profile using the access token ──
function google_get_user(string $access_token): ?array {
    $ch = curl_init(GOOGLE_USER_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["Authorization: Bearer $access_token"],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $err      = curl_error($ch);
    curl_close($ch);

    if ($err) return null;
    $data = json_decode($response, true);
    return (isset($data['email'])) ? $data : null;
}
