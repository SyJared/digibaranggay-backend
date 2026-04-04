<?php
session_start();
include 'index.php'; // your DB connection

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$rawInput = file_get_contents("php://input");
$data = json_decode($rawInput, true);

if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "message" => "Missing email or password"]);
    exit();
}

$email = trim($data['email']);
$password = trim($data['password']);

$stmt = $conn->prepare("
    SELECT id, password, firstname, middlename, lastname, sitio, street, birthdate, contactnumber, gender, civilstatus, email, housenumber, status, role 
    FROM registered 
    WHERE email = ? AND status = 'Accepted'
    ORDER BY id DESC 
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (password_verify($password, $row['password'])) {
        if ($row['status'] !== "Accepted") {
            echo json_encode(["success" => false, "message" => "Account not approved"]);
            exit();
        }

        // ✅ Store user/admin in the same session key
        $_SESSION['user'] = [
            "id" => $row['id'],
            "firstname" => $row['firstname'],
            "middlename" => $row['middlename'],
            "lastname" => $row['lastname'],
            "role" => $row['role'], // 'user' or 'admin'
            "email" => $row['email'],
            "status" => $row['status'],
            "household" => $row['housenumber'] ?? "N/A",
            "sitio" => $row['sitio'],
            "street" => $row['street'],
            "birthdate" => $row['birthdate'],
            "contactnumber" => $row['contactnumber'] ?? "N/A",
            "gender" => $row['gender'],
            "civilstatus" => $row['civilstatus']
        ];

        echo json_encode(["success" => true, "role" => $row['role']]);
    } else {
        echo json_encode(["success" => false, "message" => "Incorrect password"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Email not found"]);
}

$stmt->close();
$conn->close();
?>