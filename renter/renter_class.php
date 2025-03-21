<?php
require_once __DIR__ . '/../db/db.php';
class renter {
    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public function verifyCsrfToken($token) {
        if (empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }


    //Database Connection
    private $conn;
    private $userId;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Authentication check to ensure user is logged in and is an owner
    public function authenticateRenter() {
        if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'renter') {
            header("Location: /rb/login.php");
            exit();
        }
        $this->userId = $_SESSION['id'];
    }



    public function logError($message) {
        $log_file = __DIR__ . '/error_log.txt';
        $current_time = date('Y-m-d H:i:s');
        $formatted_message = "[{$current_time}] {$message}\n";
        file_put_contents($log_file, $formatted_message, FILE_APPEND);
    }





    //Browse Page
    // Search and paginate products excluding 'pending_confirmation' status
    public function searchProducts(
        string $searchTerm = '',
        int $perPage = 8,
        int $page = 1,
        bool $excludeOwnProducts = false,
        int $currentUserId = null
    ) {
        // Debug info
        error_log("searchProducts called with: searchTerm=$searchTerm, excludeOwnProducts=$excludeOwnProducts, currentUserId=$currentUserId");
        
        $offset = ($page - 1) * $perPage;
        
        // Start with a basic query
        $sql = "
            SELECT 
                p.*,
                COALESCE((
                    SELECT AVG(r.rating) 
                    FROM reviews r 
                    JOIN rentals rl ON r.rental_id = rl.id 
                    WHERE rl.product_id = p.id
                ), 0) AS average_rating,
                COALESCE((
                    SELECT COUNT(r.id) 
                    FROM reviews r 
                    JOIN rentals rl ON r.rental_id = rl.id 
                    WHERE rl.product_id = p.id
                ), 0) AS rating_count
            FROM products p
            WHERE (p.name LIKE :search OR p.description LIKE :search)
            AND p.status IN ('available', 'rented')
        ";
        
        $countSql = "
            SELECT COUNT(*) 
            FROM products p
            WHERE (p.name LIKE :search OR p.description LIKE :search)
            AND p.status IN ('available', 'rented')
        ";
    
        // Add the owner exclusion condition
        if ($excludeOwnProducts && $currentUserId !== null) {
            $sql .= " AND p.owner_id != :owner_id";
            $countSql .= " AND p.owner_id != :owner_id";
            error_log("Added exclusion condition for owner_id=$currentUserId");
        }
        
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
            
            if ($excludeOwnProducts && $currentUserId !== null) {
                $stmt->bindValue(':owner_id', $currentUserId, PDO::PARAM_INT);
            }
            
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            // Log the final SQL with parameters
            error_log("Final SQL: " . $sql);
            error_log("Parameters: search=" . '%' . $searchTerm . '%' . ", limit=$perPage, offset=$offset" . 
                     ($excludeOwnProducts && $currentUserId !== null ? ", owner_id=$currentUserId" : ""));
            
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Count query
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->bindValue(':search', '%' . $searchTerm . '%', PDO::PARAM_STR);
            
            if ($excludeOwnProducts && $currentUserId !== null) {
                $countStmt->bindValue(':owner_id', $currentUserId, PDO::PARAM_INT);
            }
            
            $countStmt->execute();
            $totalProducts = $countStmt->fetchColumn();
            
            // Format the results
            $formatted = array_map(function($product) {
                return [
                    'id' => $product['id'],
                    'owner_id' => $product['owner_id'],
                    'name' => htmlspecialchars($product['name']),
                    'description' => htmlspecialchars($product['description']),
                    'rental_price' => number_format($product['rental_price'], 2),
                    'image' => $product['image'],
                    'average_rating' => round($product['average_rating'], 1),
                    'rating_count' => (int)$product['rating_count']
                ];
            }, $products);
            
            error_log("Found " . count($formatted) . " products, total pages: " . ceil($totalProducts / $perPage));
            
            return [
                'products' => $formatted,
                'totalPages' => ceil($totalProducts / $perPage)
            ];
            
        } catch (PDOException $e) {
            error_log("Search error: " . $e->getMessage());
            return false;
        }
    }





    //File Dispute Page
    // Fetch user's rentals for dispute selection
public function getRentalsForDispute($userId) {
    $stmt = $this->conn->prepare("SELECT r.id, p.name 
                                  FROM rentals r 
                                  JOIN products p ON r.product_id = p.id 
                                  WHERE r.renter_id = :userId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// File a dispute
public function fileDispute($userId, $rental_id, $reason, $description) {
    $stmt = $this->conn->prepare("INSERT INTO disputes (rental_id, initiated_by, reason, description) 
                                  VALUES (:rental_id, :initiated_by, :reason, :description)");
    $stmt->bindParam(':rental_id', $rental_id, PDO::PARAM_INT);
    $stmt->bindParam(':initiated_by', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':reason', $reason, PDO::PARAM_STR);
    $stmt->bindParam(':description', $description, PDO::PARAM_STR);
    return $stmt->execute();
}



//Item Page
// Method to fetch product by ID
public function getProductById($productId) {
    $stmt = $this->conn->prepare("SELECT * FROM products WHERE id = ? AND status IN ('available', 'rented')");
    $stmt->execute([$productId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Method to add item to cart
public function addToCart($userId, $productId) {
    // Check product availability
    $product = $this->getProductById($productId);
    if (!$product || $product['quantity'] < 1) {
        return "Product is currently unavailable.";
    }

    // Check if item is already in cart
    $stmt = $this->conn->prepare("SELECT * FROM cart_items WHERE renter_id = :userId AND product_id = :productId");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
    $stmt->execute();
    if ($stmt->fetch()) {
        return "Item is already in your cart.";
    }

    // Add item to cart
    $stmt = $this->conn->prepare("INSERT INTO cart_items (renter_id, product_id, created_at, updated_at) VALUES (:userId, :productId, NOW(), NOW())");
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->bindParam(':productId', $productId, PDO::PARAM_INT);
    if ($stmt->execute()) {
        return "Item added to cart successfully.";
    } else {
        return "Failed to add item to cart.";
    }
}

public function getCartItems($userId) {
    $sql = "SELECT c.*, p.name, p.image, p.rental_price, p.category, p.status, p.description, p.quantity
            FROM cart_items c
            INNER JOIN products p ON c.product_id = p.id
            WHERE c.renter_id = :userId";
    $stmt = $this->conn->prepare($sql);
    $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
    $stmt->execute();
    $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = 0;
    $allAvailable = true;
    foreach ($cartItems as $item) {
        if ($item['quantity'] < 1) {
            $allAvailable = false;
        }
        $subtotal += $item['rental_price'];
    }

    return ['cartItems' => $cartItems, 'subtotal' => $subtotal, 'allAvailable' => $allAvailable];
}



//Profile Page
// Add these methods to your existing Renter class

public function switchRole() {
    try {
        $sql = "SELECT role FROM users WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute(['user_id' => $_SESSION['id']]);
        $currentRole = $stmt->fetchColumn();

        $newRole = ($currentRole === 'renter') ? 'owner' : 'renter';

        $updateSql = "UPDATE users SET role = :new_role WHERE id = :user_id";
        $updateStmt = $this->conn->prepare($updateSql);
        $updateStmt->execute([
            'new_role' => $newRole,
            'user_id' => $_SESSION['id']
        ]);

        $_SESSION['role'] = $newRole;
        return $newRole;
    } catch (Exception $e) {
        $this->logError("Error switching role: " . $e->getMessage());
        return false;
    }
}

public function getUserData($userId) {
    $sql = "SELECT id, first_name, last_name, email, role, created_at, profile_picture 
            FROM users 
            WHERE id = :user_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    return $stmt->fetch();
}

public function getVerificationData($userId) {
    $stmt = $this->conn->prepare("SELECT * FROM user_verification WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

public function updateProfilePicture($userId, $file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }

    $uploadDir = '../uploads/profile_pictures/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $filename = time() . '_' . basename($file['name']);
    $destination = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $sql = "UPDATE users SET profile_picture = :picture WHERE id = :user_id";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            'picture' => 'uploads/profile_pictures/' . $filename,
            'user_id' => $userId
        ]);
        return 'uploads/profile_pictures/' . $filename;
    }

    return false;
}



//Review Page
// Fetch owner details
public function getOwnerDetails($ownerId) {
    $stmt = $this->conn->prepare("
        SELECT first_name, last_name, profile_picture 
        FROM users 
        WHERE id = ?
    ");
    $stmt->execute([$ownerId]);
    return $stmt->fetch(PDO::FETCH_OBJ); // Return as object
}

// Fetch account creation date from user_verification
public function getOwnerJoinDate($ownerId) {
    $stmt = $this->conn->prepare("SELECT created_at FROM user_verification WHERE user_id = :ownerId");
    $stmt->execute([':ownerId' => $ownerId]);
    $verification = $stmt->fetch();
    return $verification ? $verification['created_at'] : 'Unknown';
}

// Fetch owner's products
public function getOwnerProducts($ownerId) {
    $stmt = $this->conn->prepare("SELECT * FROM products WHERE owner_id = :ownerId");
    $stmt->execute([':ownerId' => $ownerId]);
    return $stmt->fetchAll();
}

// Fetch owner reviews
public function getOwnerReviews($ownerId) {
    $stmt = $this->conn->prepare("SELECT * FROM owner_reviews WHERE owner_id = :ownerId");
    $stmt->execute([':ownerId' => $ownerId]);
    return $stmt->fetchAll();
}

// Fetch average owner rating
public function getOwnerAverageRating($ownerId) {
    $stmt = $this->conn->prepare("SELECT AVG(rating) as avg_rating FROM owner_reviews WHERE owner_id = :ownerId");
    $stmt->execute([':ownerId' => $ownerId]);
    $rating = $stmt->fetch();
    return $rating['avg_rating'] ? round($rating['avg_rating'], 1) : 0;
}

// Fetch reviews with sorting and filtering
public function getReviews($userId, $role, $sort = 'newest', $filter = 'all') {
    $reviewTable = ($role === 'renter') ? 'renter_reviews' : 'owner_reviews';
    $filterColumn = ($role === 'renter') ? 'renter_id' : 'owner_id';

    // Sorting conditions
    $sortOrder = "created_at DESC";
    switch ($sort) {
        case 'oldest':
            $sortOrder = "created_at ASC";
            break;
        case 'highest_rating':
            $sortOrder = "rating DESC";
            break;
        case 'lowest_rating':
            $sortOrder = "rating ASC";
            break;
    }

    // Filtering conditions
    if ($filter === 'buyer') {
        $filterColumn = "owner_id";
    } elseif ($filter === 'seller') {
        $filterColumn = "renter_id";
    }

    $stmt = $this->conn->prepare("SELECT * FROM $reviewTable WHERE $filterColumn = :userId ORDER BY $sortOrder");
    $stmt->execute([':userId' => $userId]);
    return $stmt->fetchAll();
}



//Rentals Page
// In renter_class.php
public function formatRentalPeriod($startDate, $endDate) {
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    
    // For rentals within the same month
    if ($start->format('F Y') === $end->format('F Y')) {
        return $start->format('F j') . ' - ' . $end->format('j, Y');
    }
    // For rentals spanning different months
    return $start->format('M j, Y') . ' to ' . $end->format('M j, Y');
}
public function getRentals($renterId) {
    $stmt = $this->conn->prepare("
        SELECT r.*, 
               p.name AS product_name,
               p.image,
               p.brand,
               CONCAT_WS(' ', u.first_name, u.last_name) AS owner_name
        FROM rentals r
        JOIN products p ON r.product_id = p.id
        JOIN users u ON p.owner_id = u.id
        WHERE r.renter_id = ?
        ORDER BY r.created_at DESC
    ");
    $stmt->execute([$renterId]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rentals as &$rental) {
        if (in_array($rental['status'], ['completed', 'returned'])) {
            $rental['remaining_days'] = 'Completed';
        } elseif ($rental['status'] === 'cancelled') {
            $rental['remaining_days'] = 'Cancelled';
        } elseif (!empty($rental['end_date'])) {
            // Accurate date comparison
            $today = new DateTime('today');
            $endDate = new DateTime($rental['end_date']);
            $endDate->setTime(0, 0, 0);
            
            $interval = $today->diff($endDate);
            $absoluteDays = $interval->days;

            if ($interval->invert) { // Past due
                if ($rental['status'] !== 'overdue' && !in_array($rental['status'], ['completed', 'returned'])) {
                    // Update status to overdue
                    $updateStmt = $this->conn->prepare("
                        UPDATE rentals 
                        SET status = 'overdue', 
                            updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $updateStmt->execute([$rental['id']]);
                    $rental['status'] = 'overdue';
                }
                $rental['remaining_days'] = 'Overdue by ' . $absoluteDays . ' day' . ($absoluteDays !== 1 ? 's' : '');
            } elseif ($absoluteDays > 0) {
                $rental['remaining_days'] = $absoluteDays . ' day' . ($absoluteDays !== 1 ? 's left' : ' left');
            } else {
                $rental['remaining_days'] = 'Due Today';
            }
        } else {
            $rental['remaining_days'] = 'N/A';
        }
    }
    unset($rental);

    return $rentals;
}
private function calculateRemainingDays($status, $startDate, $endDate) {
    if ($status === 'returned') {
        return 'Completed';
    } elseif ($status === 'cancelled') {
        return 'Cancelled';
    } elseif ($status === 'overdue') {
        $today = new DateTime();
        $end = new DateTime($endDate);
        $interval = $today->diff($end);
        return -$interval->days;
    } elseif (!empty($endDate)) {
        $today = new DateTime();
        $end = new DateTime($endDate);
        $interval = $today->diff($end);
        return ($today < $end) ? $interval->days : -$interval->days;
    }
    return 'N/A';
}

public function getStatusBadgeColor($status) {
    switch ($status) {
        case 'pending_confirmation': return 'warning';
        case 'approved': return 'primary';
        case 'delivery_in_progress': return 'info';
        case 'delivered': return 'info';
        case 'renting': return 'info';
        case 'completed': return 'success';
        case 'returned': return 'success';
        case 'cancelled': return 'danger';
        case 'overdue': return 'danger';
        default: return 'secondary';
    }
}

public function getRemainingDaysBadgeColor($remainingDays) {
    if (strpos($remainingDays, 'Overdue') === 0) return 'danger';
    if (strpos($remainingDays, 'day') !== false) return 'info';
    if ($remainingDays === 'Due Today') return 'warning';
    if ($remainingDays === 'Completed') return 'success';
    if ($remainingDays === 'Cancelled') return 'secondary';
    return 'secondary';
}



//Rental Details
// Get rental details
public function getRentalDetails($renterId, $rentalId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT r.*, p.name AS product_name, p.brand, p.image, 
                   p.rental_period, p.rental_price, 
                   CONCAT_WS(' ', u.first_name, u.last_name) AS owner_name
            FROM rentals r
            INNER JOIN products p ON r.product_id = p.id
            INNER JOIN users u ON r.owner_id = u.id
            WHERE r.id = ? AND r.renter_id = ?
        ");
        $stmt->execute([$rentalId, $renterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching rental details: " . $e->getMessage());
        return false;
    }
}
public function getProofs($rentalId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT p.* 
            FROM proofs p
            JOIN rentals r ON p.rental_id = r.id
            WHERE p.rental_id = :rental_id
            AND r.renter_id = :renter_id
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([
            ':rental_id' => $rentalId,
            ':renter_id' => $this->userId
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $this->logError("getProofs Error: " . $e->getMessage());
        return [];
    }
}

// Fetch proofs
private function uploadFile($file) {
    // Your existing XAMPP path
    $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/rb/uploads/proofs/';
    
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Upload failed. Check permissions for: " . $uploadDir);
    }
    
    // Return web-accessible path relative to domain root
    return '/rb/uploads/proofs/' . $filename; // Add /rb/ here
}


// Check overdue status
public function checkOverdueStatus($rentalId, $currentStatus, $endDate) {
    if (in_array($currentStatus, ['renting', 'delivered']) && date('Y-m-d') > $endDate) {
        try {
            $this->conn->beginTransaction();
            $stmt = $this->conn->prepare("UPDATE rentals SET status = 'overdue' WHERE id = ?");
            $stmt->execute([$rentalId]);
            $this->conn->commit();
            return 'overdue';
        } catch (PDOException $e) {
            $this->conn->rollBack();
            error_log("Error updating overdue status: " . $e->getMessage());
        }
    }
    return $currentStatus;
}

// Confirm end rental
public function confirmEndRental($rentalId) {
    try {
        $this->conn->beginTransaction();
        $this->conn->prepare("
            UPDATE rentals 
            SET status = 'completed', actual_end_date = CURDATE()
            WHERE id = ?
        ")->execute([$rentalId]);
        $this->conn->commit();
        return true;
    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log("Error ending rental: " . $e->getMessage());
        return false;
    }
}

// Confirm return
public function confirmReturn($rentalId, $productId) {
    try {
        $this->conn->beginTransaction();
        $this->conn->prepare("
            UPDATE rentals 
            SET status = 'returned', actual_end_date = CURDATE()
            WHERE id = ?
        ")->execute([$rentalId]);

        $this->conn->prepare("
            UPDATE products 
            SET quantity = quantity + 1 
            WHERE id = ?
        ")->execute([$productId]);

        $this->conn->commit();
        return true;
    } catch (Exception $e) {
        $this->conn->rollBack();
        error_log("Error confirming return: " . $e->getMessage());
        return false;
    }
}

// Check feedback
public function checkFeedback($productId, $renterId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT * FROM comments 
            WHERE product_id = ? AND renter_id = ?
        ");
        $stmt->execute([$productId, $renterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error checking feedback: " . $e->getMessage());
        return false;
    }
}

// Check owner review
public function checkOwnerReview($rentalId, $renterId) {
    try {
        $stmt = $this->conn->prepare("
            SELECT * FROM owner_reviews 
            WHERE rental_id = ? AND renter_id = ?
        ");
        $stmt->execute([$rentalId, $renterId]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error checking owner review: " . $e->getMessage());
        return false;
    }
}

public function endRental($rentalId) {
    try {
        $this->conn->beginTransaction();
        
        // Freeze end date to today without changing status
        $stmt = $this->conn->prepare("
            UPDATE rentals 
            SET end_date = CURDATE(),
                updated_at = NOW()
            WHERE id = ? 
            AND renter_id = ?
            AND status = 'picked_up'
        ");
        $stmt->execute([$rentalId, $this->userId]);
        
        $this->conn->commit();
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        throw new Exception("Failed to end rental: " . $e->getMessage());
    }
}

public function initiateReturn($rentalId, $returnProof) {
    try {
        $this->conn->beginTransaction();
        
        // Upload proof
        $proofPath = $this->uploadFile($returnProof);
        
        // Update status to waiting_admin_acceptance (not pending)
        $stmt = $this->conn->prepare("
            UPDATE rentals 
            SET status = 'waiting_admin_acceptance', 
                updated_at = NOW() 
            WHERE id = ? AND renter_id = ?
        ");
        $stmt->execute([$rentalId, $this->userId]);
        
        // Store proof
        $this->storeProof($rentalId, 'return', $proofPath);
        
        $this->conn->commit();
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        throw new Exception("Return initiation failed: " . $e->getMessage());
    }
}

private function storeProof($rentalId, $type, $path) {
    $stmt = $this->conn->prepare("
        INSERT INTO proofs 
        (rental_id, proof_type, proof_url, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$rentalId, $type, $path]);
}

private function logRentalAction($rentalId, $action) {
    $stmt = $this->conn->prepare("
        INSERT INTO rental_logs 
        (rental_id, user_id, action, timestamp)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$rentalId, $this->userId, $action]);
}


public function isStatusActive($statusKey, $currentStatus, $statusFlow) {
    $statusKeys = array_keys($statusFlow);
    $currentIndex = array_search($currentStatus, $statusKeys);
    $statusIndex = array_search($statusKey, $statusKeys);
    
    // Special case for return initiated
    if ($currentStatus === 'return_pending' && $statusKey === 'return_pending') {
        return true;
    }
    
    return $statusIndex <= $currentIndex;
}



public function submitFeedback($rentalId, $productRating, $productComment, $ownerRating, $ownerComment) {
    try {
        $this->conn->beginTransaction();

        // 1. Save product feedback
        $stmt = $this->conn->prepare("
            INSERT INTO comments (product_id, renter_id, rating, comment, created_at)
            VALUES (:product_id, :renter_id, :rating, :comment, NOW())
        ");
        $stmt->execute([
            ':product_id' => $this->getProductIdFromRental($rentalId),
            ':renter_id' => $_SESSION['id'],
            ':rating' => $productRating,
            ':comment' => $productComment
        ]);

        // 2. Save owner review
        $stmt = $this->conn->prepare("
            INSERT INTO owner_reviews (owner_id, renter_id, rental_id, rating, comment, created_at)
            VALUES (:owner_id, :renter_id, :rental_id, :rating, :comment, NOW())
        ");
        $stmt->execute([
            ':owner_id' => $this->getOwnerIdFromRental($rentalId),
            ':renter_id' => $_SESSION['id'],
            ':rental_id' => $rentalId,
            ':rating' => $ownerRating,
            ':comment' => $ownerComment
        ]);

        // 3. Update rental status
        $stmt = $this->conn->prepare("
            UPDATE rentals SET status = 'completed' WHERE id = ?
        ");
        $stmt->execute([$rentalId]);

        $this->conn->commit();
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        throw new Exception("Failed to submit feedback: " . $e->getMessage());
    }
}

private function uploadProof($file, $type) {
    $uploadDir = '../uploads/proofs/';
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type. Only JPEG, PNG, and PDF are allowed.");
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload proof file.");
    }

    return $filename;
}

private function getProductIdFromRental($rentalId) {
    $stmt = $this->conn->prepare("SELECT product_id FROM rentals WHERE id = ?");
    $stmt->execute([$rentalId]);
    return $stmt->fetchColumn();
}

private function getOwnerIdFromRental($rentalId) {
    $stmt = $this->conn->prepare("SELECT owner_id FROM rentals WHERE id = ?");
    $stmt->execute([$rentalId]);
    return $stmt->fetchColumn();
}
    


//profile
public function updateProfile($userId, $firstName, $lastName, $email, $mobile) {
    $stmt = $this->conn->prepare("UPDATE users 
        SET first_name = ?, 
            last_name = ?, 
            email = ?, 
            mobile_number = ? 
        WHERE id = ?");
    return $stmt->execute([$firstName, $lastName, $email, $mobile, $userId]);
}

public function verifyCurrentPassword($userId, $password) {
    $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    return password_verify($password, $user['password']);
}

public function updatePassword($userId, $newPassword) {
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashedPassword, $userId]);
}

//changepassword
public function changePassword($userId, $currentPassword, $newPassword) {
    // Verify current password
    $stmt = $this->conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!password_verify($currentPassword, $user['password'])) {
        return false;
    }
    
    // Update to new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $this->conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashedPassword, $userId]);
}

public function getRentalsByUserId($userId) {
    $stmt = $this->conn->prepare("
        SELECT r.*, p.name AS product_name, p.image AS product_image, 
               p.brand AS product_brand, u.name AS owner_name 
        FROM rentals r
        JOIN products p ON r.product_id = p.id
        JOIN users u ON r.owner_id = u.id
        WHERE r.renter_id = ?
        ORDER BY r.start_date DESC
    ");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


public function getRentalsByStatus($userId, $statuses = []) {
    $query = "SELECT r.*, p.name AS product_name, p.image AS product_image, 
                     u.name AS owner_name, DATEDIFF(r.end_date, CURDATE()) AS days_remaining 
              FROM rentals r
              JOIN products p ON r.product_id = p.id
              JOIN users u ON r.owner_id = u.id
              WHERE r.renter_id = ?";
    
    if (!empty($statuses)) {
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $query .= " AND r.status IN ($placeholders)";
    }
    
    $query .= " ORDER BY r.created_at DESC";
    
    $stmt = $this->conn->prepare($query);
    $params = [$userId];
    if (!empty($statuses)) {
        $params = array_merge($params, $statuses);
    }
    $stmt->execute($params);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function removeFromCart($cartItemId) {
    $stmt = $this->conn->prepare("DELETE FROM cart_items WHERE id = ?");
    return $stmt->execute([$cartItemId]);
}
}
?>