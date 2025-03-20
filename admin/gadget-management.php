<?php
require_once 'includes/auth.php';
require_once '../db/db.php';
require_once 'admin_class.php';
require_once 'admin_auth.php';

$admin = new Admin($conn);
$admin->checkAdminLogin();

$pendingAssignments = $admin->getPendingAssignments();
$responsibleDevices = $admin->getResponsibleDevices($_SESSION['admin_id']);

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
        elseif (isset($_POST['update_status'])) {
            $admin->updateDeviceStatus(
                $_POST['product_id'],
                $_POST['new_status'],
                $_SESSION['admin_id']
            );
            $_SESSION['success'] = "Status updated successfully!";
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
    <title>Admin Dashboard</title>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #f8f9fa; }
        .main-content { margin-left: 260px; padding: 80px 20px; }
        .card { border: none; box-shadow: 0 0.15rem 1.75rem 0 rgba(58,59,69,.15); }
        .assignment-card { transition: transform 0.2s; }
        .assignment-card:hover { transform: translateY(-3px); }
        .table-container { background: white; border-radius: 0.35rem; }
        .status-badge { padding: 0.35em 0.65em; font-size: 0.75em; }
        .device-image { width: 60px; height: 60px; object-fit: cover; }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>
<div class="main-content">
    <div class="container-fluid">
        <!-- Pending Handovers Section -->
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

        <!-- Responsible Devices Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-left-primary">
                    <div class="card-header py-3">
                        <h5 class="m-0 font-weight-bold text-primary">
                            <i class="fas fa-shield-alt fa-fw mr-2"></i>Rental Devices
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table table-hover align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Device</th>
                                        <th>Owner</th>
                                        <th>Renter</th>
                                        <th>Status</th>
                                        <th>Rental Period</th>
                                        <th>Assignment Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($responsibleDevices)): ?>
                                        <?php foreach ($responsibleDevices as $device): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <img src="../img/uploads/<?= htmlspecialchars($device['image'] ?? 'default.jpg') ?>" 
                                                        class="device-image me-3">
                                                    <div>
                                                        <div class="font-weight-bold">
                                                            <?= htmlspecialchars($device['product_name'] ?? 'Unnamed Product') ?>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= htmlspecialchars($device['brand'] ?? 'No brand specified') ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($device['owner_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($device['renter_name'] ?? 'N/A') ?></td>
                                            <td>
                                                <span class="badge <?= getStatusBadgeClass($device['status']) ?>">
                                                    <?= htmlspecialchars($device['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('M d, Y', strtotime($device['start_date'])) ?> - 
                                                <?= date('M d, Y', strtotime($device['end_date'])) ?>
                                            </td>
                                            <td>
                                                <?= date('M d, Y H:i', strtotime($device['assignment_date'])) ?>
                                            </td>
                                            <td>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" 
                                                        value="<?= htmlspecialchars($device['product_id']) ?>">
                                                    <input type="hidden" name="csrf_token" 
                                                        value="<?= htmlspecialchars($csrfToken) ?>">
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                            <a href="pickup-confirmations.php?product_id=<?= $device['product_id'] ?>&rental_id=<?= $device['rental_id'] ?>" 
                                                                class="dropdown-item">
                                                                <i class="fas fa-eye me-2"></i>View Details & Pickups
                                                            </a>
                                                            </li>
                                                            <li>
                                                                <button type="submit" name="update_status" 
                                                                    class="dropdown-item">
                                                                    <i class="fas fa-sync-alt me-2"></i>Update Status
                                                                </button>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="fas fa-box-open fa-2x mb-3"></i>
                                                <p class="mb-0">No devices under your responsibility</p>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal and Scripts -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Device Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <!-- Dynamic content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const detailsModal = document.getElementById('detailsModal');
    detailsModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const deviceId = button.getAttribute('data-id');
        
        fetch(`get_device_details.php?id=${deviceId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalContent').innerHTML = html;
            });
    });

    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            new bootstrap.Alert(alert).close();
        });
    }, 5000);
});
</script>
</body>
</html>

<?php
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'available': return 'bg-success';
        case 'rented': return 'bg-primary';
        case 'under_maintenance': return 'bg-warning text-dark';
        case 'overdue': return 'bg-danger';
        default: return 'bg-secondary';
    }
}
?>