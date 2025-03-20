<?php
// Prevent any output before headers
ob_start();

// Include database connection
require_once '../db/db.php';

// Get parameters
$rentalId = isset($_GET['rental_id']) ? (int)$_GET['rental_id'] : 0;
$proofType = isset($_GET['type']) ? $_GET['type'] : '';

// Simple validation
if (!$rentalId || !$proofType) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Direct database query
    $stmt = $conn->prepare("
        SELECT * FROM proofs 
        WHERE rental_id = ? 
        AND proof_type = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$rentalId, $proofType]);
    $proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Clear any buffered output
    ob_clean();
    
    // Return JSON
    header('Content-Type: application/json');
    echo json_encode($proofs);
    exit;
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}