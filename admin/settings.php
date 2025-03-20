<?php
require_once 'includes/auth.php';
require_once '../db/db.php';
require_once 'admin_class.php';
require_once 'admin_auth.php';

$admin = new Admin($conn);
$admin->checkAdminLogin();

$pendingAssignments = $admin->getPendingAssignments();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $admin->verifyCsrfToken($_POST['csrf_token'] ?? '');
        
        if (isset($_POST['accept_assignment'])) {
            $admin->acceptAssignment(
                $_POST['assignment_id'],
                $_SESSION['admin_id']
            );
            $_SESSION['success'] = "Assignment accepted successfully!";
        }
        
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: ".$_SERVER['PHP_SELF']);
        exit();
    }
}

$csrfToken = $admin->generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Handover Requests</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; }
        .main-content { margin-left: 260px; padding: 80px 20px; }
        .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
        .assignment-card { transition: transform 0.2s; }
        .assignment-card:hover { transform: translateY(-3px); }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>
<div class="main-content">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-left-primary">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-handshake fa-fw mr-2"></i>Pending Handover Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if (!empty($pendingAssignments)): ?>
                                <?php foreach ($pendingAssignments as $assignment): ?>
<div class="col-xl-3 col-md-6 mb-4">
    <div class="card assignment-card h-100">
        <div class="card-body">
            <h5 class="card-title">Rental #<?= htmlspecialchars($assignment['rental_id']) ?></h5>
            <p class="card-text">
                Product: <?= htmlspecialchars($assignment['product_name']) ?><br>
                Owner: <?= htmlspecialchars($assignment['owner_name']) ?><br>
            </p>
            <form method="post">
                <input type="hidden" name="assignment_id" 
                    value="<?= htmlspecialchars($assignment['assignment_id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                <button type="submit" name="accept_assignment" class="btn btn-success">
                    Accept
                </button>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12 text-center py-5">
                                    <div class="text-muted">
                                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                                        <p class="mb-0">No pending handover requests</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Keep script section from original -->
</body>
</html>