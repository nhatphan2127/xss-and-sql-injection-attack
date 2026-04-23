<?php
$host = 'db';
$user = 'root';
$pass = 'root';
$db   = 'vulnerable_bank';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
