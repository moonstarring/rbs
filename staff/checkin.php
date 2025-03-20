<?php
session_start();
require_once '../db/db.php';
require_once 'staff_class.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$staff = new Staff($conn);
$error = $success = '';
$rentalDetails = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        // Handle assignment acceptance
        if (isset($_POST['accept_assignment'])) {
            $staff->acceptAssignment(
                $_POST['assignment_id'],
                $_SESSION['id']
            );
            $_SESSION['success'] = "Assignment accepted successfully!";
            header("Location: checkin.php");
            exit();
        }

        // Handle check-in submission
        if (isset($_POST['check_in'])) {
            $rentalId = $_POST['rental_id'] ?? '';
            $conditionNotes = $_POST['condition_notes'] ?? '';
            $adminNotes = $_POST['admin_notes'] ?? '';
            
            if (empty($rentalId)) {
                throw new Exception("Please select a rental");
            }

            if ($staff->processCheckIn(
                $rentalId,
                $_SESSION['id'],
                $conditionNotes,
                $adminNotes,
                $_FILES['proof_photo'] ?? null
            )) {
                $_SESSION['success'] = "Gadget checked in successfully!";
                header("Location: checkin.php");
                exit();
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get data
$pendingAssignments = $staff->getPendingAssignments();
$acceptedRentals = $staff->getAssignedRentals($_SESSION['id']);
$csrfToken = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $csrfToken;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'head.php' ?>
    <link rel="stylesheet" href="style.css">
    <style>
        .assignment-card {
            transition: transform 0.2s;
            border-left: 4px solid #0d6efd;
        }
        .assignment-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body class="container-fluid bg-dark-subtle m-0 p-0">
    <?php require_once 'navbar.php' ?>

    <div class="container min-vh-100 p-3">
        <!-- Pending Assignments Section -->
        <?php if (!empty($pendingAssignments)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-tasks me-2"></i>Pending Assignments
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($pendingAssignments as $assignment): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card assignment-card h-100">
                                    <div class="card-body">
                                        <h6 class="card-title text-primary">
                                            Rental #<?= htmlspecialchars($assignment['rental_id']) ?>
                                        </h6>
                                        <p class="card-text small mb-1">
                                            <i class="fas fa-cube me-2"></i>
                                            <?= htmlspecialchars($assignment['product_name']) ?>
                                        </p>
                                        <p class="card-text small mb-1">
                                            <i class="fas fa-user me-2"></i>
                                            <?= htmlspecialchars($assignment['renter_name']) ?>
                                        </p>
                                        <form method="POST">
                                            <input type="hidden" name="assignment_id" 
                                                value="<?= htmlspecialchars($assignment['assignment_id']) ?>">
                                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                            <button type="submit" name="accept_assignment" 
                                                class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Accept Assignment
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Check-In Form Section -->
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0"><i class="bi bi-box-arrow-in-down me-2"></i>Device Check-In</h4>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                        <div class="alert alert-success"><?= $success ?></div>
                        <?php endif; ?>

                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Select Rental</label>
                                <select class="form-select" name="rental_id" required>
                                    <option value="">Choose accepted rental...</option>
                                    <?php foreach ($acceptedRentals as $rental): ?>
                                    <option value="<?= $rental['id'] ?>">
                                        Rental #<?= $rental['id'] ?> - 
                                        <?= htmlspecialchars($rental['product_name']) ?> 
                                        (<?= $rental['renter_name'] ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Condition Notes</label>
                                        <textarea class="form-control" name="condition_notes" rows="3" 
                                            placeholder="Describe device condition..." required></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Proof Photo</label>
                                        <input type="file" class="form-control" name="proof_photo" 
                                            accept="image/*" capture="environment" required>
                                        <small class="text-muted">Take clear photo of the device</small>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Staff Notes</label>
                                <textarea class="form-control" name="admin_notes" rows="2"
                                    placeholder="Internal notes..."></textarea>
                            </div>

                            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                            <div class="d-grid gap-2">
                                <button type="submit" name="check_in" class="btn btn-success btn-lg">
                                    <i class="bi bi-check-circle me-2"></i>Complete Check-In
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>