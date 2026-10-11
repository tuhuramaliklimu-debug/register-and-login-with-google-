<?php
// ─────────────────────────────────────────────
//  register.php  –  Registration Page
//  Save this file in: C:/xampp/htdocs/form/
// ─────────────────────────────────────────────

// Include the database connection file.
// After this line, $pdo is available and connected to MySQL.
require "db.php";

// Start the PHP session.
// Sessions let PHP remember who is logged in across page loads.
session_start();

// If the user is already logged in, send them to the dashboard.
// $_SESSION['user_id'] is set when someone logs in successfully.
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

// $error and $success hold messages to show the user.
$error   = "";
$success = "";

// ── Handle the form submission ──
// $_SERVER['REQUEST_METHOD'] tells us how the page was opened.
// "POST" means the user clicked the Register button.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Read what the user typed and remove extra spaces with trim().
    $full_name        = trim($_POST['full_name']);
    $email            = trim($_POST['email']);
    $username         = trim($_POST['username']);
    $password         = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // ── Step 1: Check that no field is empty ──
    if (empty($full_name) || empty($email) || empty($username) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required. Please fill in every box.";

    // ── Step 2: Check that the two passwords match ──
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match. Please try again.";

    // ── Step 3: Check password length (at least 6 characters) ──
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";

    // ── Step 4: Check for a valid email format ──
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";

    } else {
        // ── Step 5: Check if the email already exists in the database ──
        // A prepared statement keeps our SQL safe from hackers (SQL injection).
        // The :email is a placeholder – PDO replaces it with the real value safely.
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);

        if ($stmt->rowCount() > 0) {
            // rowCount() > 0 means a row was found → email is already taken.
            $error = "That email address is already registered. Please log in.";

        } else {
            // ── Step 6: Check if the username already exists ──
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->execute([':username' => $username]);

            if ($stmt->rowCount() > 0) {
                $error = "That username is already taken. Please choose another.";

            } else {
                // ── Step 7: Hash the password ──
                // NEVER store plain-text passwords!
                // password_hash() turns "mypassword" into a scrambled string like
                // "$2y$10$abc..." that cannot be reversed.
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // ── Step 8: Insert the new user into the database ──
                $stmt = $pdo->prepare(
                    "INSERT INTO users (full_name, email, username, password)
                     VALUES (:full_name, :email, :username, :password)"
                );
                $stmt->execute([
                    ':full_name' => $full_name,
                    ':email'     => $email,
                    ':username'  => $username,
                    ':password'  => $hashed_password,
                ]);

                // Registration was successful!
                $success = "Account created! You can now log in.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register – User Auth</title>
    <meta name="description" content="Create a new account to get started.">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="card">

    <!-- Icon at the top -->
    <div class="card-icon">✏️</div>

    <h1>Create Account</h1>
    <p class="subtitle">Fill in the details below to register</p>

    <!-- Show error message if $error is not empty -->
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Show success message if $success is not empty -->
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <!--
        The form sends data to this same page (register.php) using POST.
        action="" means "send to the current page".
        method="post" means the data is sent in the request body (not in the URL).
    -->
    <form action="" method="post" novalidate>

        <div class="form-group">
            <label for="full_name">Full Name</label>
            <input
                type="text"
                id="full_name"
                name="full_name"
                placeholder="John Doe"
                value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
            >
        </div>

        <div class="form-group">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="john@example.com"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
            >
        </div>

        <div class="form-group">
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                placeholder="johndoe"
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
            >
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                placeholder="At least 6 characters"
            >
        </div>

        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                placeholder="Re-type your password"
            >
        </div>

        <button type="submit" class="btn">Create Account</button>
    </form>

    <!-- Link to the login page -->
    <p class="switch-link">
        Already have an account? <a href="login.php">Log in here</a>
    </p>

</div>

</body>
</html>
