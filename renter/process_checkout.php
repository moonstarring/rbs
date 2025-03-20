<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../db/db.php';

function log_error($message) {
    $logFile = '../logs/error_log.txt';
    $formattedMessage = "[" . date('Y-m-d H:i:s') . "] " . $message . "\n";
    file_put_contents($logFile, $formattedMessage, FILE_APPEND);
}

if (!isset($_SESSION['id'])) {
    header('Location: ../renter/login.php');
    exit();
}

$userId = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF Token Validation
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            throw new Exception("Invalid CSRF token");
        }

        $conn->beginTransaction();
        $isDirectCheckout = isset($_POST['direct_checkout']);

        // Direct Checkout Flow
        if ($isDirectCheckout) {
            // Validate direct checkout parameters
            if (empty($_POST['product_id']) || empty($_POST['start_date']) || empty($_POST['end_date'])) {
                throw new Exception("Missing required fields for direct checkout.");
            }

            $productId = (int)$_POST['product_id'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            // Validate product existence
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();

            if (!$product) throw new Exception("Product not found");
            if ($product['quantity'] < 1) throw new Exception("Product out of stock");

            // Calculate rental period
            $start = new DateTime($startDate);
            $end = new DateTime($endDate);
            $interval = $start->diff($end);
            $days = $interval->days + 1;

            switch (strtolower($product['rental_period'])) {
                case 'day': $periods = $days; break;
                case 'week': $periods = ceil($days / 7); break;
                case 'month': $periods = ceil($days / 30); break;
                default: $periods = 1;
            }

            $totalCost = $product['rental_price'] * $periods;

            // Create rental record
            $stmt = $conn->prepare("INSERT INTO rentals (
                product_id, renter_id, owner_id, start_date, end_date,
                rental_price, total_cost, payment_method, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending_confirmation')");
            
            $stmt->execute([
                $productId,
                $userId,
                $product['owner_id'],
                $startDate,
                $endDate,
                $product['rental_price'],
                $totalCost,
                'cod'
            ]);
        }
        // Cart Checkout Flow
        else {
            // Get cart items with product details
            $stmt = $conn->prepare("
                SELECT c.*, p.owner_id, p.rental_price, p.rental_period
                FROM cart_items c
                INNER JOIN products p ON c.product_id = p.id
                WHERE c.renter_id = :userId
            ");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $cartItems = $stmt->fetchAll();

            if (empty($cartItems)) {
                throw new Exception("Your cart is empty.");
            }

            foreach ($cartItems as $item) {
                // Validate cart item dates
                if (empty($item['start_date']) || empty($item['end_date'])) {
                    throw new Exception("Missing dates for product ID: " . $item['product_id']);
                }

                // Calculate rental period
                $start = new DateTime($item['start_date']);
                $end = new DateTime($item['end_date']);
                $interval = $start->diff($end);
                $days = $interval->days + 1;

                switch (strtolower($item['rental_period'])) {
                    case 'day': $periods = $days; break;
                    case 'week': $periods = ceil($days / 7); break;
                    case 'month': $periods = ceil($days / 30); break;
                    default: $periods = 1;
                }

                $totalCost = $item['rental_price'] * $periods;

                // Create rental record
                $stmt = $conn->prepare("
                    INSERT INTO rentals (
                        product_id, renter_id, owner_id, start_date, end_date,
                        rental_price, total_cost, status, payment_method, created_at, updated_at
                    ) VALUES (
                        :product_id, :renter_id, :owner_id, :start_date, :end_date,
                        :rental_price, :total_cost, 'pending_confirmation', 'cod', NOW(), NOW()
                    )
                ");
                
                $stmt->execute([
                    ':product_id' => $item['product_id'],
                    ':renter_id' => $userId,
                    ':owner_id' => $item['owner_id'],
                    ':start_date' => $item['start_date'],
                    ':end_date' => $item['end_date'],
                    ':rental_price' => $item['rental_price'],
                    ':total_cost' => $totalCost
                ]);
            }

            // Clear cart after successful processing
            $stmt = $conn->prepare("DELETE FROM cart_items WHERE renter_id = :userId");
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
        }

        $conn->commit();
        $_SESSION['success_message'] = "Checkout successful! Awaiting owner approval.";
        header('Location: checkout_success.php');
        exit();

    } catch (Exception $e) {
        $conn->rollBack();
        $_SESSION['error_message'] = "Checkout failed: " . $e->getMessage();
        log_error("Checkout failed for user ID: $userId - " . $e->getMessage());
        header('Location: checkout.php');
        exit();
    }
} else {
    header('Location: checkout.php');
    exit();
}
?>