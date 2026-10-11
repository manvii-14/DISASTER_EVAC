<!DOCTYPE html>
<html>
<head>
    <title><?php echo $page_title; ?> - Evacuation System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="topbar">
    <b>Evacuation & Emergency Transport Management System</b>
    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']); ?> | <a href="logout.php">Logout</a></span>
</div>
<div class="menu">
    <a href="dashboard.php">Dashboard</a>
    <a href="victims.php">Victims</a>
    <a href="drivers.php">Drivers</a>
    <a href="dispatch.php">Dispatches</a>
    <a href="transport.php">Transport</a>
    <a href="queries.php">Queries</a>
</div>
<div class="content">
<h2><?php echo $page_title; ?></h2>
