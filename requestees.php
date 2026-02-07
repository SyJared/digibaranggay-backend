<?php
include 'index.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// 1️⃣ Check DB connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// 2️⃣ Update Pending requests whose pickup date +1 day is before today
$updateSql = "
    UPDATE requests 
    SET status = 'Expired' 
    WHERE pickup != '0000-00-00' 
      AND DATE_ADD(pickup, INTERVAL 1 DAY) <= CURDATE() 
      AND status = 'Approved'
";
$conn->query($updateSql);

// 3️⃣ Fetch all requests
$sql = "SELECT * FROM requests";
$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to fetch requests"
    ]);
    exit;
}

$data = [];
if ($result->num_rows === 0) {
    echo json_encode([
        'success' => false,
        'message' => 'There are no requests'
    ]);
    exit();
}

while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// 4️⃣ Return JSON
echo json_encode([
    "success" => true,
    "data" => $data
]);
