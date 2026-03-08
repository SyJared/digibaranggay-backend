<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include 'index.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/* ===== CHECK ADMIN SESSION ===== */

$user = $_SESSION['user'] ?? null;

if (!$user || $user['role'] !== "admin") {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

/* ===== FETCH ALL NOTIFICATIONS ===== */

$stmt = $conn->prepare("
    SELECT n.id,
           n.user_id,
           n.transaction,
           n.type,
           n.message,
           n.is_read,
           n.created_at,
           r.firstname,
           r.lastname
    FROM notifications n
    JOIN registered r ON r.id = n.user_id
    ORDER BY n.created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $notifications
]);
?>