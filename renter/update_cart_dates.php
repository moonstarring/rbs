<?php
session_start();
header('Content-Type: application/json');

require_once '../db/db.php'; // Ensure this uses PDO

function log_error($message) {
    file_put_contents('../logs/error_log.txt', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

try {
    if (!isset($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'renter') {
        throw new Exception('Unauthorized access.');
    }

    if (empty($_POST['cart_id']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
        throw new Exception('Missing required fields.');
    }

    $renterId = $_SESSION['id'];
    $cartId = (int)$_POST['cart_id'];
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Validate dates
    if (!strtotime($startDate) || !strtotime($endDate) || 
        date('Y-m-d', strtotime($startDate)) !== $startDate || 
        date('Y-m-d', strtotime($endDate)) !== $endDate) {
        throw new Exception('Invalid date format.');
    }

    if (strtotime($startDate) > strtotime($endDate)) {
        throw new Exception('End date cannot be before start date.');
    }

    // Check ownership using PDO
    $checkQuery = "SELECT id FROM cart_items WHERE id = :cartId AND renter_id = :renterId";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bindParam(':cartId', $cartId, PDO::PARAM_INT);
    $stmt->bindParam(':renterId', $renterId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() === 0) {
        throw new Exception('Cart item not found.');
    }

    // Update dates
    $updateQuery = "UPDATE cart_items SET start_date = :startDate, end_date = :endDate WHERE id = :cartId";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':startDate', $startDate);
    $updateStmt->bindParam(':endDate', $endDate);
    $updateStmt->bindParam(':cartId', $cartId);

    if (!$updateStmt->execute()) {
        throw new Exception('Database update failed.');
    }

    echo json_encode(['success' => true, 'message' => 'Dates updated successfully.']);
} catch (Exception $e) {
    log_error($e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>