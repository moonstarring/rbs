<?php
// renter/file_dispute.php
session_start();
require_once 'renter_class.php';
require_once '../db/db.php';

$renter = new Renter($conn);
$renter->authenticateRenter();
$userId = $_SESSION['id'];

// Fetch rentals for dropdown
$rentals = $renter->getRentalsForDispute($userId);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = intval($_POST['rental_id']);
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description']);

    $errors = [];
    if (empty($rental_id)) $errors[] = "Rental selection is required.";
    if (empty($reason)) $errors[] = "Reason is required.";
    if (empty($description)) $errors[] = "Description is required.";

    if (empty($errors)) {
        if ($renter->fileDispute($userId, $rental_id, $reason, $description)) {
            $_SESSION['success'] = "Dispute filed successfully.";
        } else {
            $_SESSION['error'] = "Failed to file dispute.";
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }

    header('Location: file_dispute.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <title>File a Dispute</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .image-bg {
            background-image: url('../IMG_5129.JPG');
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
    <div class="container-fluid bg-secondary-subtle p-4">
        <div class="bg-body m-md-4 m-lg-4 p-md-4 p-lg-4 rounded-3 shadow">
            <h2 class="fs-5">File a Dispute</h2>

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

            <div class="card my-4">
                <div class="card-header">
                    Dispute Form
                </div>
                <div class="card-body">
                    <form action="file_dispute.php" method="POST">
                        <div class="mb-3">
                            <label for="rental_id" class="form-label">Select Rental</label>
                            <select class="form-select" id="rental_id" name="rental_id" required>
                                <option value="" disabled selected>Select a rental</option>
                                <?php foreach ($rentals as $rental): ?>
                                    <option value="<?= htmlspecialchars($rental['id']) ?>"><?= htmlspecialchars($rental['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason</label>
                            <input type="text" class="form-control" id="reason" name="reason" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit Dispute</button>
                    </form>
                </div>
            </div>

        </div>
        <div class="bg-body m-md-4 m-lg-4 p-md-4 p-lg-4 rounded-3 shadow">
            <!-- Existing Disputes -->
            <h3 class="fs-5">Your Reports</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Rental</th>
                        <th>Reason</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Filed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $conn->prepare("SELECT d.*, p.name AS product_name 
                                                FROM disputes d 
                                                JOIN rentals r ON d.rental_id = r.id 
                                                JOIN products p ON r.product_id = p.id 
                                                WHERE d.initiated_by = :userId 
                                                ORDER BY d.created_at DESC");
                    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                    $stmt->execute();
                    $userDisputes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!empty($userDisputes)): ?>
                        <?php foreach ($userDisputes as $dispute): ?>
                            <tr>
                                <td><?= htmlspecialchars($dispute['product_name']) ?></td>
                                <td><?= htmlspecialchars($dispute['reason']) ?></td>
                                <td><?= nl2br(htmlspecialchars($dispute['description'])) ?></td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $dispute['status'] === 'open' ? 'warning' : ($dispute['status'] === 'under_review' ? 'info' : ($dispute['status'] === 'resolved' ? 'success' : 'secondary'))
                                                            ?>">
                                        <?= ucfirst($dispute['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($dispute['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No disputes filed.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>