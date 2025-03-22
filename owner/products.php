<?php
session_start();
require_once '../db/db.php';

// Authentication check
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../login.php");
    exit();
}

$owner_id = $_SESSION['id'];
$error = null;
$success = null;
$products = [];

try {
    // Get owner profile data
    $userQuery = "
        SELECT users.*, 
               COALESCE(user_verification.verification_status, 'pending') AS verification_status
        FROM users
        LEFT JOIN user_verification ON users.id = user_verification.user_id
        WHERE users.id = ?
    ";
    $stmt = $conn->prepare($userQuery);
    $stmt->execute([$owner_id]);
    $userData = $stmt->fetch();

    // Handle profile picture path
    $profilePic = '/owner/includes/user.png';
    if (!empty($userData['profile_picture'])) {
        $correctedPath = (strpos($userData['profile_picture'], '/') === 0) 
            ? $userData['profile_picture'] 
            : '/' . $userData['profile_picture'];
        $profilePic = file_exists($_SERVER['DOCUMENT_ROOT'] . $correctedPath) 
            ? $correctedPath 
            : '/owner/includes/user.png';
    }

    // Get owner's products
    $productQuery = "
        SELECT * FROM products 
        WHERE owner_id = ?
        ORDER BY created_at DESC
    ";
    $stmt = $conn->prepare($productQuery);
    $stmt->execute([$owner_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentBox - My Products</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <style>
        .profile-picture {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .product-card {
            transition: transform 0.2s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-5px);
        }
        .product-image {
            height: 200px;
            object-fit: contain;
            background: #f8f9fa;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }
    </style>
</head>
<body>

    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 bg-light border-end">
                <div class="d-flex flex-column p-3">
                    <div class="text-center mb-4">
                        <img src="<?= htmlspecialchars($profilePic) ?>" 
                             class="rounded-circle shadow-sm profile-picture mb-2">
                        <h5 class="mb-0"><?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?></h5>
                        <small class="text-muted">Owner Account</small>
                    </div>
                    
                    <ul class="nav nav-pills flex-column">
                        <li class="nav-item">
                            <a href="dashboard.php" class="nav-link">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a href="products.php" class="nav-link active">My Products</a>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a href="reviews.php" class="nav-link">Reviews</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">My Products</h2>
                    <a href="gadget.php" class="btn btn-success">
                        <i class="bi bi-plus-circle me-2"></i>Add New Product
                    </a>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="card product-card shadow-sm">
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="/img/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                             class="card-img-top product-image">
                                    <?php else: ?>
                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="bi bi-image text-muted fs-1"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <span class="badge bg-<?= $product['status'] === 'available' ? 'success' : 'warning' ?> status-badge">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                    
                                    <div class="card-body">
                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <?= htmlspecialchars($product['brand']) ?>
                                        </h6>
                                        <p class="card-text text-truncate-3">
                                            <?= htmlspecialchars($product['description']) ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="fw-bold text-success">
                                                    ₱<?= number_format($product['rental_price'], 2) ?>
                                                </span>
                                                <span class="text-muted">/<?= $product['rental_period'] ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-box-seam fs-1 text-muted mb-3"></i>
                                    <h4>No Products Found</h4>
                                    <p class="text-muted">You haven't listed any products yet.</p>
                                    <a href="create-product.php" class="btn btn-success">
                                        <i class="bi bi-plus-circle me-2"></i>Add Your First Product
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>