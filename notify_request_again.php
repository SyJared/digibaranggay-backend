<?php
include 'index.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['transaction'])) {
    echo json_encode([
        "success" => false,
        "message" => "Transaction required"
    ]);
    exit();
}

$user_id = $_SESSION['user']['id'];
$transaction = $data['transaction'];
$type = "request_again";

/*
-------------------------------------------------
ANTI-SPAM PROTECTION
-------------------------------------------------
Prevent multiple unread request_again notifications
*/

$check = $conn->prepare("
    SELECT id FROM notifications
    WHERE user_id = ?
    AND transaction = ?
    AND type = ?
    AND is_read = 0
");

$check->bind_param("iss", $user_id, $transaction, $type);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    echo json_encode([
        "success" => true,
        "message" => "Admin already notified. Please wait for approval."
    ]);
    exit();
}

/*
-------------------------------------------------
INSERT NOTIFICATION
-------------------------------------------------
*/

$message = "User requested to allow request again for $transaction.";

$stmt = $conn->prepare("
    INSERT INTO notifications (user_id, transaction, type, message)
    VALUES (?, ?, ?, ?)
");

$stmt->bind_param("isss", $user_id, $transaction, $type, $message);

if ($stmt->execute()) {
    
    echo json_encode([
        "success" => true,
        "message" => "Admin notified successfully."
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Failed to send notification."
    ]);
}