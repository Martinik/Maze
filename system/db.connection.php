<?php
require_once "config.php";

try {
    $dbDsn = 'mysql:host='.$dbConfig['host'].';dbname='.$dbConfig['name'];
    $pdoInstance = new PDO($dbDsn, $dbConfig['user'], $dbConfig['pass']);

} catch(\Exception $e){
    echo "Error!";
    exit;
}