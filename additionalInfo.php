<?php
session_start();
include 'index.php';

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if (!isset($_SESSION['user']['id'])) {
    echo json_encode(["success"=>false,"message"=>"Unauthorized"]); exit;
}

$user_id = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $conn->prepare("SELECT height, weight, tin FROM additional_info WHERE user_id=?");
    $stmt->bind_param("i",$user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();
    echo json_encode(["success"=>true,"additional_info"=>$data]); exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$height = $conn->real_escape_string($data['height']);
$weight = $conn->real_escape_string($data['weight']);
$tin = $conn->real_escape_string($data['tin']);
$position = $conn->real_escape_string($data['position'] ?? '');
$employer = $conn->real_escape_string($data['employer'] ?? '');

// Check if exists
$check = $conn->prepare("SELECT user_id FROM additional_info WHERE user_id=?");
$check->bind_param("i",$user_id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE additional_info SET height=?, weight=?, tin=?, position=?, employer=? WHERE user_id=?");
    $stmt->bind_param("ddsssi",$height,$weight,$tin,$position,$employer,$user_id);
} else {
    // INSERT
    $stmt = $conn->prepare("INSERT INTO additional_info (user_id, height, weight, tin, position, employer) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isssss",$user_id,$height,$weight,$tin,$position,$employer);
}

if ($stmt->execute()) {
    echo json_encode(["success"=>true,"message"=>"Additional Info saved successfully"]);
} else {
    echo json_encode(["success"=>false,"message"=>$conn->error]);
}

$conn->close();
?>