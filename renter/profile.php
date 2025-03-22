<?php
session_start();
require_once '../db/db.php';
require_once 'renter_class.php';
$renter = new renter($conn);
$renter->authenticateRenter();

// Initialize error and success messages
$error = null;
$success = null;

// Fetch user data
$userData = $renter->getUserData($_SESSION['id']);
$verificationData = $renter->getVerificationData($_SESSION['id']);
$userData = array_merge($userData, $verificationData ? $verificationData : []);

// Handle profile picture update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture'])) {
    $updatedPicture = $renter->updateProfilePicture($_SESSION['id'], $_FILES['profile_picture']);
    if ($updatedPicture) {
        $userData['profile_picture'] = $updatedPicture;
        $success = "Profile picture updated successfully!";
    } else {
        $error = "Failed to update profile picture.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox</title>
    <link rel="icon" type="image/png" href="../images/brand/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/renter/style.css">
    <style>
        .profile-picture {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }
        .verification-badge {
            font-size: 0.75rem;
        }
        .accordion-button:not(.collapsed) {
            background-color: #198754;
            color: white;
        }
        .nav-tabs .nav-link.active {
            border-color: #198754;
            color: #198754;
        }
        .form-control:focus {
            border-color: #198754;
            box-shadow: 0 0 0 0.25rem rgba(25, 135, 84, 0.25);
        }
        .profile-info-card {
    transition: transform 0.2s ease;
}

.profile-info-card:hover {
    transform: translateY(-2px);
}

.info-label {
    font-size: 0.9rem;
    margin-bottom: 0.3rem;
}

.verified-info div {
    margin-bottom: 1.2rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #dee2e6;
}
    </style>
</head>

<body>
<?php require_once '../includes/navbarr.php'; ?>
    <div class="container-fluid image-bg m-0 p-0 overflow-auto">
        <!-- navbar -->


        <!-- body -->
        <div class="row container-fluid bg-dark-subtle px-3 pb-5 pt-3 m-0">
            <!-- Left Sidebar -->
            <div class="col-3 bg-body p-4 rounded-3 shadow-sm">
                <div class="d-flex align-items-center">
                    <img src="<?= isset($userData['profile_picture']) && $userData['profile_picture'] ? '../' . $userData['profile_picture'] : '../images/user/pfp.png' ?>" 
                         class="img-thumbnail rounded-circle pfp me-3 shadow-sm profile-picture" 
                         alt="Profile Picture">
<div class="d-flex flex-column">
    <p class="fs-5 fw-bold m-0 p-0">
        <?= htmlspecialchars($userData['first_name'] . ' ' . $userData['last_name']) ?>
        <?php if($userData['verification_status'] === 'verified'): ?>
            <i class="bi bi-patch-check-fill text-success ms-1"></i>
        <?php else: ?>
            <i class="bi bi-x-circle-fill text-danger ms-1"></i>
        <?php endif; ?>
    </p>
    <form method="post" enctype="multipart/form-data" class="d-flex">
        <label class="text-secondary text-decoration-none cursor-pointer">
            <small><i class="bi bi-pen-fill pe-1"></i>Edit Photo</small>
            <input type="file" name="profile_picture" class="d-none" onchange="form.submit()">
        </label>
    </form>
</div>
                </div>
                <hr>

                <!-- Sidebar Menu -->
                <div class="d-flex flex-column gap-2 mt-3 overflow-auto">
                    <div class="accordion border-0" id="accordionPanelsStayOpen">
                        <!-- Account Section -->
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button rounded-3 fw-bold" type="button" data-bs-toggle="collapse" 
                                        data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" 
                                        aria-controls="panelsStayOpen-collapseOne">
                                    <i class="bi bi-person-fill-gear me-2"></i>My Account
                                </button>
                            </h2>
                            <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
                                <div class="accordion-body p-3">
                                    <div class="d-flex align-items-start flex-column gap-1">
                                        <a href="profile.php" class="fs-6 text-decoration-none text-secondary">Profile</a>
                                        <a href="changepassword.php" class="fs-6 text-decoration-none text-secondary">Change Password</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Rentals Section -->
                        <div class="accordion-item border-0">
    <h2 class="accordion-header">
        <a href="myrental.php">
            <button class="accordion-button collapsed rounded-3 fw-bold" type="button" 
                    data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" 
                    aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                <i class="bi bi-box2-heart-fill me-2 "></i>My Rentals
            </button>
        </a>
    </h2>
</div>


                        <!-- Verification Section -->
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed rounded-3 fw-bold" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseThree" 
                                        aria-expanded="false" aria-controls="panelsStayOpen-collapseThree">
                                    <i class="bi bi-shield-check me-2"></i>Verification
                                </button>
                            </h2>
                            <div id="panelsStayOpen-collapseThree" class="accordion-collapse collapse">
                                <div class="accordion-body">
                                    <div class="d-flex flex-column gap-2">
                                        <p class="m-0"><strong>Status:</strong> 
                                            <span class="badge <?= ($userData['verification_status'] === 'verified') ? 'bg-success' : 'bg-warning' ?>">
                                                <?= ucfirst($userData['verification_status'] ?? 'pending') ?>
                                            </span>
                                        </p>
                                        <?php if($userData['verification_status'] === 'verified'): ?>
                                            <p class="m-0"><strong>Co-signee:</strong> 
                                                <?= htmlspecialchars($userData['cosignee_first_name'] . ' ' . $userData['cosignee_last_name']) ?>
                                            </p>
                                            <p class="m-0"><strong>Relationship:</strong> 
                                                <?= htmlspecialchars($userData['cosignee_relationship']) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

           <!-- Main Content -->
           <div class="col">
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

                <!-- Profile Info Section -->
               <!-- Profile Info Section -->
               <div class="row g-4">
                    <!-- Personal Information Card -->
                    <div class="col-md-6">
                        <div class="bg-white p-4 rounded-3 shadow-sm mb-4">
                            <h4 class="mb-4 text-success"><i class="bi bi-person-lines-fill me-2"></i>Personal Information</h4>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">First Name</label>
                                    <input type="text" name="first_name" class="form-control border-secondary"
                                           value="<?= htmlspecialchars($userData['first_name'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Last Name</label>
                                    <input type="text" name="last_name" class="form-control border-secondary"
                                           value="<?= htmlspecialchars($userData['last_name'] ?? '') ?>">
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Email Address</label>
                                    <input type="email" name="email" class="form-control border-secondary"
                                           value="<?= htmlspecialchars($userData['email'] ?? '') ?>" required>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label fw-medium">Mobile Number</label>
                                    <input type="tel" name="mobile_number" class="form-control border-secondary"
                                           value="<?= htmlspecialchars($userData['mobile_number'] ?? '') ?>">
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <button type="submit" name="update_profile" 
                                            class="btn btn-success px-4">
                                        <i class="bi bi-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Verification Details Card -->
                    <div class="col-md-6">
                        <div class="bg-white p-4 rounded-3 shadow-sm">
                            <h4 class="mb-4 text-success"><i class="bi bi-shield-check me-2"></i>Verification Details</h4>
                            <div class="verification-details">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fw-medium">Status:</span>
                                    <span class="badge <?= ($userData['verification_status'] === 'verified') ? 'bg-success' : 'bg-warning' ?>">
                                        <?= ucfirst($userData['verification_status'] ?? 'pending') ?>
                                    </span>
                                </div>

                                <?php if($userData['verification_status'] === 'verified'): ?>
                                    <div class="verified-info">
                                        <div class="mb-3 pb-2 border-bottom">
                                            <div class="fw-medium">Verified Date</div>
                                            <div class="text-muted"><?= date('F j, Y', strtotime($userData['updated_at'])) ?></div>
                                        </div>
                                        <div class="mb-3 pb-2 border-bottom">
                                            <div class="fw-medium">Co-signee</div>
                                            <div class="text-muted"><?= htmlspecialchars($userData['cosignee_first_name'] . ' ' . $userData['cosignee_last_name']) ?></div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="fw-medium">Relationship</div>
                                            <div class="text-muted"><?= htmlspecialchars($userData['cosignee_relationship']) ?></div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info mt-3">
                                        <i class="bi bi-info-circle me-2"></i>
                                        Account verification pending. Complete verification in account settings.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit profile picture form
        document.querySelector('input[name="profile_picture"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>
</html>