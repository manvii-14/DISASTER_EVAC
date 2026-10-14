<?php
session_start();
include "db.php";
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user = trim($_POST['username']);
    $pass = $_POST['password'];

    $stmt = $conn->prepare("SELECT Full_Name, Password_Hash FROM Users WHERE Username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row && password_verify($pass, $row['Password_Hash'])) {
        $_SESSION['user'] = $row['Full_Name'];
        header("Location: dashboard.php");
        exit;
    } else {
        $msg = "<div class='error'>Wrong username or password.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in | Evacuation Management</title>
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
            <span class="auth-kicker">EMERGENCY RESPONSE PLATFORM</span>
            <h1>Coordinating help.<br>Supporting lives.</h1>
            <p>A central workspace for managing incidents, response teams, transport and victim assignments.</p>

            <div class="auth-feature-list">
                <div class="auth-feature">
                    <span class="auth-feature-icon">01</span>
                    <div>
                        <strong>Incident coordination</strong>
                        <p>Keep response information organised.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <span class="auth-feature-icon">02</span>
                    <div>
                        <strong>Resource management</strong>
                        <p>Coordinate vehicles, drivers and facilities.</p>
                    </div>
                </div>
                <div class="auth-feature">
                    <span class="auth-feature-icon">03</span>
                    <div>
                        <strong>Transport tracking</strong>
                        <p>Follow assignments and dispatch status.</p>
                    </div>
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

            <p class="page-eyebrow">WELCOME BACK</p>
            <h2>Sign in to your account</h2>
            <p class="auth-subtitle">Enter your credentials to access the operations dashboard.</p>

            <?php echo $msg; ?>

            <form method="post" class="auth-form">
                <div class="form-field">
                    <label for="login-username">Username</label>
                    <input id="login-username" type="text" name="username"
                           placeholder="Enter your username" autocomplete="username" required>
                </div>

                <div class="form-field">
                    <label for="login-password">Password</label>
                    <input id="login-password" type="password" name="password"
                           placeholder="Enter your password" autocomplete="current-password" required>
                </div>

                <button type="submit" class="auth-submit">Sign in <span>→</span></button>
            </form>

            <p class="auth-switch">
                Don't have an account? <a href="register.php">Create one</a>
            </p>

            <p class="auth-legal">Authorised access for emergency management operations.</p>
        </div>
    </main>
</div>

</body>
</html>
