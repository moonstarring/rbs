<?php
require_once __DIR__ . '/../db/db.php';


class admin{

    //Database Connection
    protected $db;

    private $conn;

    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }

    // Account Management Page

    // SQL Query to get users based on selected filters and search term
    public function getAllUsers($role_filter = "", $sort_by = "name", $order = "ASC", $search_term = "", $items_per_page = 6, $page = 1) {
        $offset = ($page - 1) * $items_per_page;
        $sql = "SELECT u.*, uv.mobile_number FROM users u
                LEFT JOIN user_verification uv ON u.id = uv.user_id
                WHERE u.role LIKE :role_filter AND (u.name LIKE :search_term OR u.email LIKE :search_term)
                ORDER BY $sort_by $order LIMIT :items_per_page OFFSET :offset";

        $stmt = $this->conn->prepare($sql);
        $role_filter = "%$role_filter%";
        $search_term = "%$search_term%";
        
        $stmt->bindParam(':role_filter', $role_filter);
        $stmt->bindParam(':search_term', $search_term);
        $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    // Get total number of users for pagination
    public function getTotalUsers($role_filter = "", $search_term = "") {
        $sql = "SELECT COUNT(*) AS total FROM users u
                LEFT JOIN user_verification uv ON u.id = uv.user_id
                WHERE u.role LIKE :role_filter AND (u.name LIKE :search_term OR u.email LIKE :search_term)";

        $stmt = $this->conn->prepare($sql);
        $role_filter = "%$role_filter%";
        $search_term = "%$search_term%";
        
        $stmt->bindParam(':role_filter', $role_filter);
        $stmt->bindParam(':search_term', $search_term);
        $stmt->execute();

        return $stmt->fetch()['total'];
    }




    //Analytics Page

    // Total Revenue
    public function getTotalRevenue() {
        $stmt = $this->conn->prepare("SELECT SUM(total_cost) AS total_revenue FROM rentals WHERE status IN ('completed', 'returned')");
        $stmt->execute();
        return $stmt->fetch()['total_revenue'] ?? 0;
    }
    // Most Rented Products
    public function getPopularProducts($limit = 5) {
        $stmt = $this->conn->prepare("SELECT p.name, COUNT(r.id) AS rental_count 
                                      FROM rentals r 
                                      JOIN products p ON r.product_id = p.id 
                                      GROUP BY p.id 
                                      ORDER BY rental_count DESC 
                                      LIMIT :limit");
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }



    //Dashboard Page

    // Utility Functions
    private function fetchData($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return false;
        }
    }


    //USED a LOT
    private function fetchAllData($query, $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            return [];
        }
    }

    // Check Admin Login
    public function checkAdminLogin() {
        if (!isset($_SESSION['admin_id'])) {
            header("Location: login.php");
            exit();
        }
        
        // Optional additional check
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$_SESSION['admin_id']]);
        
        if (!$stmt->fetch()) {
            session_destroy();
            header("Location: login.php");
            exit();
        }
    }

    // Fetch Key Statistics
    public function getKeyStatistics() {
        $query = "SELECT 
                    (SELECT COUNT(*) FROM users) AS total_users,
                    (SELECT COUNT(*) FROM rentals) AS total_rentals,
                    (SELECT COUNT(*) FROM products WHERE status = 'pending_approval') AS pending_gadgets,
                    (SELECT COUNT(*) FROM support_requests WHERE status = 'open') AS open_support_requests,
                    (SELECT COUNT(*) FROM disputes WHERE status = 'open') AS open_disputes,
                    (SELECT COUNT(*) FROM products) AS total_gadgets,
                    (SELECT COUNT(*) FROM users WHERE role = 'owner') AS total_owners,
                    (SELECT COUNT(*) FROM users WHERE role = 'renter') AS total_renters,
                    (SELECT COUNT(*) FROM disputes) AS total_disputes,
                    (SELECT COUNT(*) FROM support_requests) AS total_support_requests";
        return $this->fetchData($query) ?? [
            'total_users' => 0, 'total_rentals' => 0, 'pending_gadgets' => 0,
            'open_support_requests' => 0, 'open_disputes' => 0, 'total_gadgets' => 0,
            'total_owners' => 0, 'total_renters' => 0, 'total_disputes' => 0,
            'total_support_requests' => 0
        ];
    }

    // Fetch Recent Rentals
    public function getRecentRentals() {
        $query = "SELECT r.*, p.name AS product_name, u.name AS renter_name 
                  FROM rentals r 
                  JOIN products p ON r.product_id = p.id 
                  JOIN users u ON r.renter_id = u.id 
                  ORDER BY r.created_at DESC 
                  LIMIT 5";
        return $this->fetchAllData($query);
    }

    // Fetch Recent Support Requests
    public function getRecentSupportRequests() {
        $query = "SELECT sr.*, u.name AS user_name 
                  FROM support_requests sr 
                  JOIN users u ON sr.user_id = u.id 
                  ORDER BY sr.created_at DESC 
                  LIMIT 5";
        return $this->fetchAllData($query);
    }

    // Fetch Monthly Rentals for Chart (also used in analytics)
    public function getMonthlyRentals() {
        $query = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS rentals 
                  FROM rentals 
                  GROUP BY month 
                  ORDER BY month ASC";
        return $this->fetchAllData($query);
    }




    //Gadget Verification and Confirmation Page

    //Approve Gadget
    public function approveGadget($gadgetId) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE products 
                SET status = 'available' 
                WHERE id = ?
            ");
            return $stmt->execute([$gadgetId]);
        } catch (PDOException $e) {
            error_log("Approval Error: " . $e->getMessage());
            return false;
        }
    }
    

    //Reject Gadget
    public function rejectGadget($gadgetId) {
        $stmt = $this->conn->prepare("
            DELETE FROM products 
            WHERE id = ?
        ");
        return $stmt->execute([$gadgetId]);
    }
    


    //Pending Gadgets
    public function getPendingGadgets() {
        $stmt = $this->conn->prepare("
            SELECT p.*, u.name AS owner_name 
            FROM products p
            JOIN users u ON p.owner_id = u.id
            WHERE p.status = 'pending'
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function getPendingAssignments() {
        $query = "SELECT 
            aa.id AS assignment_id,
            r.id AS rental_id,
            p.name AS product_name,
            u.name AS owner_name
        FROM admin_assignments aa
        JOIN rentals r ON aa.rental_id = r.id
        JOIN products p ON r.product_id = p.id
        JOIN users u ON r.owner_id = u.id
        WHERE aa.status = 'pending'
        AND r.status IN ('approved', 'waiting_admin_acceptance')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function acceptAssignment($assignmentId, $adminId) {
        $this->conn->beginTransaction();
        try {
            // Get rental ID
            $stmt = $this->conn->prepare("SELECT rental_id FROM admin_assignments WHERE id = ?");
            $stmt->execute([$assignmentId]);
            $rentalId = $stmt->fetchColumn();
    
            if (!$rentalId) throw new Exception("Assignment not found");
    
            // Update the assignment (set admin_id and status)
            $stmt = $this->conn->prepare("
                UPDATE admin_assignments 
                SET 
                    admin_id = ?, 
                    status = 'accepted' 
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $assignmentId]); // Corrected: No admin_id check in WHERE
    
            // Update rental record
            $stmt = $this->conn->prepare("
                UPDATE rentals 
                SET 
                    admin_id = ?, 
                    status = 'handed_over_to_admin' 
                WHERE id = ?
            ");
            $stmt->execute([$adminId, $rentalId]);
    
            // Reject other assignments for this rental
            $stmt = $this->conn->prepare("
                UPDATE admin_assignments 
                SET status = 'rejected' 
                WHERE rental_id = ? 
                AND id != ?
            ");
            $stmt->execute([$rentalId, $assignmentId]);
    
            $this->conn->commit();
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw new Exception("Failed to accept assignment: " . $e->getMessage());
        }
    }

    public function verifyCsrfToken($token) {
        if (!hash_equals($_SESSION['csrf_token'], $token)) {
            throw new Exception("Invalid CSRF token");
        }
    }

    public function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }



    
    public function handlePickupConfirmation($data) {
        try {
            $this->conn->beginTransaction();
            
            $rentalId = $data['rental_id'];
            $adminId = $_SESSION['admin_id'];
            $proof = $this->handleProofUpload($_FILES['pickup_proof']);
    
            $stmt = $this->conn->prepare("
                UPDATE rentals SET status = 'picked_up' WHERE id = ?
            ");
            $stmt->execute([$rentalId]);
    
            $stmt = $this->conn->prepare("
                INSERT INTO proofs 
                (rental_id, proof_type, proof_url, created_at)
                VALUES (?, 'renter_pickup', ?, NOW())
            ");
            $stmt->execute([$rentalId, $proof]);
    
            $this->conn->commit();
            $_SESSION['success_message'] = "Pickup confirmed successfully!";
        } catch (Exception $e) {
            $this->conn->rollBack();
            $_SESSION['error_message'] = "Error confirming pickup: " . $e->getMessage();
        }
        header("Location: settings.php");
        exit();
    }
    
    private function handleProofUpload($file) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];
        $maxSize = 2 * 1024 * 1024;
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, $allowed)) {
            throw new Exception("Invalid file type. Allowed: " . implode(', ', $allowed));
        }
        
        if ($file['size'] > $maxSize) {
            throw new Exception("File size exceeds 2MB limit");
        }
        
        $filename = uniqid() . '.' . $ext;
        $target = "../uploads/proofs/" . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new Exception("Failed to upload proof");
        }
        
        return $filename;
    }

    

    // Handle dispute status update
    public function updateDisputeStatus($dispute_id, $new_status, $admin_notes) {
        // Validate status
        $valid_statuses = ['open', 'under_review', 'resolved', 'closed'];
        if (!in_array($new_status, $valid_statuses)) {
            $_SESSION['error_message'] = "Invalid status selected.";
            header('Location: review_disputes.php');
            exit();
        }

        // Update dispute
        $stmt = $this->conn->prepare("UPDATE disputes SET status = :status, admin_notes = :admin_notes, resolved_at = IF(:status = 'resolved', NOW(), resolved_at) WHERE id = :dispute_id");
        $stmt->bindParam(':status', $new_status, PDO::PARAM_STR);
        $stmt->bindParam(':admin_notes', $admin_notes, PDO::PARAM_STR);
        $stmt->bindParam(':dispute_id', $dispute_id, PDO::PARAM_INT);
        $stmt->execute();

        if ($stmt->rowCount()) {
            $_SESSION['success_message'] = "Dispute updated successfully.";
        } else {
            $_SESSION['error_message'] = "Failed to update dispute.";
        }

        header('Location: review_disputes.php');
        exit();
    }

    // Fetch disputes with optional filter and search
    public function getDisputes($filter = 'all', $search = '') {
        $filterQuery = "";
        $params = [];

        if ($search) {
            $filterQuery .= " AND (p.name LIKE :search OR u.name LIKE :search OR ur.name LIKE :search)";
            $params[':search'] = "%$search%";
        }

        switch ($filter) {
            case 'overdue':
                $filterQuery .= " AND r.status = 'overdue'";
                break;
            case 'lost':
                $filterQuery .= " AND r.status = 'lost'";
                break;
            default:
                break;
        }

        $stmt = $this->conn->prepare("SELECT d.*, u.name AS user_name, p.name AS product_name 
                                      FROM disputes d 
                                      JOIN users u ON d.initiated_by = u.id 
                                      JOIN rentals r ON d.rental_id = r.id 
                                      JOIN products p ON r.product_id = p.id 
                                      ORDER BY d.created_at DESC");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch rentals for the owner
    public function getRentalsForOwner($ownerId) {
        $sql = "SELECT r.*, p.name AS product_name, u.name AS renter_name
                FROM rentals r
                INNER JOIN products p ON r.product_id = p.id
                INNER JOIN users u ON r.renter_id = u.id
                WHERE r.owner_id = :ownerId
                ORDER BY r.created_at DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':ownerId', $ownerId, PDO::PARAM_INT);
        $stmt->execute();
        $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate remaining days for each rental
        foreach ($rentals as &$rental) {
            if (in_array($rental['status'], ['completed', 'returned'])) {
                $rental['remaining_days'] = 'Completed';
            } elseif ($rental['status'] === 'cancelled') {
                $rental['remaining_days'] = 'Cancelled';
            } elseif ($rental['status'] === 'overdue') {
                $rental['remaining_days'] = 'Overdue';
            } elseif (!empty($rental['end_date'])) {
                $today = new DateTime();
                $endDate = new DateTime($rental['end_date']);
                $interval = $today->diff($endDate);
                $days = (int)$interval->format('%R%a');

                if ($days > 0) {
                    $rental['remaining_days'] = $days . ' day' . ($days > 1 ? 's' : '');
                } elseif ($days < 0) {
                    $rental['remaining_days'] = 'Overdue';
                    if ($rental['status'] !== 'overdue') {
                        $updateSql = "UPDATE rentals SET status = 'overdue', updated_at = NOW() WHERE id = :rentalId";
                        $updateStmt = $this->conn->prepare($updateSql);
                        $updateStmt->bindParam(':rentalId', $rental['id'], PDO::PARAM_INT);
                        $updateStmt->execute();
                        $rental['status'] = 'overdue';
                    }
                } else {
                    $rental['remaining_days'] = 'Due Today';
                }
            } else {
                $rental['remaining_days'] = 'N/A';
            }
        }
        return $rentals;
    }

    // Define the status flow
    public $statusFlow = [
        'pending_confirmation' => 'Rent Pending',
        'approved' => 'Rent Confirmed',
        'delivery_in_progress' => 'Delivery in Progress',
        'delivered' => 'Delivered',
        'renting' => 'Renting',
        'completed' => 'Completed',
        'returned' => 'Returned',
        'cancelled' => 'Cancelled',
        'overdue' => 'Overdue'
    ];

    // Helper function to determine if a status should be active
    public function isStatusActive($statusKey, $currentStatus) {
        $keys = array_keys($this->statusFlow);
        $currentIndex = array_search($currentStatus, $keys);
        $statusIndex = array_search($statusKey, $keys);

        if ($statusIndex === false) {
            return false;
        }

        return $statusIndex <= $currentIndex;
    }





    //SUPPORTS PAGE
    // Respond to a support request and close it
    public function respondToSupportRequest($support_id, $admin_response) {
        // Prepare and execute the update query
        $stmt = $this->conn->prepare("UPDATE support_requests SET admin_response = :admin_response, status = 'closed' WHERE id = :support_id");
        $stmt->bindParam(':admin_response', $admin_response, PDO::PARAM_STR);
        $stmt->bindParam(':support_id', $support_id, PDO::PARAM_INT);

        return $stmt->execute();
    }

    // Fetch all support requests with user names
    public function getSupportRequests() {
        $stmt = $this->conn->prepare("SELECT sr.*, u.name AS user_name 
                                      FROM support_requests sr 
                                      JOIN users u ON sr.user_id = u.id 
                                      ORDER BY sr.created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    //Transactions Page
    public function getTransactions($filter = 'all', $search = '') {
        $filterQuery = "";
        $params = [];
    
        // Add search condition
        if ($search) {
            $filterQuery .= " AND (p.name LIKE :search OR u.name LIKE :search OR ur.name LIKE :search)";
            $params[':search'] = "%$search%";
        }
    
        // Add filter conditions
        switch ($filter) {
            case 'pickup':
                $filterQuery .= " AND r.status = 'delivery_in_progress'";
                break;
            case 'rented':
                $filterQuery .= " AND r.status = 'approved'";
                break;
            case 'returned':
                $filterQuery .= " AND r.status = 'returned'";
                break;
            case 'cancelled':
                $filterQuery .= " AND r.status = 'cancelled'";
                break;
            case 'dispute':
                $filterQuery .= " AND d.status = 'open'";
                break;
            default:
                break;
        }
    
        // Build the query
        $query = "
            SELECT r.id AS rental_id, p.name AS product_name, p.image AS product_image, u.name AS owner_name, 
                   ur.name AS renter_name, r.start_date, r.end_date, r.status
            FROM rentals r
            JOIN products p ON r.product_id = p.id
            JOIN users u ON r.owner_id = u.id
            JOIN users ur ON r.renter_id = ur.id
            LEFT JOIN disputes d ON r.id = d.rental_id
            WHERE 1=1 $filterQuery
            ORDER BY r.created_at DESC
        ";
    
        return $this->fetchAllData($query, $params);
    }
    


    //User Verification Page
    public function approveUser($user_id) {
        // Check if an admin with the same first and last name exists
        $stmt = $this->conn->prepare("SELECT name FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $pendingUser = $stmt->fetch();
    
        if ($pendingUser && !empty($pendingUser['name'])) {
            $nameParts = explode(" ", trim($pendingUser['name']));
            if (count($nameParts) >= 2) {
                $firstName = $nameParts[0];
                $lastName = $nameParts[count($nameParts) - 1];
    
                $stmtAdmin = $this->conn->prepare("SELECT COUNT(*) AS adminCount FROM users WHERE role = 'admin' AND name LIKE :namePattern");
                $stmtAdmin->execute([':namePattern' => $firstName . '% ' . $lastName]);
                $adminResult = $stmtAdmin->fetch();
    
                if ($adminResult['adminCount'] > 0) {
                    return "User cannot be approved because an admin with the same first and last name already exists.";
                }
            }
        }
    
        // Approve the user
        $stmt = $this->conn->prepare("UPDATE users SET email_verified_at = NOW() WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
    
        $stmt2 = $this->conn->prepare("UPDATE user_verification SET verification_status = 'verified' WHERE user_id = :user_id");
        $stmt2->execute([':user_id' => $user_id]);
    
        if ($stmt->rowCount() && $stmt2->rowCount()) {
            return "User approved successfully.";
        }
        return "Failed to approve the user. They may not exist.";
    }
    
    //Reject User Reject the user by deleting from users (which should cascade to user_verification if foreign keys are set).
    public function rejectUser($user_id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
    
        if ($stmt->rowCount()) {
            return "User rejected and removed successfully.";
        }
        return "Failed to reject the user. They may not exist.";
    }
    
    //Fetch Pending User
    public function getPendingUsers() {
        $sql = "SELECT 
                    u.id, u.name, u.email, u.created_at, 
                    uv.valid_id_photo, uv.selfie_photo, uv.cosignee_id_photo, uv.cosignee_selfie,
                    uv.cosignee_email, uv.cosignee_first_name, uv.cosignee_last_name, uv.cosignee_relationship,
                    uv.verification_status
                FROM users u 
                JOIN user_verification uv ON u.id = uv.user_id 
                WHERE u.role = 'renter' AND u.email_verified_at IS NULL";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }




    //View Rental Page
    // Fetch rental details by rental_id
    public function getRentalById($rentalId) {
        $stmt = $this->conn->prepare("
            SELECT 
                r.id, 
                r.product_id, 
                r.renter_id, 
                r.owner_id, 
                r.start_date, 
                r.end_date, 
                r.delivery_date, 
                r.actual_end_date, 
                r.rental_price, 
                r.total_cost, 
                r.payment_method, 
                r.status, 
                r.notification_sent, 
                r.created_at, 
                r.updated_at, 
                p.name AS product_name, 
                u.name AS renter_name
            FROM rentals r
            INNER JOIN products p ON r.product_id = p.id
            INNER JOIN users u ON r.renter_id = u.id
            WHERE r.id = :rental_id
        ");
        $stmt->bindParam(':rental_id', $rentalId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    // Ban a user by user_id
    public function banUser($userId) {
        $stmt = $this->conn->prepare("UPDATE users SET status = 'banned' WHERE id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // Resolve a dispute by rental_id
    public function resolveDispute($rentalId) {
        $stmt = $this->conn->prepare("UPDATE disputes SET status = 'resolved' WHERE rental_id = :rental_id");
        $stmt->bindParam(':rental_id', $rentalId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }
    

    //gadget-management.php
    public function getResponsibleDevices($adminId) {
        $sql = "SELECT 
                    p.id AS product_id,
                    r.id AS rental_id,
                    p.name AS product_name,
                    p.brand,
                    p.image,
                    p.status,
                    r.start_date,
                    r.end_date,
                    aa.created_at AS assignment_date,
                    owner.name AS owner_name,
                    renter.name AS renter_name
                FROM admin_assignments aa
                JOIN rentals r ON aa.rental_id = r.id
                JOIN products p ON r.product_id = p.id
                JOIN users AS owner ON r.owner_id = owner.id
                JOIN users AS renter ON r.renter_id = renter.id
                WHERE aa.admin_id = :admin_id 
                AND aa.status = 'accepted'";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function updateDeviceStatus($productId, $newStatus, $adminId) {
    $allowedStatuses = ['available', 'rented', 'under_maintenance'];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception("Invalid status");
    }

    $sql = "UPDATE products SET status = :status 
            WHERE id = :product_id AND id IN (
                SELECT p.id FROM admin_handovers ah
                JOIN rentals r ON ah.rental_id = r.id
                JOIN products p ON r.product_id = p.id
                WHERE ah.admin_id = :admin_id
            )";
            
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':status', $newStatus);
    $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Update failed or unauthorized");
    }
}

public function confirmReturn($rentalId) {
    try {
        $this->conn->beginTransaction();
        
        $stmt = $this->conn->prepare("
            UPDATE rentals 
            SET status = 'returned', 
                updated_at = NOW() 
            WHERE id = ? 
            AND status = 'waiting_admin_acceptance'
        ");
        $stmt->execute([$rentalId]);
        
        $this->conn->commit();
        
    } catch (Exception $e) {
        $this->conn->rollBack();
        throw new Exception("Confirmation failed: " . $e->getMessage());
    }
}
//pickup-confirmations.php

public function getDeviceDetails($deviceId) {
    $sql = "SELECT 
                p.id AS product_id,
                p.name AS product_name,
                p.brand,
                p.image,
                p.status,
                r.start_date,
                r.end_date
            FROM products p
            WHERE p.id = :id";
    
    try {
        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':id', $deviceId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return [];
    }
}

public function getPendingPickupsForDevice($productId) {
    $sql = "SELECT 
                r.id,
                r.status,
                CASE 
                    WHEN r.status = 'returned' THEN 
                        DATEDIFF(r.end_date, r.actual_end_date)
                    ELSE 
                        DATEDIFF(r.end_date, CURDATE())
                END AS remaining_days,
                r.start_date,
                r.end_date,
                r.actual_end_date,
                r.rental_price,
                r.total_cost,
                p.name AS product_name,
                p.image,
                u1.name AS owner_name,
                u2.name AS renter_name,
                ah.handover_date
            FROM rentals r
            JOIN products p ON r.product_id = p.id
            JOIN users u1 ON r.owner_id = u1.id
            JOIN users u2 ON r.renter_id = u2.id
            LEFT JOIN admin_handovers ah ON r.id = ah.rental_id
            WHERE r.product_id = :product_id
            AND r.status IN ('ready_for_pickup','handed_over_to_admin','picked_up', 'active', 'overdue', 'pending_return', 'returned')";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function updateRentalStatus($rentalId, $newStatus, $adminId) {

    if ($newStatus === 'end_rental') {
        $this->endRental($rentalId, $adminId);
        return;
    }

    // Validate allowed statuses
    $allowedStatuses = ['ready_for_pickup','picked_up', 'active', 'returned', 'overdue', 'pending_return'];
    if (!in_array($newStatus, $allowedStatuses)) {
        throw new Exception("Invalid status selected.");
    }

    // Validate transition rules
    $currentStatus = $this->getCurrentRentalStatus($rentalId);
    if (!$this->isValidStatusTransition($currentStatus, $newStatus)) {
        throw new Exception("Invalid status transition from $currentStatus to $newStatus.");
    }

    // Update the status
    $sql = "UPDATE rentals SET 
                status = :status,
                admin_id = :admin_id,
                updated_at = NOW()
            WHERE id = :rental_id";
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':status' => $newStatus,
        ':admin_id' => $adminId,
        ':rental_id' => $rentalId
    ]);

    // Update actual_end_date for "returned"
    if ($newStatus === 'returned') {
        $this->conn->prepare("UPDATE rentals SET actual_end_date = NOW() WHERE id = ?")
            ->execute([$rentalId]);
    }

    if ($stmt->rowCount() === 0) {
        throw new Exception("No changes made or rental not found.");
    }
}

// Helper method to get current status
private function getCurrentRentalStatus($rentalId) {
    $stmt = $this->conn->prepare("SELECT status FROM rentals WHERE id = ?");
    $stmt->execute([$rentalId]);
    return $stmt->fetchColumn();
}

public function getRentalsForDevice($deviceId) {
    $sql = "SELECT 
                r.id,
                r.status,
                DATEDIFF(r.end_date, CURDATE()) AS remaining_days,
                r.start_date,
                r.end_date,
                r.total_cost,
                p.name AS product_name,
                p.image,
                u1.name AS owner_name,
                u2.name AS renter_name,
                ah.handover_date
            FROM rentals r
            JOIN products p ON r.product_id = p.id
            JOIN users u1 ON r.owner_id = u1.id
            JOIN users u2 ON r.renter_id = u2.id
            LEFT JOIN admin_handovers ah ON r.id = ah.rental_id
            WHERE p.id = :device_id
            ORDER BY r.start_date DESC";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':device_id', $deviceId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function isValidStatusTransition($currentStatus, $newStatus) {
    $transitions = [
        'pending_confirmation' => ['approved', 'cancelled'],
        'approved' => ['ready_for_pickup', 'cancelled'],
        'ready_for_pickup' => ['picked_up', 'cancelled'],
        'handed_over_to_admin' => ['ready_for_pickup', 'cancelled'],
        'picked_up' => ['active', 'overdue', 'pending_return'],
        'active' => ['pending_return', 'overdue', 'returned'],
        'pending_return' => ['returned', 'overdue'],
        'overdue' => ['returned', 'pending_return'],
        'returned' => [],
        'cancelled' => []
    ];

    if (!array_key_exists($currentStatus, $transitions)) {
        throw new InvalidArgumentException("Invalid current status: $currentStatus");
    }

    if (!in_array($newStatus, $transitions[$currentStatus])) {
        throw new LogicException("Invalid transition from $currentStatus to $newStatus");
    }

    return true;
}

public function getStatusBadgeClass($status) {
    $classes = [
        'picked_up' => 'bg-primary',
        'active' => 'bg-success',
        'pending_return' => 'bg-warning',
        'returned' => 'bg-secondary',
        'overdue' => 'bg-danger',
        'damaged' => 'bg-dark'
    ];
    return $classes[$status] ?? 'bg-secondary';
}


public function endRental($rentalId, $adminId) {
    $sql = "UPDATE rentals 
            SET 
                status = 'pending_return', // Change to pending_return
                admin_id = :admin_id,
                updated_at = NOW()
            WHERE id = :rental_id";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->execute([
        ':admin_id' => $adminId,
        ':rental_id' => $rentalId
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception("Failed to end rental.");
    }
}



public function uploadProof($file, $uploadDir) {
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid() . '_' . basename($file['name']);
    $targetPath = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to upload file");
    }

    return $filename;
}

// New method to handle rental-specific proof uploads
public function uploadRentalProof($rentalId, $proofType, $proofFiles, $descriptions = []) {
    $allowedProofTypes = ['handed_over_to_admin', 'picked_up', 'returned'];
    
    if (!in_array($proofType, $allowedProofTypes)) {
        throw new Exception("Invalid proof type: " . $proofType);
    }
    $uploadDir = '/Applications/XAMPP/xamppfiles/htdocs/rb/uploads/proofs/';
    if (!is_dir($uploadDir)) {
        throw new Exception("Upload directory does not exist: " . $uploadDir);
    }
    
    // Verify directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception("Upload directory is not writable. Permissions: " . decoct(fileperms($uploadDir) & 0777));
    }

    foreach ($proofFiles['tmp_name'] as $index => $tmpName) {
        // Validate file upload
        if (!is_uploaded_file($tmpName)) {
            throw new Exception("Invalid file upload attempt: " . $proofFiles['name'][$index]);
        }

        // Generate safe filename
        $originalName = basename($proofFiles['name'][$index]);
        $safeName = preg_replace('/[^a-zA-Z0-9\._\-]/', '', $originalName);
        $fileName = uniqid('proof_') . '_' . $safeName;
        $targetPath = $uploadDir . $fileName;

        // Move file with error handling
        if (!move_uploaded_file($tmpName, $targetPath)) {
            $error = error_get_last();
            throw new Exception("Failed to move uploaded file. Error: " . ($error['message'] ?? 'Unknown error'));
        }

        // Get description or use default
        $description = $descriptions[$index] ?? 'Proof documentation';

        // Insert into database
        $stmt = $this->conn->prepare("
            INSERT INTO proofs 
                (rental_id, proof_type, description, proof_url, created_at)
            VALUES 
                (:rental_id, :proof_type, :description, :proof_url, NOW())
        ");
        
        $stmt->execute([
            ':rental_id' => $rentalId,
            ':proof_type' => $proofType,
            ':description' => $description,
            ':proof_url' => $fileName
        ]);
    }
}

// Method to fetch proofs for a rental
public function getProofs($rentalId) {
    $stmt = $this->conn->prepare("
        SELECT * FROM proofs 
        WHERE rental_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$rentalId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getSpecificRental($rentalId, $adminId) {
    $sql = "SELECT r.*, p.name AS product_name, p.image, 
                   owner.name AS owner_name, renter.name AS renter_name
            FROM rentals r
            JOIN products p ON r.product_id = p.id
            JOIN users owner ON r.owner_id = owner.id
            JOIN users renter ON r.renter_id = renter.id
            JOIN admin_assignments aa ON r.id = aa.rental_id
            WHERE r.id = :rental_id
            AND aa.admin_id = :admin_id
            AND aa.status = 'accepted'";
    
    $stmt = $this->conn->prepare($sql);
    $stmt->bindValue(':rental_id', $rentalId, PDO::PARAM_INT);
    $stmt->bindValue(':admin_id', $adminId, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
}
?>
