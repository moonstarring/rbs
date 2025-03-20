<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Database Configuration
require_once __DIR__ . '/../db/db.php';
require_once 'admin_class.php';

$admin = new admin($conn);
$admin->checkAdminLogin();
// Default filter values
$role_filter = $_POST['role'] ?? "";
$sort_by = $_POST['sort_by'] ?? "name";
$order = $_POST['order'] ?? "ASC";
$search_term = $_POST['search'] ?? "";
$items_per_page = 6;
$page = $_GET['page'] ?? 1;

$users = $admin->getAllUsers($role_filter, $sort_by, $order, $search_term, $items_per_page, $page);
$total_users = $admin->getTotalUsers($role_filter, $search_term);
$total_pages = ceil($total_users / $items_per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Account Management</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }
        .main-content {
            margin-left: 260px; /* Adjust to match the sidebar width */
            padding: 80px 20px;
            background: #f8f9fa;
            min-height: 100vh;
        }
        .header {
            background: white;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .badge-role {
            font-size: 0.9rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .badge-role.admin {
            background-color: #007bff; /* Blue */
            color: white;
        }
        .badge-role.renter {
            background-color: #28a745; /* Green */
            color: white;
        }
        .badge-role.owner {
            background-color: #fd7e14; /* Orange */
            color: white;
        }
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .action-icons i {
            cursor: pointer;
            margin-right: 10px;
        }
        .pagination {
            justify-content: center;
        }
    </style>
</head>
<body>
<?php include '../includes/admin-navbar.php'; ?>

    <div class="main-content">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Account Management</h2>
                <button class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Add User</button>
            </div>

            <!-- Search and Filters -->
            <form method="POST">
                <div class="d-flex justify-content-between mb-3">
                    <input type="text" class="form-control w-50" placeholder="Search" name="search" value="<?= htmlspecialchars($search_term) ?>">
                    <div class="d-flex">
                        <select class="form-select me-2" name="role" style="width: auto;">
                            <option value="">Role</option>
                            <option value="admin" <?= $role_filter == 'admin' ? 'selected' : '' ?>>Admin</option>
                            <option value="owner" <?= $role_filter == 'owner' ? 'selected' : '' ?>>Owner</option>
                            <option value="renter" <?= $role_filter == 'renter' ? 'selected' : '' ?>>Renter</option>
                        </select>
                        <select class="form-select me-2" name="sort_by" style="width: auto;">
                            <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>>Name</option>
                            <option value="created_at" <?= $sort_by == 'created_at' ? 'selected' : '' ?>>Create Date</option>
                        </select>
                        <select class="form-select me-2" name="order" style="width: auto;">
                            <option value="ASC" <?= $order == 'ASC' ? 'selected' : '' ?>>A-Z</option>
                            <option value="DESC" <?= $order == 'DESC' ? 'selected' : '' ?>>Z-A</option>
                        </select>
                        <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-filter"></i></button>
                    </div>
                </div>
            </form>

            <!-- User Table -->
            <div class="table-container">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Create Date</th>
                            <th>Phone Number</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <!-- Profile Picture or Default -->
                                        <img src="<?= !empty($row['profile_picture']) ? '../' . htmlspecialchars($row['profile_picture']) : '../images/pfp.png' ?>" alt="User" class="rounded-circle me-3" width="40" height="40">
                                        <div>
                                            <p class="mb-0 fw-bold"><?= htmlspecialchars($row['name']) ?></p>
                                            <small><?= htmlspecialchars($row['email']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-role <?= strtolower(htmlspecialchars($row['role'])) ?>"><?= ucfirst(htmlspecialchars($row['role'])) ?></span></td>
                                <td><?= htmlspecialchars($row['created_at']) ?></td>
                                <td>
                                    <?php 
                                    $phone = $row['mobile_number'];
                                    if ($phone) {
                                        $formatted_phone = "+63" . substr($phone, 0, 2) . "****" . substr($phone, -2);
                                        echo htmlspecialchars($formatted_phone);
                                    } else {
                                        echo "";
                                    }
                                    ?>
                                </td>
                                <td class="action-icons">
                                    <i class="fas fa-pen text-primary"></i>
                                    <i class="fas fa-trash text-danger"></i>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small>Items per page:</small>
                    <select class="form-select w-auto" onchange="window.location.href='?page=1&items_per_page=' + this.value;">
                        <option <?= $items_per_page == 6 ? 'selected' : '' ?> value="6">6</option>
                        <option <?= $items_per_page == 12 ? 'selected' : '' ?> value="12">12</option>
                        <option <?= $items_per_page == 24 ? 'selected' : '' ?> value="24">24</option>
                    </select>
                    <nav aria-label="Page navigation">
                        <ul class="pagination mb-0">
                            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page - 1 ?>&items_per_page=<?= $items_per_page ?>">Previous</a></li>
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&items_per_page=<?= $items_per_page ?>"><?= $i ?></a></li>
                            <?php endfor; ?>
                            <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>"><a class="page-link" href="?page=<?= $page + 1 ?>&items_per_page=<?= $items_per_page ?>">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>