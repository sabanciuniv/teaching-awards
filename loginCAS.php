<?php
// -------------------------
// Include phpCAS and config
// -------------------------

// Include phpCAS
require './phpCAS/source/CAS.php';
require_once 'api/commonFunc.php';
prep_session();

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
$_SESSION['user'] = $user;
// -------------------------
// BEGIN: Cookie & DB Logic
// -------------------------

// Generate a unique cookie ID
//$cookie_id = bin2hex(random_bytes(16)); // 32-character unique ID

// --- Define New Cookie Names ---
$newUsernameCookieName = 'teaching_awards_user';
$newIdCookieName = 'teaching_awards_token';

// Generate a unique cookie ID value (the actual token)
$cookie_token_value = bin2hex(random_bytes(16)); // 32-character unique ID

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
            ':cookie_id' => $cookie_token_value,
            ':username'  => $user,
        ]);
    } else {
        // If the user does not exist, insert a new record
        $insertQuery = "INSERT INTO user_cookies (SUNET_Username, cookie_id) 
                        VALUES (:username, :cookie_id)";
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([
            ':username'  => $user,
            ':cookie_id' => $cookie_token_value,
        ]);
    }

    // (2) Set cookies for the client, valid for 2 hours
    $cookie_lifetime = 24 * 60 * 60; // 24 hours in seconds
    $cookie_path     = "/courses/awards/";
    $cookie_domain   = ""; 
    $cookie_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $cookie_httponly = true;
    $cookie_samesite = 'Lax';
    
    /*setcookie("username", $user, time() + $cookie_lifetime, "/", "", $secure, $httponly);
    setcookie("cookie_id", $cookie_id, time() + $cookie_lifetime, "/", "", $secure, $httponly);*/

     // Set the NEW username cookie
     setcookie($newUsernameCookieName, $user, [
        'expires' => time() + $cookie_lifetime,
        'path' => $cookie_path,
        'domain' => $cookie_domain,
        'secure' => $cookie_secure,
        'httponly' => $cookie_httponly,
        'samesite' => $cookie_samesite
    ]);

    // Set the NEW ID/token cookie
    setcookie($newIdCookieName, $cookie_token_value, [
        'expires' => time() + $cookie_lifetime,
        'path' => $cookie_path,
        'domain' => $cookie_domain,
        'secure' => $cookie_secure,
        'httponly' => $cookie_httponly,
        'samesite' => $cookie_samesite
    ]);
    
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
    if (!checkIfUserIsAdmin($pdo, $user)) {
        header("Location: accessDenied.php");
        exit;
    }

    // Store the admin role (if needed)
    $role = getUserAdminRole($pdo, $user);
    $_SESSION['role'] = $role ?? 'Unknown';
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
