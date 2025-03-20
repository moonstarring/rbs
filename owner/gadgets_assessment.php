<?php
// owner/gadgets_assessment.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';
require_once 'owner_class.php';
$owner = new Owner($conn);
$owner->authenticateOwner();

// Call the handleGadgetAssessment method after form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assess_gadget'])) {
    $owner->handleGadgetAssessment($_POST, $_FILES, $_SESSION['id']);
}

// Fetch owner's products
$products = $owner->getOwnerProducts($_SESSION['id']);

// Fetch existing gadget condition assessments
$assessments = $owner->getOwnerAssessments($_SESSION['id']);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gadget Condition Assessment - Owner Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <h2 class="mt-4">Gadget Condition Assessment</h2>

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

                <!-- Assessment Form -->
                <div class="card my-4">
                    <div class="card-header">
                        Assess Gadget Condition
                    </div>
                    <div class="card-body">
                        <form action="gadgets_assessment.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="product_id" class="form-label">Select Gadget</label>
                                <select class="form-select" id="product_id" name="product_id" required>
                                    <option value="" disabled selected>Select a gadget</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= htmlspecialchars($product['id']) ?>"><?= htmlspecialchars($product['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="condition_description" class="form-label">Condition Description</label>
                                <textarea class="form-control" id="condition_description" name="condition_description" rows="4" required></textarea>
                            </div>
                            <div class="mb-3">
                                <label for="photo" class="form-label">Upload Photo (Optional)</label>
                                <input class="form-control" type="file" id="photo" name="photo" accept="image/*">
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Assessment</button>
                        </form>
                    </div>
                </div>

                <!-- Existing Assessments -->
                <h3>Existing Assessments</h3>
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Gadget</th>
                            <th>Description</th>
                            <th>Photo</th>
                            <th>Reported At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($assessments)): ?>
                            <?php foreach ($assessments as $assessment): ?>
                                <tr>
                                    <td><?= htmlspecialchars($assessment['product_name']) ?></td>
                                    <td><?= nl2br(htmlspecialchars($assessment['condition_description'])) ?></td>
                                    <td>
                                        <?php if ($assessment['photo']): ?>
                                            <a href="../uploads/gadget_conditions/<?= htmlspecialchars($assessment['photo']) ?>" target="_blank">
                                                <img src="../uploads/gadget_conditions/<?= htmlspecialchars($assessment['photo']) ?>" alt="Photo" width="100">
                                            </a>
                                        <?php else: ?>
                                            N/A
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($assessment['reported_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center">No assessments found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </main>
        </div>
    </div>

</body>
</html>