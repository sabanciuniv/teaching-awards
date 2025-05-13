<?php
$config = require __DIR__ . '/../config.php';

if (!empty($config['dev_mode'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(0);
    ini_set('display_errors', '0');
}
