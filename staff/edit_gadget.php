<?php
session_start();
require_once '../db/db.php';
require_once 'staff_class.php'; 

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

$staff = new Staff($conn);
$categories = $staff->getProductCategories();
$error = '';
$success = '';

// Get existing product data
$product = null;
if(isset($_GET['id'])) {
    try {
        $query = "SELECT * FROM products WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_GET['id']]);
        $product = $stmt->fetch();
        
        if(!$product) {
            header('Location: gadgets.php?error=Product not found');
            exit();
        }
    } catch(PDOException $e) {
        header('Location: gadgets.php?error=Database error');
        exit();
    }
} else {
    header('Location: gadgets.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $brand = $_POST['brand'];
    $description = $_POST['description'];
    $rental_price = $_POST['rental_price'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];
    $status = $_POST['status'];
    $overdue_price = $_POST['overdue_price'];
    $real_price = $_POST['real_price'];
    $condition_description = $_POST['condition_description'];
    $rental_period = $_POST['rental_period'];
    
    // Handle file upload
    $image = $product['image'];
    if(isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/rb/img/uploads/';
        $filename = uniqid() . '_' . basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $filename;
        
        // Validate image file
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        
        if(in_array($fileType, $allowedTypes)) {
            if(move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                // Delete old image if exists
                if(!empty($product['image'])) {
                    $oldImage = $uploadDir . $product['image'];
                    if(file_exists($oldImage)) {
                        unlink($oldImage);
                    }
                }
                $image = $filename;
            }
        } else {
            $error = 'Invalid file type. Only JPG, PNG, and GIF are allowed.';
        }
    }

    if(empty($error)) {
        try {
            $query = "UPDATE products SET
                name = ?, brand = ?, description = ?, rental_price = ?,
                status = ?, image = ?, quantity = ?, category = ?,
                overdue_price = ?, real_price = ?, condition_description = ?,
                rental_period = ?
                WHERE id = ?";

            $stmt = $conn->prepare($query);
            $success = $stmt->execute([
                $name, $brand, $description, $rental_price,
                $status, $image, $quantity, $category,
                $overdue_price, $real_price, $condition_description,
                $rental_period, $product['id']
            ]);

            if($success) {
                header('Location: gadgets.php?success=Product updated successfully');
                exit();
            }
        } catch(PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php require_once 'head.php' ?>
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">
<?php require_once 'navbar.php' ?>

    <!-- Main Content -->
    <div class="container mt-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Gadget</h4>
            </div>
            <div class="card-body">
                <!-- Error/Success Messages -->
                <?php if($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <!-- Left Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?= htmlspecialchars($product['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Brand</label>
                                <input type="text" name="brand" class="form-control" 
                                       value="<?= htmlspecialchars($product['brand']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required><?= 
                                    htmlspecialchars($product['description']) ?></textarea>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rental Price</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" name="rental_price" 
                                                   class="form-control" value="<?= $product['rental_price'] ?>" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Status</label>
                                        <select name="status" class="form-select" required>
                                            <option value="available" <?= $product['status'] === 'available' ? 'selected' : '' ?>>Available</option>
                                            <option value="rented" <?= $product['status'] === 'rented' ? 'selected' : '' ?>>Rented</option>
                                            <option value="under_maintenance" <?= $product['status'] === 'under_maintenance' ? 'selected' : '' ?>>Under Maintenance</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Product Image</label>
                                <input type="file" name="image" class="form-control" accept="image/*">
                                <?php if($product['image']): ?>
                                <div class="mt-3">
                                    <p class="text-muted mb-1">Current Image:</p>
                                    <img src="../img/uploads/<?= htmlspecialchars($product['image']) ?>" 
                                         class="img-thumbnail" style="max-height: 200px;">
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Category</label>
                                        <select name="category" class="form-select" required>
                                            <?php foreach ($categories as $cat): ?>
                                            <option value="<?= $cat ?>" <?= $product['category'] === $cat ? 'selected' : '' ?>>
                                                <?= $cat ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Rental Period</label>
                                        <select name="rental_period" class="form-select" required>
                                            <option value="Day" <?= $product['rental_period'] === 'Day' ? 'selected' : '' ?>>Daily</option>
                                            <option value="Week" <?= $product['rental_period'] === 'Week' ? 'selected' : '' ?>>Weekly</option>
                                            <option value="Month" <?= $product['rental_period'] === 'Month' ? 'selected' : '' ?>>Monthly</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Quantity</label>
                                        <input type="number" name="quantity" class="form-control" 
                                               value="<?= $product['quantity'] ?>" min="1" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Actual Value</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" step="0.01" name="real_price" 
                                                   class="form-control" value="<?= $product['real_price'] ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Overdue Price (per day)</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" name="overdue_price" 
                                           class="form-control" value="<?= $product['overdue_price'] ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Condition Description</label>
                                <textarea name="condition_description" class="form-control" rows="3"><?= 
                                    htmlspecialchars($product['condition_description']) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <a href="gadgets.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Gadget</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light mt-5">
        <div class="container py-5">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5>Rentbox</h5>
                    <p class="text-muted">
                        A company registered in the Philippines<br>
                        Company Reg. No. CS2024123456
                    </p>
                </div>
                <div class="col-md-3">
                    <h5>For Owners</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">Become an Owner</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Owner Dashboard</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>For Renters</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light text-decoration-none">Create an Account</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Browse Gadgets</a></li>
                        <li><a href="#" class="text-light text-decoration-none">Start Renting</a></li>
                    </ul>
                </div>
                <div class="col-12">
                    <hr class="border-light">
                    <div class="d-flex justify-content-between">
                        <div>
                            <a href="#" class="text-light text-decoration-none me-3">About Rentbox</a>
                            <a href="#" class="text-light text-decoration-none me-3">Help Center</a>
                            <a href="#" class="text-light text-decoration-none me-3">Terms and Conditions</a>
                            <a href="#" class="text-light text-decoration-none">Contact Us</a>
                        </div>
                        <div class="text-muted">
                            &copy; 2024 Rentbox. All rights reserved.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script src="../vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>