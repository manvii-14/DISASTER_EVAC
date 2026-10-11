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
<html>
<head><title>Register</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="loginbox">
    <h2>Register</h2>
    <?php echo $msg; ?>
    <form method="post" class="box" style="width:auto">
        <label>Full name</label><input type="text" name="full_name">
        <label>Username</label><input type="text" name="username">
        <label>Password</label><input type="password" name="password">
        <label>Confirm password</label><input type="password" name="confirm">
        <input type="submit" value="Register">
    </form>
    <a href="login.php">Back to login</a>
</div>
</body>
</html>
