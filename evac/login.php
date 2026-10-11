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
<html>
<head><title>Login</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="loginbox">
    <h2>Evacuation System Login</h2>
    <?php echo $msg; ?>
    <form method="post" class="box" style="width:auto">
        <label>Username</label><input type="text" name="username">
        <label>Password</label><input type="password" name="password">
        <input type="submit" value="Login">
    </form>
    <a href="register.php">Create a new account</a>
</div>
</body>
</html>
