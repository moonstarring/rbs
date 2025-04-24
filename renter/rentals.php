<?php
// renter/rentals.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once __DIR__ . '/../db/db.php';
require_once 'renter_class.php';
$renter = new renter($conn);
$renter->authenticateRenter();



// Get the logged-in renter's ID
$renterId = $_SESSION['id'];

// Fetch rentals
$rentals = $renter->getRentals($renterId);

// Example usage in HTML
foreach ($rentals as $rental) {
    $statusColor = $renter->getStatusBadgeColor($rental['status']);
    $daysColor = $renter->getRemainingDaysBadgeColor($rental['remaining_days']);
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Rentals</title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .image-bg {
            background-image: url('../IMG_5129.JPG');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
        }

        .card {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            width: 100%;
            max-width: 1200px;
            padding: 2rem;
        }

        .table-responsive {
            max-height: 70vh;
            overflow-y: auto;
        }

        .img-thumbnail {
            height: 100px;
            width: 100px;
            object-fit: cover;
            margin: auto;
        }

        .table th,
        .table td {
            vertical-align: middle;
            text-align: center;
            height: 50px;
        }
    </style>

</head>
<!-- mobile only navigation -->
<nav class="navbar bg-secondary-subtle fixed-bottom d-md-none d-lg-none">
    <div class="container">
        <div class="d-flex justify-content-around align-items-center w-100">
            <a class="navbar-brand" href="browse.php">
                <i class="bi bi-house-fill text-success"></i>
            </a>
            <a class="navbar-brand" href="../renter/cart.php">
                <i class="bi bi-basket3-fill rb"></i>
            </a>
            <a class="m-0 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                <i class="bi bi-person-circle rb m-0 p-0" style="font-size: 20px;"></i>
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

<body class="image-bg">
    <?php include '../includes/navbarr.php'; ?>

    <main class="container-fluid bg-secondary-subtle p-2 p-md-4 p-lg-4">
        <div class="card container-fluid p-2 p-md-3 p-lg-4">
            <h2 class="text-center mb-md-4 mb-lg-4">My Rentals</h2>

            <!-- Success Message -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <div class="table-responsive rounded-3">
                <table class="table table-sm table-striped table-bordered text-center">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Gadget</th>
                            <th>Owner</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Remaining Days</th>
                            <th class="d-none d-md-table-cell d-lg-table-cell">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($rentals)): ?>
                            <?php foreach ($rentals as $index => $rental): ?>
                                <tr class="h-100">
                                    <td><?= htmlspecialchars($index + 1) ?></td>
                                    <td>
                                        <div class="d-flex flex-column align-items-center">
                                            <img src="../img/uploads/<?= htmlspecialchars($rental['image']) ?>"
                                                alt="<?= htmlspecialchars($rental['product_name']) ?>"
                                                class="img-thumbnail d-none d-md-block d-lg-block">
                                            <a href="rental_details.php?rental_id=<?= htmlspecialchars($rental['id']) ?>" class="link-success link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">
                                            <p class="small mt-1 mb-0"><?= htmlspecialchars($rental['product_name']) ?> (<?= htmlspecialchars($rental['brand']) ?>)
                                            </p></a>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($rental['owner_name'] ?? 'Unknown') ?></td>
                                    <td><?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($rental['end_date'] ?? 'N/A') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $renter->getStatusBadgeColor($rental['status']) ?>">
                                            <?= htmlspecialchars($rental['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $renter->getRemainingDaysBadgeColor($rental['remaining_days']) ?>">
                                            <?= htmlspecialchars($rental['remaining_days']) ?>
                                        </span>
                                    </td>
                                    <td class="d-none d-md-table-cell d-lg-table-cell">
                                        <a href="rental_details.php?rental_id=<?= htmlspecialchars($rental['id']) ?>" class="btn btn-info btn-sm text-white">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No rentals found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>