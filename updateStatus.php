<?php
// updateStatus.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'index.php';

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Get input JSON
$input = json_decode(file_get_contents("php://input"), true);
$id = intval($input['id'] ?? 0);
$status = $input['status'] ?? "";

if (!$id || !in_array($status, ["Pending", "Accepted", "Rejected"])) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// Update query
$stmt = $conn->prepare("UPDATE registered SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    echo json_encode(["success" => true, "message" => "Status updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update status"]);
}

$stmt->close();
$conn->close();
?>
