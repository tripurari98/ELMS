<?php

require_once 'config.php';

try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database Connection Failed: ' . $e->getMessage());
}





# add new 


$conn = new mysqli('localhost', 'root', '', 'employee_leave');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
