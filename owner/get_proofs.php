<?php
require_once '../db/db.php';
require_once 'owner_class.php';

$owner = new Owner($conn);
$owner->authenticateOwner();

if (!isset($_GET['rental_id']) || !isset($_GET['type'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid request']));
}

$rentalId = (int)$_GET['rental_id'];
$proofType = $_GET['type'];

$stmt = $conn->prepare("
    SELECT * FROM proofs 
    WHERE rental_id = ? 
    AND proof_type = ?
    ORDER BY created_at DESC
");
$stmt->execute([$rentalId, $proofType]);
$proofs = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($proofs);