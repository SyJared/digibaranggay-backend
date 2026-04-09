<?php
include 'index.php';

header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Notification ID required"
    ]);
    exit();
}

$notif_id = (int)$data['id'];

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE id = ?
");

$stmt->bind_param("i", $notif_id);

if ($stmt->execute()) {
    echo json_encode([
        "success" => true
    ]);
} else {
    echo json_encode([
        "success" => false
    ]);
}