<?php
error_log("Form Action: " . $_POST['action']);
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Cache-Control: no-cache, must-revalidate");
header("Expires: 0");
require_once '../db/db.php';
require_once 'owner_class.php';

$owner = new owner($conn);
$owner->authenticateOwner();

$rentalId = $_GET['rental_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "CSRF token mismatch.";
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    }

    try {
        $action = $_POST['action'];
        $owner->handleRentalAction($rentalId, $action, $_POST, $_FILES);

        // Fetch the updated rental details to get the current status after cancellation
        $rental = $owner->getRentalDetails($rentalId);
        $currentStatus = $rental['status'];

        // Get the status flow
        $statusFlow = $owner->getStatusFlow();

        // Filter out the 'cancelled' status from the status flow
        $filteredStatusFlow = array_filter($statusFlow, function ($key) {
            return $key !== 'cancelled'; // Exclude 'cancelled' status
        }, ARRAY_FILTER_USE_KEY);

        $_SESSION['success'] = "Rental has been $action.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header("Location: view_rental.php?rental_id=$rentalId");
    exit();
}


if (!($rental = $owner->getRentalDetails($rentalId))) {
    $_SESSION['error'] = "Rental not found.";
    header('Location: rentals.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $renterRating = filter_input(INPUT_POST, 'renter_rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    $productRating = filter_input(INPUT_POST, 'product_rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    $renterComment = filter_input(INPUT_POST, 'renter_comment', FILTER_SANITIZE_SPECIAL_CHARS);
    $productComment = filter_input(INPUT_POST, 'product_comment', FILTER_SANITIZE_SPECIAL_CHARS);

    // Strict validation
    if (!$renterRating || !$productRating || !$renterComment || !$productComment) {
        $_SESSION['error'] = "All fields are required, and ratings must be between 1 and 5.";
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    }

    try {
        $conn->beginTransaction();
        
        // Insert into renter_reviews
        $stmt = $conn->prepare("
            INSERT INTO renter_reviews 
                (renter_id, owner_id, rental_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $rental['renter_id'], 
            $owner->getUserId(), 
            $rentalId, 
            $renterRating, 
            $renterComment
        ]);
        
        // Insert into comments
        $stmt = $conn->prepare("
            INSERT INTO comments 
                (product_id, renter_id, rating, comment, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $rental['product_id'], 
            $rental['renter_id'], 
            $productRating, 
            $productComment
        ]);

        $conn->commit();
        $_SESSION['success'] = "Feedback submitted successfully.";
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: view_rental.php?rental_id=$rentalId");
        exit();
    }
}

$currentStatus = $rental['status'];
$proofsByType = [
    'handed_over_to_admin' => [],  
    'picked_up' => [],            
    'returned' => []          
];

foreach ($owner->getProofs($rentalId) as $proof) {
    $type = $proof['proof_type'];
    if (array_key_exists($type, $proofsByType)) {
        $proofsByType[$type][] = $proof;
    }
}
$statusFlow = $owner->getStatusFlow();
$proofs = $owner->getProofs($rentalId);
$remainingDays = $owner->calculateRemainingDays($rental['end_date'] ?? null);
$hasOwnerReview = $owner->hasOwnerReview($rentalId);
$csrfToken = $owner->generateCsrfToken();

if ($currentStatus === 'cancelled') {
    $filteredStatusFlow = ['cancelled' => 'Cancelled'];
} else {
    $filteredStatusFlow = array_filter($statusFlow, function ($key) {
        return !in_array($key, ['cancelled', 'overdue']);
    }, ARRAY_FILTER_USE_KEY);
}

// In the progress container
foreach ($filteredStatusFlow as $key => $label) {
    $isActive = $owner->isStatusActive($key, $currentStatus, $filteredStatusFlow);
    // Use $isActive in your HTML
}


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rental Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/owner/renter_details.css">
    <style>
        .progress-container {
            position: relative;
            display: flex;
            justify-content: space-between;
            margin: 40px 0 60px;
        }
        .progress-line {
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            height: 4px;
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
        .progress-step .label {
            position: absolute;
            top: 50px;
            width: 120px;
            text-align: center;
        }
        .proof-links {
            position: absolute;
            top: 80px;
            width: 100%;
            display: flex;
            justify-content: space-between;
        }
        .proof-link {
            width: 120px;
            text-align: center;
        }
    </style>
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>

<main>
    <div class="card">
        <div class="card-header">Rental Details</div>
        <div class="card-body">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <h5 class="card-title">Rental ID: <?= htmlspecialchars($rental['id']) ?></h5>
            <p class="card-text"><strong>Rental Date:</strong> <?= htmlspecialchars($rental['created_at'] ?? 'N/A') ?></p>
            <p class="card-text"><strong>Start Date:</strong> <?= htmlspecialchars($rental['start_date'] ?? 'N/A') ?></p>

            <?php if ($currentStatus === 'returned' && !$hasOwnerReview): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#feedbackModal">
                    Give Feedback
                </button>
            <?php endif; ?>

            <?php if ($currentStatus === 'pending_confirmation'): ?>
                <div class="mb-4">
                <form method="post" class="d-inline">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="action" value="approve">
    <button type="submit" class="btn btn-success me-2">Approve Rental</button>
</form>

                    <form method="post" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="cancel">
                        <button type="submit" class="btn btn-danger">Cancel Rental</button>
                    </form>
                </div>
            <?php endif; ?>

<div class="progress-container">
    <?php if ($currentStatus !== 'cancelled'): ?>
        <div class="progress-line"></div>
    <?php endif; ?>

    <?php foreach ($filteredStatusFlow as $key => $label): ?>
        <div class="progress-step <?= $owner->isStatusActive($key, $currentStatus, $filteredStatusFlow) ? 'active' : '' ?>">
            <div class="circle"><?= $key === $currentStatus ? "✔" : "" ?></div>
            <div class="label">
                <?= htmlspecialchars($label) ?>
                
                <?php if ($currentStatus !== 'cancelled'): ?>
                    <?php 
                    // Map status steps to proof types
                    $proofType = match($key) {
                        'ready_for_pickup' => 'handed_over_to_admin', // Show admin handover proofs under "With Admin"
                        'picked_up' => 'picked_up',
                        'returned' => 'returned',
                        default => null
                    };
                    ?>
                    
                    <?php if ($proofType && !empty($proofsByType[$proofType])): ?>
                        <div class="mt-2">
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
            </div>
        </div>
    <?php endforeach; ?>
</div>

</main>

<div class="modal fade" id="feedbackModal" tabindex="-1" aria-labelledby="feedbackModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="feedbackModalLabel">Submit Feedback</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Renter Rating (1-5)</label>
                        <select class="form-select" name="renter_rating" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Commend for Renter</label>
                        <textarea class="form-control" name="renter_comment" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Rating (1-5)</label>
                        <select class="form-select" name="product_rating" required>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comment for Product</label>
                        <textarea class="form-control" name="product_comment" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Feedback</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 800px;">
        <div class="modal-content">
            <div class="modal-header">
                <div class="d-flex justify-content-between w-100 align-items-center">
                    <h5 class="modal-title m-0" id="proofModalLabel"></h5>
                    <span id="proofDate" class="text-muted small me-3"></span>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofImage" src="" alt="Proof Image" style="max-width: 80%; height: auto; margin: 0 auto;">
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
                <div id="proofCarousel" class="carousel slide" data-bs-ride="carousel">
                    <div class="carousel-inner" id="proofItems">
                        <!-- Dynamic content -->
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#proofCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Previous</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#proofCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        <span class="visually-hidden">Next</span>
                    </button>
                </div>
                <div id="proofList" class="d-none">
                    <!-- List view fallback -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const proofModal = new bootstrap.Modal(document.getElementById('proofDetailsModal'));
    
    document.getElementById('proofDetailsModal').addEventListener('show.bs.modal', function(event) {
        const button = event.relatedTarget;
        const proofType = button.getAttribute('data-bs-type');
        const rentalId = button.getAttribute('data-bs-rental');
        const titleMap = {
            'handed_over_to_admin': 'Owner Handover Proofs',
            'picked_up': 'Renter Pickup Proofs',
            'returned': 'Return Completion Proofs'
        };

        this.querySelector('.modal-title').textContent = titleMap[proofType];
        
        // Fetch proofs via AJAX
        fetch(`get_proofs.php?rental_id=${rentalId}&type=${proofType}`)
            .then(response => response.json())
            .then(proofs => {
                const carouselInner = document.getElementById('proofItems');
                const proofList = document.getElementById('proofList');
                carouselInner.innerHTML = '';
                proofList.innerHTML = '';

                if (proofs.length === 0) {
                    carouselInner.innerHTML = '<div class="text-center py-4">No proofs found</div>';
                    return;
                }

                proofs.forEach((proof, index) => {
                    const isImage = /\.(jpg|jpeg|png|gif)$/i.test(proof.proof_url);
                    const proofUrl = proof.proof_url.startsWith('http') ? 
                        proof.proof_url : 
                        `../uploads/proofs/${proof.proof_url}`;

                    // Carousel items
                    const carouselItem = `
                        <div class="carousel-item ${index === 0 ? 'active' : ''}">
                            <div class="text-center">
                                ${isImage ? 
                                    `<img src="${proofUrl}" class="d-block w-100" style="max-height: 500px; object-fit: contain;">` :
                                    `<iframe src="${proofUrl}" style="width:100%; height:500px;" frameborder="0"></iframe>`
                                }
                                <div class="carousel-caption bg-dark bg-opacity-75">
                                    <p class="mb-0">${proof.description || 'No description'}</p>
                                    <small>Uploaded: ${new Date(proof.created_at).toLocaleDateString()}</small>
                                </div>
                            </div>
                        </div>
                    `;
                    carouselInner.innerHTML += carouselItem;

                    // List view items
                    const listItem = `
                        <div class="card mb-3">
                            <div class="card-body">
                                ${isImage ? 
                                    `<img src="${proofUrl}" class="img-fluid mb-3">` :
                                    `<a href="${proofUrl}" target="_blank" class="btn btn-primary mb-3">View Document</a>`
                                }
                                <p class="mb-1"><strong>Description:</strong> ${proof.description || 'No description'}</p>
                                <small class="text-muted">Uploaded: ${new Date(proof.created_at).toLocaleDateString()}</small>
                            </div>
                        </div>
                    `;
                    proofList.innerHTML += listItem;
                });

                // Show appropriate view
                if (proofs.length > 1) {
                    document.getElementById('proofCarousel').classList.remove('d-none');
                    document.getElementById('proofList').classList.add('d-none');
                } else {
                    document.getElementById('proofCarousel').classList.add('d-none');
                    document.getElementById('proofList').classList.remove('d-none');
                }
            });
    });
});
</script>
</body>
</html>