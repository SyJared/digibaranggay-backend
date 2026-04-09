<?php
header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'index.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$user_id     = $data['user_id']     ?? null;
$transaction = $data['transaction'] ?? null;
$message     = $data['message']     ?? null;
$type        = $data['type']        ?? 'user';

if (!$user_id || !$transaction || !$message) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

$stmt = $conn->prepare("
    INSERT INTO notifications (user_id, transaction, type, message, user_read, is_read, created_at)
    VALUES (?, ?, ?, ?, 0, 0, NOW())
");

$stmt->bind_param("isss", $user_id, $transaction, $type, $message);
$stmt->execute();

echo json_encode(["success" => true, "message" => "Notification sent"]);
?>