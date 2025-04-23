<?php
session_start();
require_once __DIR__ . '/database/dbConnection.php';
require_once 'api/commonFunc.php';

// Ensure category is provided
if (!isset($_GET['category'])) {
    die("Error: No category selected.");
}
$categoryId = intval($_GET['category']);


$academicYearID = getCurrentAcademicYearID($pdo);
if (!$academicYearID) {
    die("Error: Unable to determine current academic year.");
}
$academicYear = getCurrentAcademicYear($pdo);

// Retrieve winners for the selected category and academic year
$stmt = $pdo->prepare("
    SELECT w.Rank AS rank, 
           c.Name AS candidate_name, 
           c.Mail AS candidate_email, 
           c.Role AS candidate_role, 
           IFNULL(a.Academic_year, :fallback_year) AS Academic_year
    FROM WinnerList_Table w
    INNER JOIN Candidate_Table c ON w.WinnerID = c.id
    LEFT JOIN AcademicYear_Table a ON w.YearID = a.YearID
    WHERE w.YearID = :year_id AND w.CategoryID = :category_id
    ORDER BY w.Rank ASC
");
$stmt->execute([
    ':year_id' => $academicYearID,
    ':category_id' => $categoryId,
    ':fallback_year' => $academicYear
]);

$winners = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if winners exist
if (empty($winners)) {
    die("No winners found for the selected category.");
}

// Generate CSV File
$filename = "Winners_Category_" . $categoryId . ".csv";

// Set headers to force file download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');
if ($output === false) {
    die("Error: Unable to open output stream.");
}

// Write CSV headers
$headers = ["Rank", "Name", "Email", "Role", "Academic Year"];
fputcsv($output, $headers, ';'); // Use semicolon as delimiter


// Write each winner row
foreach ($winners as $winner) {
    fputcsv($output, [
        $winner['rank'],
        $winner['candidate_name'],
        $winner['candidate_email'],
        $winner['candidate_role'],
        $winner['Academic_year']
    ], ';');
}

fclose($output);
exit();
