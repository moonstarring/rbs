<?php
// view_rental.php
session_start();
require_once __DIR__ . '/../db/db.php';
require_once 'admin_class.php';
$admin = new admin($conn);
$admin->checkAdminLogin();

// Validate rental_id
if (!isset($_GET['rental_id'])) {
    $_SESSION['error_message'] = "Invalid request";
    header('Location: review_disputes.php');
    exit();
}
$rentalId = intval($_GET['rental_id']);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Proof Upload Handling
    if (isset($_POST['proof_type'])) {
        $proofType = $_POST['proof_type'];
        $uploadDir = '../uploads/proofs/';
        
        try {
            $filename = $admin->uploadProof($_FILES['proof_file'], $uploadDir);
            $conn->beginTransaction();
            
            // Insert proof
            $stmt = $conn->prepare("INSERT INTO proofs (rental_id, proof_type, proof_url) VALUES (?, ?, ?)");
            $stmt->execute([$rentalId, $proofType, $filename]);

            // Update status based on proof type
            $newStatus = match($proofType) {
                'owner_handover' => 'handed_over_to_admin',
                'admin_handover' => 'picked_up',
                'return' => 'returned',
                default => null
            };
            
            if ($newStatus) {
                $conn->prepare("UPDATE rentals SET status = ? WHERE id = ?")->execute([$newStatus, $rentalId]);
            }
            
            $conn->commit();
            $_SESSION['success_message'] = "Proof uploaded successfully";
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Error: " . $e->getMessage();
        }
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    }
    // Ban/Resolve Handling
    elseif (isset($_POST['action'])) {
        $action = $_POST['action'];
        $userId = $rental['renter_id']; // From fetched rental details
        
        if ($action === 'ban' && $admin->banUser($userId)) {
            $_SESSION['success_message'] = "User has been banned.";
        } elseif ($action === 'resolve' && $admin->resolveDispute($rentalId)) {
            $_SESSION['success_message'] = "Dispute has been resolved.";
        } else {
            $_SESSION['error_message'] = "Action failed.";
        }
        header('Location: review_disputes.php');
        exit();
    }
}

// Fetch rental details
$rental = $admin->getRentalById($rentalId);
if (!$rental) {
    $_SESSION['error_message'] = "Rental not found.";
    header('Location: review_disputes.php');
    exit();
}

// Determine allowed proof type based on current status
$currentStatus = $rental['status'];
$allowedProofType = null;
$proofLabel = '';

switch ($currentStatus) {
    case 'ready_for_pickup':
        $allowedProofType = 'owner_handover';
        $proofLabel = 'Owner-to-Admin Handover Proof';
        break;
    case 'handed_over_to_admin':
        $allowedProofType = 'admin_handover';
        $proofLabel = 'Admin-to-Renter Handover Proof';
        break;
    case 'return_pending':
        $allowedProofType = 'return';
        $proofLabel = 'Return Verification Proof';
        break;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Rental - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

<div class="container-fluid">
    <div class="row">
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-4">
                    <?= htmlspecialchars($_SESSION['success_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-4">
                    <?= htmlspecialchars($_SESSION['error_message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Proof Upload Form (Conditional) -->
            <?php if ($allowedProofType): ?>
                <div class="card mt-4">
                    <div class="card-header">Admin Actions</div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="proof_type" value="<?= $allowedProofType ?>">
                            
                            <div class="mb-3">
                                <label class="form-label"><?= $proofLabel ?></label>
                                <input type="file" name="proof_file" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-upload"></i> Upload Proof
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Rental Information -->
            <div class="card mt-4">
                <div class="card-header">Rental Details</div>
                <div class="card-body">
                    <h5>Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
                    <p><strong>Status:</strong> <?= htmlspecialchars($rental['status']) ?></p>
                    
                    <!-- Ban/Resolve Actions -->
                    <form method="POST" class="mt-3">
                        <button type="submit" name="action" value="ban" class="btn btn-danger">Ban User</button>
                        <button type="submit" name="action" value="resolve" class="btn btn-success">Mark Resolved</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>