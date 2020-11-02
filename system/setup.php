<?php
require_once  __DIR__ . '/../vendor/autoload.php';
require_once  __DIR__ . '/config.php';
require_once  __DIR__ . '/db.connection.php';
require_once __DIR__ . '/../services/DbService.php';

foreach(glob(__DIR__ . "/../models/*.php") as $file){
    require $file;
}

$db = new DbService($pdoInstance);