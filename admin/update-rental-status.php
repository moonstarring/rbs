<?php
session_start();
require_once '../db/db.php';  // Contains your $conn variable
require_once 'admin_class.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Pass the database connection to the constructor
    $admin = new admin($conn);  // Note: Case-sensitive class name
    
    try {
        $adminId = $_SESSION['user_id'];
        
        if ($_POST['status'] === 'end_rental') {
            $admin->endRental($_POST['rental_id'], $adminId);
            $_SESSION['message'] = "Rental successfully ended";
        } else {
            $admin->updateRentalStatus(
                $_POST['rental_id'],
                $_POST['status'],
                $adminId
            );
            $_SESSION['message'] = "Status updated successfully";
        }
        
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit();
}
?>