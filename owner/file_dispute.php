<?php
// owner/file_dispute.php
session_start();
require_once __DIR__ . '/../db/db.php';
require_once 'owner_class.php';
$owner = new Owner($conn);
$owner->authenticateOwner(); // Authenticate owner and set userId
// Fetch rentals of the owner to select for dispute
$rentals = $owner->getRentalsForDispute();

// Handle form submission to file a dispute
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rental_id = intval($_POST['rental_id']);
    $reason = trim($_POST['reason']);
    $description = trim($_POST['description']);

    // Call the fileDispute method to handle the dispute filing
    $owner->fileDispute($rental_id, $reason, $description);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File a Dispute - Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php require_once '../includes/owner-header-sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">File a Dispute</h2>

                <!-- Success Message -->
                <?php if (isset($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['success']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <!-- Error Message -->
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($_SESSION['error']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>

                <div class="card my-4">
                    <div class="card-header">
                        Dispute Form
                    </div>
                    <div class="card-body">
                        <form action="file_dispute.php" method="POST">
                            <div class="mb-3">
                                <label for="rental_id" class="form-label">Select Rental</label>
                                <select class="form-select" id="rental_id" name="rental_id" required>
                                    <option value="" disabled selected>Select a rental</option>
                                    <?php foreach ($rentals as $rental): ?>
                                        <option value="<?= htmlspecialchars($rental['id']) ?>">
                                            <?= htmlspecialchars($rental['name']) ?> (Renter: <?= htmlspecialchars($rental['renter_name']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="reason" class="form-label">Reason</label>
                                <input type="text" class="form-control" id="reason" name="reason" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Dispute</button>
                        </form>
                    </div>
                </div>

                <!-- Existing Disputes -->
                <h3>Your Disputes</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Rental</th>
                            <th>Reason</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Filed At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch disputes filed by the owner
                        $stmt = $conn->prepare("SELECT d.*, p.name AS product_name, u.name AS renter_name 
                                                FROM disputes d 
                                                JOIN rentals r ON d.rental_id = r.id 
                                                JOIN products p ON r.product_id = p.id 
                                                JOIN users u ON r.renter_id = u.id 
                                                WHERE d.initiated_by = :userId 
                                                ORDER BY d.created_at DESC");
                        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
                        $stmt->execute();
                        $ownerDisputes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>

                        <?php if (!empty($ownerDisputes)): ?>
                            <?php foreach ($ownerDisputes as $dispute): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dispute['product_name']) ?> (Renter: <?= htmlspecialchars($dispute['renter_name']) ?>)</td>
                                    <td><?= htmlspecialchars($dispute['reason']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($dispute['description'])) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $dispute['status'] === 'open' ? 'warning' : 
                                            ($dispute['status'] === 'under_review' ? 'info' : 
                                            ($dispute['status'] === 'resolved' ? 'success' : 'secondary'))
                                        ?>">
                                            <?= ucfirst($dispute['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($dispute['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center">No disputes filed.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>