<?php
// MySQL connection details
$host = "localhost";
$username = "root";
$password = "root";
$adminpw = "admin"; // password for admin access

$db = new PDO("mysql:host=$host", $username, $password);
?>