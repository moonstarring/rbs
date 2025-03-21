<?php
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$db   = 'PROJECT';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

try {
    // Get request parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = 8;
    $offset = ($page - 1) * $limit;
    $sort = $_GET['sort'] ?? 'newest';
    $categories = isset($_GET['categories']) ? explode(',', $_GET['categories']) : [];
    $excludeUserId = $_SESSION['id'] ?? null;

    // Base SQL queries
    $baseSql = "SELECT p.*, 
                COALESCE(AVG(r.rating), 0) AS average_rating,
                COUNT(DISTINCT r.id) AS rating_count
                FROM products p
                LEFT JOIN reviews r ON p.id = r.rental_id
                WHERE p.status = 'available'";

    $countSql = "SELECT COUNT(DISTINCT p.id) FROM products p WHERE p.status = 'available'";

    // Initialize parameters
    $params = [];
    $countParams = [];

    // Add owner exclusion
    if ($excludeUserId) {
        $baseSql .= " AND p.owner_id != ?";
        $countSql .= " AND p.owner_id != ?";
        $params[] = $excludeUserId;
        $countParams[] = $excludeUserId;
    }

    // Add category filter
    if (!empty($categories)) {
        $placeholders = str_repeat('?,', count($categories) - 1) . '?';
        $baseSql .= " AND p.category IN ($placeholders)";
        $countSql .= " AND p.category IN ($placeholders)";
        $params = array_merge($params, $categories);
        $countParams = array_merge($countParams, $categories);
    }

    // Add grouping and sorting
    $baseSql .= " GROUP BY p.id";
    switch ($sort) {
        case 'top_rated':
            $baseSql .= " ORDER BY average_rating DESC, p.created_at DESC";
            break;
        default:
            $baseSql .= " ORDER BY p.created_at DESC";
            break;
    }

    // Add pagination to main query
    $baseSql .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    // Execute main query
    $stmt = $pdo->prepare($baseSql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Execute count query
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($countParams);
    $totalItems = (int)$countStmt->fetchColumn();
    $totalPages = ceil($totalItems / $limit);

    // Format numeric values
    foreach ($products as &$product) {
        $product['average_rating'] = round($product['average_rating'], 1);
        $product['rating_count'] = (int)$product['rating_count'];
        $product['rental_price'] = number_format($product['rental_price'], 2);
    }

    // Return response
    echo json_encode([
        'products' => $products,
        'pagination' => [
            'total_items' => $totalItems,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'per_page' => $limit,
            'has_next' => $page < $totalPages,
            'has_previous' => $page > 1
        ]
    ]);

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
?>