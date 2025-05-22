<?php
die("***DISABLED***".__FILE__);
session_start();
require_once '../database/dbConnection.php';
require_once 'impersonationLogger.php';
header('Content-Type: application/json');

// Check if request has authorization
if (!isset($_SESSION['user']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access"]);
    exit();
}


// API functionality to get logs
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Only allow admins to view logs
    if (!in_array($_SESSION['role'], ['Admin', 'IT_Admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Access denied. Only Admins or IT_Admins can view logs."]);
        exit();
    }
    
    try {
        $filters = [];
        $params = [];
        
        // Apply filters if provided
        if (isset($_GET['admin_user'])) {
            $filters[] = "admin_user = :admin_user";
            $params[':admin_user'] = $_GET['admin_user'];
        }
        
        if (isset($_GET['impersonated_user'])) {
            $filters[] = "impersonated_user = :impersonated_user";
            $params[':impersonated_user'] = $_GET['impersonated_user'];
        }
        
        if (isset($_GET['date_from'])) {
            $filters[] = "timestamp >= :date_from";
            $params[':date_from'] = $_GET['date_from'] . ' 00:00:00';
        }
        
        if (isset($_GET['date_to'])) {
            $filters[] = "timestamp <= :date_to";
            $params[':date_to'] = $_GET['date_to'] . ' 23:59:59';
        }
        
        $whereClause = !empty($filters) ? "WHERE " . implode(" AND ", $filters) : "";
        
        $stmt = $pdo->prepare("
            SELECT * FROM Impersonate_logs 
            $whereClause
            ORDER BY timestamp DESC
            LIMIT 1000
        ");
        
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($logs);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error: " . $e->getMessage()]);
    }
}

// API functionality to add a log entry
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure we're in an impersonation session
    if (!isset($_SESSION['impersonating']) || $_SESSION['impersonating'] !== true) {
        http_response_code(400);
        echo json_encode(["error" => "Not in an impersonation session"]);
        exit();
    }
    
    $rawData = file_get_contents("php://input");
    $data = json_decode($rawData, true);
    
    if (!isset($data['action']) || empty($data['action'])) {
        http_response_code(400);
        echo json_encode(["error" => "Missing required 'action' parameter"]);
        exit();
    }
    
    $action = $data['action'];
    $details = $data['details'] ?? null;
    $documentUpload = $data['document_upload'] ?? null;
    
    $logId = logImpersonationAction($pdo, $action, $details, $documentUpload);
    
    if ($logId) {
        echo json_encode(["success" => true, "log_id" => $logId]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to log action"]);
    }
}
?>