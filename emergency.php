<?php
session_start();
include 'index.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Check login
if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        "success" => false,
        "message" => "Unauthorized"
    ]);
    exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // ---------------- GET: return existing emergency info ----------------
    $stmt = $conn->prepare("SELECT emergency_name, emergency_address, emergency_contact, emergency_relation FROM emergency WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $emergency = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "emergency" => $emergency ?: null
    ]);
    exit;
}

// ---------------- POST: insert or update ----------------
$data = json_decode(file_get_contents("php://input"), true);

// Validate
if (!isset(
    $data['emergency_name'],
    $data['emergency_address'],
    $data['emergency_contact'],
    $data['emergency_relation']
)) {
    echo json_encode([
        "success" => false,
        "message" => "Missing fields"
    ]);
    exit;
}

// Sanitize
$name     = $conn->real_escape_string($data['emergency_name']);
$address  = $conn->real_escape_string($data['emergency_address']);
$contact  = $conn->real_escape_string($data['emergency_contact']);
$relation = $conn->real_escape_string($data['emergency_relation']);

// Check if record exists
$check = $conn->prepare("SELECT user_id FROM emergency WHERE user_id = ?");
$check->bind_param("i", $user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // UPDATE
    $stmt = $conn->prepare("
        UPDATE emergency
        SET emergency_name=?, emergency_address=?, emergency_contact=?, emergency_relation=? 
        WHERE user_id=?
    ");
    $stmt->bind_param("ssssi", $name, $address, $contact, $relation, $user_id);
} else {
    // INSERT
    $stmt = $conn->prepare("
        INSERT INTO emergency
        (user_id, emergency_name, emergency_address, emergency_contact, emergency_relation) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issss", $user_id, $name, $address, $contact, $relation);
}

// Execute
if ($stmt->execute()) {
    echo json_encode([
        "success" => true,
        "message" => "Emergency contact saved successfully"
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => $conn->error
    ]);
}

$conn->close();
?>