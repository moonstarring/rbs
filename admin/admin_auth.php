<?php
session_start(); // Start the session

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

// Verify admin status in database
require_once __DIR__ . '/../db/db.php';
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$_SESSION['id']]); // Use the correct session variable
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>