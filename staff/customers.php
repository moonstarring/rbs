<?php
session_start();
require_once '../db/db.php';
require_once 'staff_class.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$staff = new Staff($conn);

// Get search and filter parameters
$search = $_GET['search'] ?? '';
$verification_status = $_GET['verification_status'] ?? '';

// Get customers with optional filters
$query = "SELECT u.*, uv.verification_status 
          FROM users u
          LEFT JOIN user_verification uv ON u.id = uv.user_id
          WHERE u.role = 'renter'";

$params = [];
$conditions = [];

if (!empty($search)) {
    $conditions[] = "(CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($verification_status) && $verification_status !== 'all') {
    $conditions[] = "uv.verification_status = ?";
    $params[] = $verification_status;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'head.php' ?>
    <link rel="stylesheet" href="style.css">
</head>
<body class="container-fluid bg-dark-subtle m-0 p-0">
    <?php require_once 'navbar.php' ?>

    <div class="container-fluid min-vh-100 p-3">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2 class="mb-0"><i class="bi bi-people"></i> Customer Management</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form id="filterForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" 
                                           placeholder="Search customers..." 
                                           name="search" 
                                           value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="verification_status">
                                        <option value="all">All Verification Statuses</option>
                                        <option value="verified" <?= $verification_status === 'verified' ? 'selected' : '' ?>>Verified</option>
                                        <option value="pending" <?= $verification_status === 'pending' ? 'selected' : '' ?>>Pending Verification</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel"></i> Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Email</th>
                                        <th>Registered</th>
                                        <th>Verification</th>
                                        <th>Total Rentals</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
    <?php foreach ($customers as $customer): ?>
    <tr>
        <td>
            <div class="d-flex align-items-center">
                <?php if(!empty($customer['profile_picture'])): ?>
                    <img src="/rb/uploads/profile_pictures/<?= htmlspecialchars($customer['profile_picture']) ?>" 
                         class="rounded-circle me-2" 
                         style="width: 40px; height: 40px; object-fit: cover">
                <?php else: ?>
                <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                     style="width: 40px; height: 40px">
                    <i class="bi bi-person text-light"></i>
                </div>
                <?php endif; ?>
                <div>
                    <h6 class="mb-0"><?= htmlspecialchars($customer['first_name']) . ' ' . htmlspecialchars($customer['last_name']) ?></h6>
                </div>
            </div>
        </td>
        <td><?= $staff->obfuscateEmail($customer['email']) ?></td>
        <td><?= date('M d, Y', strtotime($customer['created_at'])) ?></td>
        <td>
            <span class="badge <?= $customer['verification_status'] === 'verified' ? 'bg-success' : 'bg-warning' ?>">
                <?= ucfirst($customer['verification_status'] ?? 'pending') ?>
            </span>
        </td>
        <td>
            <?php 
            $rentalCount = $staff->countUserRentals($customer['id']);
            echo $rentalCount > 0 ? $rentalCount : 'No rentals';
            ?>
        </td>
        <td>
            <div class="btn-group">
                <a href="customer_details.php?id=<?= $customer['id'] ?>" 
                   class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye"></i> View
                </a>
                <button class="btn btn-sm btn-outline-secondary" 
                        data-bs-toggle="modal" 
                        data-bs-target="#contactModal<?= $customer['id'] ?>">
                    <i class="bi bi-envelope"></i>
                </button>
            </div>
        </td>
    </tr>
    
    <!-- Contact Modal -->
    <div class="modal fade" id="contactModal<?= $customer['id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Contact <?= htmlspecialchars($customer['first_name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="send_message.php" method="POST">
                        <input type="hidden" name="user_id" value="<?= $customer['id'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="message" rows="4" required></textarea>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'footer.php'; ?>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>