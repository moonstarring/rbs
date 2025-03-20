<?php
// admin/review_disputes.php
session_start();
require_once 'includes/auth.php'; 
require_once __DIR__ . '/../db/db.php';
require_once 'admin_class.php';
$admin = new Admin($conn);

$admin->checkAdminLogin();

$adminId = $_SESSION['id'];

// Handle dispute status update
// Ensure variables are set to avoid 'undefined variable' warnings
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$ownerId = isset($_GET['ownerId']) ? intval($_GET['ownerId']) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dispute_id = intval($_POST['dispute_id']);
    $new_status = $_POST['status'];
    $admin_notes = trim($_POST['admin_notes']);

    $admin->updateDisputeStatus($dispute_id, $new_status, $admin_notes);
}

// Fetch disputes and rentals using Admin class methods
$disputes = $admin->getDisputes($filter, $search);
$rentals = $admin->getRentalsForOwner($ownerId);

// Access status flow or check if status is active
$statusFlow = $admin->statusFlow;
$isActive = $admin->isStatusActive('delivered', 'renting');


// Generate CSRF token for security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review Disputes - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">Review Disputes</h2>

                <!-- Success Message -->
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error_message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>
                <form method="get" action="" class="w-100">
                 <div class="d-flex w-100">
                    <input type="text" name="search" class="form-control w-75" placeholder="Search" value="<?= htmlspecialchars($search) ?>">
                    <div class="d-flex ms-2">
                        <select class="form-select me-2" name="sort_by" style="width: auto;">
                            <option selected>Sort by</option>
                            <option value="1" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == '1' ? 'selected' : '' ?>>Rental ID</option>
                            <option value="2" <?= isset($_GET['sort_by']) && $_GET['sort_by'] == '2' ? 'selected' : '' ?>>Gadget</option>
                        </select>
                        <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                 </div>
                </form>
                <ul class="nav nav-tabs mb-3" id="transactionTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter == 'all' ? 'active' : '' ?>" href="?filter=all&search=<?= htmlspecialchars($search) ?>" role="tab">All</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter == 'overdue' ? 'active' : '' ?>" href="?filter=pickup&search=<?= htmlspecialchars($search) ?>" role="tab">Overdue</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $filter == 'lost' ? 'active' : '' ?>" href="?filter=rented&search=<?= htmlspecialchars($search) ?>" role="tab">Lost Devices</a>
                        </li>
                    </ul>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Product</th>
                            <th>Reason</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Filed At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($disputes)): ?>
                            <?php foreach ($disputes as $dispute): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dispute['user_name']) ?></td>
                                    <td><?= htmlspecialchars($dispute['product_name']) ?></td>
                                    <td><?= htmlspecialchars($dispute['reason']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($dispute['description'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $dispute['status'] === 'open' ? 'warning' : 
                                            ($dispute['status'] === 'under_review' ? 'info' : 
                                            ($dispute['status'] === 'resolved' ? 'success' : 'secondary'))
                                        ?>">
                                            <?= ucfirst($dispute['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($dispute['created_at']) ?></td>
                                    <td>
                                        <!-- Button to open modal for updating dispute -->
                                        <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#updateDisputeModal<?= $dispute['id'] ?>">
                                            Update
                                        </button>
                                        <!-- View Button -->
                                        <a href="view_rental.php?rental_id=<?= htmlspecialchars($dispute['rental_id']) ?>" class="btn btn-info btn-sm">View</a>

                                        <!-- Modal -->
                                        <div class="modal fade" id="updateDisputeModal<?= $dispute['id'] ?>" tabindex="-1" aria-labelledby="updateDisputeModalLabel<?= $dispute['id'] ?>" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form action="review_disputes.php" method="POST">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="updateDisputeModalLabel<?= $dispute['id'] ?>">Update Dispute Status</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <input type="hidden" name="dispute_id" value="<?= htmlspecialchars($dispute['id']) ?>">
                                                            <div class="mb-3">
                                                                <label for="status<?= $dispute['id'] ?>" class="form-label">Status</label>
                                                                <select class="form-select" id="status<?= $dispute['id'] ?>" name="status" required>
                                                                    <option value="open" <?= $dispute['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                                                                    <option value="under_review" <?= $dispute['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                                                                    <option value="resolved" <?= $dispute['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                                                                    <option value="closed" <?= $dispute['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="admin_notes<?= $dispute['id'] ?>" class="form-label">Admin Notes</label>
                                                                <textarea class="form-control" id="admin_notes<?= $dispute['id'] ?>" name="admin_notes" rows="3"><?= htmlspecialchars($dispute['admin_notes']) ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No disputes found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>