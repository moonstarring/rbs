<?php
// checkout.success.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <title>Checkout Successful</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <style>
        .image-bg {
            background-image: url('../images/IMG_5129.JPG');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
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
                <button class="btn m-0 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                    <i class="bi bi-person-circle r
                    "></i>
                </button>
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
    <div class="container-fluid bg-body mt-5 p-5 h-100">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['success_message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <div class="text-center">
            <h1 class="display-4">Thank You for Your Rental!</h1>
            <p class="lead">Your rental request has been received and is awaiting owner approval.</p>
            <a href="browse.php" class="btn btn-success mt-3"><i class="bi bi-arrow-left-circle"></i> Continue Browsing</a>
        </div>
    </div>
    
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer>
        <div class="d-flex flex-md-column justify-content-between py-2 border-top">
            <p class="ps-3">© 2024 Rentbox. All rights reserved.</p>
            <ul class="list-unstyled d-flex pe-3">
                <li class="ms-3"><a href=""><i class="bi bi-facebook text-body"></i></a></li>
                <li class="ms-3"><a href=""><i class="bi bi-twitter text-body"></i></a></li>
                <li class="ms-3"><a href=""><i class="bi bi-linkedin text-body"></i></a></li>
            </ul>
        </div>
    </footer>
</html>