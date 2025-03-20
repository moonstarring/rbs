<?php
require_once 'includes/auth.php';
require_once '../db/db.php';
require_once 'admin_class.php';
$admin = new admin($conn);
$admin->checkAdminLogin();

// Handle User Approvals and Rejections
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error_message'] = "Invalid CSRF token.";
        header("Location: user-confirmation.php");
        exit();
    }

    $action = $_POST['action'] ?? null;
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id && $action) {
        if ($action === 'approve') {
            $_SESSION['success_message'] = $admin->approveUser($user_id);
        } elseif ($action === 'reject') {
            $_SESSION['success_message'] = $admin->rejectUser($user_id);
        }
    } else {
        $_SESSION['error_message'] = "Invalid action or user.";
    }

    header("Location: user-confirmation.php");
    exit();
}

// Generate CSRF Token if needed
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch pending users
$pendingUsers = $admin->getPendingUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Verification - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { font-family: Arial, sans-serif; }
        .main-content {
            margin-left: 260px;
            padding: 80px 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 20px;
        }
        .action-icons i { cursor: pointer; margin-right: 10px; }
        .modal-img { width: 100%; max-width: 300px; height: auto; }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>
<div class="main-content">
    <div class="container">
        <h2 class="mb-4">User Verification</h2>

        <!-- Display success/error messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Table for Pending Users -->
        <div class="table-container">
            <table class="table table-hover align-middle" id="usersTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Applied On</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($pendingUsers)): ?>
                        <?php foreach ($pendingUsers as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars(date('d M, Y', strtotime($user['created_at']))) ?></td>
                                <td class="action-icons">
                                    <!-- Eye icon to view verification details in a modal -->
                                    <button type="button" class="btn btn-sm btn-info view-details" data-bs-toggle="modal" data-bs-target="#verificationModal"
    data-name="<?= htmlspecialchars($user['name']) ?>"
    data-email="<?= htmlspecialchars($user['email']) ?>"
    data-validid="<?= htmlspecialchars($user['valid_id_photo']) ?>"
    data-selfie="<?= htmlspecialchars($user['selfie_photo']) ?>"
    data-cosigneeemail="<?= htmlspecialchars($user['cosignee_email'] ?? 'N/A') ?>"
    data-cosigneefname="<?= htmlspecialchars($user['cosignee_first_name'] ?? 'N/A') ?>"
    data-cosigneelname="<?= htmlspecialchars($user['cosignee_last_name'] ?? 'N/A') ?>"
    data-cosigneerelationship="<?= htmlspecialchars($user['cosignee_relationship'] ?? 'N/A') ?>"
    data-cosigneeid="<?= htmlspecialchars($user['cosignee_id_photo']) ?>"
    data-cosigneeselfie="<?= htmlspecialchars($user['cosignee_selfie']) ?>"
>
    <i class="fas fa-eye" title="View Verification Details"></i>
</button>

                                    <!-- Approve Form -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>

                                    <!-- Reject Form -->
                                    <form method="POST" action="" style="display:inline;">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="user_id" value="<?= htmlspecialchars($user['id']) ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Reject" onclick="return confirm('Are you sure you want to reject this user?');">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">No pending users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal for Verification Details -->
<div class="modal fade" id="verificationModal" tabindex="-1" aria-labelledby="verificationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="verificationModalLabel">Verification Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <!-- Details will be filled via JavaScript -->
        <div id="verificationDetails">
          <p><strong>Name:</strong> <span id="modalName"></span></p>
          <p><strong>Email:</strong> <span id="modalEmail"></span></p>
          <p><strong>Valid ID Photo:</strong></p>
          <img id="modalValidID" src="" alt="Valid ID Photo" class="modal-img mb-3">
          <p><strong>Selfie Photo:</strong></p>
          <img id="modalSelfie" src="" alt="Selfie Photo" class="modal-img mb-3">
          <hr>
          <p><strong>Cosignee Email:</strong> <span id="modalCosigneeEmail"><?= htmlspecialchars($user['cosignee_email'] ?? 'N/A') ?></span></p>
<p><strong>Cosignee Name:</strong> <span id="modalCosigneeName"><?= htmlspecialchars($user['cosignee_first_name'] ?? 'N/A') ?> <?= htmlspecialchars($user['cosignee_last_name'] ?? 'N/A') ?></span></p>
<p><strong>Relationship:</strong> <span id="modalCosigneeRelationship"><?= htmlspecialchars($user['cosignee_relationship'] ?? 'N/A') ?></span></p>
<p><strong>Cosignee ID Photo:</strong></p>
<img id="modalCosigneeID" src="<?= $user['cosignee_id_photo'] ? "../" . htmlspecialchars($user['cosignee_id_photo']) : 'default_image.jpg' ?>" alt="Cosignee ID Photo" class="modal-img mb-3">
<p><strong>Cosignee Selfie:</strong></p>
<img id="modalCosigneeSelfie" src="<?= $user['cosignee_selfie'] ? "../" . htmlspecialchars($user['cosignee_selfie']) : 'default_image.jpg' ?>" alt="Cosignee Selfie" class="modal-img mb-3">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap JS (with Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // When a "View Details" button is clicked, fill the modal with data attributes.
    document.querySelectorAll('.view-details').forEach(function(button) {
        button.addEventListener('click', function() {
            document.getElementById('modalName').textContent = this.getAttribute('data-name');
            document.getElementById('modalEmail').textContent = this.getAttribute('data-email');
            document.getElementById('modalValidID').src = "../" + this.getAttribute('data-validid');
            document.getElementById('modalSelfie').src = "../" + this.getAttribute('data-selfie');
            document.getElementById('modalCosigneeEmail').textContent = this.getAttribute('data-cosigneeemail');
            document.getElementById('modalCosigneeName').textContent = this.getAttribute('data-cosigneefname') + " " + this.getAttribute('data-cosigneelname');
            document.getElementById('modalCosigneeRelationship').textContent = this.getAttribute('data-cosigneerelationship');
            document.getElementById('modalCosigneeID').src = this.getAttribute('data-cosigneeid') ? "../" + this.getAttribute('data-cosigneeid') : "";
            document.getElementById('modalCosigneeSelfie').src = this.getAttribute('data-cosigneeselfie') ? "../" + this.getAttribute('data-cosigneeselfie') : "";
        });
    });
</script>
</body>
</html>
