<?php
// Include phpCAS
require './phpCAS/source/CAS.php';

// Include configuration
$config = include 'config.php';

// Configure phpCAS client using config values
$cas_host      = $config['cas_host'];
$cas_context   = $config['cas_context'];
$cas_port      = $config['cas_port'];
$app_base_url  = $config['app_base_url'];

phpCAS::client(CAS_VERSION_2_0, $cas_host, $cas_port, $cas_context, $app_base_url);

// Disable server validation (for testing only)
phpCAS::setNoCasServerValidation();

// Force CAS authentication
phpCAS::forceAuthentication();

// Retrieve the authenticated user's ID
$user = phpCAS::getUser();



// Start the session and store the username
session_start();
$_SESSION['user'] = $user;
// -------------------------
// BEGIN: Cookie & DB Logic
// -------------------------

// Generate a unique cookie ID
$cookie_id = bin2hex(random_bytes(16)); // 32-character unique ID

// Include the database connection
require_once 'database/dbConnection.php';

try {
    // (1) Insert or update the user_cookies table

    // Check if the user exists in the user_cookies table
    $checkQuery = "SELECT 1 FROM user_cookies WHERE SUNET_Username = :username";
    $checkStmt  = $pdo->prepare($checkQuery);
    $checkStmt->execute([':username' => $user]);

    if ($checkStmt->fetch()) {
        // If the user exists, update the cookie_id
        $updateQuery = "UPDATE user_cookies 
                        SET cookie_id = :cookie_id 
                        WHERE SUNET_Username = :username";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([
            ':cookie_id' => $cookie_id,
            ':username'  => $user,
        ]);
    } else {
        // If the user does not exist, insert a new record
        $insertQuery = "INSERT INTO user_cookies (SUNET_Username, cookie_id) 
                        VALUES (:username, :cookie_id)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([
            ':username'  => $user,
            ':cookie_id' => $cookie_id,
        ]);
    }

    // (2) Set cookies for the client, valid for 2 hours
    $cookie_lifetime = 24 * 60 * 60; // 2 hours in seconds
    setcookie("username", $user, time() + $cookie_lifetime, "/", "", isset($_SERVER['HTTPS']), true);
    setcookie("cookie_id", $cookie_id, time() + $cookie_lifetime, "/", "", isset($_SERVER['HTTPS']), true);

} catch (PDOException $e) {
    die("Database operation failed: " . $e->getMessage());
}

// -----------------------
// END: Cookie & DB Logic
// -----------------------
// -------------------------
// BEGIN: Fetch name and surname from database
// -----
// 
try {
    // Try fetching from Student_Table
    $stmtStudent = $pdo->prepare("SELECT StudentFullName FROM Student_Table WHERE SuNET_Username = :username LIMIT 1");
    $stmtStudent->execute([':username' => $user]);
    $rowStudent = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if ($rowStudent) {
        $_SESSION['full_name'] = $rowStudent['StudentFullName'];
    } else {
        // Try Admin_Table
        $stmtAdmin = $pdo->prepare("SELECT Name FROM Admin_Table WHERE AdminSuUsername = :username LIMIT 1");
        $stmtAdmin->execute([':username' => $user]);
        $rowAdmin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);

        if ($rowAdmin) {
            $_SESSION['full_name'] = $rowAdmin['Name'];
        } else {
            // For candidates, just use the username from CAS ---> they dont have usernames in database
            $_SESSION['full_name'] = $user;
        }
    }
} catch (PDOException $e) {
    $_SESSION['full_name'] = $user; // fallback to username if there's an error
    error_log("Failed to fetch full name: " . $e->getMessage());
}



// -----------------------
// END:name and surname from database
// -----------------------



// -------------------------
// BEGIN: Admin Access Check
// -------------------------

// If the user tries to go to adminDashboard.php, ensure they exist in Admin_Table
// AND checkRole is not 'Removed'.
if (isset($_GET['redirect']) && $_GET['redirect'] === 'adminDashboard.php') {
    try {
        // Check if the username exists in Admin_Table and has checkRole <> 'Removed'
        $adminQuery = "SELECT Role
                       FROM Admin_Table 
                       WHERE AdminSuUsername = :username
                         AND checkRole <> 'Removed'";
        $adminStmt  = $pdo->prepare($adminQuery);
        $adminStmt->execute([':username' => $user]);
        
        // If user is found, fetch the role and store it in the session
        if ($adminRow = $adminStmt->fetch()) {
            $role = $adminRow['Role']; // Get the 'Role' from the result
            $_SESSION['role'] = $role; // Store the role in the session
        } else {
            // If not found or role is 'Removed', redirect to index.php
            header("Location: index.php");
            exit;
        }

    } catch (PDOException $e) {
        die("Admin check failed: " . $e->getMessage());
    }
}

// -----------------------
// END: Admin Access Check
// -----------------------


// Handle redirection after authentication
if (isset($_GET['redirect'])) {
    $redirect_url = $_GET['redirect'];
    // Validate and sanitize the redirect URL
    $allowed_pages = ['nominate.php', 'voteCategory.php', 'adminDashboard.php'];
    if (in_array($redirect_url, $allowed_pages)) {
        header("Location: $redirect_url");
        exit;
    }
}



// Default action if no redirect parameter is provided
echo "Authentication successful for user: " . htmlspecialchars($user);
?>
