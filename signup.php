<?php
session_start();
require_once 'db/db.php'; // This file must return a PDO instance in $conn

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  // Retrieve and validate form inputs
  $email      = trim($_POST['email']);
  $first_name = trim($_POST['first_name']);
  $last_name  = trim($_POST['last_name']);
  $password   = $_POST['password'];
  $confirm    = $_POST['confirm_password'];

  if (!$email) {
    $error = "Please enter a valid email address.";
  } elseif ($password !== $confirm) {
    $error = "Passwords do not match.";
  } else {
    // Check if the email already exists in the users table.
    $sqlCheck = "SELECT COUNT(*) AS cnt FROM users WHERE email = :email";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->execute([':email' => $email]);
    $result = $stmtCheck->fetch();

    if ($result && $result['cnt'] > 0) {
      $error = "An account with this email already exists. <a href='login.php'>Log in to your account</a>.";
    } else {
      // Hash the password and combine names.
      $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
      $fullName = $first_name . ' ' . $last_name;

      // Insert new user into users table.
      // Note: email_verified_at remains NULL, so the account is pending.
      $sql = "INSERT INTO users (name, email, password, role, created_at, updated_at) 
                    VALUES (:name, :email, :password, 'renter', NOW(), NOW())";
      $stmt = $conn->prepare($sql);
      $params = [
        ':name'     => $fullName,
        ':email'    => $email,
        ':password' => $hashedPassword
      ];

      if ($stmt->execute($params)) {
        $user_id = $conn->lastInsertId();
        $_SESSION['user_id'] = $user_id;

        // Insert a pending record in user_verification.
        $sqlVer = "INSERT INTO user_verification (user_id, mobile_number, otp, verification_status) 
                           VALUES (:user_id, '', '', 'pending')";
        $stmtVer = $conn->prepare($sqlVer);
        $stmtVer->execute([':user_id' => $user_id]);

        // Redirect to the first verification step (e.g., addnumber.php)
        header("Location: addnumber.php");
        exit;
      } else {
        $errorInfo = $stmt->errorInfo();
        $error = "Error creating account: " . $errorInfo[2];
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <link rel="icon" type="image/png" href="images/rb logo white.png">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign Up - Rentbox</title>
  <link href="vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="vendor/font/bootstrap-icons.css">
</head>

<body>
  <div class="container-fluid p-0 m-0 bg-dark-subtle">
    <div class="row d-flex justify-content-center align-items-center m-0 p-lg-5 vh-100">
      <div class="col-md-7 col-lg-5 col-sm-10 col-xs-10">
        <div class="card rounded-5 shadow p-4">
          <div class="card-body d-flex flex-column justify-content-center">
            <a href="landing.php" class="text-center">
              <img class="my-4" src="images/rb logo text colored.png" alt="Logo" height="50px">
            </a>
            <h5 class="text-center mt-4 mb-2 fw-bold">Sign Up</h5>
            <h6 class="text-center mb-4">Welcome!</h6>

          </div><?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
          <?php endif; ?>
          <form action="signup.php" method="POST">
            <div class="form-floating mb-3">
              <input type="email" name="email" class="form-control" placeholder="Email" required>
              <label>Email</label>
            </div>
            <div class="row mb-3">
              <div class="col">
                <div class="form-floating">
                  <input type="text" name="first_name" class="form-control" placeholder="First Name" required>
                  <label>First Name</label>
                </div>
              </div>
              <div class="col">
                <div class="form-floating">
                  <input type="text" name="last_name" class="form-control" placeholder="Last Name" required>
                  <label>Last Name</label>
                </div>
              </div>
            </div>
            <div class="form-floating mb-3">
              <input type="password" name="password" class="form-control" placeholder="Password" required>
              <label>Password</label>
            </div>
            <div class="form-floating mb-3">
              <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
              <label>Confirm Password</label>
            </div>
            <div class="text-center mb-3" >
              <p>Have an Account? <a href="login.php">Log in</a></p>
            </div>
            <div class="d-flex justify-content-center mb-4">
              <button type="submit" class="btn btn-success d-flex">Create Account</button>
            </div>
            <p class="mt-3 text-center" style="font-size: 12px;">
              Signing up for a Rentbox account means you agree to the <a href="#" class="text-secondary">Privacy Policy</a> and <a href="#" class="text-secondary">Terms of Service</a>
            </p>
          </form>
        </div>
      </div>
    </div>
  </div>

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