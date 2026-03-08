<?php
session_start();

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");

// ✅ Check single session key
if (isset($_SESSION['user'])) {
    echo json_encode([
        "authenticated" => true,
        "user" => $_SESSION['user'] // contains role
    ]);
} else {
    echo json_encode(["authenticated" => false]);
}
?>