<?php
require "index.php";

header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$transaction = $data['transaction'] ?? null;

if (!$id || !$transaction) {
    echo json_encode([
        "success" => false,
        "message" => "Missing parameters"
    ]);
    exit;
}

try {

    $stmt = $conn->prepare("
        UPDATE requests
        SET status = 'Successful',
            completed_date = CURRENT_TIMESTAMP
        WHERE id = ?
        AND transaction = ?
        AND status = 'Approved'
    ");

    $stmt->bind_param("is", $id, $transaction);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode([
            "success" => true,
            "message" => "Request marked as successful"
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "No eligible approved request found"
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}