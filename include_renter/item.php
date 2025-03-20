<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../db/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 
              (isset($_GET['id']) ? (int)$_GET['id'] : die("Product ID not provided."));

if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Updated comment query with concatenated name
    $query = "SELECT c.*, 
              CONCAT_WS(' ', u.first_name, u.last_name) AS name, 
              u.profile_picture 
              FROM comments c 
              JOIN users u ON c.renter_id = u.id 
              WHERE c.product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    $comments = $stmt->fetchAll();

    $query = "SELECT name, description, category, quantity, condition_description, 
              rental_price, rental_period, image, owner_id 
              FROM products 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();

    if ($product) {
        $product_description = $product['description'];
        $category = $product['category'];
        $quantity = $product['quantity'];
        $condition_description = $product['condition_description'];
        $rental_price = $product['rental_price'];
        $rental_period = $product['rental_period'];
        $image = $product['image'];
        $owner_id = $product['owner_id'];
    } else {
        echo "Product not found.";
        exit();
    }

    // Updated owner name query
    $query = "SELECT CONCAT_WS(' ', first_name, last_name) AS name 
              FROM users 
              WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $owner_name = $stmt->fetchColumn();

    // Rest of the code remains the same...
    $query = "SELECT verification_status FROM user_verification WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $active_status = $stmt->fetchColumn();

    $query = "SELECT SUM(rating) AS total_ratings 
              FROM renter_reviews 
              WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $total_ratings = $stmt->fetchColumn();

    $query = "SELECT created_at FROM user_verification WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $owner_data = $stmt->fetch();

    if ($owner_data && isset($owner_data['created_at'])) {
        $join_date = new DateTime($owner_data['created_at']);
    } else {
        echo "Owner join date is not available.";
        exit();
    }

    $current_date = new DateTime();
    $interval = $join_date->diff($current_date);

    $joined_duration = "";
    if ($interval->y > 0) {
        $joined_duration .= $interval->y . " year" . ($interval->y > 1 ? "s" : "") . " ";
    }
    if ($interval->m > 0) {
        $joined_duration .= $interval->m . " month" . ($interval->m > 1 ? "s" : "") . " ";
    }
    $joined_duration .= $interval->d . " day" . ($interval->d > 1 ? "s" : "");

    $query = "SELECT COUNT(*) FROM rentals WHERE owner_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$owner_id]);
    $rental_count = $stmt->fetchColumn();

    $availabilityClass = $quantity > 0 ? 'bg-success-subtle' : 'bg-danger-subtle';
    $availabilityText = $quantity > 0 ? 'Available' : 'Unavailable';

    $images = explode(',', $image);

    $total_ratings = 0;
    $rating_count = count($comments);
    foreach ($comments as $comment) {
        $total_ratings += $comment['rating'];
    }

    $average_rating = $rating_count > 0 ? round($total_ratings / $rating_count, 1) : 0;

    $selected_rating = isset($_GET['rating']) ? $_GET['rating'] : 'all';
    if ($selected_rating != 'all') {
        $comments = array_filter($comments, function($comment) use ($selected_rating) {
            return $comment['rating'] == $selected_rating;
        });
    }
} else {
    echo "Product ID not provided.";
    exit();
}
?>