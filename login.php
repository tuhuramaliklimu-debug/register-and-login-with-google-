<?php
// ─────────────────────────────────────────────
//  login.php  –  Login Page
//  Save this file in: C:/xampp/htdocs/form/
// ─────────────────────────────────────────────

// Start the session so we can read and write session variables.
session_start();

// Include the database connection ($pdo becomes available).
require "db.php";
require "google-oauth.php";

// If the user is already logged in, skip the login page.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

// ── Generate a CSRF state token for Google OAuth ──
// This random token is stored in the session and compared in the callback
// to prevent CSRF attacks on the OAuth flow.
if (empty($_SESSION['oauth_state'])) {
    $_SESSION['oauth_state'] = bin2hex(random_bytes(16));
}
$google_login_url = google_auth_url();

// Show any OAuth error passed back from google-callback.php
if (!empty($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// ── Handle the login form ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read what the user typed.
    // $login_input can be either an email or a username.
    $login_input = trim($_POST['login_input']);
    $password    = $_POST['password'];

    // ── Step 1: Make sure neither field is blank ──
    if (empty($login_input) || empty($password)) {
        $error = "Please enter your email/username and password.";

    } else {
        // ── Step 2: Look up the user in the database ──
        // We check both the email column AND the username column.
        // If either matches, we fetch that row.
        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = :login OR username = :login LIMIT 1"
        );
        // We pass the same value for both :login placeholders.
        $stmt->execute([':login' => $login_input]);

        // fetch() returns one row as an array, or false if nothing was found.
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // ── Step 3: Check if the user exists AND the password is correct ──
        // password_verify() compares the plain-text password the user typed
        // with the hashed password stored in the database.
        if ($user && password_verify($password, $user['password'])) {

            // ── Step 4: Save user info in the session ──
            // The session stores data on the server and gives the browser
            // a cookie with a session ID so PHP can recognize the user.
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];

            // ── Step 5: Redirect to the dashboard ──
            header("Location: dashboard.php");
            exit; // Always call exit after header() to stop the rest of the code.

        } else {
            // Wrong email/username or wrong password.
            $error = "Incorrect email/username or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – User Auth</title>
    <meta name="description" content="Log in to your account.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">

    <div class="card-icon">🔐</div>

    <h1>Welcome Back</h1>
    <p class="subtitle">Log in to access your dashboard</p>

    <!-- Show error message when login fails -->
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="" method="post">

        <div class="form-group">
            <label for="login_input">Email or Username</label>
            <input
                type="text"
                id="login_input"
                name="login_input"
                placeholder="john@example.com or johndoe"
                value="<?php echo isset($_POST['login_input']) ? htmlspecialchars($_POST['login_input']) : ''; ?>"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="Your password"
            >
        </div>

        <button type="submit" class="btn">Log In</button>
    </form>

    <!-- ── Google Sign-In ── -->
    <div class="divider"><span>or</span></div>

    <a href="<?php echo htmlspecialchars($google_login_url); ?>" class="btn-google" id="google-signin-btn">
        <svg width="20" height="20" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.31-8.16 2.31-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            <path fill="none" d="M0 0h48v48H0z"/>
        </svg>
        Sign in with Google
    </a>

    <p class="switch-link">
        Don't have an account? <a href="register.php">Register here</a>
    </p>

</div>

</body>
</html>
