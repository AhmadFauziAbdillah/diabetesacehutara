<?php

$hostname = 'db.be-mons1.bengt.wasmernet.com';
$port = '3306';
$user = 'ebb596d777bf8000624be6a0442e';
$password = '0685ebb5-96d7-7916-8000-7c8ac6004371';
$database = 'dbscan'; // You shouldn't use the "root" database. This is just for the example. The recommended way is to create a dedicated database (and user) in PhpMyAdmin and use it then here.

$dsn = "mysql:host=$hostname;port=$port;dbname=$database;charset=utf8mb4";

$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
);

$pdo = new PDO($dsn, $user, $password, $options);

$stm = $pdo->query("SELECT VERSION()");
$version = $stm->fetch();

?>
