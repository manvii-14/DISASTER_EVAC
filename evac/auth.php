<?php
// include this at the top of every page that needs login
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}
include "db.php";
?>
