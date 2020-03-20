<?php
//initialize variables to hold connection parameter
$config = require __DIR__ . '/../config/app.php';

$driver = $config['database']['driver'];
$host = $config['database']['host'];
$dbname = $config['database']['dbname'];
$db_username = $config['database']['username'];
$db_password = $config['database']['password'];

$dsn = "{$driver}:host={$host}; dbname={$dbname}";

try{
    //create an instance of the PDO class with the required parameters
    $db = new PDO($dsn, $db_username, $db_password);

    //set pdo error mode to exception
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    //display success message
    //echo "Connected to the register database";

}catch (PDOException $ex){
    //display error message
    echo "Connection failed ".$ex->getMessage();
}

