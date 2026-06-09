<?php
session_start();

if (isset($_SESSION['personel_id'])) {
    header("Location: dashboard.php");
    exit;
} else {
    header("Location: login.php");
    exit;
}
?>