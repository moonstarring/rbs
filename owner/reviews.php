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
$reviews = [];

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

$reviewQuery = "
    SELECT r.*, 
           u.first_name, 
           u.last_name,
           u.profile_picture AS renter_pic,
           p.name AS product_name,
           p.image AS product_image
    FROM owner_reviews r
    JOIN users u ON r.renter_id = u.id
    JOIN rentals rt ON r.rental_id = rt.id
    JOIN products p ON rt.product_id = p.id
    WHERE r.owner_id = ?
    ORDER BY r.created_at DESC
";
    $stmt = $conn->prepare($reviewQuery);
    $stmt->execute([$owner_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RentBox - My Reviews</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <style>
        .profile-picture {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .review-card {
            border-left: 4px solid #198754;
            transition: transform 0.2s ease;
        }
        .review-card:hover {
            transform: translateY(-3px);
        }
        .star-rating {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .renter-pic {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .product-thumb {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        .nav-tabs .nav-link.active {
            border-color: #198754 #198754 transparent;
            color: #198754;
        }
        .bg-light-success {
            background-color: #e8f5e9;
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
                            <a href="products.php" class="nav-link">My Products</a>
                        </li>
                        <li class="nav-item">
                            <a href="profile.php" class="nav-link">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a href="reviews.php" class="nav-link active">Reviews</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Renter Feedback</h2>
                    <span class="badge bg-success"><?= count($reviews) ?> Reviews</span>
                </div>

                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <?php if (!empty($reviews)): ?>
                        <?php foreach ($reviews as $review): ?>
                            <div class="col-12">
                                <div class="card shadow-sm review-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php
                                                $renterPic = '/owner/includes/user.png';
                                                if (!empty($review['renter_pic'])) {
                                                    $correctedPath = (strpos($review['renter_pic'], '/') === 0) 
                                                        ? $review['renter_pic'] 
                                                        : '/' . $review['renter_pic'];
                                                    $renterPic = file_exists($_SERVER['DOCUMENT_ROOT'] . $correctedPath) 
                                                        ? $correctedPath 
                                                        : '/owner/includes/user.png';
                                                }
                                                ?>
                                                <img src="<?= htmlspecialchars($renterPic) ?>" 
                                                     class="rounded-circle renter-pic">
                                                <div>
                                                    <h5 class="mb-0">
                                                        <?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?>
                                                    </h5>
                                                    <div class="star-rating">
                                                        <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                            </small>
                                        </div>

                                        <?php if (!empty($review['product_name'])): ?>
                                            <div class="d-flex align-items-center gap-3 mb-3">
<?php if (!empty($review['product_image'])): ?>
    <img src="/img/uploads/<?= htmlspecialchars($review['product_image']) ?>" 
         class="product-thumb rounded">
<?php endif; ?>
                                                <div>
                                                    <span class="text-muted">Product:</span>
                                                    <h6 class="mb-0"><?= htmlspecialchars($review['product_name']) ?></h6>
                                                </div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($review['comment'])): ?>
                                            <div class="bg-light-success p-3 rounded">
                                                <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-body text-center py-5">
                                    <i class="bi bi-chat-square-text fs-1 text-muted mb-3"></i>
                                    <h4>No Reviews Yet</h4>
                                    <p class="text-muted">Your products haven't received any feedback from renters yet.</p>
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