<?php
require_once 'includes/auth.php';
require_once '../db/db.php';
require_once 'admin_class.php';
require_once 'admin_auth.php';

$admin = new Admin($conn);
$admin->checkAdminLogin();

$productId = $_GET['product_id'] ?? null;
$rentalId = $_GET['rental_id'] ?? null;

if (!$productId || !ctype_digit($productId) || !$rentalId || !ctype_digit($rentalId)) {
    header("Location: gadget-management.php");
    exit();
}

$deviceDetails = $admin->getDeviceDetails($productId);
$specificRental = $admin->getSpecificRental($rentalId, $_SESSION['admin_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $admin->verifyCsrfToken($_POST['csrf_token'] ?? '');

        if (isset($_POST['update_status'])) {
            $admin->updateRentalStatus(
                $_POST['rental_id'],
                $_POST['status'],
                $_SESSION['admin_id']
            );
            $_SESSION['success'] = "Status updated successfully!";
            header("Location: pickup-confirmations.php?product_id=$productId&rental_id=$rentalId");
            exit();
        } elseif (isset($_POST['upload_proof'])) {
            $rentalId = $_POST['rental_id'];
            $proofType = $_POST['proof_type'];
            $descriptions = $_POST['descriptions'] ?? [];
            $proofFiles = $_FILES['proof_files'];
            
            try {
                if(!empty($proofFiles['name'][0])) {
                    $admin->uploadRentalProof(
                        $rentalId, 
                        $proofType, 
                        $proofFiles, 
                        $descriptions
                    );
                    $_SESSION['success'] = count($proofFiles['name'])." proof(s) uploaded!";
                } else {
                    throw new Exception("Please select at least one file to upload.");
                }
            } catch (Exception $e) {
                $_SESSION['error'] = $e->getMessage();
            }
            header("Location: pickup-confirmations.php?product_id=$productId&rental_id=$rentalId");
            exit();
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: pickup-confirmations.php?product_id=$productId&rental_id=$rentalId");
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
    <title>Pickup Confirmations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .status-badge { padding: 0.25rem 0.5rem; font-size: 0.875rem; border-radius: 0.25rem; }
        .remaining-days { min-width: 120px; }
        .status-select { max-width: 150px; }
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
                            <i class="fas fa-box-open fa-fw mr-2"></i>
                            Pickup Details for: <?= htmlspecialchars($specificRental['product_name'] ?? 'Unknown Device') ?>
                        </h5>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-left-success">
                    <div class="card-body">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Renter</th>
                                        <th>Status</th>
                                        <th class="remaining-days">Remaining Days</th>
                                        <th>Rental Period</th>
                                        <th>Total Cost</th>
                                        <th>Handover Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($specificRental): 
                                        $startDate = $specificRental['handover_date'] 
                                            ? strtotime($specificRental['handover_date']) 
                                            : strtotime($specificRental['start_date']);
                                        
                                        $originalEndDate = strtotime($specificRental['end_date']);
                                        $adjustedEndDate = $originalEndDate;
                                        
                                        if ($specificRental['status'] === 'picked_up' && $specificRental['handover_date']) {
                                            $originalDuration = $originalEndDate - strtotime($specificRental['start_date']);
                                            $adjustedEndDate = $startDate + $originalDuration;
                                        }
                                        
                                        $remainingDays = ($specificRental['status'] === 'picked_up')
                                            ? ceil(($adjustedEndDate - time()) / (60 * 60 * 24))
                                            : ceil(($originalEndDate - $startDate) / (60 * 60 * 24));
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <img src="../img/uploads/<?= htmlspecialchars($specificRental['image'] ?? 'default.jpg') ?>" 
                                                     class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <div>
                                                    <div class="font-weight-bold"><?= htmlspecialchars($specificRental['product_name']) ?></div>
                                                    <small class="text-muted">Owner: <?= htmlspecialchars($specificRental['owner_name']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($specificRental['renter_name']) ?></td>
                                        <td>
                                            <span class="badge 
                                                <?= match($specificRental['status']) {
                                                    'handed_over_to_admin' => 'bg-info',
                                                    'ready_for_pickup' => 'bg-info',
                                                    'picked_up' => 'bg-primary',
                                                    'pending_return' => 'bg-warning',
                                                    'returned' => 'bg-secondary',
                                                    'overdue' => 'bg-danger',
                                                    default => 'bg-secondary'
                                                } ?>">
                                                <?= ucfirst(str_replace('_', ' ', $specificRental['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($specificRental['status'] === 'picked_up'): ?>
                                                <span class="badge <?= $remainingDays > 0 ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= $remainingDays > 0 
                                                        ? "$remainingDays days left" 
                                                        : "Overdue by ".abs($remainingDays)." days" ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <?= ceil(($originalEndDate - $startDate) / (60 * 60 * 24)) ?> day rental
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?= date('M d, Y', $startDate) ?> - 
                                            <?= date('M d, Y', $adjustedEndDate) ?>
                                            <?php if ($specificRental['status'] === 'picked_up' && $specificRental['handover_date']): ?>
                                                <br><small class="text-muted">Adjusted from pickup</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?= number_format($specificRental['total_cost'], 2) ?></td>
                                        <td>
                                            <?= $specificRental['handover_date'] 
                                                ? date('M d, Y H:i', strtotime($specificRental['handover_date'])) 
                                                : '<span class="text-muted">Not recorded</span>' ?>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="rental_id" value="<?= $specificRental['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                                <div class="input-group input-group-sm">
                                                    <select name="status" class="form-select form-select-sm" required>
                                                        <option value="">Change status</option>
                                                        <?php 
                                                        $currentStatus = $specificRental['status'];
                                                        $allowedNextStatuses = match ($currentStatus) {
                                                            'handed_over_to_admin' => ['ready_for_pickup'],
                                                            'ready_for_pickup' => ['picked_up'],
                                                            'picked_up' => $remainingDays < 0 ? ['overdue'] : ['pending_return'],
                                                            'pending_return' => ['returned'],
                                                            'overdue' => ['returned'],
                                                            default => []
                                                        };
                                                        foreach ($allowedNextStatuses as $status):
                                                        ?>
                                                        <option value="<?= $status ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $status)) ?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                                </div>
                                            </form>

                                            <?php 
                                            $proofTypeMap = [
                                                'ready_for_pickup' => 'handed_over_to_admin',
                                                'picked_up' => 'picked_up',
                                                'pending_return' => 'returned'
                                            ];
                                            if (array_key_exists($currentStatus, $proofTypeMap)):
                                            ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success mt-2" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#proofModal"
                                                        data-rental-id="<?= $specificRental['id'] ?>"
                                                        data-proof-type="<?= $proofTypeMap[$currentStatus] ?>">
                                                    <i class="fas fa-upload me-1"></i>Add Proofs
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-box-open fa-2x mb-3"></i>
                                            <p class="mb-0">No rental found for this device</p>
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

<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="proofModalLabel">Upload Rental Proofs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" enctype="multipart/form-data" id="proofUploadForm">
                <div class="modal-body">
                    <input type="hidden" name="rental_id" id="modalRentalId">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="proof_type" id="modalProofType">
                    
                    <div id="proofEntries">
                        <div class="proof-entry mb-3">
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="text" 
                                           class="form-control" 
                                           name="descriptions[]" 
                                           placeholder="Proof description (optional)"
                                           maxlength="100">
                                </div>
                                <div class="col-md-5">
                                    <input type="file" 
                                           class="form-control" 
                                           name="proof_files[]" 
                                           accept="image/*,.pdf,.doc,.docx"
                                           required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" 
                                            class="btn btn-danger btn-remove-entry" 
                                            disabled>
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-secondary btn-sm" id="addProofEntry">
                        <i class="fas fa-plus me-1"></i>Add Another Proof
                    </button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="upload_proof" class="btn btn-primary">Upload Proofs</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const proofModal = document.getElementById('proofModal');
    const proofEntries = document.getElementById('proofEntries');
    const addEntryBtn = document.getElementById('addProofEntry');
    let entryCount = 1;

    addEntryBtn.addEventListener('click', function() {
        if(entryCount >= 5) return;
        entryCount++;
        const newEntry = document.createElement('div');
        newEntry.className = 'proof-entry mb-3';
        newEntry.innerHTML = `
            <div class="row g-2">
                <div class="col-md-6">
                    <input type="text" 
                           class="form-control" 
                           name="descriptions[]" 
                           placeholder="Proof description (optional)"
                           maxlength="100">
                </div>
                <div class="col-md-5">
                    <input type="file" 
                           class="form-control" 
                           name="proof_files[]" 
                           accept="image/*,.pdf,.doc,.docx"
                           required>
                </div>
                <div class="col-md-1">
                    <button type="button" 
                            class="btn btn-danger btn-remove-entry">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        proofEntries.appendChild(newEntry);
        updateRemoveButtons();
    });

    proofEntries.addEventListener('click', function(e) {
        if(e.target.closest('.btn-remove-entry')) {
            e.target.closest('.proof-entry').remove();
            entryCount--;
            updateRemoveButtons();
        }
    });

    function updateRemoveButtons() {
        const entries = proofEntries.querySelectorAll('.proof-entry');
        entries.forEach((entry, index) => {
            const removeBtn = entry.querySelector('.btn-remove-entry');
            removeBtn.disabled = entries.length === 1;
        });
    }

    proofModal.addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        document.getElementById('modalRentalId').value = button.getAttribute('data-rental-id');
        document.getElementById('modalProofType').value = button.getAttribute('data-proof-type');
        proofEntries.innerHTML = `
            <div class="proof-entry mb-3">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" 
                               class="form-control" 
                               name="descriptions[]" 
                               placeholder="Proof description (optional)"
                               maxlength="100">
                    </div>
                    <div class="col-md-5">
                        <input type="file" 
                               class="form-control" 
                               name="proof_files[]" 
                               accept="image/*,.pdf,.doc,.docx"
                               required>
                    </div>
                    <div class="col-md-1">
                        <button type="button" 
                                class="btn btn-danger btn-remove-entry" 
                                disabled>
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        entryCount = 1;
        updateRemoveButtons();
    });
});
</script>
</body>
</html>