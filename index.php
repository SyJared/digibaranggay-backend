<?php
// Database connection
$host = "localhost";
$user = "u699160327_digimon";
$password = "Groupfour123";
$dbname = "u699160327_digidb";
$port = 3306; // Hostinger default

$conn = new mysqli($host, $user, $password, $dbname, $port);

// Check connection
if ($conn->connect_error) {
    // For debugging, show actual error temporarily
    die("DB connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset("utf8");
?>