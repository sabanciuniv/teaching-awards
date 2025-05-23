<?php
// Include the config file for database credentials
$config = include(__DIR__ . '/../config.php');

// Extract database credentials
$dbConfigOracle = $config['database_oracle'];

$conn = oci_connect($dbConfigOracle['username'], $dbConfigOracle['password'], $dbConfigOracle['host']);


?>