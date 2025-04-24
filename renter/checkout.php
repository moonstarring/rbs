<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];
$cartItems = [];
$subtotal = 0;
$discount = 0;
$shippingCharge = 0;
$taxRate = 0.12;
$taxAmount = 0;
$total = 0;
$isDirectCheckout = false;
$allAvailable = true;

// Check if it's direct checkout (from browse.php) or cart checkout
$isDirectCheckout = isset($_POST['direct_checkout']) && $_POST['direct_checkout'] == 1;
$productId = $isDirectCheckout ? (int)$_POST['product_id'] : null;
$startDate = $isDirectCheckout ? $_POST['start_date'] : null;
$endDate = $isDirectCheckout ? $_POST['end_date'] : null;

// Handle Direct Checkout (From browse.php)
if ($isDirectCheckout) {
    // Validate direct checkout parameters
    if (!$productId || !$startDate || !$endDate) {
        $_SESSION['error_message'] = "Missing required checkout parameters.";
        header('Location: browse.php');
        exit();
    }

    // Validate dates
    $dateFormat = 'Y-m-d';
    $startDateObj = DateTime::createFromFormat($dateFormat, $startDate);
    $endDateObj = DateTime::createFromFormat($dateFormat, $endDate);

    if (!$startDateObj || !$endDateObj || $startDateObj->format($dateFormat) !== $startDate || $endDateObj->format($dateFormat) !== $endDate) {
        $_SESSION['error_message'] = "Invalid date format.";
        header('Location: item.php?id=' . $productId);
        exit();
    }

    if ($startDateObj > $endDateObj) {
        $_SESSION['error_message'] = "End date cannot be before start date.";
        header('Location: item.php?id=' . $productId);
        exit();
    }

    // Fetch product data
    $sql = "SELECT * FROM products WHERE id = :productId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['error_message'] = "Product not found.";
        header('Location: browse.php');
        exit();
    }

    if ($product['quantity'] < 1) {
        $allAvailable = false;
    }

    // Calculate total cost based on rental period
    $rentalPeriod = strtolower($product['rental_period']);
    $interval = $startDateObj->diff($endDateObj);
    $days = $interval->days + 1;

    switch ($rentalPeriod) {
        case 'day':
            $periods = $days;
            break;
        case 'week':
            $periods = ceil($days / 7);
            break;
        case 'month':
            $periods = ceil($days / 30);
            break;
        default:
            $periods = 1;
    }

    $totalCost = $product['rental_price'] * $periods;
    $subtotal += $totalCost;

    $cartItems[] = [
        'id' => 0,
        'product_id' => $product['id'],
        'name' => htmlspecialchars($product['name']),
        'image' => $product['image'],
        'rental_price' => $product['rental_price'],
        'rental_period' => $product['rental_period'],
        'start_date' => $startDate,
        'end_date' => $endDate,
        'periods' => $periods,
        'total_cost' => $totalCost,
    ];
}
// Handle Cart Checkout (From cart.php)
else {
    $sql = "SELECT c.*, p.name, p.image, p.rental_price, p.category, p.description, p.owner_id, p.rental_period, p.quantity
            FROM cart_items c
            INNER JOIN products p ON c.product_id = p.id
            WHERE c.renter_id = :userId";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $fetchedCartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($fetchedCartItems) {
        foreach ($fetchedCartItems as $item) {
            if (empty($item['start_date']) || empty($item['end_date'])) {
                $_SESSION['error_message'] = "Please set both start and end dates for all items in your cart.";
                header('Location: cart.php');
                exit();
            }

            if ($item['quantity'] < 1) {
                $allAvailable = false;
                break;
            }

            // Calculate rental period
            $rentalPeriod = strtolower($item['rental_period']);
            $startDateObj = new DateTime($item['start_date']);
            $endDateObj = new DateTime($item['end_date']);
            $interval = $startDateObj->diff($endDateObj);
            $days = $interval->days + 1;

            switch ($rentalPeriod) {
                case 'day':
                    $periods = $days;
                    break;
                case 'week':
                    $periods = ceil($days / 7);
                    break;
                case 'month':
                    $periods = ceil($days / 30);
                    break;
                default:
                    $periods = 1;
            }

            $totalCost = $item['rental_price'] * $periods;
            $subtotal += $totalCost;

            $cartItems[] = [
                'id' => $item['id'],
                'product_id' => $item['product_id'],
                'name' => htmlspecialchars($item['name']),
                'image' => $item['image'],
                'rental_price' => $item['rental_price'],
                'rental_period' => $item['rental_period'],
                'start_date' => $item['start_date'],
                'end_date' => $item['end_date'],
                'periods' => $periods,
                'total_cost' => $totalCost,
            ];
        }
    }
}

$taxAmount = $subtotal * $taxRate;
$total = $subtotal - $discount + $shippingCharge + $taxAmount;
$enableCheckout = $allAvailable;

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Checkout</title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <link rel="stylesheet" href="../vendor/flatpickr.min.css">
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
    <div class="container-fluid bg-body rounded-5 p-4">
        <div class="row">
            <div class="col-12 col-md-7 col-lg-7 p-0 mb-3 mb-md-0 mb-lg-0">
                <div class="card rounded-4">
                    <div class="rounded-4 rounded-bottom-0 d-flex flex-wrap bg-secondary-subtle justify-content-between align-items-center">
                        <h5 class="mb-0 text-success ps-4">Checkout</h5>
                        <div class="d-flex">
                            <a href="browse.php" class="d-flex btn btn-outline-light align-items-center border-0 rounded-start-0  smol">
                                <i class="bi bi-caret-left-fill text-success pe-2 fs-6"></i>
                                <h6 class="mb-0 text-success pe-md-3 pe-lg-3">Continue shopping</h6>
                            </a>
                        </div>
                    </div>
                    <hr class="m-0 p-0">
                    <div class="card-body m-0">
                        <?php if (isset($_SESSION['error_message'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['error_message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['error_message']); ?>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?= htmlspecialchars($_SESSION['success_message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        <ol class="activity-checkout mb-0 px-4 mt-3">
                            <li class="">
                                <h6 class="mb-2 fw-bold">Order Confirmation</h6>
                                <div class="mb-3">
                                    <form method="post" action="process_checkout.php" class="needs-validation" novalidate>
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="product_id" value="<?= $productId ?>">
                                        <input type="hidden" name="start_date" value="<?= $startDate ?>">
                                        <input type="hidden" name="end_date" value="<?= $endDate ?>">
                                        <!-- Inside the form in checkout.php -->
                                        <?php if ($isDirectCheckout): ?>
                                            <input type="hidden" name="direct_checkout" value="1"> <!-- Add this line -->
                                            <input type="hidden" name="product_id" value="<?= htmlspecialchars($productId); ?>">
                                            <input type="hidden" name="start_date" value="<?= htmlspecialchars($startDate); ?>">
                                            <input type="hidden" name="end_date" value="<?= htmlspecialchars($endDate); ?>">
                                        <?php endif; ?>

                                        <!-- Your Information Section -->
                                        <div class="card mb-3"> <!-- Changed from mb-4 -->
                                            <div class="card-body p-3"> <!-- Reduced padding -->
                                                <h5 class="mb-2 fs-5">Your Information</h5> <!-- Smaller heading -->
                                                <div class="row g-2"> <!-- Tighter grid spacing -->
                                                    <!-- First Name -->
                                                    <div class="col-6">
                                                        <div class="bg-light rounded p-2"> <!-- Lighter background, smaller padding -->
                                                            <small class="text-muted d-block">First Name</small>
                                                            <span class="fs-6"><?= htmlspecialchars($_SESSION['first_name'] ?? '') ?></span>
                                                        </div>
                                                    </div>

                                                    <!-- Last Name -->
                                                    <div class="col-6">
                                                        <div class="bg-light rounded p-2">
                                                            <small class="text-muted d-block">Last Name</small>
                                                            <span class="fs-6"><?= htmlspecialchars($_SESSION['last_name'] ?? '') ?></span>
                                                        </div>
                                                    </div>

                                                    <!-- Contact Number -->
                                                    <div class="col-6">
                                                        <div class="bg-light rounded p-2 mt-2"> <!-- Added top margin -->
                                                            <small class="text-muted d-block">Contact</small>
                                                            <span class="fs-6"><?= htmlspecialchars($_SESSION['phone'] ?? 'N/A') ?></span>
                                                        </div>
                                                    </div>

                                                    <!-- Email Address -->
                                                    <div class="col-6">
                                                        <div class="bg-light rounded p-2 mt-2">
                                                            <small class="text-muted d-block">Email</small>
                                                            <span class="fs-6 text-truncate d-block"><?= htmlspecialchars($_SESSION['email'] ?? '') ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="card mb-3 mb-md-4 mb-lg-4">
                                            <div class="card-body">
                                                <h5 class="mb-2 mb-md-3 mb-lg-3 ">Payment Method</h5>
                                                <div class="alert alert-info smolll">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    All transactions are cash-only. Please prepare exact amount for in-store pickup.
                                                </div>
                                                <input type="hidden" name="payment_method" value="cod">
                                            </div>
                                        </div>

                                        <button type="submit" class="btn btn-success smoll w-100" <?= $enableCheckout ? '' : 'disabled' ?>>
                                            <i class="bi bi-check2-circle me-2"></i>Confirm Order
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4 col-lg-4 m-0 p-0">
                <div class="card checkout-order-summary rounded-4">
                    <div class="card-body">
                        <div class="p-3 mb-3">
                            <h6 class="font-size-16 mb-0">Order Summary</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-centered mb-0 table-nowrap">
                                <thead>
                                    <tr>
                                        <th class="border-top-0" style="width: 80px;" scope="col">Product</th>
                                        <th class="border-top-0" scope="col">Description</th>
                                        <th class="border-top-0" scope="col">Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cartItems as $item): ?>

                                        <tr>
                                            <th scope="row">
                                                <img src="../img/uploads/<?= htmlspecialchars($item['image']); ?>" alt="product-img" title="product-img" class="rounded" style="width: 60px; height: 60px; object-fit: cover;" onerror="this.onerror=null; this.src='../img/uploads/default.png';">
                                            </th>
                                            <td>
                                                <h6 class="font-size-16 text-truncate"><a href="item.php?id=<?= $item['product_id']; ?>" class="text-dark"><?= htmlspecialchars($item['name']); ?></a></h6>
                                                <p class="text-muted mb-0">
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                    <i class="bi bi-star-fill text-warning"></i>
                                                    <i class="bi bi-star-half text-warning"></i>
                                                </p>
                                                <p class="text-muted mb-0 mt-1">₱<?= number_format($item['rental_price'], 2); ?> per <?= htmlspecialchars($item['rental_period']); ?></p>
                                                <p class="text-muted mb-0">Duration: <?= htmlspecialchars($item['start_date']); ?> to <?= htmlspecialchars($item['end_date']); ?> (<?= $item['periods']; ?> <?= htmlspecialchars($item['rental_period'] . ($item['periods'] > 1 ? 's' : '')); ?>)</p>
                                            </td>
                                            <td>₱<?= number_format($item['total_cost'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="2">
                                            <h6 class="font-size-14 m-0">Sub Total :</h6>
                                        </td>
                                        <td>
                                            ₱<?= number_format($subtotal, 2); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <h6 class="font-size-14 m-0">Discount :</h6>
                                        </td>
                                        <td>
                                            - ₱<?= number_format($discount, 2); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <h6 class="font-size-14 m-0">Shipping Charge :</h6>
                                        </td>
                                        <td>
                                            ₱<?= number_format($shippingCharge, 2); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2">
                                            <h6 class="font-size-14 m-0">Estimated Tax (12%) :</h6>
                                        </td>
                                        <td>
                                            ₱<?= number_format($taxAmount, 2); ?>
                                        </td>
                                    </tr>
                                    <tr class="bg-light">
                                        <td colspan="2">
                                            <h6 class="font-size-14 m-0">Total:</h6>
                                        </td>
                                        <td>
                                            ₱<?= number_format($total, 2); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
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
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/flatpickr.min.js"></script>
    <script>
        (() => {
            'use strict'
            const forms = document.querySelectorAll('.needs-validation')
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })();
        <?php if (!$allAvailable): ?>
            document.querySelector('button[type="submit"]').disabled = true;
        <?php endif; ?>
    </script>
</body>

</html>