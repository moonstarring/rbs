<?php
// Ensure session is started only once
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../db/db.php';

// Change 'user_id' to 'id' to match your login.php
if (isset($_SESSION['id'])) {
    $userId = $_SESSION['id'];

    try {
        // Modified query to get user data
        $query = "
            SELECT 
                CONCAT_WS(' ', users.first_name, users.last_name) AS name,
                users.role, 
                users.profile_picture, 
                COALESCE(user_verification.verification_status, 'pending') AS verification_status
            FROM users
            LEFT JOIN user_verification ON users.id = user_verification.user_id
            WHERE users.id = :user_id
            LIMIT 1
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $user = $stmt->fetch();

        if ($user) {
            $username = $user['name'];
            $userRole = $user['role'];

            // Correct path handling
            $profilePic = '/owner/includes/user.png'; // Default image
            if (!empty($user['profile_picture'])) {
                $correctedPath = (strpos($user['profile_picture'], '/') === 0)
                    ? $user['profile_picture']
                    : '/' . $user['profile_picture'];

                // Check file existence using document root
                $fullPath = $_SERVER['DOCUMENT_ROOT'] . $correctedPath;
                if (file_exists($fullPath)) {
                    $profilePic = $correctedPath;
                }
            }
        } else {
            // Handle invalid user session
            $username = 'Guest';
            $userRole = 'renter';
            $profilePic = '/owner/includes/user.png';
        }
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $username = 'Guest';
        $userRole = 'renter';
        $profilePic = '/owner/includes/user.png';
    }
} else {
    $username = 'Guest';
    $userRole = 'renter';
    $profilePic = '/owner/includes/user.png';
}
// Handle the "Become an Owner" button click
if (isset($_POST['become_owner'])) {
    try {
        // Check if the user's verification status is 'verified'
        $query = "
            SELECT verification_status 
            FROM user_verification 
            WHERE user_id = :user_id
            LIMIT 1
        ";
        $stmt = $conn->prepare($query);
        $stmt->execute(['user_id' => $userId]);
        $verification = $stmt->fetch();

        // If verified, update the role to 'owner' and redirect to dashboard
        if ($verification && $verification['verification_status'] === 'verified') {
            $updateQuery = "
                UPDATE users 
                SET role = 'owner' 
                WHERE id = :user_id
            ";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute(['user_id' => $userId]);

            $_SESSION['role'] = 'owner';
            header('Location: ../owner/dashboard.php');
            exit;
        } else {
            echo "<script>alert('Your verification is pending. Please complete the verification process to become an owner.');</script>";
        }
    } catch (PDOException $e) {
        error_log("Database Error in becoming owner: " . $e->getMessage());
        echo "<script>alert('An error occurred. Please try again later.');</script>";
    }
}
?>
<!-- HTML for navbar with username dynamically displayed 
<nav class="navbar navbar-expand-lg mx-5 bg-body rounded-bottom-5 d-flex mb-5 py-3 shadow">
    <div class="container-fluid d-flex justify-content-between mx-5">
        <a href="browse.php" class="naxbar-brand me-auto">
            <img class="my-4 me-5" src="../images/rb logo text colored.png" alt="Logo" height="50px">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarToggler" aria-controls="navbarToggler" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-three-dots"></i>
        </button>
        <div class="collapse navbar-collapse gap-3" id="navbarToggler">
            <ul class="navbar-nav me-auto mb-lg-0 container-fluid d-flex justify-content-between align-items-center">
                <li class="nav-item mx-auto">
                    <div class="d-flex gap-4 align-items-center justify-content-center align-items-center mb-2">
                        <a href="browse.php" class="fs-5 text-decoration-none fw-bold active d-none d-sm-block">Browse</a>
                        <a href="#" class="secondary fs-5 text-decoration-none fw-bold d-none d-sm-block" id="toggleRoleButton" data-bs-toggle="modal" data-bs-target="#becomeOwnerModal">Become an Owner</a>
                    </div>
                </li>
                <li class="nav-item">
                    <div class="d-flex align-items-center gap-3">
                        <a href="../renter/cart.php">
                            <button type="button" class="success btn btn-outline-success rounded-circle">
                                <i class="bi bi-basket3 fs-5"></i>
                            </button>
                        </a>



                        
                    </div>
                </li>
            </ul>
        </div>
    </div>


</nav>-->
<nav class="navbar navbar-expand-lg bg-body rounded-bottom-5 shadow mx-3 mx-md-5 mx-md-lg mb-5 px-3 p-md-4 p-lg-4">
    <div class="container-fluid">
        <a class="navbar-brand active fw-bolder fs-4 fs-sm-6" href="browse.php">
            <img class="" src="../images/rb logo colored.png" alt="Logo" height="50px">
            Rentbox</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="bi bi-three-dots"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
            <div class="navbar-nav ms-auto">
                <a class="nav-link link-success my-auto mx-auto fw-bold" href="" id="toggleRoleButton" data-bs-toggle="modal" data-bs-target="#becomeOwnerModal">Become an Owner</a>
                <!-- <a class="nav-link my-auto" href="">Sign Up</a> -->
                <!-- <a class="nav-link my-auto" href="">Log In</a> -->
                <div class="nav-link d-flex mx-auto">
                    <a class="me-5 me-md-3" href="../renter/cart.php">
                        <button type="button" class="success btn btn-outline-success rounded-circle">
                            <i class="bi bi-basket3 fs-5"></i>
                        </button>
                    </a>
                    
                    <!-- IF LOGGED IN   -->
                    <div class="dropstart my-auto">
                        <button type="button" class="success btn btn-outline-success rounded-circle m-0 p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <img src="<?= htmlspecialchars($profilePic) ?>" class="object-fit-cover border rounded-circle" alt="pfp" style="width:50px; height: 50px;">
                        </button>
                        <ul class="dropdown-menu rounded-4">
                            <li>
                                <p class="dropdown-item-text fw-bold m-0"><?= htmlspecialchars($username) ?></p>
                            </li>
                            <hr class="m-0 p-0">
                            <li class="my-1"><a class="dropdown-item" href="profile.php"><i class="bi bi-gear-fill me-2"></i>Profile</a></li>
                            <li class="my-1"><a class="dropdown-item" href="rentals.php"><i class="bi bi-box2-heart-fill me-2"></i>Rentals</a></li>
                            <hr class="m-0 p-0">
                            <li class="my-1"><a class="dropdown-item" href="supports.php"><i class="bi bi-headset me-2"></i>Supports</a></li>
                            <li class="my-1"><a class="dropdown-item" href="file_dispute.php"><i class="bi bi-file-earmark-x-fill me-2"></i>File Dispute</a></li>
                            <hr class="m-0 p-0">
                            <li class="my-1"><a class="dropdown-item" href="../includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Log out</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Modal for confirmation of switching to Owner mode -->
<div class="modal fade" id="becomeOwnerModal" tabindex="-1" aria-labelledby="becomeOwnerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="becomeOwnerModalLabel">Become an Owner</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to switch to Owner mode?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="post" action="">
                    <button type="submit" name="become_owner" class="btn btn-primary">Switch to Owner</button>
                </form>
            </div>
        </div>
    </div>
</div>