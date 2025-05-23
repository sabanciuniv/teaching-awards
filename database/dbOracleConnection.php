<?php
// Include the config file for database credentials
$config = include(__DIR__ . '/../config.php');

// Extract database credentials
$dbConfigOracle = $config['database_oracle'];

$prodconn = ocilogon ($dbConfigOracle['username'], $dbConfigOracle['password'], $dbConfigOracle['host'], 'AL32UTF8');


?>