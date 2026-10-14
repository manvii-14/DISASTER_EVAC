<?php
$current_page = basename($_SERVER['PHP_SELF']);
$safe_title = htmlspecialchars($page_title ?? 'Dashboard', ENT_QUOTES, 'UTF-8');
$safe_user = htmlspecialchars($_SESSION['user'] ?? 'User', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $safe_title; ?> | Evacuation Management</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="app-shell">

    <aside class="sidebar">
        <a href="dashboard.php" class="brand">
            <span class="brand-mark">E</span>
            <span class="brand-name">
                <strong>Evacuation</strong>
                <small>Emergency Management</small>
            </span>
        </a>

        <div class="sidebar-label">WORKSPACE</div>

        <nav class="sidebar-nav">
            <a href="dashboard.php"
               class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                <span class="nav-icon">▦</span>
                <span>Dashboard</span>
            </a>

            <a href="victims.php"
               class="nav-link <?php echo $current_page === 'victims.php' ? 'active' : ''; ?>">
                <span class="nav-icon">◇</span>
                <span>Victims</span>
            </a>

            <a href="drivers.php"
               class="nav-link <?php echo $current_page === 'drivers.php' ? 'active' : ''; ?>">
                <span class="nav-icon">↗</span>
                <span>Drivers</span>
            </a>

            <a href="dispatch.php"
               class="nav-link <?php echo $current_page === 'dispatch.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⇄</span>
                <span>Dispatches</span>
            </a>

            <a href="transport.php"
               class="nav-link <?php echo $current_page === 'transport.php' ? 'active' : ''; ?>">
                <span class="nav-icon">▤</span>
                <span>Transport</span>
            </a>

            <a href="queries.php"
               class="nav-link <?php echo $current_page === 'queries.php' ? 'active' : ''; ?>">
                <span class="nav-icon">⌕</span>
                <span>SQL Queries</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <span class="status-dot"></span>
            Database-backed system
        </div>
    </aside>

    <div class="app-main">

        <header class="topbar">
            <div class="topbar-heading">
                <span class="topbar-caption">EMERGENCY OPERATIONS</span>
                <h1><?php echo $safe_title; ?></h1>
            </div>

            <div class="user-area">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($safe_user, 0, 1)); ?>
                </div>

                <div class="user-details">
                    <strong><?php echo $safe_user; ?></strong>
                    <small>Administrator</small>
                </div>

                <a href="logout.php" class="logout-link">Logout ↗</a>
            </div>
        </header>

        <main class="content">
