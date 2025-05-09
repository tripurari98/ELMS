<?php

//logout.php
$redirectUrl = 'index.php';

session_start();

if(isset($_SESSION['employee_id'])){
	$redirectUrl = 'index.php';
}

session_destroy();

header("location:".$redirectUrl."");

?>