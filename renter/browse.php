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


function shortenText($text, $maxLength = 20)
{
    if (strlen($text) > $maxLength) {
        return htmlspecialchars(substr($text, 0, $maxLength) . "...");
    } else {
        return htmlspecialchars($text);
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

        <div class="bg-body rounded-top-5 d-flex">
            <div class="mx-5 my-4 container-fluid d-flex justify-content-between align-items-center">
                <p class="fs-4 fw-bolder my-auto rb d-none d-sm-block">Rent Gadgets, Your Way</p>
                <form class="d-flex gap-3 my-lg-0" method="GET" action="">
                    <input class="form-control rounded-5 px-3 shadow-sm ms-auto"
                        type="text"
                        placeholder="Type to search..."
                        id="searchInput"
                        name="search"
                        value="<?php echo htmlspecialchars($searchTerm); ?>">
                    <button class="btn rounded-pill gradient-success rounded-5 px-4 py-0 m-0 shadow-sm" type="submit">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <div class="container-fluid bg-dark-subtle  m-0 p-0">
            <!-- Include Sidebar -->
            <?php include '../includes/sidebar.php'; ?>
            <!-- Products Display Area  id="product-list" -->
            <!-- <div class="row row-cols-md-5 row-cols-sm-2 g-3 m-0 p-3">
                <?php foreach ($formattedProducts as $product): ?>
                    <div class="col-sm-2 ">
                        <div class="card hover-effect h-100" style="max-height: 60vh;">
                            <a href="item.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                <img class="object-fit-cover shadow-sm card-img-top border-bottom" style="max-height: 30vh; max-width: auto;" src="../img/uploads/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title" style="font-size:large;"><?php echo shortenText($product['name']); ?></h5>
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
                                        <a href="/rb/renter/item.php?id=<?= $product['id'] ?>" class="btn btn-sm rounded-pill gradient-success">Rent Now</a>
                                    </div>
                                </div>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div> -->
            <div class="container-fluid m-0 p-0 p-md-4 mt-md-2 ">
                <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-5 g-1 g-sm-0 g-md-3 g-lg-4">
                    <?php foreach ($formattedProducts as $product): ?>
                        <div class="col p-0 mt-md-0">
                            <div class="card card-height hover-effect shadow-sm m-1">
                                <a href="item.php?id=<?php echo $product['id']; ?>" class="text-decoration-none text-dark">
                                    <img src="../img/uploads/<?php echo $product['image']; ?>"
                                        alt="<?php echo htmlspecialchars($product['name']); ?>"
                                        class="card-img-top object-fit-cover border" style="height: 200px;">

                                    <div class="card-body d-flex flex-column m-0 p-2 p-md-3">
                                        <h6 class="card-title fs-6 fw-bold m-0"><?php echo shortenText($product['name']); ?></h6>

                                        <div class="mt-auto justify-content-between align-items-center mb-1"> <!-- Push buttons to the bottom -->
                                            <div class="d-flex justify-content-between align-items-baseline mb-1">
                                                <small class="text-muted">
                                                    <i class="bi bi-star-fill text-warning me-1"></i>
                                                    <?php echo $product['average_rating']; ?> (<?php echo $product['rating_count']; ?>)
                                                </small>
                                                <p class="mb-0">₱<?php echo $product['rental_price']; ?><small class="text-muted">/day</small></p>
                                            </div>
                                            
                                            <form action="add_to_cart.php" method="POST" class="d-inline">
                                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                                <input type="hidden" name="page" value="<?php echo htmlspecialchars($_GET['page'] ?? 1); ?>">
                                                <a type="submit" class="btn btn-outline-dark btn-sm rounded-5 shadow-sm">Add to Cart
                                                </a>
                                            </form>
                                            <a href="/rb/renter/item.php?id=<?= $product['id'] ?>" class="btn btn-sm rounded-pill gradient-success">Rent Now</a>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <!-- pagination -->
            <div class="mx-5 my-4">
                <div class="d-flex justify-content-between mx-5">
                    <!-- Previous button -->
                    <button type="button" class="btn btn-light text-start ms-5"
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
                    <button type="button" class="btn btn-light text-start me-5"
                        <?php echo $page >= $totalPages ? 'disabled' : ''; ?>
                        onclick="window.location.href='?search=<?php echo $searchTerm; ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $_GET['sort'] ?? 'newest'; ?>'">
                        <small><i class="bi bi-caret-right-fill"></i></small>
                    </button>
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

        <?php require_once '../includes/footer.php' ?>

    </div>

    <?php if (empty($formattedProducts)): ?>
        <div class="col-12 no-products">
            <p>No products found. Try another search!</p>
        </div>
    <?php endif; ?>


    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');

            searchInput.addEventListener('focus', function() {
                searchInput.classList.add('expanded-input');
            });

            searchInput.addEventListener('blur', function() {
                searchInput.classList.remove('expanded-input');
            });
        });
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