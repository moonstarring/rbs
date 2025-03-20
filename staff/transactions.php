<?php
session_start();
require_once '../db/db.php';
require_once 'staff_class.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$staff = new Staff($conn);

// Get filter parameters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Build base query
$query = "SELECT r.*, 
            p.name AS product_name,
            u_renter.name AS renter_name,
            u_owner.name AS owner_name
          FROM rentals r
          JOIN products p ON r.product_id = p.id
          JOIN users u_renter ON r.renter_id = u_renter.id
          JOIN users u_owner ON r.owner_id = u_owner.id
          WHERE 1=1";

$params = [];

// Add filters
if (!empty($status)) {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (p.name LIKE ? OR u_renter.name LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($start_date) && !empty($end_date)) {
    $query .= " AND r.created_at BETWEEN ? AND ?";
    $params[] = $start_date;
    $params[] = $end_date;
}

$query .= " ORDER BY r.created_at DESC";

// Prepare and execute
$stmt = $conn->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="mb-0"><i class="bi bi-cash-stack"></i> Transaction History</h2>
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
                                <div class="col-md-3">
                                    <select class="form-select" name="status">
                                        <option value="">All Statuses</option>
                                        <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Completed</option>
                                        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="overdue" <?= $status === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="date" class="form-control" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
                                </div>
                                <div class="col-md-3">
                                    <input type="text" class="form-control" placeholder="Search..." name="search" value="<?= htmlspecialchars($search) ?>">
                                </div>
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <i class="bi bi-funnel"></i> Apply Filters
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Product</th>
                                        <th>Renter</th>
                                        <th>Owner</th>
                                        <th>Dates</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $transaction): ?>
                                    <tr>
                                        <td>#<?= $transaction['id'] ?></td>
                                        <td><?= htmlspecialchars($transaction['product_name']) ?></td>
                                        <td><?= htmlspecialchars($transaction['renter_name']) ?></td>
                                        <td><?= htmlspecialchars($transaction['owner_name']) ?></td>
                                        <td>
                                            <?= date('M d', strtotime($transaction['start_date'])) ?> - 
                                            <?= date('M d, Y', strtotime($transaction['end_date'])) ?>
                                        </td>
                                        <td>₱<?= number_format($transaction['total_cost'], 2) ?></td>
                                        <td>
                                            <span class="badge <?= $staff->getStatusBadgeClass($transaction['status']) ?>">
                                                <?= $staff->formatStatus($transaction['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="transaction_details.php?id=<?= $transaction['id'] ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-file-text"></i> Details
                                            </a>
                                        </td>
                                    </tr>
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