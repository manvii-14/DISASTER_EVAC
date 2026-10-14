<?php
include "db.php";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full = trim($_POST['full_name']);
    $user = trim($_POST['username']);
    $pass = $_POST['password'];
    $pass2 = $_POST['confirm'];

    if ($full == "" || $user == "" || $pass == "") {
        $msg = "<div class='error'>Please fill all the fields.</div>";
    } elseif ($pass != $pass2) {
        $msg = "<div class='error'>Passwords do not match.</div>";
    } else {
        $hash = password_hash($pass, PASSWORD_DEFAULT);
        try {
            $stmt = $conn->prepare("INSERT INTO Users (Full_Name, Username, Password_Hash) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full, $user, $hash);
            $stmt->execute();
            $msg = "<div class='ok'>Account created. You can <a href='login.php'>login</a> now.</div>";
        } catch (mysqli_sql_exception $e) {
            $msg = "<div class='error'>That username is already taken.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account | Evacuation Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">

<div class="auth-layout">
    <section class="auth-visual">
        <a href="index.php" class="auth-brand">
            <span class="brand-mark">E</span>
            <span>
                <strong>Evacuation</strong>
                <small>Emergency Management</small>
            </span>
        </a>

        <div class="auth-visual-content">
            <span class="auth-kicker">JOIN THE WORKSPACE</span>
            <h1>One workspace.<br>Better coordination.</h1>
            <p>Create an account to access the evacuation and emergency transport management system.</p>

            <div class="auth-highlight">
                <span class="auth-highlight-mark">✓</span>
                <div>
                    <strong>Centralised operations</strong>
                    <p>Manage response information through one organised interface.</p>
                </div>
            </div>
        </div>

        <p class="auth-visual-footer">EVACUATION &amp; EMERGENCY TRANSPORT MANAGEMENT</p>
    </section>

    <main class="auth-main">
        <div class="auth-form-wrap">
            <div class="auth-mobile-brand">
                <span class="brand-mark">E</span>
                <span>
                    <strong>Evacuation</strong>
                    <small>Emergency Management</small>
                </span>
            </div>

            <p class="page-eyebrow">GET STARTED</p>
            <h2>Create your account</h2>
            <p class="auth-subtitle">Fill in your details to set up your account.</p>

            <?php echo $msg; ?>

            <form method="post" class="auth-form">
                <div class="form-field">
                    <label for="register-full-name">Full name</label>
                    <input id="register-full-name" type="text" name="full_name"
                           placeholder="Enter your full name" autocomplete="name" required>
                </div>

                <div class="form-field">
                    <label for="register-username">Username</label>
                    <input id="register-username" type="text" name="username"
                           placeholder="Choose a username" autocomplete="username" required>
                </div>

                <div class="form-field">
                    <label for="register-password">Password</label>
                    <input id="register-password" type="password" name="password"
                           placeholder="Create a password" autocomplete="new-password" required>
                </div>

                <div class="form-field">
                    <label for="register-confirm">Confirm password</label>
                    <input id="register-confirm" type="password" name="confirm"
                           placeholder="Enter your password again" autocomplete="new-password" required>
                </div>

                <button type="submit" class="auth-submit">Create account <span>→</span></button>
            </form>

            <p class="auth-switch">
                Already registered? <a href="login.php">Sign in</a>
            </p>

            <p class="auth-legal">Your account credentials are handled by the application.</p>
        </div>
    </main>
</div>

</body>
</html>
