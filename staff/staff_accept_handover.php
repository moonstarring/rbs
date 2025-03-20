<?php
session_start();
require_once '../db/db.php';
require_once 'staff_class.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$staff = new Staff($conn);
$staff->checkStaffLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        $staff->verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['accept_assignment'])) {
            $assignmentId = $_POST['assignment_id'];
            $rentalId = $_POST['rental_id'];
            
            // Update assignment status
            $staff->acceptHandover($assignmentId, $_SESSION['id']);
            
            $_SESSION['success'] = "Handover accepted successfully!";
            header("Location: pickup-confirmations.php?assignment_id=$assignmentId&rental_id=$rentalId");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: dashboard.php");
        exit();
    }
} else {
    // If accessed directly without POST
    header("Location: dashboard.php");
    exit();
}