<?php
include 'index.php';

header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['firstname'], $data['lastname'], $data['email'], $data['password'], $data['gender'])) {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
    exit;
}

// Clean inputs
$firstname     = $conn->real_escape_string($data['firstname']);
$middlename    = $conn->real_escape_string($data['middlename'] ?? '');
$lastname      = $conn->real_escape_string($data['lastname']);
$email         = $conn->real_escape_string($data['email']);
$sitio  = $conn->real_escape_string($data['sitio'] ?? '');
$street = $conn->real_escape_string($data['street'] ?? '');
$birthdate     = $conn->real_escape_string($data['birthdate'] ?? '');
$password      = password_hash(trim($data['password']), PASSWORD_DEFAULT);
$gender        = $conn->real_escape_string($data['gender']);
$housenumber   = $conn->real_escape_string($data['housenumber'] ?? '');
$contactnumber = $conn->real_escape_string($data['contactnumber'] ?? '');

// Check if email exists with Pending or Accepted
$check = $conn->prepare("SELECT status FROM registered WHERE email=? AND status IN ('Pending', 'Accepted')");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    $check->bind_result($status);
    $check->fetch();
    echo json_encode([
        "success" => false,
        "message" => "This email is already registered. Current status: $status"
    ]);
    exit;
}

// Insert new user (always insert a new row)
$sql = "INSERT INTO registered 
        (firstname, middlename, lastname, email, sitio, street, birthdate, password, gender, housenumber, contactnumber, status)
        VALUES 
        ('$firstname', '$middlename', '$lastname', '$email', '$sitio', '$street', '$birthdate', '$password', '$gender', '$housenumber', '$contactnumber', 'Pending')";

if ($conn->query($sql) === TRUE) {
    $user_id = $conn->insert_id;

    // Create admin notification
    $transaction = "registration_request";
    $message = "$firstname $lastname submitted a registration request.";

    $notif = $conn->prepare("
        INSERT INTO notifications 
        (user_id, transaction, type, message, user_read, is_read, created_at)
        VALUES (?, ?, 'admin', ?, 0, 0, NOW())
    ");
    $notif->bind_param("iss", $user_id, $transaction, $message);
    $notif->execute();

    echo json_encode([
        "success" => true,
        "message" => "Registration successful. Please wait for approval.",
        "user_id" => $user_id
    ]);
    exit;
} else {
    echo json_encode([
        "success" => false,
        "message" => $conn->error
    ]);
    exit;
}

$conn->close();
?>