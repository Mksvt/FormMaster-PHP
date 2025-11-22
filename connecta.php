<?php
// connecta.php
// З’єднання з БД для користувача з обмеженими правами

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$hostname = "localhost";
$username = "labuser";          
$password = "labpass";          
$dbName   = "architecturalworkshop";

$dbc = mysqli_connect($hostname, $username, $password, $dbName);
mysqli_set_charset($dbc, 'utf8mb4');
?>
