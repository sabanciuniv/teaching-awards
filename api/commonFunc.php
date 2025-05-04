<?php
// commonFunc.php

//FOR SECURITY REAONS MAKE SURE THE USER IS ADMIN 
function enforceAdminAccess(PDO $pdo): void {
    if (!isset($_SESSION['user']) || !checkIfUserIsAdmin($pdo, $_SESSION['user'])) {
        http_response_code(403); // Forbidden
        echo json_encode(['success' => false, 'error' => 'Access denied. Admins only.']);
        exit();
    }
}

//starting session function
function init_session(): void {
    //TO DO: session cookie 
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Optionally, enforce login here
    if (!isset($_SESSION['user'])) {
        header("Location: login.php");
        exit();
    }

    // Error reporting (you can toggle this based on environment)
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

function deleteExcludedCandidate(PDO $pdo, int $candidateID): array {
    enforceAdminAccess($pdo);
    try {
        $stmt = $pdo->prepare("DELETE FROM Exception_Table WHERE CandidateID = :candidateID");
        $stmt->execute(['candidateID' => $candidateID]);

        return ['success' => true];
    } catch (PDOException $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

//add to excluded candidate
function addExcludedCandidate(PDO $pdo, int $candidateID, string $excludedBy): array {
    enforceAdminAccess($pdo);
    try {
        // Check if candidate exists
        $checkStmt = $pdo->prepare("SELECT id FROM Candidate_Table WHERE id = :id");
        $checkStmt->execute([':id' => $candidateID]);

        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'error' => "Candidate with ID $candidateID does not exist"];
        }

        // Check if candidate is already excluded
        $alreadyExcluded = $pdo->prepare("SELECT id FROM Exception_Table WHERE CandidateID = :cid");
        $alreadyExcluded->execute([':cid' => $candidateID]);

        if ($alreadyExcluded->rowCount() > 0) {
            return ['success' => false, 'error' => 'Candidate is already excluded.'];
        }

        // Insert into Exception_Table
        $insertStmt = $pdo->prepare("
            INSERT INTO Exception_Table (CandidateID, excluded_by)
            VALUES (:cid, :eby)
        ");
        $insertStmt->execute([
            ':cid' => $candidateID,
            ':eby' => $excludedBy
        ]);

        return ['success' => true];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

//add or remove system into the exception table
function handleCandidateExclusion(PDO $pdo): void {
    enforceAdminAccess($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['candidateID'], $_POST['action'])) {
        $candidateID = intval($_POST['candidateID']);
        $username = $_SESSION['user'] ?? 'system';

        if ($_POST['action'] === 'exclude') {
            $result = addExcludedCandidate($pdo, $candidateID, $username);
        } elseif ($_POST['action'] === 'unexclude') {
            $result = deleteExcludedCandidate($pdo, $candidateID);
        } else {
            $result = ['success' => false, 'error' => 'Invalid action.'];
        }

        echo json_encode($result);
        exit;
    }
}

function getClientIP(): string {
    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // If multiple IPs, take the first one (real user's IP)
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ipList[0]);
    }

    // Default: REMOTE_ADDR
    return $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
}

function getAllCategories(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT CategoryID,CategoryCode, CategoryDescription 
           FROM Category_Table 
          ORDER BY CategoryID ASC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllAcademicYears(PDO $pdo): array {
    $stmt = $pdo->query(
        "SELECT YearID, Academic_year 
           FROM AcademicYear_Table 
          ORDER BY YearID DESC"
    );
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//get the current academic year
function getCurrentAcademicYear(PDO $pdo): ?string {
    $stmt = $pdo->query("
        SELECT Academic_year 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC 
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['Academic_year'] : null;
}

function fetchCurrentAcademicYear(PDO $pdo): ?array {
    $stmt = $pdo->query("
        SELECT YearID, Academic_year, Start_date_time, End_date_time
        FROM AcademicYear_Table
        ORDER BY Start_date_time DESC
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

//match the year with the ID
function getAcademicYearFromID(PDO $pdo, int $yearID): ?string {
    $stmt = $pdo->prepare("SELECT Academic_year FROM AcademicYear_Table WHERE YearID = ?");
    $stmt->execute([$yearID]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['Academic_year'] : null;
}


//get the current year's ID
function getCurrentAcademicYearID(PDO $pdo): ?int {
    $stmt = $pdo->query("
        SELECT YearID 
        FROM AcademicYear_Table 
        ORDER BY Start_date_time DESC 
        LIMIT 1
    ");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? (int)$row['YearID'] : null;
}

//get candidates according to current year
function getCandidatesForYear(PDO $pdo, int $yearID): array {
    $stmt = $pdo->prepare("
        SELECT c.id, c.SU_ID, c.Name, c.Mail, c.Role, c.Sync_Date, c.Status,
            GROUP_CONCAT(DISTINCT cat.CategoryCode SEPARATOR ', ') AS Categories,
            GROUP_CONCAT(DISTINCT CONCAT(co.Subject_Code, ' ', co.Course_Number) SEPARATOR ', ') AS Courses
        FROM Candidate_Table c
        LEFT JOIN Candidate_Course_Relation cc ON c.id = cc.CandidateID
        LEFT JOIN Category_Table cat ON cc.CategoryID = cat.CategoryID
        LEFT JOIN Courses_Table co ON cc.CourseID = co.CourseID
        WHERE (cc.Academic_Year = :academicYear OR cc.Academic_Year IS NULL)
        GROUP BY c.id
        ORDER BY c.Name ASC
    ");
    $stmt->bindParam(':academicYear', $yearID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//get students according to the year
function getStudentsForYear(PDO $pdo, int $yearID): array {
    $stmt = $pdo->prepare("
        SELECT s.*, 
            GROUP_CONCAT(DISTINCT cat.CategoryCode SEPARATOR ', ') AS Categories
        FROM Student_Table s
        LEFT JOIN Student_Category_Relation scr ON s.id = scr.student_id
        LEFT JOIN Category_Table cat ON scr.categoryID = cat.CategoryID
        WHERE s.YearID = :yearID
        GROUP BY s.id
    ");
    $stmt->bindParam(':yearID', $yearID, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

//check if the user is admin
function checkIfUserIsAdmin(PDO $pdo, string $username): bool {
    $stmt = $pdo->prepare("SELECT 1 FROM Admin_Table WHERE AdminSuUsername = :username AND checkRole <> 'Removed' LIMIT 1");
    $stmt->execute([':username' => $username]);
    return (bool) $stmt->fetch();
}

//check if the use is admin or IT_admin
function getUserAdminRole(PDO $pdo, string $username): ?string {
    $stmt = $pdo->prepare("
        SELECT Role 
        FROM Admin_Table 
        WHERE AdminSuUsername = :username 
          AND checkRole <> 'Removed'
        ORDER BY GrantedDate DESC 
        LIMIT 1
    ");
    $stmt->execute([':username' => $username]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row['Role'] ?? null;
}

function getAllAdmins(PDO $pdo): array {
    try {
        $stmt = $pdo->query("
            SELECT AdminSuUsername,
                   Role,
                   GrantedBy,
                   GrantedDate,
                   RemovedBy,
                   RemovedDate
            FROM Admin_Table
            ORDER BY AdminSuUsername ASC
        ");
        return [
            'status' => 'success',
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => "Database error: " . $e->getMessage()
        ];
    }
}



//get the instructors for each student
function getInstructorsForStudent(PDO $pdo, string $suNetUsername, string $categoryCode): array {
    try {
        $academicYearData = fetchCurrentAcademicYear($pdo);
        if (!$academicYearData || !isset($academicYearData['Academic_year'])) {
            return ['status' => 'error', 'message' => 'Failed to retrieve academic year'];
        }

        $academicYear = intval($academicYearData['Academic_year']);

        if ($categoryCode === 'B') {
            $validTerms = [
                ($academicYear)     . '01',
                ($academicYear)     . '02',
                ($academicYear - 1) . '01',
                ($academicYear - 1) . '02'
            ];
        } else {
            $validTerms = [
                ($academicYear) . '01',
                ($academicYear) . '02'
            ];
        }
        

        // Get student ID
        $stmtStudent = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = :suNetUsername");
        $stmtStudent->execute(['suNetUsername' => $suNetUsername]);
        $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);
        if (!$student) return ['status' => 'error', 'message' => 'Student not found'];
        $studentID = $student['id'];

        // Get enrolled course IDs
        $stmtCourses = $pdo->prepare("SELECT CourseID FROM Student_Course_Relation WHERE `student.id` = :studentID AND EnrollmentStatus = 'enrolled'");
        $stmtCourses->execute(['studentID' => $studentID]);
        $courses = $stmtCourses->fetchAll(PDO::FETCH_COLUMN);
        if (empty($courses)) return ['status' => 'error', 'message' => 'No enrolled courses found'];

        // Get category ID
        $stmtCategory = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :categoryCode");
        $stmtCategory->execute(['categoryCode' => $categoryCode]);
        $category = $stmtCategory->fetch(PDO::FETCH_ASSOC);
        if (!$category) return ['status' => 'error', 'message' => 'Invalid category code'];
        $categoryID = $category['CategoryID'];

        // Dynamically bind placeholders
        $placeholders = implode(',', array_fill(0, count($courses), '?'));

        // Query instructors and group courses with GROUP_CONCAT
        $query = "
            SELECT 
                i.id AS InstructorID,
                i.Name AS InstructorName,
                i.Mail AS InstructorEmail,
                i.Status,
                GROUP_CONCAT(DISTINCT CONCAT(c.Subject_Code, ' ', c.Course_Number) ORDER BY c.Subject_Code SEPARATOR ', ') AS Courses
            FROM Candidate_Table i
            INNER JOIN Candidate_Course_Relation r ON i.id = r.CandidateID
            INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
            WHERE i.Role = 'Instructor' 
              AND i.Status = 'Etkin' 
              AND r.CategoryID = ?
              AND r.Term IN (?, ?, ?, ?)
              AND r.CourseID IN ($placeholders)
              AND NOT EXISTS (
                  SELECT 1 FROM Exception_Table e WHERE e.CandidateID = i.id
              )
            GROUP BY i.id
        ";

        $params = array_merge([$categoryID], $validTerms, $courses);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $instructors
            ? ['status' => 'success', 'data' => $instructors]
            : ['status' => 'error', 'message' => 'No instructors found'];
    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function getTAsForStudent(PDO $pdo, string $suNetUsername, string $categoryCode): array {
    try {
        // Use local function to get current academic year
        $academicYear = getCurrentAcademicYear($pdo);
        if (!$academicYear) {
            return ['status' => 'error', 'message' => 'Current academic year not found'];
        }

        $validTerms = [$academicYear . '01', $academicYear . '02'];

        // Get student ID
        $stmtStudent = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = :suNetUsername");
        $stmtStudent->execute(['suNetUsername' => $suNetUsername]);
        $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);
        if (!$student) return ['status' => 'error', 'message' => 'Student not found'];
        $studentID = $student['id'];

        // Get courses student is enrolled in
        $stmtCourses = $pdo->prepare("SELECT CourseID FROM Student_Course_Relation WHERE `student.id` = :studentID AND EnrollmentStatus = 'enrolled'");
        $stmtCourses->execute(['studentID' => $studentID]);
        $courses = $stmtCourses->fetchAll(PDO::FETCH_COLUMN);
        if (empty($courses)) return ['status' => 'error', 'message' => 'No enrolled courses found'];

        // Get category ID
        $stmtCategory = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :categoryCode");
        $stmtCategory->execute(['categoryCode' => $categoryCode]);
        $category = $stmtCategory->fetch(PDO::FETCH_ASSOC);
        if (!$category) return ['status' => 'error', 'message' => 'Invalid category code'];
        $categoryID = $category['CategoryID'];

        // Build query with placeholders
        $placeholders = implode(',', array_fill(0, count($courses), '?'));
        $query = "
            SELECT 
                i.id AS TA_ID,
                i.Name AS TA_Name,
                i.Mail AS TA_Email,
                i.Status,
                c.CourseName,
                c.Subject_Code,
                c.Course_Number,
                r.Term
            FROM Candidate_Table i
            INNER JOIN Candidate_Course_Relation r ON i.id = r.CandidateID
            INNER JOIN Courses_Table c ON r.CourseID = c.CourseID
            WHERE i.Role = 'TA' 
              AND i.Status = 'Etkin' 
              AND r.CategoryID = ?
              AND r.Term IN (?, ?)
              AND r.CourseID IN ($placeholders)
              AND NOT EXISTS (
                  SELECT 1 FROM Exception_Table e WHERE e.CandidateID = i.id
              )
        ";

        $params = array_merge([$categoryID], $validTerms, $courses);
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $results
            ? ['status' => 'success', 'data' => $results]
            : ['status' => 'error', 'message' => 'No TAs found for the given courses and category'];

    } catch (PDOException $e) {
        return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
    }
}

function checkVotingWindow(PDO $pdo) {
    try {
        $stmt = $pdo->query("
            SELECT Start_date_time, End_date_time
              FROM AcademicYear_Table
             ORDER BY Start_date_time DESC
             LIMIT 1
        ");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // No academic year defined at all → treat as closed
            header("Location: votingClosed.php");
            exit;
        }

        $now       = new DateTime('now', new DateTimeZone(date_default_timezone_get()));
        $start     = new DateTime($row['Start_date_time']);
        $end       = new DateTime($row['End_date_time']);

        if ($now < $start || $now > $end) {
            header("Location: votingClosed.php");
            exit;
        }
    } catch (PDOException $e) {
        // logging, then redirect
        error_log("Voting‐window check failed: " . $e->getMessage());
        header("Location: votingClosed.php");
        exit;
    }
}

function logUnauthorizedAccess(PDO $pdo, string $username, string $page): void {
    $ip = getClientIP();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO Unauthorized_Access_Logs (Username, Page, IP_Address, AccessTime)
            VALUES (:username, :page, :ip, NOW())
        ");
        $stmt->execute([
            ':username' => $username,
            ':page' => $page,
            ':ip' => $ip
        ]);
    } catch (PDOException $e) {
        error_log("Failed to log unauthorized access: " . $e->getMessage());
    }
}

function enforceCategoryVotingAccess(PDO $pdo, string $username, string $categoryCode): void {
    $yearID = getCurrentAcademicYearID($pdo);
    if (!$yearID) {
        echo "Academic year not set.";
        exit;
    }

    // Get the category ID
    $stmtCat = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :code");
    $stmtCat->execute([':code' => $categoryCode]);
    $cat = $stmtCat->fetch(PDO::FETCH_ASSOC);
    if (!$cat) {
        echo "Invalid category.";
        exit;
    }
    $categoryID = $cat['CategoryID'];

    // Get student ID from username
    $stmtStudent = $pdo->prepare("SELECT id FROM Student_Table WHERE SuNET_Username = :username");
    $stmtStudent->execute([':username' => $username]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        echo "User not found.";
        exit;
    }
    $voterID = $student['id'];

    // Check if this student has already voted in this category and year
    $stmtVote = $pdo->prepare("
        SELECT 1 
        FROM Votes_Table 
        WHERE VoterID = :voterID AND CategoryID = :catID AND AcademicYear = :yearID
        LIMIT 1
    ");
    $stmtVote->execute([
        ':voterID' => $voterID,
        ':catID' => $categoryID,
        ':yearID' => $yearID
    ]);

    if ($stmtVote->fetch()) {
        logUnauthorizedAccess($pdo, $username, "voteScreen.php - already voted");
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Voting Notice</title>
            <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css">
            <link href="assets/css/bootstrap_limitless.min.css" rel="stylesheet" type="text/css">
            <link href="assets/css/components.min.css" rel="stylesheet" type="text/css">
        </head>
        <body>
            <div class="container mt-5">
                <div class="alert alert-warning" role="alert" style="font-size: 1.2rem;">
                    <strong>You have already voted in this category.</strong>
                </div>
                <a href="index.php" class="btn btn-secondary">Back to Main Page</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

function enforceCategoryOwnership(PDO $pdo, string $username, string $categoryCode): void {
    // Get current academic year ID
    $yearID = getCurrentAcademicYearID($pdo);
    if (!$yearID) {
        header("Location: accessDenied.php");
        exit;
    }

    // Get student ID + YearID
    $stmtStudent = $pdo->prepare("
        SELECT id, YearID 
        FROM Student_Table 
        WHERE SuNET_Username = :username
    ");
    $stmtStudent->execute([':username' => $username]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        logUnauthorizedAccess($pdo, $username, "voteScreen.php - student not found");
        header("Location: accessDenied.php");
        exit;
    }

    $studentID = $student['id'];
    $studentYearID = $student['YearID'];

    // Check if student's YearID matches the current academic year
    if ($studentYearID != $yearID) {
        logUnauthorizedAccess($pdo, $username, "voteScreen.php - year mismatch");
        header("Location: accessDenied.php");
        exit;
    }

    // Get category ID
    $stmtCat = $pdo->prepare("SELECT CategoryID FROM Category_Table WHERE CategoryCode = :code");
    $stmtCat->execute([':code' => $categoryCode]);
    $cat = $stmtCat->fetch(PDO::FETCH_ASSOC);

    if (!$cat) {
        logUnauthorizedAccess($pdo, $username, "voteScreen.php - invalid category");
        header("Location: accessDenied.php");
        exit;
    }

    $categoryID = $cat['CategoryID'];

    // Now check Student_Category_Relation + Student_Table.YearID = current year
    $stmt = $pdo->prepare("
        SELECT 1 
        FROM Student_Category_Relation scr
        JOIN Student_Table s ON scr.student_id = s.id
        WHERE scr.student_id = :studentID 
          AND scr.categoryID = :categoryID
          AND s.YearID = :yearID
        LIMIT 1
    ");
    $stmt->execute([
        ':studentID' => $studentID,
        ':categoryID' => $categoryID,
        ':yearID' => $yearID
    ]);

    if (!$stmt->fetch()) {
        logUnauthorizedAccess($pdo, $username, "voteScreen.php - category ownership failed");
        header("Location: accessDenied.php");
        exit;
    }
}

function getAllowedCategories(PDO $pdo, string $username): array {
    try {
        // 1) Get student ID and YearID
        $stmtStudent = $pdo->prepare("
            SELECT id, YearID 
            FROM Student_Table 
            WHERE SuNET_Username = :sunet_username
        ");
        $stmtStudent->execute(['sunet_username' => $username]);
        $student = $stmtStudent->fetch(PDO::FETCH_ASSOC);

        if (!$student) {
            return [
                "status" => "error",
                "message" => "Student not found."
            ];
        }

        $student_id = $student['id'];
        $year_id    = $student['YearID'];

        // 2) Get categories + vote status
        $stmtCat = $pdo->prepare("
                SELECT 
                c.CategoryID,
                c.CategoryCode,
                c.CategoryDescription,
                CASE WHEN COUNT(vt.id) > 0 THEN 1 ELSE 0 END AS isVoted
            FROM Student_Category_Relation scr
            JOIN Category_Table c ON scr.categoryID = c.CategoryID
            JOIN Student_Table s ON scr.student_id = s.id
            LEFT JOIN Votes_Table vt
                ON vt.VoterID = s.id
            AND vt.CategoryID = c.CategoryID
            AND vt.AcademicYear = s.YearID
            WHERE s.id = :student_id
            AND s.YearID = :year_id
            GROUP BY c.CategoryID, c.CategoryCode, c.CategoryDescription
        ");
        $stmtCat->execute([
            'student_id' => $student_id,
            'year_id'    => $year_id
        ]);

        $categories = $stmtCat->fetchAll(PDO::FETCH_ASSOC);

        if (empty($categories)) {
            return [
                "status"  => "success",
                "message" => "No voting categories found for this academic year.",
                "categories" => []
            ];
        }

        return [
            "status"     => "success",
            "categories" => $categories
        ];

    } catch (PDOException $e) {
        error_log("Database error in getAllowedCategories: " . $e->getMessage());
        return [
            "status" => "error",
            "message" => "Database query failed."
        ];
    }
}


//getAdmins.php

?>