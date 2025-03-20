<?php
// admin/analytics.php
session_start();
require_once 'includes/auth.php'; 
require_once __DIR__ . '/../db/db.php';
require_once 'admin_class.php';
$admin = new admin($conn);
$admin->checkAdminLogin();

$revenue = $admin->getTotalRevenue();
$popularProducts = $admin->getPopularProducts();
$monthlyRentals = $admin->getMonthlyRentals();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* Custom styles for better visualization */
        .mt{
            margin-top: 80px;
        }

    </style>
</head>
<body>
    <?php include '../includes/admin-navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt">Analytics & Reporting</h2>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                Total Revenue
                            </div>
                            <div class="card-body">
                                <h3>₱<?= number_format($revenue, 2) ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                Most Rented Products
                            </div>
                            <div class="card-body">
                                <ul class="list-group">
                                    <?php foreach ($popularProducts as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($product['name']) ?>
                                            <span class="badge bg-primary rounded-pill"><?= htmlspecialchars($product['rental_count']) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12">
                        <div class="card mb-3">
                            <div class="card-header">
                                Monthly Rentals
                            </div>
                            <div class="card-body">
                                <canvas id="monthlyRentalsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    const ctx = document.getElementById('monthlyRentalsChart').getContext('2d');
                    const monthlyRentalsChart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: [<?php foreach ($monthlyRentals as $mr) echo '"' . htmlspecialchars($mr['month']) . '",'; ?>],
                            datasets: [{
                                label: 'Number of Rentals',
                                data: [<?php foreach ($monthlyRentals as $mr) echo htmlspecialchars($mr['rentals']) . ','; ?>],
                                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                                borderColor: 'rgba(54, 162, 235, 1)',
                                borderWidth: 1,
                                fill: true,
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    precision: 0
                                }
                            }
                        }
                    });
                </script>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>