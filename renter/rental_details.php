<?php
error_log("DEBUG: Current rental status: " . ($rental['status'] ?? 'unknown'));
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';
require_once 'renter_class.php';
$renter = new renter($conn);
$renter->authenticateRenter();

$renterId = $_SESSION['id'];
$rentalId = filter_input(INPUT_GET, 'rental_id', FILTER_VALIDATE_INT);
$csrfToken = $renter->generateCsrfToken();
if (!$rentalId) {
    $_SESSION['error'] = "Invalid rental ID";
    header('Location: rentals.php');
    exit();
}

$rental = $renter->getRentalDetails($renterId, $rentalId);
if (!$rental) {
    $_SESSION['error'] = "Rental not found";
    header('Location: rentals.php');
    exit();
}
// Feedback submission handling
if (isset($_POST['submit_feedback'])) {
    try {
        // Validate input
        $productRating = filter_input(INPUT_POST, 'product_rating', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5]
        ]);
        $productComment = filter_input(INPUT_POST, 'product_comment', FILTER_SANITIZE_STRING);
        $ownerRating = filter_input(INPUT_POST, 'owner_rating', FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 5]
        ]);
        $ownerComment = filter_input(INPUT_POST, 'owner_comment', FILTER_SANITIZE_STRING);

        if (!$productRating || !$productComment || !$ownerRating || !$ownerComment) {
            throw new Exception("All feedback fields are required and must be valid.");
        }

        // Start transaction
        $conn->beginTransaction();

        // Insert product feedback
        $stmt = $conn->prepare("
            INSERT INTO comments 
                (product_id, renter_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $rental['product_id'],
            $renterId,
            $productRating,
            $productComment
        ]);

        // Insert owner review
        $stmt = $conn->prepare("
            INSERT INTO owner_reviews 
                (owner_id, renter_id, rental_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $rental['owner_id'],
            $renterId,
            $rentalId,
            $ownerRating,
            $ownerComment
        ]);

        // Check if owner has already submitted feedback
        $ownerFeedbackCheck = $conn->prepare("
            SELECT id FROM renter_reviews 
            WHERE rental_id = ?
        ");
        $ownerFeedbackCheck->execute([$rentalId]);

        // Update status to completed only if both parties have submitted feedback
        if ($ownerFeedbackCheck->rowCount() > 0) {
            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'completed' 
                WHERE id = ?
            ");
            $stmt->execute([$rentalId]);
        } else {
            // Update to returned if only renter has submitted
            $stmt = $conn->prepare("
                UPDATE rentals 
                SET status = 'returned' 
                WHERE id = ?
            ");
            $stmt->execute([$rentalId]);
        }

        $conn->commit();
        if ($renter->hasReceivedFeedbackFromOwner($rentalId)) {
            $renter->updateRentalStatus($rentalId, 'completed');
        } else {
            $renter->updateRentalStatus($rentalId, 'returned');
        }
        $_SESSION['success'] = "Feedback submitted successfully!";
        header("Location: rental_details.php?rental_id=" . $rentalId);
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = $e->getMessage();
        header("Location: rental_details.php?rental_id=" . $rentalId);
        exit();
    }
}

// Get rental data

$currentStatus = $rental['status'];
$allProofs = $renter->getProofs($rentalId);
$today = new DateTime('today');
$originalEndDate = new DateTime($rental['end_date']);
$currentEndDate = new DateTime($rental['end_date']);
$remainingDays = $currentEndDate->diff($today)->days;
$isFrozen = $rental['end_date'] != $originalEndDate->format('Y-m-d');


// Handle other POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$renter->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Security verification failed. Please refresh the page and try again.");
        }

        if (isset($_POST['end_rental'])) {
            $renter->endRental($rentalId);
            $_SESSION['success'] = "Rental successfully terminated!";
        }

        header("Location: rental_details.php?rental_id=" . $rentalId);
        exit();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: rental_details.php?rental_id=" . $rentalId);
        exit();
    }
}

// Prepare proofs data
$proofsByType = [
    'handed_over_to_admin' => [],
    'picked_up' => [],
    'returned' => []
];

foreach ($allProofs as $proof) {
    $type = $proof['proof_type'];
    if (array_key_exists($type, $proofsByType)) {
        $proofsByType[$type][] = $proof;
    }
}

// Check feedback status
$hasFeedback = $renter->checkFeedback($rental['product_id'], $renterId);
$hasOwnerReview = $renter->checkOwnerReview($rentalId, $renterId);

// Status flow configuration
$statusFlow = [
    'pending_confirmation' => 'Pending',
    'approved' => 'Approved',
    'ready_for_pickup' => 'Ready for Pickup',
    'picked_up' => 'In Possession',
    'returned' => 'Returned',
    'completed' => 'Completed'
];

if (!array_key_exists($currentStatus, $statusFlow)) {
    $currentStatus = 'pending';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <title>Rental Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        .image-bg {
            background-image: url('../IMG_5129.JPG');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100vh;
        }

        .progress-container {
            position: relative;
            display: flex;
            justify-content: space-between;
        }

        .progress-line {
            top: 13px;
            bottom: auto;
            left: 0;
            right: 0;
            height: 4px;
            position: absolute;
            background-color: rgb(182, 184, 186);
            z-index: 0;
        }

        .progress-step {
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
            z-index: 1;
            width: 100%;
        }

        .progress-step .circle {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: #fff;
            border: 3px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 8px;
        }

        .progress-step.active .circle {
            border-color: #0d6efd;
            background-color: #0d6efd;
            color: white;
        }

        .progress-step .label {
            font-size: 0.9rem;
            color: #6c757d;
            white-space: nowrap;
            position: absolute;
            top: 50px;
            width: 120px;
            text-align: center;
        }

        .progress-step.ready_for_pickup .circle {
            border-color: #ffc107;
            background-color: #ffc107;
        }

        .progress-step.return_pending .circle {
            border-color: #fd7e14;
            background-color: #fd7e14;
        }
    </style>
</head>

<body class="image-bg">
    <?php include '../includes/navbarr.php'; ?>
    <!-- mobile only navigation -->
    <nav class="navbar bg-secondary-subtle fixed-bottom d-md-none d-lg-none border-top">
        <div class="container">
            <div class="d-flex justify-content-around align-items-center w-100">
                <a class="navbar-brand" href="browse.php">
                    <i class="bi bi-house-fill rb"></i>
                </a>
                <a class="navbar-brand" href="../renter/cart.php">
                    <i class="bi bi-basket3-fill rb"></i>
                </a>
                <a class="navbar-brand m-0 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                    <i class="bi bi-person-circle m-0 p-0 text-success" style="font-size: 20px;"></i>
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

    <main class="container-fluid bg-secondary-subtle rounded h-100 p-2 p-md-4 p-lg-4 ">
        <div class="card shadow">
            <div class="card-header">Rental Details</div>
            <div class="card-body">
                <div class="alert-container">
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                </div>

                <h5 class="card-title">Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
                <p class="card-text"><strong>Rental Date:</strong> <?= htmlspecialchars($rental['created_at'] ?? 'N/A') ?></p>
                <div class="progress-container container-fluid m-0 mb-5 mb-md-0 mb-lg-0">
                    <?php if ($currentStatus !== 'cancelled'): ?>
                        <div class="progress-line"></div>
                    <?php endif; ?>

                    <?php foreach ($statusFlow as $key => $label): ?>
                        <div class="progress-step  <?= $renter->isStatusActive($key, $currentStatus, $statusFlow) ? 'active' : '' ?>">
                            <div class="circle m-0"><?= $key === $currentStatus ? "✔" : "" ?></div>
                            <div class="label">
                                <p class="smolll">
                                    <?= htmlspecialchars($label) ?>

                                    <?php if (!in_array($key, ['pending_confirmation', 'approved'])): ?>
                                        <?php


                                        $proofType = match ($key) {
                                            'ready_for_pickup' => 'handed_over_to_admin',
                                            'picked_up' => 'picked_up',
                                            'returned' => 'returned',
                                            default => null
                                        };
                                        ?>

                                        <?php if ($proofType && !empty($proofsByType[$proofType])): ?>
                                <div class="mt-md-2 mt-lg-2">
                                    <a href="#" class="text-primary small view-proofs"
                                        data-bs-toggle="modal"
                                        data-bs-target="#proofDetailsModal"
                                        data-bs-type="<?= $proofType ?>"
                                        data-bs-rental="<?= $rentalId ?>">
                                        View Proofs
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="rental-summary d-flex align-items-center mt-4">
                    <img src="../img/uploads/<?= htmlspecialchars($rental['image']) ?>"
                        alt="<?= htmlspecialchars($rental['product_name']) ?>"
                        class="img-thumbnail"
                        style="width: 150px; height: auto; object-fit: cover;">
                    <div class="ms-3">
                        <h5><?= htmlspecialchars($rental['product_name']) ?></h5>
                        <p>Brand: <?= htmlspecialchars($rental['brand']) ?></p>
                        <p><strong>₱<?= number_format($rental['rental_price'], 2) ?></strong> / <?= htmlspecialchars($rental['rental_period']) ?></p>
                    </div>
                </div>

                <?php if ($currentStatus === 'delivery_in_progress'): ?>
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $renter->generateCsrfToken() ?>">
                        <div class="mb-3">
                            <label class="form-label">Upload Delivery Confirmation</label>
                            <input type="file" class="form-control" name="proof_of_delivered" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Upload Confirmation</button>
                    </form>
                <?php endif; ?>

                <!-- Modified rental-actions section -->
                <div class="rental-actions mt-4">
                    <?php if (!$hasOwnerReview): ?>
                        <?php if ($currentStatus === 'returned'): ?>
                            <!-- Feedback Button -->
                            <button type="button" class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#endRentalModal">
                                <i class="bi bi-chat-left-text"></i> Submit Feedback
                            </button>
                        <?php else: ?>
                            <?php if ($isFrozen || $remainingDays <= 0): ?>
                                <!-- Initiate Return Button -->
                                <form method="post" enctype="multipart/form-data" class="d-inline">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <div class="input-group">
                                        <input type="file" class="form-control" name="return_proof" required
                                            accept="image/*,.pdf">
                                        <button type="submit" name="initiate_return" class="btn btn-warning btn-lg">
                                            <i class="bi bi-box-arrow-up"></i> Initiate Return
                                        </button>
                                    </div>
                                </form>
                            <?php elseif ($remainingDays > 0 && $currentStatus === 'picked_up' && $currentStatus !== 'completed'): ?>
                                <!-- End Rental Button (only show if picked_up status) -->
                                <form method="post" class="d-inline ms-2">
                                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                                    <button type="submit" name="end_rental" class="btn btn-danger btn-lg"
                                        onclick="return confirm('This will freeze the rental timer. Continue?')">
                                        <i class="bi bi-x-circle"></i> End Rental Early
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="modal fade" id="endRentalModal" tabindex="-1" aria-labelledby="endRentalModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="endRentalModalLabel">Provide Feedback Before Returning the Item</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="returnItemForm" method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                    <h6>Product Feedback</h6>
                                    <div class="mb-3">
                                        <label for="product_rating" class="form-label">Product Rating (1-5)</label>
                                        <select class="form-select" id="product_rating" name="product_rating" required>
                                            <option value="" selected disabled>Select rating</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="product_comment" class="form-label">Product Comment</label>
                                        <textarea class="form-control" id="product_comment" name="product_comment" rows="3" required></textarea>
                                    </div>
                                    <h6>Owner Feedback</h6>
                                    <div class="mb-3">
                                        <label for="owner_rating" class="form-label">Owner Rating (1-5)</label>
                                        <select class="form-select" id="owner_rating" name="owner_rating" required>
                                            <option value="" selected disabled>Select rating</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="owner_comment" class="form-label">Owner Comment</label>
                                        <textarea class="form-control" id="owner_comment" name="owner_comment" rows="3" required></textarea>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback and End Rental</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="proofDetailsModal" tabindex="-1" aria-labelledby="proofDetailsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="proofDetailsModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div id="proofContent"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const proofModal = new bootstrap.Modal(document.getElementById('proofDetailsModal'));

            document.getElementById('proofDetailsModal').addEventListener('show.bs.modal', function(event) {
                const button = event.relatedTarget;
                const proofType = button.getAttribute('data-bs-type');
                const rentalId = button.getAttribute('data-bs-rental');
                const titleMap = {
                    'handed_over_to_admin': 'Ready for Pickup Proofs',
                    'picked_up': 'In Possession Proofs',
                    'returned': 'Return Completion Proofs'
                };

                this.querySelector('.modal-title').textContent = titleMap[proofType];

                // Show loading state
                const content = document.getElementById('proofContent');
                content.innerHTML = '<div class="text-center py-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';

                fetch(`get_proofs.php?rental_id=${rentalId}&type=${proofType}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! Status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(proofs => {
                        console.log('Proofs data:', proofs);

                        content.innerHTML = '';

                        if (proofs.length === 0) {
                            content.innerHTML = '<div class="text-center py-4">No proofs found</div>';
                            return;
                        }

                        proofs.forEach(proof => {
                            const isImage = /\.(jpg|jpeg|png|gif)$/i.test(proof.proof_url);

                            // Try different paths based on your server structure
                            const proofUrl = `../uploads/proofs/${proof.proof_url}`;

                            content.innerHTML += `
                        <div class="card mb-3">
                            <div class="card-body">
                                ${isImage ? 
                                    `<img src="${proofUrl}" class="img-fluid mb-2 rounded" 
                                         onerror="this.onerror=null; this.style.display='none'; this.nextElementSibling.style.display='block';">
                                     <div class="alert alert-warning" style="display:none">Image could not be loaded. Path tried: ${proofUrl}</div>` :
                                    `<a href="${proofUrl}" target="_blank" class="btn btn-sm btn-primary">
                                        View Document
                                    </a>`
                                }
                                <p class="mt-2 mb-0 small">${proof.description || 'No description provided'}</p>
                                <small class="text-muted d-block mt-1">
                                    Uploaded: ${new Date(proof.created_at).toLocaleDateString()}
                                </small>
                            </div>
                        </div>
                    `;
                        });
                    })
                    .catch(error => {
                        console.error('Fetch Error:', error);
                        content.innerHTML =
                            `<div class="alert alert-danger">
                        <p>Error loading proofs: ${error.message}</p>
                        <p>Please check the console for more details.</p>
                    </div>`;
                    });
            });
        });
    </script>
</body>

</html>