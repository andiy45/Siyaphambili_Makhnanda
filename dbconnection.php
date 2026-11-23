<?php
// Database connection parameters
$hostname = "is3-dev.ict.ru.ac.za";
$username = "G23G6834";
$password = "GumAnd23!";
$dbname   = "mykhandatech";
// Create connection
$conn = new mysqli($hostname, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>