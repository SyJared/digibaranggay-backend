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



if (!isset($data['transaction'], $data['purpose'], $data['user'])) {
    echo json_encode([
        "success" => false,
        "message" => "Missing required data"
    ]);
    exit();
}

$user = $data['user'];

$id = (int)$user['id'];                 
$transaction = trim($data['transaction']);
$name = trim(
$user['firstname'] . ' ' .
$user['middlename'] . ' ' .
$user['lastname']
); 
$address    = $user['address'];
$birthdate  = $user['birthdate'];
$purpose    = trim($data['purpose']);
$dateUpdated = date("Y-m-d H:i:s");
$payment = isset($data['payment']) ? (float)$data['payment'] : 0.00;



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

$sql = "
    INSERT INTO requests
    (id, transaction, name, address, birthdate, purpose, pay)
    VALUES (?, ?, ?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([  
        "success" => false,
        "message" => "Prepare failed"
    ]);
    exit();
}

$stmt->bind_param(
    "isssssd",
    $id,
    $transaction,
    $name,
    $address,
    $birthdate,
    $purpose,
    $payment

);

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
        "message" => "Your request is successful",

    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Database insert failed: " . $stmt->error
    ]);
}

$stmt->close();
$conn->close();