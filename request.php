<?php
include 'index.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

if (!isset($data['transaction'], $data['purpose'], $data['user'], $data['pin'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required data"
    ]);
    exit();
}

$user = $data['user'];
$pin = trim($data['pin']);  // PIN from frontend

$id = (int)$user['id'];
$transaction = trim($data['transaction']);
$name = trim($user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname']);
$address = $user['address'];
$birthdate = $user['birthdate'];
$purpose = trim($data['purpose']);
$payment = isset($data['payment']) ? (float)$data['payment'] : 0.00;

// ===== VERIFY PIN (stored as VARCHAR in requests table) =====
$pinSql = "SELECT pin FROM registered WHERE id = ? ORDER BY id DESC LIMIT 1";
$pinStmt = $conn->prepare($pinSql);
$pinStmt->bind_param("i", $id);
$pinStmt->execute();
$pinResult = $pinStmt->get_result();

// If user has no previous PIN stored
if ($pinResult->num_rows === 0) {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect PIN"
    ]);
    exit();
}

$row = $pinResult->fetch_assoc();
$storedPin = $row['pin'];

if ($pin !== $storedPin) {
    echo json_encode([
        "success" => false,
        "message" => "Incorrect PIN"
    ]);
    exit();
}

// ===== CHECK EXISTING REQUEST =====
$checkSql = "SELECT * FROM requests WHERE id = ? AND transaction = ?";
$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("is", $id, $transaction);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode([
        "success" => false,
        "message" => "You already submitted a request for this transaction"
    ]);
    exit();
}

// ===== INSERT REQUEST =====
$sql = "INSERT INTO requests (id, transaction, name, address, birthdate, purpose, pay) VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed"
    ]);
    exit();
}

$stmt->bind_param("isssssd", $id, $transaction, $name, $address, $birthdate, $purpose, $payment);

if ($stmt->execute()) {
    $lastId = $conn->insert_id;
    $transactionId = $id . str_pad($lastId, 3, '0', STR_PAD_LEFT);

    // Update the row with transacid
    $updateStmt = $conn->prepare("UPDATE requests SET transactionid = ? WHERE id = ?");
    $updateStmt->bind_param("ii", $transactionId, $lastId);
    $updateStmt->execute();
    $updateStmt->close();

    echo json_encode([
        "success" => true,
        "message" => "Your request is successful"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database insert failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
