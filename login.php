<?php
include 'index.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get and decode JSON input
$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

// Validate input
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        "success" => false, 
        "message" => "Missing email or password"
    ]);
    exit();
}

$password = trim($data['password']);
$email = trim($data['email']);


// Prepare and execute query
$stmt = $conn->prepare("SELECT id, password, firstname, middlename, lastname, address, birthdate, contactnumber, gender, civilstatus, email, status, role FROM registered WHERE email = ?");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $conn->error
    ]);
    exit();
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Check if user exists
if ($row = $result->fetch_assoc()) {
    // Verify password
    if (password_verify($password, $row['password'])) {
        echo json_encode([
            "success" => true,
            "user" => [
        "id" => $row['id'],
        "firstname" => $row['firstname'],
        "lastname" => $row['lastname'],
        "role" => $row['role'],
        "middlename" => $row['middlename'],
        "address" => $row['address'],
        "birthdate" => $row['birthdate'],
        "contact" => $row['contactnumber'],
        "gender" => $row['gender'],
        "civilstatus" => $row['civilstatus'],
        "email" => $row['email'],
        "status" => $row['status']
    ]
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Incorrect password",
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Email not found"
    ]);
}

$stmt->close();
$conn->close();
?>