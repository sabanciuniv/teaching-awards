<?php
require_once __DIR__.'/api/commonFunc.php';
prep_session();
session_unset(); 
session_destroy(); 

$config = include 'config.php';

header('Location: '.$config['cas_service_url']);
exit();
?>
