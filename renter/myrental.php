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
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <style>
        .image-bg {
            background-image: url('../images/IMG_5129.JPG');
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
    </style>
</head>

<body class="image-bg">
    <?php require_once '../includes/navbarr.php'; ?>
    <!-- mobile only navigation -->
    <nav class="navbar bg-secondary-subtle fixed-bottom d-md-none d-lg-none">
        <div class="container">
            <div class="d-flex justify-content-around align-items-center w-100">
                <a class="navbar-brand" href="browse.php">
                    <i class="bi bi-house-fill rb"></i>
                </a>
                <a class="navbar-brand" href="../renter/cart.php">
                    <i class="bi bi-basket3-fill rb"></i>
                </a>
                <a class="m-0 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                    <i class="bi bi-person-circle text-success m-0 p-0" style="font-size: 20px;"></i>
                </a>
            </div>
        </div>
    </nav>
    <!-- mobile only sidebar -->
    <div class="offcanvas offcanvas-end d-md-none d-lg-none" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
        <div class="offcanvas-header">
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= htmlspecialchars($profilePic) ?>" class="object-fit-cover border rounded-circle" alt="pfp" style="width:100px; height: 100px;">
                <div class="">
                    <p class="dropdown-item-text fs-5 fw-bold m-0"><?= htmlspecialchars($username) ?></p>
                    <a class="link-success fw-bold" href="" id="toggleRoleButton" data-bs-toggle="modal" data-bs-target="#becomeOwnerModal">Become an Owner</a>
                </div>
            </div>
            <div class="d-flex flex-column gap-3 mt-3">
                <hr class="m-0 p-0">
                <a class="active text-decoration-none" href="profile.php"><i class="bi bi-gear-fill me-2"></i>Profile</a>
                <a class="active text-decoration-none" href="rentals.php"><i class="bi bi-box2-heart-fill me-2"></i>Rentals</a>
                <hr class="m-0 p-0">
                <a class="active text-decoration-none" href="supports.php"><i class="bi bi-headset me-2"></i>Supports</a>
                <a class="active text-decoration-none" href="file_dispute.php"><i class="bi bi-file-earmark-x-fill me-2"></i>File Dispute</a>
                <hr class="m-0 p-0">
                <a class="active text-decoration-none" href="../includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a>
            </div>
        </div>
    </div>

    <div class="row m-0 p-0 overflow-auto">
        <div class="row bg-dark-subtle px-3 pb-5 pt-3 m-0">
            <!-- Left Sidebar -->
            <div class="col-12 col-md-3 col-lg-3 bg-body p-4 rounded-3 shadow-sm me-md-3 me-lg-3">
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

                <!-- Sidebar Menu -->
                <div class="d-flex flex-column gap-2 mt-3 overflow-auto">
                    <div class="accordion border-0" id="accordionPanelsStayOpen">
                        <!-- Account Section -->
                        <div class="accordion-item border-0">
                            <h2 class="accordion-header">
                                <button class="accordion-button rounded-3 fw-bold bg-body" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="false"
                                    aria-controls="panelsStayOpen-collapseOne">
                                    <i class="bi bi-person-fill-gear me-2"></i>My Account
                                </button>
                            </h2>
                            <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse">
                                <div class="accordion-body  p-3">
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
            <div class="col p-0 mt-3 mt-md-0 mt-lg-0">
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

                <div class="bg-body py-3 px-2 p-md-4 p-lg-4 rounded-3 m-0 ">
                    <p class="fs-5 fw-bold ms-3 mb-0 ">My Rentals</p>
                    <hr class="my-2 p-0">
                    <ul class="smoll nav nav-tabs d-flex justify-content-around" id="myTab" role="tablist">
                        <li class="nav-item d-md-none d-lg-none" role="presentation">
                            <a class="nav-link <?= $currentTab === 'all' ? 'active' : '' ?> text-dark px-3" href="?tab=all">All</a>
                        </li>
                        <li class="nav-item d-md-none d-lg-none" role="presentation">
                            <a class="nav-link <?= $currentTab === 'pending' ? 'active' : '' ?> text-dark px-3" href="?tab=pending">Pending Approval</a>
                        </li>

                        <div class="d-none d-md-flex d-lg-flex">
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
                        </div>
                        <!-- mobile specific tabs -->
                        <li class="nav-item d-md-none d-lg-none" role="presentation">
                            <div class="dropdown">
                                <button class="btn dropdown-toggle smoll" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    More
                                </button>
                                <ul class="dropdown-menu">
                                    <li class="nav-item dropdown-item" role="presentation">
                                        <a class="nav-link <?= $currentTab === 'pick' ? 'active' : '' ?> text-dark px-3" href="?tab=pick">For Pick Up</a>
                                    </li>
                                    <li class="nav-item dropdown-item" role="presentation">
                                        <a class="nav-link <?= $currentTab === 'return' ? 'active' : '' ?> text-dark px-3" href="?tab=return">Returned</a>
                                    </li>
                                    <li class="nav-item dropdown-item" role="presentation">
                                        <a class="nav-link <?= $currentTab === 'complete' ? 'active' : '' ?> text-dark px-3" href="?tab=complete">Completed</a>
                                    </li>
                                    <li class="nav-item dropdown-item" role="presentation">
                                        <a class="nav-link <?= $currentTab === 'overdue' ? 'active' : '' ?> text-dark px-3" href="?tab=overdue">Overdue</a>
                                    </li>
                                    <li class="nav-item dropdown-item" role="presentation">
                                        <a class="nav-link <?= $currentTab === 'cancel' ? 'active' : '' ?> text-dark px-3" href="?tab=cancel">Cancelled</a>
                                    </li>
                                </ul>
                            </div>
                        </li>


                    </ul>

                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" role="tabpanel">
                            <?php if (empty($rentals)): ?>
                                <div class="alert alert-info mx-1 mx-md-0 mx-lg-0 ">No rentals found in this category</div>
                            <?php else: ?>
                                <?php foreach ($rentals as $rental): ?>
                                    <div class="container-fluid p-1 p-md-3 p-lg-3 bg-body mt-3 rounded-3 border pb-2 shadow-sm">
                                        <div class="row">
                                            <div class="col d-flex align-items-center ms-3 gap-2 mb-2">
                                                <p class="m-0 p-0 smolll">
                                                    <span class="badge text-bg-success me-2">Verified</span>
                                                </p>
                                                <div class="d-flex">
                                                    <a href="review.php?owner_id=<?= $rental['owner_id'] ?>" class="btn btn-outline-secondary m-0 px-2 smolll">View Profile</a>
                                                </div>
                                            </div>
                                            <div class="col d-flex align-items-center justify-content-end me-3 gap-2 mb-2">
                                                <p class="m-0 p-0 text-success  smolll">
                                                    <?= strtoupper(str_replace('_', ' ', $rental['status'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                        <hr class="mb-3 mt-0 p-0">
                                        <div class="d-flex m-0 p-0">
                                            <a href="rental_details.php?rental_id=<?= $rental['id'] ?>">
                                                <img src="../img/uploads/<?= htmlspecialchars($rental['product_image']) ?>"
                                                    alt="product"
                                                    class="border rent-img border-3 shadow-sm">
                                            </a>
                                            <div class=" container-fluid p-1 p-md-3 p-lg-3 d-flex flex-column justify-content-around">
                                                    <div class="d-flex justify-content-between align-items-top">
                                                        <a href="rental_details.php?rental_id=<?= $rental['id'] ?>"
                                                            class="fw-bold m-0 p-0 text-decoration-none text-dark smoll">
                                                            <?= htmlspecialchars($rental['product_name']) ?>
                                                        </a>
                                                        <div class="">
                                                            <a href="rental_details.php?rental_id=<?= $rental['id'] ?>"
                                                                class="btn btn-outline-primary btn-sm smolll d-flex">
                                                                <i class="bi bi-eye-fill pe-1"></i> View
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <p class="text-secondary m-0 p-0 smol">
                                                        <?= date('M d, Y', strtotime($rental['start_date'])) ?> -
                                                        <?= date('M d, Y', strtotime($rental['end_date'])) ?>
                                                    </p>
                                                <div class="d-flex justify-content-end align-items-baseline gap-2 ms-auto">
                                                    <p class="text-secondary m-0 p-0 smol">Payment Total:</p>
                                                    <p class="fw-bold m-0 p-0 smoll">₱<?= number_format($rental['total_cost'], 2) ?></p>
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