<?php
header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Content-Type: application/json; charset=UTF-8");

include 'index.php'; // your DB connection

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// Get all users with additional info (LEFT JOIN in case some users have no additional info)
$sql = "SELECT r.*, 
        a.height, a.weight, a.tin, a.position, a.employer,
        e.emergency_name, e.emergency_address, e.emergency_relation, e.emergency_contact
        FROM registered r
        LEFT JOIN additional_info a ON r.id = a.user_id
        LEFT JOIN emergency e ON r.id = e.user_id
        ORDER BY r.dateregistered DESC";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $conn->error
    ]);
    exit();
}

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $data
]);