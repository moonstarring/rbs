<?php
// cart.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
require_once '../db/db.php';
require_once 'renter_class.php';
$renter = new renter($conn);
$renter->authenticateRenter();

$userId = $_SESSION['id'];
$cartData = $renter->getCartItems($userId);

$cartItems = $cartData['cartItems'];
$subtotal = $cartData['subtotal'];
$allAvailable = $cartData['allAvailable'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <title>Cart</title>
    <link href="../vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../vendor/flatpickr.min.css">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
</head>

<body>
    <div class="container-fluid image-bg m-0 p-0">
        <?php require_once '../includes/navbarr.php'; ?>

        <div class="bg-body rounded-top-5 mx-md-4 mx-lg-4 mt-md-4 mt-lg-4 p-md-4 p-lg-4 px-md-4 pb-md-4 px-lg-4 pb-lg-4 h-100">
            <div class="row m-0 p-0">
                <!-- Items wrapper -->
                <div class="col-md-9 col-lg-9 order-first p-0 border">
                    <!-- Title -->
                    <div class="rounded-3 d-flex bg-body-secondary justify-content-between align-items-center">
                        <h5 class="m-0 p-0 text-success ps-md-3">Your Cart</h5>
                        <a href="browse.php" class="d-flex btn btn-outline-light align-items-center border-0 rounded-start-0">
                            <i class="bi bi-caret-left-fill text-success pe-2 fs-6"></i>
                            <h6 class="mb-0 text-success">Continue shopping</h6>
                        </a>
                    </div>

                    <?php if ($cartItems): ?>
                        <?php foreach ($cartItems as $item): ?>
                            <!-- Item with actions -->
                            <div class="row m-0 py-md-3 py-lg-3 justify-content-between">
                                <!-- Info -->
                                <div class="col-9 m-0 p-0 border">
                                    <div class="d-flex">
                                        <a href="item.php?id=<?php echo $item['product_id']; ?>">
                                            <img src="../img/uploads/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-img border border-2 object-fit-cover">
                                        </a>
                                        <div class="border ms-md-3 ms-lg-3">
                                            <a href="item.php?id=<?php echo $item['product_id']; ?>" class="text-decoration-none fw-bold text-dark text-start fs-6 smol"><?php echo htmlspecialchars($item['name']); ?></a>
                                            <div class="d-flex gap-3 align-items-start mt-md-2">
                                                <small class="text-body-secondary smol">Condition</small>
                                                <small class="mb-0 border rounded border-success px-2 py-1 text-success fw-bold smol"><?php echo htmlspecialchars($item['status']); ?></small>
                                            </div>
                                            <div class="gap-3 align-items-start mt-2 d-none d-md-flex d-lg-flex">
                                                <small class="text-body-secondary">Category</small>
                                                <div class="d-flex align-items-center border rounded border-success px-2 py-1 text-success">
                                                    <small class="mb-0"><?php echo htmlspecialchars($item['category']); ?></small>
                                                </div>
                                            </div>
                                            <div class="gap-3 align-items-start mt-2 d-none d-md-flex d-lg-flex">
                                                <small class="text-body-secondary">Description</small>
                                                <div class="d-flex align-items-center px-2 py-1">
                                                    <small class="mb-0"><?php echo htmlspecialchars($item['description']); ?></small>
                                                </div>
                                            </div>
                                            <!-- Reserve dates -->
                                            <div class="d-flex gap-3 align-items-start mt-2">
                                                <small class="text-body-secondary">Reserve</small>
                                                <div class="d-flex">
                                                    <input class="border border-success border-1 rounded-start px-2 text-success" type="text" id="startDate_<?php echo $item['id']; ?>" data-cart-id="<?php echo $item['id']; ?>" placeholder="Start Date" style="width: 100px; font-size: 14px;" value="<?= htmlspecialchars($item['start_date'] ?? ''); ?>">
                                                    <input class="border border-success border-1 rounded-end px-2 text-success" type="text" id="endDate_<?php echo $item['id']; ?>" data-cart-id="<?php echo $item['id']; ?>" placeholder="End Date" style="width: 100px; font-size: 14px;" value="<?= htmlspecialchars($item['end_date'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="col-3 d-flex flex-column align-items-center border">
                                    <div class="d-flex flex-column align-items-center">
                                        <!-- Remove Item -->
                                        <a class="btn btn-outline-danger btn-sm px-3 mb-2 fs-6 rounded d-flex justify-content-center" href="remove_from_cart.php?cart_id=<?php echo $item['id']; ?>" type="button">
                                            <i class="bi bi-trash fs-6 pe-2 pt-1"></i>
                                            <small class="pt-1">Remove</small>
                                        </a>

                                        <a type="button" class="btn btn-light px-3" href="browse.php?category=<?php echo urlencode($item['category']); ?>" style="font-size:small;">View Similar</a>
                                    </div>
                                </div>
                                <hr class="px-2 mt-2">
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-5">
                            <h3 class="text-center">Your cart is empty.</h3>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Other functions wrapper -->
                <div class="col-3 order-last">
                    <!-- Total -->
                    <div class="d-flex flex-column justify-content-center">
                        <h5 class="h6 py-2 rounded bg-body-secondary text-success text-center">Subtotal</h5>
                        <h4 class="fw-bold text-center pt-3 pb-1">₱<?php echo number_format($subtotal, 2); ?></h4>

                        <hr class="mx-2 p-0">

                        <small class="mt-1 mx-auto">Additional comments</small>

                        <textarea class="form-control mt-2 mb-3" id="order-comments" rows="5" style="font-size:12px;"></textarea>

                        <?php if ($allAvailable): ?>
                            <a class="btn btn-success btn-sm px-3 rounded d-flex justify-content-center" href="checkout.php" type="button">
                                <i class="bi bi-credit-card fs-6 pe-2 pt-1"></i>
                                Checkout
                            </a>
                        <?php else: ?>
                            <button class="btn btn-success btn-sm px-3 rounded d-flex justify-content-center" type="button" disabled>
                                <i class="bi bi-credit-card fs-6 pe-2 pt-1"></i>
                                Checkout
                            </button>
                            <small class="text-danger mt-2">One or more items are unavailable for checkout.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-body mt-4">
        <div class="d-flex flex-column flex-sm-row justify-content-between py-2 border-top">
            <p class="ps-3">© 2024 Rentbox. All rights reserved.</p>
            <ul class="list-unstyled d-flex pe-3">
                <li class="ms-3"><a href=""><i class="bi bi-facebook text-body"></i></a></li>
                <li class="ms-3"><a href=""><i class="bi bi-twitter text-body"></i></a></li>
                <li class="ms-3"><a href=""><i class="bi bi-linkedin text-body"></i></a></li>
            </ul>
        </div>
    </footer>
    </div>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../vendor/flatpickr.min.js"></script>
    <script>
        // Initialize flatpickr for each cart item's date inputs and handle AJAX updates
        <?php foreach ($cartItems as $item): ?>
            flatpickr("#startDate_<?php echo $item['id']; ?>", {
                dateFormat: "Y-m-d",
                minDate: "today",
                maxDate: new Date(2025, 11, 1),
                disableMobile: true,
                defaultDate: "<?= htmlspecialchars($item['start_date'] ?? ''); ?>",
                onChange: function(selectedDates, dateStr, instance) {
                    const cartId = instance.element.dataset.cartId;
                    const endDateInput = document.getElementById('endDate_' + cartId);
                    if (endDateInput.value && new Date(endDateInput.value) < selectedDates[0]) {
                        endDateInput.value = '';
                        alert('End date cannot be before start date.');
                    }
                    if (endDateInput.value) {
                        updateCartDates(cartId, dateStr, endDateInput.value);
                    }
                }
            });

            flatpickr("#endDate_<?php echo $item['id']; ?>", {
                dateFormat: "Y-m-d",
                minDate: "today",
                maxDate: new Date(2025, 11, 1),
                disableMobile: true,
                defaultDate: "<?= htmlspecialchars($item['end_date'] ?? ''); ?>",
                onChange: function(selectedDates, dateStr, instance) {
                    const cartId = instance.element.dataset.cartId;
                    const startDateInput = document.getElementById('startDate_' + cartId);
                    if (startDateInput.value && new Date(startDateInput.value) > selectedDates[0]) {
                        startDateInput.value = '';
                        alert('Start date cannot be after end date.');
                    }
                    if (startDateInput.value) {
                        updateCartDates(cartId, startDateInput.value, dateStr);
                    }
                }
            });
        <?php endforeach; ?>

        // Function to send AJAX request to update cart dates
        function updateCartDates(cartId, startDate, endDate) {
            // Ensure both dates are provided
            if (!startDate || !endDate) {
                alert('Both start and end dates must be selected.');
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'update_cart_dates.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                console.log('Cart dates updated successfully.');
                                // Optionally, display a success message to the user
                            } else {
                                alert('Failed to update dates: ' + response.message);
                            }
                        } catch (e) {
                            alert('Invalid server response.');
                        }
                    } else {
                        alert('An error occurred while updating dates.');
                    }
                }
            };
            xhr.send('cart_id=' + encodeURIComponent(cartId) +
                '&start_date=' + encodeURIComponent(startDate) +
                '&end_date=' + encodeURIComponent(endDate));
        }
    </script>
</body>

</html>