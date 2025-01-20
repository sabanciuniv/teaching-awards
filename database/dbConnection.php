<?php
// Include the config file for database credentials
$config = include(__DIR__ . '/../config.php');

// Extract database credentials
$dbConfig = $config['database'];

try {
    // Establish the database connection
    $pdo = new PDO(
        "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['dbname']};charset=utf8mb4",
        $dbConfig['username'],
        $dbConfig['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Comment out the below lines after initial table creation
    // echo "Database connection successful!<br>";
    // $sql = file_get_contents(__DIR__ . '/createTables.sql');
    // if (!$sql) {
    //     throw new Exception("SQL file could not be read.");
    // }
    // $pdo->exec($sql);
    // echo "Tables created successfully!<br>";

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
