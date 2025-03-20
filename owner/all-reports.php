<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require_once __DIR__ . '/../db/db.php'; // Include your database connection
require_once 'functions.php'; // Include your custom functions (for CSRF validation and others)
require_once 'owner_class.php';

// Initialize Owner class
$owner = new Owner($conn);
$owner->authenticateOwner();

// Fetch data
$currentYear = date('Y');
$currentMonth = date('n');


$availability = $owner->getGadgetAvailability();

// Safely extract values, ensuring no undefined variable warnings
$available = $availability['available'] ?? 0;
$rented = $availability['rented'] ?? 0;
$inMaintenance = $availability['inMaintenance'] ?? 0;
$incomeData = $owner->getMonthlyIncomeData($currentYear);
$weeksData = $owner->getWeeklyIncomeData($currentMonth, $currentYear);
$rentalFrequency = $owner->getRentalFrequency();
$maintenanceIssues = $owner->getMaintenanceIssues();
$transactions = $owner->getTransactionHistory();
$gadgetAvailability = $owner->getGadgetAvailability();
$ratings = $owner->getRatings();
$earnings = $owner->calculateEarnings();
$commissionRate = 0.10;
$commission = $earnings['commission'] * $commissionRate;
$netEarnings = $earnings['netEarnings'] - $commision;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>
    <div class="main-content">
        <div class="row">
            <div class="col-md-9 offset-md-3 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h2 class="mb-0">All Reports</h2>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Total Income</h5>
                                <?php if(array_sum($incomeData) > 0): ?>
                                    <canvas id="totalIncomeChart"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No income data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Earning This Month</h5>
                                <?php if(array_sum($weeksData) > 0): ?>
                                    <canvas id="earningThisMonthChart"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No earnings data for this month</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rental Frequency & Maintenance -->
                <div class="row">
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Rental Frequency</h5>
                                <?php if(!empty($rentalFrequency)): ?>
                                    <canvas id="rentalFrequencyChart"></canvas>
                                <?php else: ?>
                                    <p class="text-muted">No rental data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Maintenance & Issues</h5>
                                <?php if(!empty($maintenanceIssues)): ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Gadget</th>
                                                <th>Issue Reported</th>
                                                <th>Date Reported</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($maintenanceIssues as $issue): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($issue['gadget']) ?></td>
                                                    <td><?= htmlspecialchars($issue['issue_reported']) ?></td>
                                                    <td><?= date('M j, Y', strtotime($issue['reported_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">No maintenance issues reported</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History & Availability -->
                <div class="row">
                    <div class="col-md-8 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Transaction History</h5>
                                <?php if(!empty($transactions)): ?>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Gadget</th>
                                                <th>Renter</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($transactions as $transaction): ?>
                                                <tr>
                                                    <td><?= date('Y-m-d', strtotime($transaction['date'])) ?></td>
                                                    <td><?= htmlspecialchars($transaction['gadget']) ?></td>
                                                    <td><?= htmlspecialchars($transaction['renter']) ?></td>
                                                    <td>₱<?= number_format($transaction['amount'], 2) ?></td>
                                                    <td><?= ucfirst($transaction['payment_status']) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p class="text-muted">No transaction history available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Gadgets Availability & Ratings</h5>
                                <p>Available: <strong><?= htmlspecialchars($available) ?></strong></p>
                                <p>Rented: <strong><?= htmlspecialchars($rented) ?></strong></p>
                                <p>In Maintenance: <strong><?= htmlspecialchars($inMaintenance) ?></strong></p>
                                <?php foreach($ratings as $rating): ?>
                                    <p><?= $rating['category'] ?>: 
                                        <span class="text-warning">
                                            <?= str_repeat('⭐', round($rating['avg_rating'])) ?>
                                            (<?= number_format($rating['avg_rating'], 1) ?>)
                                        </span>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Commission -->
                <div class="row">
                    <div class="col-md-12 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Commission Deducted</h5>
                                <p>Total Commission Deducted: <strong>₱<?= number_format($commission ?? 0, 2) ?></strong></p>
<p>Commission Rate: <strong><?= $commissionRate * 100 ?>%</strong></p>
<p>Earnings Before Deduction: <strong>₱<?= number_format($totalEarnings ?? 0, 2) ?></strong></p>
<p>Net Earnings After Deduction: <strong>₱<?= number_format($netEarnings ?? 0, 2) ?></strong></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Total Income Chart
        <?php if(array_sum($incomeData) > 0): ?>
            new Chart(document.getElementById('totalIncomeChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                    datasets: [{
                        label: 'Total Income',
                        data: <?= json_encode($incomeData) ?>,
                        borderColor: 'rgba(75, 192, 192, 1)',
                        fill: false
                    }]
                }
            });
        <?php else: ?>
            document.getElementById('totalIncomeChart').parentElement.innerHTML = '<p class="text-muted">No income data available</p>';
        <?php endif; ?>

        // Earning This Month Chart
        <?php if(array_sum($weeksData) > 0): ?>
            new Chart(document.getElementById('earningThisMonthChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
                    datasets: [{
                        label: 'Earnings',
                        data: <?= json_encode($weeksData) ?>,
                        backgroundColor: 'rgba(153, 102, 255, 0.6)'
                    }]
                }
            });
        <?php else: ?>
            document.getElementById('earningThisMonthChart').parentElement.innerHTML = '<p class="text-muted">No earnings data for this month</p>';
        <?php endif; ?>

        // Rental Frequency Chart
        <?php if(!empty($rentalFrequency)): ?>
            new Chart(document.getElementById('rentalFrequencyChart').getContext('2d'), {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_column($rentalFrequency, 'category')) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($rentalFrequency, 'count')) ?>,
                        backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(75, 192, 192, 0.6)']
                    }]
                }
            });
        <?php else: ?>
            document.getElementById('rentalFrequencyChart').parentElement.innerHTML = '<p class="text-muted">No rental data available</p>';
        <?php endif; ?>
    </script>
</body>
</html>