<?php
require_once 'system/setup.php';
require_once 'App.php';

$app = new App($db);
$app->handlePath();
?>



