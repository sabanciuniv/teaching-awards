<?php
function logImpersonationAction($pdo, $action, $details = null, $documentUpload = null) {
    if (!isset($_SESSION['impersonating']) || $_SESSION['impersonating'] !== true) {
        return false;
    }

    $adminUser = $_SESSION['admin_user'] ?? 'unknown';
    $impersonatedUser = $_SESSION['impersonated_user'] ?? 'unknown';
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;

    $detailsJson = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : $details;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO Impersonate_logs 
                (admin_user, impersonated_user, action, document_upload, details, ip_address) 
            VALUES 
                (:admin_user, :impersonated_user, :action, :document_upload, :details, :ip_address)
        ");

        $stmt->execute([
            ':admin_user' => $adminUser,
            ':impersonated_user' => $impersonatedUser,
            ':action' => $action,
            ':document_upload' => $documentUpload,
            ':details' => $detailsJson,
            ':ip_address' => $ipAddress
        ]);

        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error logging impersonation action: " . $e->getMessage());
        return false;
    }
}
?>