<?php
session_start();
require_once __DIR__ . '/../db/db.php'; // Your existing DB connection

// Check if owner_id is provided
$owner_id = isset($_GET['owner_id']) ? (int)$_GET['owner_id'] : 0;
if (!$owner_id) die("Invalid owner ID");

// Fetch owner details
$stmt = $conn->prepare("
    SELECT u.*, uv.verification_status 
    FROM users u
    LEFT JOIN user_verification uv ON u.id = uv.user_id
    WHERE u.id = ?
");
$stmt->execute([$owner_id]);
$owner = $stmt->fetch(); 


$join_date = !empty($owner['created_at']) ? new DateTime($owner['created_at']) : null;
$now = new DateTime();

if ($join_date) {
    $diff = $join_date->diff($now);
    $membership_duration = $diff->m . "m" . $diff->d . "d";
} else {
    $membership_duration = "N/A";
}

// Get average rating and review count
$stmt = $conn->prepare("
    SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count 
    FROM owner_reviews 
    WHERE owner_id = ?
");
$stmt->execute([$owner_id]);
$rating_data = $stmt->fetch();

// Get owner's products
$stmt = $conn->prepare("
    SELECT * FROM products 
    WHERE owner_id = ? 
    AND status IN ('available', 'rented')
    ORDER BY created_at DESC
");
$stmt->execute([$owner_id]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get owner reviews
$stmt = $conn->prepare("
    SELECT orev.*, u.first_name, u.last_name, u.profile_picture 
    FROM owner_reviews orev
    JOIN users u ON orev.renter_id = u.id
    WHERE orev.owner_id = ?
");
$stmt->execute([$owner_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "
    SELECT * FROM products 
    WHERE owner_id = ? 
    AND status IN ('available', 'rented')
";

// Add search condition if search term is provided
if (!empty($search)) {
    $query .= " AND (name LIKE ? OR brand LIKE ? OR description LIKE ?)";
    $params = [$owner_id, "%$search%", "%$search%", "%$search%"];
} else {
    $params = [$owner_id];
}

$query .= " ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rentbox - <?= htmlspecialchars($owner['first_name']) ?> Profile</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/renter/style.css">
    <style>
        .rb {
            color: #4CAF50; /* Adjust this to match your brand color */
        }
        .gradient-success {
            background: linear-gradient(to right, #4CAF50, #8BC34A);
            color: white;
        }
        .pfp2 {
            width: 100px;
            height: 100px;
            border: 3px solid white;
        }
        .rating-star {
            color: #ffc107;
        }
        .nav-underline .nav-link.active {
            border-bottom: 2px solid #4CAF50;
            font-weight: bold;
        }
    </style>
</head>
<body class="container-fluid bg-dark-subtle m-0 p-0">
    <?php include '../includes/navbarr.php'; ?>

<!-- header thing -->
<div class="d-flex bg-body align-items-center justify-content-between shadow-lg py-4 px-5">
    <p class="fs-4 fw-bold rb col-5 m-0">Rent Gadgets, Your Way</p>
    <form class="col-7 d-flex gap-3" method="GET" action="">
        <input class="form-control rounded-5 px-3 shadow-sm" name="search" placeholder="Type to search for Gadgets...">
        <input type="hidden" name="owner_id" value="<?= $owner_id ?>">
        <button type="submit" class="btn gradient-success rounded-5 m-0 shadow-sm px-5">Search</button>
    </form>
</div>

    <div class="container-fluid bg-body px-5 mb-5">
        <!-- profile -->
        <div class="container-fluid bg-body position-relative border border-0 m-0 pt-5 rounded-3">
            <div class="m-0 p-0 position-absolute top-0 start-0 w-100 h-75" style="z-index: 1;">
                <img src="../images/header.png" class="object-fit-cover w-100 h-75" alt="header image">
            </div>
            <div class="position-relative mt-5 ms-4 mb-4 pe-5 me-5" style="z-index: 2;">
                <div class="d-flex bg-body shadow me-5 p-4 rounded-3">
                <?php

$profile_picture = $owner['profile_picture'] ? "../uploads/profile_pictures/" . basename(htmlspecialchars($owner['profile_picture'])) : "../images/user/pfp.png";
?>
                <img src="<?= $profile_picture ?>" 
     class="pfp2 rounded-circle shadow-sm mx-3"
     onerror="this.src='../images/user/pfp.png'">
                    <div class="row container-fluid d-flex align-items-center m-0 p-0">
                        <div class="col-4">
                        <p class="fs-5 fw-bold m-0 p-0"><?= htmlspecialchars($owner['first_name'] . ' ' . $owner['last_name']) ?></p>
                            <div class="d-flex gap-2">
                                <p class="text-secondary fs-6"><?= ucfirst($owner['verification_status'] ?? 'unverified') ?></p>
                            </div>
                        </div>
                        <div class="col border-end">
                            <p class="fs-6 text-secondary m-0 p-0"><?= number_format($rating_data['avg_rating'], 1) ?> <i class="bi bi-star-fill rb"></i></p>
                            <p class="fs-6 text-secondary m-0 p-0"><?= $rating_data['review_count'] ?> reviews</p>
                        </div>
                        <div class="col border-end">
    <p class="fs-6 text-secondary m-0 p-0"><?= $membership_duration ?></p>
    <p class="fs-6 text-secondary m-0 p-0">Joined</p>
</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- navigation tabs -->
        <div class="">
            <ul class="nav nav-underline d-flex" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active text-dark px-5" id="listing-tab" data-bs-toggle="tab" data-bs-target="#listing" type="button" role="tab" aria-controls="listing" aria-selected="true">
                        Listings (<?= count($products) ?>)</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link text-dark px-5" id="review-tab" data-bs-toggle="tab" data-bs-target="#review" type="button" role="tab" aria-controls="review" aria-selected="false">
                        Reviews (<?= count($reviews) ?>)</button>
                </li>
            </ul>

            <!-- tab content -->
            <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade show active pb-5" id="listing" role="tabpanel" aria-labelledby="listing-tab">
    <?php if (!empty($search)): ?>
        <div class="alert alert-info mt-3">
            Showing results for: "<?= htmlspecialchars($search) ?>" 
            <a href="?owner_id=<?= $owner_id ?>" class="float-end">Clear search</a>
        </div>
    <?php endif; ?>
    
    <?php if (empty($products)): ?>
        <div class="alert alert-warning mt-3">
            <?= !empty($search) ? 'No products found matching your search.' : 'This owner has no products listed.' ?>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-4 g-4 mt-3">
            <?php foreach ($products as $product): ?>
                <div class="col">
    <div class="card h-100 product-card shadow-sm">
        <img src="<?= $product['image'] ? '../img/uploads/' . basename(htmlspecialchars($product['image'])) : '../images/default-product.jpg' ?>" 
             class="card-img-top" 
             style="height: 200px; object-fit: cover">
        <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
            <p class="card-text text-muted"><?= htmlspecialchars($product['brand']) ?></p>
            <div class="d-flex justify-content-between align-items-center">
                <span class="h5 text-success">
                    ₱<?= number_format($product['rental_price'], 2) ?>/<?= $product['rental_period'] ?>
                </span>
                <span class="badge bg-<?= $product['status'] === 'available' ? 'success' : 'warning' ?>">
                    <?= ucfirst($product['status']) ?>
                </span>
            </div>
        </div>
        <div class="card-footer bg-white border-top-0 d-flex gap-2">
    <form action="add_to_cart.php" method="POST" class="d-inline flex-grow-1">
        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
        <input type="hidden" name="owner_id" value="<?= $owner_id ?>">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-outline-success w-100">
            Add to Cart
        </button>
    </form>
    <a href="/rb/renter/item.php?id=<?= $product['id'] ?>" class="btn gradient-success flex-grow-1">Rent Now</a>
</div>
    </div>
</div>
                        <?php endforeach; ?>
                        </div>
    <?php endif; ?>
</div>
                    </div>
                </div>

                <div class="tab-pane fade pb-5 mt-3" id="review" role="tabpanel" aria-labelledby="review-tab">
                    <?php if (empty($reviews)): ?>
                        <div class="alert alert-info">No reviews yet</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($reviews as $review): ?>
                                <div class="col-12">
                                    <div class="card shadow-sm">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <img src="../<?= htmlspecialchars($review['profile_picture']) ?>" 
                                                     class="rounded-circle" 
                                                     width="50"
                                                     onerror="this.src='../images/user/pfp.png'">
                                                <div>
                                                    <h6 class="mb-0"><?= htmlspecialchars($review['first_name'] . ' ' . $review['last_name']) ?></h6>
                                                    <small class="text-muted">
                                                        <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="rb-star mb-2">
                                                <?= str_repeat('<i class="bi bi-star-fill text-warning"></i>', $review['rating']) ?>
                                            </div>
                                            <p class="card-text"><?= htmlspecialchars($review['comment']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<!-- Toast Notification -->
<div class="position-fixed top-0 start-50 translate-middle-x mt-4" style="z-index: 9999">
    <div id="cartToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" 
         data-bs-autohide="true" data-bs-delay="3000">
        <div class="toast-header bg-success text-white">
            <i class="bi bi-cart-check me-2"></i>
            <strong class="me-auto">Success!</strong>
            <small class="text-white">Just now</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body bg-light">
            <span class="text-success"><i class="bi bi-check-circle-fill me-2"></i>Item added to cart!</span>
        </div>
        <div class="progress" style="height: 3px">
            <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
        </div>
    </div>
</div>

<script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Toast Notification System
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const toastEl = document.getElementById('cartToast');
    const toast = bootstrap.Toast.getOrCreateInstance(toastEl);
    
    const progressBar = toastEl.querySelector('.progress-bar');
    
    if (urlParams.has('success') || urlParams.has('error')) {
        // Clean URL
        const cleanURL = new URL(window.location);
        ['success', 'error'].forEach(param => cleanURL.searchParams.delete(param));
        history.replaceState({}, document.title, cleanURL);

        // Configure toast
        const isSuccess = urlParams.has('success');
        toastEl.querySelector('.toast-header').className = `toast-header ${isSuccess ? 'bg-success' : 'bg-danger'} text-white`;
        toastEl.querySelector('.toast-body').innerHTML = `
            <span class="${isSuccess ? 'text-success' : 'text-danger'}">
                <i class="bi ${isSuccess ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill'} me-2"></i>
                ${isSuccess ? 'Item added to cart!' : 'Failed to add item!'}
            </span>
        `;
        progressBar.className = `progress-bar ${isSuccess ? 'bg-success' : 'bg-danger'}`;

        // Animate progress bar
        progressBar.style.width = '100%';
        toastEl.addEventListener('shown.bs.toast', () => {
            progressBar.style.transition = 'width 3s linear';
            progressBar.style.width = '0%';
        });

        toast.show();
    }
});
</script>
</body>
</html>