<?php
include 'index.php';

header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
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
$sitio = $user['sitio'];
$street = $user['street'];
$address = trim("$street, $sitio");
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
// ===== CHECK IF USER CAN REQUEST AGAIN =====
$checkSql = "
    SELECT request_again
    FROM requests
    WHERE id = ?
    AND transaction = ?
    ORDER BY id DESC
    LIMIT 1
";

$checkStmt = $conn->prepare($checkSql);
$checkStmt->bind_param("is", $id, $transaction);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

// If request exists, check request_again flag
if ($checkResult->num_rows > 0) {
    $row = $checkResult->fetch_assoc();

    if ((int)$row['request_again'] === 0) {
        echo json_encode([
            "success" => false,
            "message" => "You already submitted a request. Please wait for admin permission."
        ]);
        exit();
    }
}
// ===== INSERT REQUEST =====
$sql = "INSERT INTO requests (id, transaction, name, address, birthdate, purpose, pay, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
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

    // Update transaction ID
    $updateStmt = $conn->prepare("
        UPDATE requests 
        SET transactionid = ? 
        WHERE id = ?
    ");

    $updateStmt->bind_param("si", $transactionId, $lastId);
    $updateStmt->execute();
    $updateStmt->close();

    /*
    ===============================
    CREATE NOTIFICATION (NEW REQUEST)
    ===============================
    */

    $notif = $conn->prepare("
        INSERT INTO notifications 
        (user_id, transaction, type, message, is_read)
        VALUES (?, ?, 'new', ?, 0)
    ");

    $notifMessage = "New request submitted for " . $transaction;

    $notif->bind_param(
        "iss",
        $id,
        $transaction,
        $notifMessage
    );

    $notif->execute();
    $notif->close();

    echo json_encode([
        "success" => true,
        "message" => "Your request is successful"
    ]);
}else {
    echo json_encode([
        "success" => false,
        "message" => "Database insert failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();