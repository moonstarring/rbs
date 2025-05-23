<?php
require_once '../include_renter/item.php';
require_once 'renter_class.php';
require_once '../db/db.php';


$renter = new Renter($conn);
$renter->authenticateRenter();
$userId = $_SESSION['id'];


// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = $_SESSION['id'];
$productId = (int)$_GET['id'];


// Fetch product data
$product = $renter->getProductById($product_id);
if (!$product) {
    die("Product not found.");
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $message = "Invalid CSRF token.";
    } else {
        $message = $renter->addToCart($userId, $productId);
    }
}

// Format product data for display
$productData = [
    'id' => $product['id'],
    'owner_id' => $product['owner_id'],
    'name' => htmlspecialchars($product['name']),
    'brand' => htmlspecialchars($product['brand']),
    'description' => htmlspecialchars($product['description']),
    'rental_price' => number_format($product['rental_price'], 2),
    'status' => htmlspecialchars($product['status']),
    'created_at' => $product['created_at'],
    'updated_at' => $product['updated_at'],
    'image' => $product['image'],
    'quantity' => $product['quantity'],
    'category' => htmlspecialchars($product['category']),
    'rental_period' => htmlspecialchars($product['rental_period']),
];


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
    <link rel="stylesheet" href="../css/renter/style.css">
    <link rel="stylesheet" href="../vendor/flatpickr.min.css">
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

<body>
    <div class="container-fluid image-bg m-0 p-0">
        <!-- Navbar Section -->
        <?php require_once '../includes/navbarr.php'; ?>

        <!-- Body Section -->
        <div class="bg-dark-subtle p-3 shadow-lg">

            <div class="row d-flex container-fluid m-0 p-0 gap-3">

                <!-- Image Carousel style="max-width: 80vh;" -->
                <div class="col-6 bg-body p-4 rounded-3 shadow-sm m-0 d-none d-md-block d-lg-block" style="max-height: 100vh;">
                    <div id="carouselIndicators" class="carousel carousel-dark slide h-100">
                        <div class="carousel-inner border rounded-3 shadow-sm border border-3 h-100">
                            <?php
                            foreach ($images as $index => $image) {
                                echo '<div class="carousel-item ' . ($index === 0 ? 'active' : '') . ' h-100">';
                                echo '<img src="../img/uploads/' . htmlspecialchars($image) . '" class="d-block w-100  object-fit-cover" alt="...">';
                                echo '</div>';
                            }
                            ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselIndicators" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselIndicators" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                </div>

                <!-- Product Info Section -->
                <div class="col bg-body p-0 rounded-3 shadow-sm m-0">
                    <!-- iamge carousel for smobile -->
                    <div id="carouselIndicators" class="carousel carousel-dark slide d-md-none d-lg-none">
                        <div class="carousel-inner rounded-3 shadow-sm ">
                            <?php
                            foreach ($images as $index => $image) {
                                echo '<div class="carousel-item ' . ($index === 0 ? 'active' : '') . ' h-100">';
                                echo '<img src="../img/uploads/' . htmlspecialchars($image) . '" class="d-block w-100 h-100 object-fit-cover" alt="...">';
                                echo '</div>';
                            } 
                            ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselIndicators" data-bs-slide="prev">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Previous</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselIndicators" data-bs-slide="next">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>
                    <div class="m-0 p-4">
                        <div class="justify-content-end gap-1 d-none d-md-flex d-lg-flex">
                            <a href="#" class="link-success link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">Browse</a>
                            <p class="text-secondary"> > </p>
                            <a href="#" class="link-success link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover">
                                <?php echo htmlspecialchars($product['name'] ?? 'Product Name Not Available'); ?>
                            </a>
                        </div>

                        <div class="d-flex smol">
                            <h2 class="fw-bold m-0 p-0"><?php echo htmlspecialchars($product['name'] ?? 'Product Name Not Available'); ?>
                                <a href="#" class="btn btn-outline-secondary m-0 opacity-50 m-0 p-1">
                                    <small class="m-0 p-0"><?php echo htmlspecialchars($product['category'] ?? 'Category Not Available'); ?>
                                    </small></a>
                            </h2>

                        </div>

                        <div class="d-flex">
                            <p class="me-1 text-decoration-none"><?php echo $average_rating; ?></p>
                            <i class="bi bi-star-fill text-warning"></i>
                            <p class="mx-2">|</p>
                            <a href="#" class="me-1 text-decoration-none text-success"><?php echo count($comments); ?> Reviews</a>
                        </div>

                        <!-- Price and Availability Section -->
                        <div class="row d-flex align-items-bottom">
                            <div class="border border-1 col-auto rounded-3 shadow-sm p-0 mb-3 mt-2 ms-3">
                                <div class="m-0 <?php echo $availabilityClass; ?> align-items-stretch border text-center">
                                    <p class="active fs-6 my-2"><?php echo $availabilityText; ?></p>
                                </div>

                                <div class="d-flex justify-content-center align-items-center p-2">
                                    <p class="fs-3 fw-bold m-0 p-0 active">₱<?php echo htmlspecialchars($product['rental_price']); ?></p>
                                    <p class="fs-3 fw-bold m-0 p-0 active">/</p>
                                    <p class="fs-3 fw-bold m-0 p-0 active"><?php echo htmlspecialchars($product['rental_period']); ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Product Description Section -->
                        <small class="text-secondary p-0 mb-2">Product Description:</small>
                        <p class="fs-6 p-0 mb-3"><?php echo htmlspecialchars($product_description); ?></p>

                        <!-- Additional Product Info -->

                        <!-- <div class="col-4">
                                <small class="text-secondary p-0 mb-2">Brand/Model:</small>
                                <div class="d-flex gap-2 mt-1 mb-3">
                                    <a href="#" class="btn btn-outline-success m-0 p-1">Apple</a>
                                    <a href="#" class="btn btn-outline-success m-0 p-1">Airpods Pro 1</a>
                                </div>

                                <small class="text-secondary p-0 mb-2">Comes with:</small>
                                <div class="d-flex gap-2 mt-1">
                                    <a href="#" class="btn btn-outline-success m-0 p-1">Case</a>
                                    <a href="#" class="btn btn-outline-success m-0 p-1">IOS Charger</a>
                                </div>
                            </div> -->
                        <!-- Reservation Form -->
                        <div class="col-auto col-md-5 col-lg-6 d-flex mb-md-3 mb-lg-3  flex-column">
                            <small class="text-secondary p-0 mb-2">Set a Date:</small>
                            <div class="d-flex flex-column m-0 p-0">
                                <input class="border border-success border-1 rounded-top px-2 text-success" type="text" id="startDate" placeholder="Start Date" required>
                                <input class="border border-success border-1 rounded-bottom px-2 text-success" type="text" id="endDate" placeholder="End Date" required>
                            </div>
                        </div>
                        <div class="d-flex mt-3 justify-content-between justify-content-md-around justify-content-lg-around">
                            <!-- Add to Cart Form -->
                            <form method="post" action="">
                                <input type="hidden" name="add_to_cart" value="1">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                <button type="submit" class="px-3 py-2 btn btn-lg rounded-pill shadow-sm btn-outline-dark px-3 smol-btn">
                                    <i class="bi bi-bag-plus pe-1"></i>
                                    Add to Cart
                                </button>
                            </form>

                            <!-- Rent Now Form (Checkout) -->
                            <form method="post" action="checkout.php" class="d-inline">
                                <!-- CSRF Token -->
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                                <!-- Direct Checkout Indicator -->
                                <input type="hidden" name="direct_checkout" value="1">
                                <!-- Product Details -->
                                <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                                <input type="hidden" name="start_date" id="checkout_start_date" value="">
                                <input type="hidden" name="end_date" id="checkout_end_date" value="">
                                <button type="submit" class="px-3 py-2 btn btn-lg rounded-pill shadow-sm gradient-success d-flex align-items-center gap-2 smol-btn" <?php echo ($productData['quantity'] < 1) ? 'disabled' : ''; ?>>
                                    Checkout
                                    <span class="mb-0 ps-1 fw-bold" id="checkoutTotalPrice">₱<?php echo $productData['rental_price']; ?></span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Advertisements Section 
                <div class="d-none d-md-block col-md-3 bg-body p-4 rounded-3 shadow-sm m-0">
                    <img src="" alt="advertisement" class="img-thumbnail">
                    promo code
                </div>
                -->
            </div>

            <!-- Product Ratings and Owner Information Section -->
            <div class="row container-fluid mt-3 mx-0 bg-body rounded-3 shadow-sm p-4 gap-3 d-flex align-items-start">
                <div class="col-auto">
                    <div class="row m-0 p-0">
                        <div class="col-auto d-flex justify-content-center align-items-center">
                            <?php
                            // Fetch owner's profile picture from the users table
                            $query = "SELECT profile_picture FROM users WHERE id = ?";
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$owner_id]);
                            $owner_profile_picture = $stmt->fetchColumn();
                            $profile_picture = $owner_profile_picture ? "../" . htmlspecialchars($owner_profile_picture) : "images/user/pfp.png";
                            ?>
                            <img src="<?php echo $profile_picture; ?>" alt="Owner Profile Picture" class="rounded-circle border shadow-sm" style="width: 60px; height: 60px;">
                        </div>
                        <div class="col-auto d-flex flex-column">
                            <a href="#" class="fs-5 text-decoration-none text-dark fw-bold m-0 p-0"><?php echo htmlspecialchars($owner_name); ?></a>
                            <p class="fs-6 text-success p-0 m-0"><?php echo $active_status; ?></p> <!-- Active status -->
                            <a href="review.php?owner_id=<?php echo $product['owner_id']; ?>" class="btn btn-outline-secondary m-0 px-2 smol">View Profile</a>
                        </div>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="row">
                        <div class="col-auto">
                            <p class="fs-6 text-secondary m-0 p-0">Rating</p>
                            <a href="#" class="fs-6 text-success m-0 p-0 text-decoration-none smol"><?php echo $total_ratings; ?></a> <!-- Total Ratings -->
                        </div>

                        <div class="col-auto">
                            <p class="fs-6 text-secondary m-0 p-0">Rentals</p>
                            <a href="#" class="fs-6 text-success mt-3 p-0 text-decoration-none smol"><?php echo $rental_count; ?> Rentals</a> <!-- Rental Count -->
                        </div>

                        <div class="col-auto">
                            <p class="fs-6 text-secondary m-0 p-0">Joined</p>
                            <a href="#" class="fs-6 text-success m-0 p-0 text-decoration-none smol"><?php echo $joined_duration; ?></a> <!-- Joined Duration -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product Specification Section -->
            <div class="row container-fluid mt-3 mx-0 bg-body rounded-3 shadow-sm p-4 d-flex align-items-start">
                <p class="fs-5 text-decoration-none text-dark fw-bold mb-2 p-0">Product Specification</p>

                <div class="gap-3 d-flex">
                    <small class="text-secondary pt-1">Category</small>
                    <a href="#" class="p-0 m-0 link-success link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"><?php echo htmlspecialchars($category); ?></a>
                </div>
                <div class="gap-3 d-flex">
                    <small class="text-secondary pt-1">Available Stock</small>
                    <p class="fs-6 text-success m-0 p-0 smol"><?php echo htmlspecialchars($quantity); ?></p>
                </div>

                <p class="fs-5 fw-bold mt-5 mb-1 mb-md-3 mb-lg-3 p-0">Product Condition</p>
                <p class="m-0 ps-2"><?php echo htmlspecialchars($condition_description); ?></p> <!-- Display the dynamic condition description -->
            </div>

            <!-- Ratings Section -->
            <div class="container-fluid mt-3 mx-0 bg-body rounded-3 p-4">
                <p class="fs-5 text-decoration-none text-dark fw-bold mb-2 p-0">Ratings</p>
                <p class="fs-6 fw-bold ps-2"><?php echo $average_rating; ?> out of 5 <i class="bi bi-star-fill text-warning"></i></p>

                <div class="text-center text-md-start text-lg-start gap-2">
                    <!-- Filter buttons for ratings -->
                    <a href="?id=<?php echo $product_id; ?>&rating=all" class="btn btn-small btn-outline-success smol">All</a>
                    <a href="?id=<?php echo $product_id; ?>&rating=5" class="btn btn-small btn-outline-success smol">5 </a>
                    <a href="?id=<?php echo $product_id; ?>&rating=4" class="btn btn-small btn-outline-success smol">4 </a>
                    <a href="?id=<?php echo $product_id; ?>&rating=3" class="btn btn-small btn-outline-success smol">3 </a>
                    <a href="?id=<?php echo $product_id; ?>&rating=2" class="btn btn-small btn-outline-success smol">2 </a>
                    <a href="?id=<?php echo $product_id; ?>&rating=1" class="btn btn-small btn-outline-success smol">1</a>
                </div>

                <!-- Review Section -->
                <?php if (empty($comments)): ?>
                    <p class="text-center mt-2 mt-md-3 mt-lg-3 text-md-start text-lg-start">No comments available for this product.</p> <!-- Message when no comments exist -->
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="mt-3 d-flex">
                            <!-- Check if the profile picture exists, otherwise use the default -->
                            <img src="<?= htmlspecialchars($profilePic) ?>" alt="" class="rounded-circle border me-3 p-0" style="width: 40px; height: 40px;">
                            <div class="d-flex flex-column">
                                <p class="fs-6 fw-bold m-0 p-0"><?php echo htmlspecialchars($comment['name']); ?></p>
                                <div class="d-flex gap-1 m-0">
                                    <!-- Dynamically display the rating stars -->
                                    <?php
                                    $rating = $comment['rating'];
                                    for ($i = 0; $i < 5; $i++) {
                                        echo $i < $rating ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-warning"></i>';
                                    }
                                    ?>
                                    <div class="d-flex gap-1 m-0">
                                        <p class="text-secondary">|</p>
                                        <p class="text-secondary"><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></p>
                                    </div>
                                </div>
                                <p class="p-0"><?php echo htmlspecialchars($comment['comment']); ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer Section -->
        <?php require_once '../includes/footer.php' ?>

    </div>
    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/flatpickr.min.js"></script>
    <script>
        // Initialize flatpickr
        flatpickr("#startDate", {
            dateFormat: "Y-m-d",
            maxDate: new Date(2025, 11, 1),
            minDate: "today",
            disableMobile: true,
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('checkout_start_date').value = dateStr;
                calculateTotal();
            }
        });

        flatpickr("#endDate", {
            dateFormat: "Y-m-d",
            maxDate: new Date(2025, 11, 1),
            minDate: "today",
            disableMobile: true,
            onChange: function(selectedDates, dateStr, instance) {
                document.getElementById('checkout_end_date').value = dateStr;
                calculateTotal();
            }
        });

        document.querySelector('form[action="checkout.php"]').addEventListener('submit', function(e) {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;

            if (!startDate || !endDate) {
                e.preventDefault();
                alert('Please select both start and end dates.');
            } else {
                // Update hidden inputs for checkout
                document.getElementById('checkout_start_date').value = startDate;
                document.getElementById('checkout_end_date').value = endDate;
            }
        });
        // Calculate total rental price based on selected dates
        function calculateTotal() {
            const startDateInput = document.getElementById('startDate');
            const endDateInput = document.getElementById('endDate');
            const totalPriceDisplay = document.getElementById('checkoutTotalPrice');

            const pricePerPeriod = <?php echo floatval($product['rental_price']); ?>; // PHP price
            const rentalPeriod = "<?php echo strtolower($product['rental_period']); ?>"; // e.g., 'day', 'week', 'month'
            const startDate = new Date(startDateInput.value);
            const endDate = new Date(endDateInput.value);

            // Validate if both dates are selected and startDate is before or equal to endDate
            if (startDateInput.value && endDateInput.value && startDate <= endDate) {
                const timeDifference = endDate - startDate; // Milliseconds difference
                const daysDifference = Math.ceil(timeDifference / (1000 * 3600 * 24)) + 1; // Convert to days (+1 for inclusive day)

                let periods = 1;
                switch (rentalPeriod) {
                    case 'day':
                        periods = daysDifference;
                        break;
                    case 'week':
                        periods = Math.ceil(daysDifference / 7);
                        break;
                    case 'month':
                        periods = Math.ceil(daysDifference / 30);
                        break;
                    default:
                        periods = 1;
                }

                const totalPrice = periods * pricePerPeriod; // Total cost calculation
                totalPriceDisplay.textContent = '₱' + totalPrice.toFixed(2); // Update display
            } else {
                totalPriceDisplay.textContent = '₱' + pricePerPeriod.toFixed(2); // Default price per period
            }
        }

        // Initialize total price on page load
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal();
        });
    </script>
</body>
<script src="vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</html>