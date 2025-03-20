<?php
require_once '../auth.php';

if ($_SESSION['user_role'] !== 'owner') {
    header("Location: ../login.php");
    exit();
}
?>