<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
session_start();

require_once __DIR__ . '/../db/db.php'; // Include your database connection
require_once 'owner_class.php';
$owner = new Owner($conn);
$owner->authenticateOwner(); // Authenticate owner and set userId

// Call methods to get data
$username = $owner->getUserName();
$totalEarnings = $owner->getTotalEarningsForMonth();
$totalRentals = $owner->getTotalRentals();
$topGadgets = $owner->getTopEarningGadgets();
$listedGadgets = $owner->getListedGadgets();
$alerts = $owner->getAlerts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>

    <?php include '../includes/owner-header-sidebar.php'; ?>

    <div class="main-content">
        <div class="row">
            <div class="col-md-9 offset-md-3 mt-4">
                <!-- Welcome Section -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="welcome">Welcome back, <?php echo htmlspecialchars($username); ?>!</h2>
                        <p class="overview">Here's Your Current Sales Overview</p>
                    </div>
                </div>

                <!-- Overview Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card card-hover shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Earnings this Month</h5>
                                <div>
                                    <h3>₱ <?php echo number_format($totalEarnings, 2); ?> <span class="text-success fs-5">&#x25B2;</span></h3>
                                    <p class="card-text text-muted">Increase compared to last week</p>
                                    <a href="#" class="text-decoration-none">Revenues report &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-hover shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Total Rentals</h5>
                                <div>
                                    <h3><?php echo $totalRentals; ?></h3>
                                    <p class="card-text text-muted">You closed <?php echo $totalRentals; ?> rentals this month.</p>
                                    <a href="#" class="text-decoration-none">All Rentals &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card card-hover shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Top Earning Gadgets</h5>
                                <div>
                                    <?php foreach ($topGadgets as $gadget): ?>
                                        <div class="d-flex align-items-center mb-2">
                                            <img src="../img/uploads/<?php echo $gadget['image']; ?>" alt="<?php echo $gadget['name']; ?>" class="prod-img-db me-3">
                                            <div>
                                                <p class="mb-0 device-name"><?php echo $gadget['name']; ?></p>
                                                <span class="device-status text-muted"><?php echo $gadget['rentals_count']; ?> Rentals</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Listed Gadgets -->
                    <div class="col-md-8">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Listed Gadgets</h5>
                                <div>
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Gadget</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($listedGadgets as $product): ?>
                                                <tr>
                                                    <td class="d-flex align-items-center">
                                                        <img src="../img/uploads/<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="prod-img-db me-3">
                                                        <span class="device-name"><?php echo $product['name']; ?></span>
                                                    </td>
                                                    <td>
                                                        <div><?php echo $product['status']; ?></div>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></button>
                                                        <button class="btn btn-sm btn-secondary"><i class="fas fa-eye"></i></button>
                                                        <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                    <a href="gadget.php" class="btn btn-primary">+ Add Gadget</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts & Reminders -->
                    <div class="col-md-4">
                        <div class="card shadow-sm mb-4">
                            <div class="card-body">
                                <h5 class="card-title title-with-line">Alerts & Reminders</h5>
                                <div>
                                    <ul class="list-group list-group-flush">
                                        <?php foreach ($alerts as $alert): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($alert['subject']); ?></h6>
                                                    <small><?php echo htmlspecialchars($alert['message']); ?></small>
                                                </div>
                                                <span class="badge bg-danger"><?php echo timeAgo($alert['created_at']); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    <a href="#" class="text-decoration-none">View All &rarr;</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>