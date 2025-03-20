<?php
ini_set('display_errors', 0); // Disable error display in production
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/db.php';
require_once 'owner_class.php';
$owner = new Owner($conn);
$owner->authenticateOwner();
$ownerId = $_SESSION['id']; 
$products = $owner->getOwnerProducts($ownerId);
// Call in your page after form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $owner->handleAddProduct($_POST, $_FILES);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $owner->handleEditProduct($_POST, $_FILES);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_product'])) {
    $owner->handleDeleteProduct($_POST);
}
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Then include your modal
include __DIR__ . '/add-modal.php';
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Gadgets</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        #sidebarMenu {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding-top: 56px;
            overflow-x: hidden;
            overflow-y: auto;
            background-color: #f8f9fa;
        }
        main {
            padding-top: 56px;
        }
        @media (max-width: 768px) {
            #sidebarMenu {
                position: relative;
                height: auto;
                padding-top: 0;
            }
            main {
                padding-top: 0;
            }
        }
    </style>
</head>
<body>
<?php include '../includes/owner-header-sidebar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-2 d-md-block bg-light sidebar collapse">
                <!-- Sidebar content -->
            </nav>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="bg-secondary-subtle my-3">
                    <div class="card rounded-3">
                        <div class="d-flex justify-content-between align-items-center mt-4 mb-2 mx-5">
                            <h2 class="mb-0">My Gadgets</h2>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="bi bi-plus-lg"></i> Add Item
                            </button>
                        </div>

                        <!-- Display Success Message -->
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show mx-5" role="alert">
                                <?= htmlspecialchars($_SESSION['success']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>

                        <!-- Display Error Message -->
                        <?php if (isset($_SESSION['error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show mx-5" role="alert">
                                <?= htmlspecialchars($_SESSION['error']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['error']); ?>
                        <?php endif; ?>

                        <?php include __DIR__ . '/add-modal.php'; ?>

                        <hr class="mx-3 my-0">

                        <div class="card-body rounded-5">
                            <div class="table-container">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered text-center">
                                        <thead class="table-dark">
                                            <tr>
                                                <th scope="col" style="width: 5%;">No.</th>
                                                <th scope="col" style="width: 15%;">Product Name</th>
                                                <th scope="col" style="width: 15%;">Brand</th>
                                                <th scope="col" style="width: 20%;">Description</th>
                                                <th scope="col" style="width: 10%;">Price</th>
                                                <th scope="col" style="width: 10%;">Quantity</th>
                                                <th scope="col" style="width: 10%;">Category</th>
                                                <th scope="col" style="width: 10%;">Status</th>
                                                <th scope="col" style="width: 15%;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if ($products): ?>
                                                <?php foreach ($products as $index => $product): ?>
                                                    <tr class="align-middle">
                                                        <th scope="row"><?= htmlspecialchars($index + 1) ?></th>
                                                        <td><?= htmlspecialchars($product['name']) ?></td>
                                                        <td><?= htmlspecialchars($product['brand']) ?></td>
                                                        <td><?= nl2br(htmlspecialchars($product['description'])) ?></td>
                                                        <td>
                                                            PHP <?= number_format($product['rental_price'], 2) ?>
                                                            <?php if (isset($product['rental_period'])): ?>
                                                                per <?= htmlspecialchars($product['rental_period']) ?>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($product['quantity']) ?></td>
                                                        <td><?= htmlspecialchars($product['category']) ?></td>
                                                        <td><?= ucfirst(str_replace('_', ' ', htmlspecialchars($product['status']))) ?></td>
                                                        <td>
                                                            <div class="btn-group" role="group">
                                                                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#viewModal<?= $product['id'] ?>" title="View">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editModal<?= $product['id'] ?>" title="Edit">
                                                                    <i class="bi bi-pencil"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $product['id'] ?>" title="Delete">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <?php include __DIR__ . '/view-modal.php'; ?>
                                                    <?php include __DIR__ . '/edit-modal.php'; ?>
                                                    <?php include __DIR__ . '/delete-modal.php'; ?>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center">No gadgets found.</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        var addModal = document.getElementById('addModal');
        if (addModal) {
            addModal.addEventListener('shown.bs.modal', function () {
                document.getElementById('productName').focus();
            });
        }
        <?php foreach ($products as $product): ?>
            var editModal<?= $product['id'] ?> = document.getElementById('editModal<?= $product['id'] ?>');
            if (editModal<?= $product['id'] ?>) {
                editModal<?= $product['id'] ?>.addEventListener('shown.bs.modal', function () {
                    document.getElementById('editName<?= $product['id'] ?>').focus();
                });
            }
        <?php endforeach; ?>
    </script>
</body>
</html>