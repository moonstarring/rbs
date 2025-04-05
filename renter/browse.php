<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debugging
error_log("User ID in session: " . ($_SESSION['id'] ?? 'Not set'));
error_log("User role in session: " . ($_SESSION['role'] ?? 'Not set'));

// Make sure the session ID is set before using it
if (!isset($_SESSION['id'])) {
    // Redirect to login or handle the error
    header('Location: /rb/login.php');
    exit;
}
// Include database connection
require_once __DIR__ . '/../db/db.php';
require_once 'renter_class.php';
$renter = new renter($conn);
$renter->authenticateRenter();



// Get search term and pagination data
$searchTerm = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

$result = $renter->searchProducts(
    searchTerm: $searchTerm,
    perPage: 8,
    page: $page,
    excludeOwnProducts: true,
    currentUserId: $_SESSION['id']
);

if ($result) {
    echo "<!-- Found {$result['totalPages']} pages of products -->";
} else {
    echo "<!-- Error in searchProducts method -->";
}
if (!$result) {
    die("Error fetching products.");
}
$formattedProducts = $result['products'];
$totalPages = $result['totalPages'];
$formattedProducts = $result['products'];
$totalPages = $result['totalPages'];


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
        .card:hover {
            transform: scale(1.01);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            transition: transform 0.1s ease, box-shadow 0.1s ease;
        }

        .hover-effect {
            transition: transform 0.3s ease;
            position: relative;
        }

        .hover-effect:hover {
            transform: scale(1.02);
            z-index: 2;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .height-card{
            height: 300px;
            overflow: hidden;
        }

        .no-products {
            text-align: center;
            font-size: 1.2rem;
            color: #888;
            padding: 20px;
        }

        .toast {
            min-width: 350px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease-in-out;
        }

        .toast-header {
            padding: 0.75rem 1rem;
            border-bottom: none;
        }

        .toast-body {
            padding: 1rem;
            font-weight: 500;
        }

        .progress {
            border-radius: 0 0 0.375rem 0.375rem;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <!-- Notification Toast -->
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

    <div class="container-fluid image-bg m-0 p-0">
        <!-- Include Navbar -->
        <?php include '../includes/navbarr.php'; ?>

        <div class="container bg-body rounded-top-5 d-flex">
            <div class="mx-5 my-4 container-fluid d-flex justify-content-between align-items-center">
                <p class="fs-4 fw-bolder my-auto rb d-none d-sm-block">Rent Gadgets, Your Way</p>
                <form class="d-flex gap-3 my-lg-0" method="GET" action="">
                    <input class="form-control rounded-5 px-3 shadow-sm"
                        type="text"
                        placeholder="Type to search..."
                        id="searchInput"
                        name="search"
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button class="btn btn-success rounded-5 px-4 py-0 m-0 shadow-sm" type="submit">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <div class="container-fluid bg-light rounded-start-3">
            <div class="row" style="height: 100vh;">
                <!-- Include Sidebar -->
                <?php include '../includes/sidebar.php'; ?>


                <!-- Products Display Area -->
                <div id="product-list" class="col-md-9 rounded-start-3 bg-dark-subtle pt-3">
                    <div class="container-fluid">
                        <div class="row gap-3" id="dynamic-products">
                            <?php foreach ($formattedProducts as $product): ?>
                                <div class="col-3 col-sm-4 col-md-3 col-lg-2">
                                    <div class="rounded-3 p-3 bg-body hover-effect height-card">
                                        <div class="card-body">
                                            <a href="item.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                                <img src="../img/uploads/<?php echo $product['image']; ?>"
                                                    alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                    class="shadow-sm product-image">
                                                <p class="fs-5 mt-2 ms-2 mb-0 fw-bold"><?php echo htmlspecialchars($product['name']); ?></p>
                                            </a>
                                            <div class="d-flex justify-content-between align-items-baseline">
                                                <small class="ms-1 mb-0 text-secondary">
                                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                                    <?php echo $product['average_rating']; ?> (<?php echo $product['rating_count']; ?>)
                                                </small>
                                                <p class="fs-5 ms-auto mb-0">₱<?php echo $product['rental_price']; ?><small class="text-secondary">/day</small></p>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <form action="add_to_cart.php" method="POST" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                                    <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page'] ?? 1); ?>">
                                                    <button type="submit" class="btn btn-outline-dark btn-sm rounded-5 shadow-sm">
                                                        Add to Cart
                                                    </button>
                                                </form>
                                                <a href="/rb/renter/item.php?id=<?= $product['id'] ?>" class="...">Rent Now</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Recommendations Section -->
                    <div class="px-5 py-5 bg-body">
                        <div class="d-flex justify-content-between">
                            <p class="fs-5 fw-bold mb-3 active">Explore our Recommendations</p>
                            <div>
                                <button class="btn btn-outline-success"><i class="bi bi-arrow-left"></i></button>
                                <button class="btn btn-outline-success"><i class="bi bi-arrow-right"></i></button>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <!-- Recommended products section -->
                            <?php
                            // Fetch recommended products or related products based on some criteria (e.g., popular or based on category)
                            // Assuming the recommendation fetching logic is similar to the product fetching one
                            $recommendations = []; // Fetch recommendations here
                            foreach ($recommendations as $product): ?>
                                <div class="col">
                                    <div class="border rounded-3 p-3 bg-body hover-effect">
                                        <a href="item.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                            <img src="../img/uploads/<?php echo $product['image']; ?>"
                                                alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                class="img-thumbnail shadow-sm product-image">
                                            <p class="fs-5 mt-2 ms-2 mb-0 fw-bold"><?php echo htmlspecialchars($product['name']); ?></p>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </div>
            <!-- Pagination -->
            <!-- Pagination -->
            <div class="mx-3 mb-4">
                <div class="d-flex justify-content-between">
                    <!-- Previous button -->
                    <button type="button" class="btn btn-light text-start"
                        <?php echo $page <= 1 ? 'disabled' : ''; ?>
                        onclick="window.location.href='?search=<?php echo $searchTerm; ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>'">
                        <small><i class="bi bi-caret-left-fill"></i></small>
                    </button>

                    <!-- Page numbers -->
                    <div class="d-flex gap-2">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <button type="button" class="btn btn-light text-start"
                                onclick="window.location.href='?search=<?php echo $searchTerm; ?>&page=<?php echo $i; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>'">
                                <small><?php echo $i; ?></small>
                            </button>
                        <?php endfor; ?>
                    </div>

                    <!-- Next button -->
                    <button type="button" class="btn btn-light text-start"
                        <?php echo $page >= $totalPages ? 'disabled' : ''; ?>
                        onclick="window.location.href='?search=<?php echo $searchTerm; ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>'">
                        <small><i class="bi bi-caret-right-fill"></i></small>
                    </button>
                </div>
            </div>
            <?php require_once '../includes/footer.php' ?>
        </div>
    </div>

    <?php if (empty($formattedProducts)): ?>
        <div class="col-12 no-products">
            <p>No products found. Try another search!</p>
        </div>
    <?php endif; ?>



    <script>
        // Toast Notification System
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const toastEl = document.getElementById('cartToast');
            const toast = bootstrap.Toast.getOrCreateInstance(toastEl);

            const progressBar = toastEl.querySelector('.progress-bar');

            if (urlParams.has('success') || urlParams.has('error')) {
                // Clean URL while preserving search state
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



    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search input interaction
        const searchInput = document.getElementById('searchInput');

        searchInput.addEventListener('focus', function() {
            this.classList.add('border-success');
        });

        searchInput.addEventListener('blur', function() {
            this.classList.remove('border-success');
        });
    </script>
</body>

</html>