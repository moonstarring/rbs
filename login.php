<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'db/db.php';

$email = '';
$password = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $errorMessage = 'Please enter a valid email address.';
    } else {
        $sql = "SELECT users.*, user_verification.mobile_number 
                FROM users 
                LEFT JOIN user_verification ON users.id = user_verification.user_id 
                WHERE email = :email LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] === 'renter') {
                $sql2 = "SELECT verification_status FROM user_verification WHERE user_id = :user_id LIMIT 1";
                $stmt2 = $conn->prepare($sql2);
                $stmt2->bindValue(':user_id', $user['id']);
                $stmt2->execute();
                $verification = $stmt2->fetch();
            }

            if ($user && password_verify($password, $user['password'])) {
                // Remove the renter-specific verification check since staff doesn't need it
                $_SESSION['id'] = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['phone'] = $user['mobile_number'] ?? 'N/A';
                $_SESSION['role'] = $user['role'];
                $_SESSION['admin_id'] = $user['id']; // Consider renaming this to staff_id if needed
                $_SESSION['username'] = isset($user['username']) ? $user['username'] : $user['name'];
                session_regenerate_id(true);

                switch ($user['role']) {
                    case 'admin':
                        header('Location: admin/dashboard.php');
                        break;
                    case 'owner':
                        header('Location: owner/dashboard.php');
                        break;
                    case 'renter':
                        header('Location: renter/browse.php');
                        break;
                    case 'staff':  // Add this new case
                        header('Location: staff/staff_dashboard.php');
                        break;
                    default:
                        header('Location: logout.php');
                        break;
                }
                exit();
            }
        } else {
            $errorMessage = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox</title>
    <link rel="icon" type="image/png" href="images/rb logo white.png">
    <link href="vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="vendor/font/bootstrap-icons.css">
</head>

<body class="bg-dark-subtle">
    <main class="container-fluid m-0 p-0">
        <div class="container-fluid d-flex justify-content-center py-5" style="height: 100vh;">
            <div class="card rounded-5 shadow" style="width:500px;">
                <div class="card-body d-flex flex-column justify-content-center">
                    <a href="landing.php" class="text-center">
                        <img class="my-4" src="images/rb logo text colored.png" alt="Logo" height="50px">
                    </a>
                    <h5 class="text-center mt-4 mb-4 fw-bold">Login</h5>

                    <!-- Display error message if credentials are incorrect or account pending -->
                    <?php if ($errorMessage): ?>
                        <div class="alert alert-sm alert-danger text-center py-2 px-auto"><small><?php echo htmlspecialchars($errorMessage); ?></small></div>
                    <?php endif; ?>

                    <form method="POST" action="" class="mx-5">
                        <div class="form-floating mb-3" style="font-size: 14px;">
                            <input type="email" name="email" class="form-control ps-4 rounded-5" id="floatingInput" placeholder="Email" required value="<?= htmlspecialchars($email) ?>">
                            <label for="floatingInput" class="ps-4">Email</label>
                        </div>

                        <div class="form-floating mb-3" style="font-size: 14px;">
                            <input type="password" name="password" class="form-control ps-4 rounded-5" id="floatingPassword" placeholder="Password" required>
                            <label for="floatingPassword" class="ps-4">Password</label>
                        </div>

                        <div class="d-flex mb-4 justify-content-between">
                            <a class="link-secondary link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-50-hover" href="signup.php" style="font-size: 12px;">Create an account</a>
                            <a class="link-secondary link-offset-2 link-offset-3-hover link-underline link-underline-opacity-0 link-underline-opacity-50-hover" href="forgot-password.php" style="font-size: 12px;">Forgot Password?</a>
                        </div>

                        <div class="d-flex justify-content-center mb-4">
                            <button type="submit" class="btn btn-success rounded-5 shadow w-50">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <footer class="px-3 bg-body d-none d-md-block">
        <div class="d-flex flex-column flex-sm-row justify-content-between py-2 border-top">
            <p>© 2024 Rentbox. All rights reserved.</p>
            <ul class="list-unstyled d-flex">
                <li class="ms-3"><a href="#"><i class="bi bi-facebook text-body"></i></a></li>
                <li class="ms-3"><a href="#"><i class="bi bi-twitter-x text-body"></i></a></li>
                <li class="ms-3"><a href="#"><i class="bi bi-linkedin text-body"></i></a></li>
            </ul>
        </div>
    </footer>

    <script src="vendor/bootstrap-5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>