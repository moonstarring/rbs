<?php
session_start();
require_once __DIR__ . '/../db/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    try {
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $userId = $_SESSION['id'];
        $productId = filter_input(INPUT_POST, 'product_id', FILTER_SANITIZE_NUMBER_INT);
        $owner_id = isset($_POST['owner_id']) ? (int)$_POST['owner_id'] : 0;
        $search = $_POST['search'] ?? '';
        $page = $_POST['page'] ?? 1;

        // Check product availability
        $productCheck = $conn->prepare("
            SELECT id, quantity 
            FROM products 
            WHERE id = :productId 
            AND status = 'available'
            AND quantity > 0
        ");
        $productCheck->execute([':productId' => $productId]);
        
        if ($productCheck->rowCount() === 0) {
            throw new Exception("Product not available");
        }

        // Check if item is already in cart
        $cartCheck = $conn->prepare("
            SELECT id 
            FROM cart_items 
            WHERE renter_id = :userId 
            AND product_id = :productId
        ");
        $cartCheck->execute([':userId' => $userId, ':productId' => $productId]);

        if ($cartCheck->rowCount() > 0) {
            throw new Exception("Item already in cart.");
        }

        // Add to cart
        $insert = $conn->prepare("
            INSERT INTO cart_items (renter_id, product_id, created_at, updated_at)
            VALUES (:userId, :productId, NOW(), NOW())
        ");
        $insert->execute([':userId' => $userId, ':productId' => $productId]);

        // Determine redirect URL
        $redirectUrl = $owner_id 
            ? "review.php?owner_id=$owner_id&search=" . urlencode($search) 
            : "browse.php?search=" . urlencode($search) . "&page=$page";

        header("Location: $redirectUrl&success=1");
        exit();

    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        $redirectUrl = $owner_id 
            ? "review.php?owner_id=$owner_id&search=" . urlencode($search) 
            : "browse.php?search=" . urlencode($search) . "&page=$page";
        header("Location: $redirectUrl&error=1");
        exit();
    } catch (Exception $e) {
        error_log("Error: " . $e->getMessage());
        $redirectUrl = $owner_id 
            ? "review.php?owner_id=$owner_id&search=" . urlencode($search) 
            : "browse.php?search=" . urlencode($search) . "&page=$page";
        header("Location: $redirectUrl&error=1");
        exit();
    }
}

header('Location: browse.php');
exit();
?>