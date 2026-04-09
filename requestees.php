<?php
include 'index.php';

header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// 1️⃣ Check DB connection
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB connection failed"]);
    exit;
}

// 2️⃣ Update Approved requests whose pickup date +1 day is before today
$updateSql = "
    UPDATE requests 
    SET status = 'Expired' 
    WHERE pickup != '0000-00-00' 
      AND DATE_ADD(pickup, INTERVAL 1 DAY) <= CURDATE() 
      AND status = 'Approved'
";
$conn->query($updateSql);

// 3️⃣ Insert notifications for newly expired requests (no duplicates)
$expiredSql = "
    SELECT id, transaction 
    FROM requests 
    WHERE pickup != '0000-00-00' 
      AND DATE_ADD(pickup, INTERVAL 1 DAY) <= CURDATE() 
      AND status = 'Expired'
";
$expiredResult = $conn->query($expiredSql);

while ($row = $expiredResult->fetch_assoc()) {
    $user_id     = $row['id'];
    $transaction = $row['transaction'];
    $message     = "Your request for $transaction has Expired. Notify the admin if you want to request again.";
    $type        = "user";

    $check = $conn->prepare("
        SELECT id FROM notifications 
        WHERE user_id = ? AND transaction = ? AND type = 'user' AND message LIKE '%Expired%'
    ");
    $check->bind_param("is", $user_id, $transaction);
    $check->execute();
    $checkResult = $check->get_result();

    if ($checkResult->num_rows === 0) {
        $notif = $conn->prepare("
            INSERT INTO notifications (user_id, transaction, type, message, user_read, is_read, created_at)
            VALUES (?, ?, ?, ?, 0, 0, NOW())
        ");
        $notif->bind_param("isss", $user_id, $transaction, $type, $message);
        $notif->execute();
    }
}

// 4️⃣ Fetch all requests
$sql = "SELECT * FROM requests ORDER BY date DESC";
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

// 5️⃣ Return JSON
echo json_encode([
    "success" => true,
    "data" => $data
]);
?>