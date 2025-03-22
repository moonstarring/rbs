<?php
// Error reporting at the VERY TOP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once '../db/db.php';

// Check authentication
if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$owner_id = $_SESSION['id'];
$error = null;
$success = null;

try {
    // Get combined user and verification data
    $query = "
        SELECT 
            users.*,
            COALESCE(user_verification.verification_status, 'pending') AS verification_status,
            user_verification.mobile_number,
            user_verification.updated_at AS verification_updated
        FROM users
        LEFT JOIN user_verification ON users.id = user_verification.user_id
        WHERE users.id = ?
    ";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $userData = $stmt->fetch();

    // Handle profile picture path
    $profilePic = '/owner/includes/user.png'; // Default path
    if (!empty($userData['profile_picture'])) {
        $correctedPath = (strpos($userData['profile_picture'], '/') === 0) 
            ? $userData['profile_picture'] 
            : '/' . $userData['profile_picture'];
        
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . $correctedPath;
        if (file_exists($fullPath)) {
            $profilePic = $correctedPath;
        }
    }

    // Get additional data
    $stmt = $conn->prepare("SELECT * FROM products WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $conn->prepare("SELECT r.*, u.first_name, u.last_name 
                          FROM owner_reviews r
                          JOIN users u ON r.renter_id = u.id
                          WHERE r.owner_id = ?");
    $stmt->execute([$owner_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Profile picture upload
        if (isset($_FILES['profile_picture'])) {
            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/profile_pictures/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileName = uniqid() . '_' . basename($_FILES['profile_picture']['name']);
            $targetFile = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $targetFile)) {
                $publicPath = '/uploads/profile_pictures/' . $fileName;
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $stmt->execute([$publicPath, $owner_id]);
                
                $userData['profile_picture'] = $publicPath;
                $profilePic = $publicPath;
                $success = "Profile picture updated successfully!";
            } else {
                $error = "Failed to upload profile picture";
            }
        }

        // Profile info update
        if (isset($_POST['update_profile'])) {
            $firstName = $_POST['first_name'] ?? '';
            $lastName = $_POST['last_name'] ?? '';
            $email = $_POST['email'] ?? '';
            $mobile = $_POST['mobile_number'] ?? '';

            // Update users table
            $stmt = $conn->prepare("UPDATE users SET 
                                  first_name = ?, 
                                  last_name = ?, 
                                  email = ? 
                                  WHERE id = ?");
            $stmt->execute([$firstName, $lastName, $email, $owner_id]);

            // Update or insert mobile number
            $stmt = $conn->prepare("INSERT INTO user_verification (user_id, mobile_number) 
                                  VALUES (?, ?)
                                  ON DUPLICATE KEY UPDATE mobile_number = ?");
            $stmt->execute([$owner_id, $mobile, $mobile]);

            // Refresh user data
            $stmt = $conn->prepare($query);
            $stmt->execute([$owner_id]);
            $userData = $stmt->fetch();
            
            $success = "Profile updated successfully!";
        }

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $error = "Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox - Owner Profile</title>
    <link rel="icon" type="image/png" href="../images/brand/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <style>
        .profile-picture {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .product-card {
            transition: transform 0.2s ease;
        }
        .product-card:hover {
            transform: translateY(-3px);
        }
        .review-card {
            border-left: 4px solid #198754;
        }
        .nav-tabs .nav-link.active {
            border-color: #198754 #198754 transparent;
            color: #198754;
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
     class="rounded-circle shadow-sm profile-picture mb-2"
     alt="Profile Picture">
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
                            <a href="profile.php" class="nav-link active">Profile</a>
                        </li>
                        <li class="nav-item">
                            <a href="reviews.php" class="nav-link">Reviews</a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 px-md-4 py-4">
                <!-- Alerts -->
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <!-- Profile Info -->
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-person-gear me-2"></i>Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="mb-3 text-center">
<img src="<?= htmlspecialchars($profilePic) ?>" 
     class="rounded-circle shadow-sm profile-picture mb-2"
     alt="Profile Picture">
                                        <div class="d-flex justify-content-center">
                                            <label class="btn btn-sm btn-outline-success">
                                                <i class="bi bi-camera"></i> Change Photo
                                                <input type="file" name="profile_picture" class="d-none" 
                                                       onchange="form.submit()">
                                            </label>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" class="form-control" 
                                               value="<?= htmlspecialchars($userData['first_name']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" class="form-control" 
                                               value="<?= htmlspecialchars($userData['last_name']) ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control" 
                                               value="<?= htmlspecialchars($userData['email']) ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">Mobile Number</label>
                                        <input type="tel" name="mobile_number" class="form-control" 
       value="<?= htmlspecialchars($userData['mobile_number'] ?? '') ?>">
                                    </div>

                                    <button type="submit" name="update_profile" class="btn btn-success w-100">
                                        <i class="bi bi-save me-2"></i>Save Changes
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Stats & Verification -->
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 text-center">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded">
                                            <h4 class="text-success"><?= count($products) ?></h4>
                                            <small class="text-muted">Total Products</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded">
                                            <h4 class="text-success"><?= count($reviews) ?></h4>
                                            <small class="text-muted">Total Reviews</small>
                                        </div>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3"><i class="bi bi-shield-check me-2"></i>Verification Status</h5>
<div class="d-flex align-items-center mb-3">
    <span class="badge <?= ($userData['verification_status'] === 'verified') ? 'bg-success' : 'bg-warning' ?> me-2">
        <?= ucfirst($userData['verification_status'] ?? 'pending') ?>
    </span>
    <?php if($userData['verification_status'] === 'verified'): ?>
        <small class="text-muted">Verified on <?= date('M j, Y', strtotime($userData['updated_at'])) ?></small>
    <?php endif; ?>
</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Reviews -->
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="bi bi-star-fill me-2"></i>Recent Reviews</h5>
                            </div>
                            <div class="card-body">
                                <?php if(!empty($reviews)): ?>
                                    <?php foreach(array_slice($reviews, 0, 3) as $review): ?>
                                        <div class="card mb-3 review-card">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between">
                                                    <div>
                                                        <h6 class="mb-1"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></h6>
                                                        <div class="text-warning">
                                                            <?= str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']) ?>
                                                        </div>
                                                        <p class="mb-0"><?= htmlspecialchars($review['comment']) ?></p>
                                                    </div>
                                                    <small class="text-muted"><?= date('M j, Y', strtotime($review['created_at'])) ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="bi bi-info-circle fs-4"></i>
                                        <p class="mb-0">No reviews yet</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>