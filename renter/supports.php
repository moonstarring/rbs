<?php
// renter/support.php
session_start();
require_once __DIR__ . '/../db/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    // Validate inputs
    $errors = [];
    if (empty($subject)) $errors[] = "Subject is required.";
    if (empty($message)) $errors[] = "Message is required.";

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO support_requests (user_id, subject, message) VALUES (:user_id, :subject, :message)");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':subject', $subject, PDO::PARAM_STR);
        $stmt->bindParam(':message', $message, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Support request submitted successfully.";
        } else {
            $_SESSION['error'] = "Failed to submit support request.";
        }

        header('Location: support.php');
        exit();
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: support.php');
        exit();
    }
}

// Fetch user's support requests
$stmt = $conn->prepare("SELECT * FROM support_requests WHERE user_id = :user_id ORDER BY created_at DESC");
$stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$supportRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Support - Renter Dashboard</title>
    <link rel="icon" type="image/png" href="../images/rb logo white.png">
    <link rel="stylesheet" href="../css/renter/browse_style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body class="image-bg">
    <?php require_once '../includes/navbarr.php'; ?>
    <!-- mobile only navigation -->
    <nav class="navbar bg-secondary-subtle fixed-bottom d-md-none d-lg-none border-top">
        <div class="container">
            <div class="d-flex justify-content-around align-items-center w-100">
                <a class="navbar-brand" href="browse.php">
                    <i class="bi bi-house-fill rb"></i>
                </a>
                <a class="navbar-brand" href="../renter/cart.php">
                    <i class="bi bi-basket3-fill rb"></i>
                </a>
                <a class="navbar-brand m-0 p-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasWithBothOptions" aria-controls="offcanvasWithBothOptions">
                    <i class="bi bi-person-circle m-0 p-0 text-success" style="font-size: 20px;"></i>
                </a>
            </div>
        </div>
    </nav>
    <!-- mobile only sidebar -->
    <div class="offcanvas offcanvas-end d-md-none d-lg-none" data-bs-scroll="true" tabindex="-1" id="offcanvasWithBothOptions" aria-labelledby="offcanvasWithBothOptionsLabel">
        <div class="offcanvas-header">
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-4">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= htmlspecialchars($profilePic) ?>" class="object-fit-cover border rounded-circle" alt="pfp" style="width:100px; height: 100px;">
                <div class="">
                    <p class="dropdown-item-text fs-5 fw-bold m-0"><?= htmlspecialchars($username) ?></p>
                    <a class="link-success fw-bold" href="" id="toggleRoleButton" data-bs-toggle="modal" data-bs-target="#becomeOwnerModal">Become an Owner</a>
                </div>
            </div>
            <div class="d-flex flex-column gap-3 mt-3">
                <hr class="m-0 p-0">
                <a class="active text-decoration-none" href="profile.php"><i class="bi bi-gear-fill me-2"></i>Profile</a>
                <a class="active text-decoration-none" href="rentals.php"><i class="bi bi-box2-heart-fill me-2"></i>Rentals</a>
                <hr class="m-0 p-0">
                <a class="active text-decoration-none" href="supports.php"><i class="bi bi-headset me-2"></i>Supports</a>
                <a class="active text-decoration-none" href="file_dispute.php"><i class="bi bi-file-earmark-x-fill me-2"></i>File Dispute</a>
                <hr class="m-0 p-0">
                <a class="active text-decoration-none" href="../includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a>
            </div>
        </div>
    </div>
    <div class="container-fluid bg-secondary-subtle h-100 p-2 p-md-4 p-lg-4">
        <div class="bg-body p-3 p-md-4 p-lg-4 rounded-3 shadow">
            <h2 class="fs-5">Customer Support</h2>

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

            <!-- Support Request Form -->
            <div class="card my-4">
                <div class="card-header">
                    Submit a Support Request
                </div>
                <div class="card-body">
                    <form action="support.php" method="POST">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="bg-body p-3 p-md-4 p-lg-4 rounded-3 shadow mt-3">
            <!-- Existing Support Requests -->
            <h3 class="fs-5">Your Support Requests</h3>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th>Admin Response</th>
                        <th>Filed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($supportRequests)): ?>
                        <?php foreach ($supportRequests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['subject']) ?></td>
                                <td><?= nl2br(htmlspecialchars($request['message'])) ?></td>
                                <td>
                                    <span class="badge bg-<?=
                                                            $request['status'] === 'open' ? 'warning' : ($request['status'] === 'in_progress' ? 'info' : 'success')
                                                            ?>">
                                        <?= ucfirst($request['status']) ?>
                                    </span>
                                </td>
                                <td><?= nl2br(htmlspecialchars($request['admin_response'])) ?></td>
                                <td><?= htmlspecialchars($request['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No support requests found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>