<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Rentbox</title>
    <link rel="icon" type="image/png" href="images\rb logo white.png">
    <link href="vendor/bootstrap-5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="vendor/font/bootstrap-icons.css">
    <link rel="stylesheet" href="other.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg bg-body sticky-top pt-3 pb-3 shadow-sm d-none d-md-block d-lg-block" aria-label="navbar" id="">
        <div class="container-fluid d-flex justify-content-between">
            <a href="browse.php">
                <img class="ms-5 my-auto mt-1" src="images\rb logo text colored.png" alt="Logo" height="50px">
            </a>
            <!-- links -->
            <div class="me-5 p-1 d-flex align-items-center">
                <?php
                // Check if the user is logged in
                if (!isset($_SESSION['user_id'])):
                ?>
                    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="login.php?redirect=start_renting">Start Renting</a>
                    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="login.php?redirect=become_owner">Become an Owner</a>
                <?php else: ?>
                    <!-- If logged in, show actual links -->
                    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="renter/browse.php">Start Renting</a>
                    <a class="fw-medium ms-auto me-4 link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-100-hover" href="landing.owner.php">Become an Owner</a>
                <?php endif; ?>

                <?php if (!isset($_SESSION['id'])): ?>
                    <div>
                        <a type="button" class="btn btn-success2 rounded-4 px-4 py-2 shadow-sm" href="signup.php">Sign Up</a>
                    </div>
                <?php else: ?>
                    <div>
                        <a href="login.php" class="gradient btn rounded-4 px-4 py-2 shadow-sm" href="login.php">Log In</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container-fluid m-0 p-0">
        <!-- mobile version -->
        <div class="gradient-success">
            <div class="d-flex flex-column d-md-none d-lg-none vh-100 m-0 p-4">
                <img src="images/rb logo white.png" alt="logo" style="width: 50px; height: 50px;" class="ms-3 mt-2 mb-2">
                <img src="images/landing.images/hero.png" style="width: 300px; height: 300px;" class="ms-3 mt-auto mb-2">
                <div class="mt-2">
                    <h1 class="display-1 fw-bold  mt-5 pb-3 text-white">Your Gadgets,<br> Your Way</h1>
                    <p class="lead text-white">
                        Join the Rentbox community. Earn extra income or access the latest gadgets – it's a win-win.
                    </p>
                    <div class="justify-content-md-start mb-4 mb-lg-3 mt-4">
                        <a type="button" class="btn btn-success2 btn-md px-4 me-2 rounded-4" href="signup.php">Start Renting</a>
                        <button type="button" class="btn btn-success3 btn-md px-4 rounded-4">List Your Gadget</button>
                    </div>
                </div>
            </div>

            <div class="container-fluid d-flex flex-column d-md-none d-lg-none p-4">
                <h3 class="fw-bold mb-3">Rent Gadgets</h3>
                <p>Experience the latest technology without the long-term commitment. Rent the gadgets you need, when you need them.</p>

                <div class="d-flex align-items-center">
                    <i class="bi bi-tags-fill fs-1 text-success me-2"></i>
                    <h5 class="fw-bold">Know the price upfront</h5>
                </div>
                <p>Our transparent pricing means you know exactly how much you'll pay.</p>
                <div class="d-flex align-items-center">
                    <i class="bi bi-credit-card-2-front-fill fs-1 text-success me-2"></i>
                    <h5 class="fw-bold">Payment protection, guaranteed</h5>
                </div>
                <p>Your money is held safely until the rental period is completed.</p>
                <div class="d-flex align-items-center">
                    <i class="bi bi-headset fs-1 text-success me-2"></i>
                    <h5 class="fw-bold">We’re here for you</h5>
                </div>
                <p>Rentbox Team is here for you, anything from answering any questions to resolving any issues anytime.</p>

                <div class="d-flex align-items-center">
                    <i class="bi bi-shield-fill-check fs-1 text-success me-2"></i>
                    <h5 class="fw-bold">You’re safe with us</h5>
                </div>
                <p class="m-0 p-0">Rentbox is designed to protect you throughout the rental process.
                    With all discussions taking place on our platform, we secure your payments,
                    and your information remains confidential at all times.
                    <small class="m-0 p-0"><a href="" class="link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-10-hover">Learn more about security</a></small>
                </p>
            </div>
        </div>
        <!--desktop ver-->
        <div class="gradient-success">
            <div class="row container-fluid d-none d-md-flex d-lg-flex p-4 align-items-center">
                <div class="col-md-6 col-lg-6 ms-5 p-lg-5 pt-lg-3 m-0">
                    <h1 class="display-3 fw-bold pb-3 text-white">Your Gadgets,<br> Your Way</h1>
                    <p class="lead text-white">
                        Join the Rentbox community. Earn extra income or access the latest gadgets – it's a win-win.
                    </p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start mb-4 mb-lg-3 mt-4">
                        <a type="button" class="btn btn-success2 btn-md px-4 me-md-2 rounded-pill shadow" href="signup.php">Start Renting</a>
                        <button type="button" class="btn btn-success3 btn-md px-4 me-md-2 rounded-pill shadow">List Your Gadget</button>
                    </div>
                </div>
                <div class="col-lg-4 m-0 p-0">
                    <img class="rounded-lg-3" src="images/landing.images/hero.png" alt="image" width="500px">
                </div>
            </div>

            <div class="container-fluid d-none d-md-block d-lg-block ">
                <div class="row m-0 pb-5 px-4">
                    <div class="col-12">
                        <h3 class="fw-bold mb-3 text-center">Rent Gadgets</h3>
                        <p class="text-center">Experience the latest technology without the long-term commitment. <br> Rent the gadgets you need, when you need them.</p>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-tags-fill fs-1 text-success me-2"></i>
                            <h5 class="fw-bold">Know the price upfront</h5>
                        </div>
                        <p>Our transparent pricing means you know exactly how much you'll pay.</p>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-credit-card-2-front-fill fs-1 text-success me-2"></i>
                            <h5 class="fw-bold">Payment propaymeection, guaranteed</h5>
                        </div>
                        <p>Your money is held safely until the rental period is completed.</p>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-headset fs-1 text-success me-2"></i>
                            <h5 class="fw-bold">We’re here for you</h5>
                        </div>
                        <p>Rentbox Team is here for you, anything from answering any questions to resolving any issues anytime.</p>
                    </div>
                    <div class="col-6">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-shield-fill-check fs-1 text-success me-2"></i>
                            <h5 class="fw-bold">You’re safe with us</h5>
                        </div>
                        <p class="m-0 p-0">Rentbox is designed to protect you throughout the rental process.
                            With all discussions taking place on our platform, we secure your payments,
                            and your information remains confidential at all times.
                        </p>
                        <small class="m-0 p-0"><a href="" class="link-success link-offset-2 link-underline-opacity-0 link-underline-opacity-10-hover">Learn more about security</a></small>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>


</body>
<script src="vendor\bootstrap-5.3.3\dist\js\bootstrap.bundle.min.js"></script>
<script>

</script>

</html>