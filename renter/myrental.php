<?php
session_start();
require_once '../db/db.php';
require_once 'renter_class.php';
$renter = new renter($conn);
$renter->authenticateRenter();

$statusMap = [
    'all' => [],
    'pending' => ['pending_confirmation'],
    'pick' => ['ready_for_pickup'],
    'return' => ['returned'],
    'complete' => ['completed'],
    'overdue' => ['overdue'],
    'cancel' => ['cancelled']
];

$currentTab = isset($_GET['tab']) && array_key_exists($_GET['tab'], $statusMap) ? $_GET['tab'] : 'all';

$rentals = $renter->getRentalsByStatus($_SESSION['id'], $statusMap[$currentTab]);
$error = null;
$success = null;

$userData = $renter->getUserData($_SESSION['id']);
$verificationData = $renter->getVerificationData($_SESSION['id']);
$userData = array_merge($userData, $verificationData ?: []);

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
        .image-bg {
            background-image: url('../images/output.jpg');
            background-size: cover;
            background-position: center 70%;
            background-repeat: no-repeat;
            height: 100vh;
        }

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
                            <?php if ($userData['verification_status'] === 'verified'): ?>
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

                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <a href="myrental.php" class="text-decoration-none">
                                    <button class="accordion-button collapsed rounded-3 fw-bold w-100 text-start" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo"
                                        aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                                        <i class="bi bi-box2-heart-fill me-2"></i>My Rentals
                                    </button>
                                </a>
                            </h2>
                        </div>

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
                                        <?php if ($userData['verification_status'] === 'verified'): ?>
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

                <div class="bg-body p-4 rounded-3 m-0">
                    <p class="fs-5 fw-bold ms-3">My Rentals</p>
                    <hr>
                    <ul class="nav nav-tabs d-flex justify-content-around" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?> text-dark px-3" href="?tab=all">All</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'pending' ? 'active' : '' ?> text-dark px-3" href="?tab=pending">Pending Approval</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'pick' ? 'active' : '' ?> text-dark px-3" href="?tab=pick">For Pick Up</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'return' ? 'active' : '' ?> text-dark px-3" href="?tab=return">Returned</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'complete' ? 'active' : '' ?> text-dark px-3" href="?tab=complete">Completed</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'overdue' ? 'active' : '' ?> text-dark px-3" href="?tab=overdue">Overdue</a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link <?= $currentTab === 'cancel' ? 'active' : '' ?> text-dark px-3" href="?tab=cancel">Cancelled</a>
                        </li>
                    </ul>

                    <div class="tab-content mt-3" id="myTabContent">
                        <div class="tab-pane fade show active" role="tabpanel">
                            <?php if (empty($rentals)): ?>
                                <div class="alert alert-info">No rentals found in this category</div>
                            <?php else: ?>
                                <?php foreach ($rentals as $rental): ?>
                                    <div class="bg-body mt-3 rounded-3 p-3">
                                        <div class="row">
                                            <div class="col d-flex align-items-center ms-3 gap-2 mb-2">
                                                <p class="m-0 p-0">
                                                    <span class="badge text-bg-success me-2">Verified</span>
                                                    <?= htmlspecialchars($rental['owner_name']) ?>
                                                </p>
                                                <div class="d-flex gap-2">
                                                    <a href="review.php?owner_id=<?= $rental['owner_id'] ?>" class="btn btn-outline-secondary m-0 px-2">View Profile</a>
                                                </div>
                                            </div>
                                            <div class="col d-flex align-items-center justify-content-end me-3 gap-2 mb-2">
                                                <p class="m-0 p-0 text-success">
                                                    <?= strtoupper(str_replace('_', ' ', $rental['status'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <hr class="mb-3 mt-0 p-0">
                                        <div class="row m-0 p-0">
                                            <div class="col-2 m-0 p-0">
                                                <a href="rental_details.php?rental_id=<?= $rental['id'] ?>">
                                                    <img src="../img/uploads/<?= htmlspecialchars($rental['product_image']) ?>"
                                                        alt="product"
                                                        class="img-thumbnail border-3 shadow-sm">
                                                </a>
                                            </div>
                                            <div class="col-10 m-0 p-3 d-flex flex-column justify-content-around">
                                                <div class="m-0 p-0">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <a href="rental_details.php?rental_id=<?= $rental['id'] ?>"
                                                            class="fs-5 fw-bold m-0 p-0 text-decoration-none text-dark">
                                                            <?= htmlspecialchars($rental['product_name']) ?>
                                                        </a>
                                                        <a href="rental_details.php?rental_id=<?= $rental['id'] ?>"
                                                            class="btn btn-outline-primary btn-sm">
                                                            <i class="bi bi-eye-fill"></i> View
                                                        </a>
                                                    </div>
                                                    <p class="fs-6 text-secondary m-0 p-0">
                                                        <?= date('M d, Y', strtotime($rental['start_date'])) ?> -
                                                        <?= date('M d, Y', strtotime($rental['end_date'])) ?>
                                                    </p>
                                                </div>
                                                <div class="d-flex justify-content-end align-items-baseline gap-2">
                                                    <p class="fs-6 text-secondary m-0 p-0">Payment Total:</p>
                                                    <p class="fs-4 fw-bold m-0 p-0">₱<?= number_format($rental['total_cost'], 2) ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php require_once '../includes/footer.php' ?>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelector('input[name="profile_picture"]').addEventListener('change', function() {
            this.form.submit();
        });
    </script>
</body>

</html>