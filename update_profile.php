<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'index.php'; // Your database connection

// Get JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$id = intval($data['id']);
$firstname = $data['firstname'] ?? '';
$lastname = $data['lastname'] ?? '';
$email = $data['email'] ?? '';
$contactnumber = $data['contactnumber'] ?? '';
$birthdate = $data['birthdate'] ?? '';
$gender = $data['gender'] ?? '';
$civilstatus = $data['civilstatus'] ?? '';
$address = $data['address'] ?? '';

// Optional: validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}

// Prepare and execute update
$stmt = $conn->prepare("
    UPDATE registered
    SET firstname = ?, lastname = ?, email = ?, contactnumber = ?, birthdate = ?, gender = ?, civilstatus = ?, address = ?
    WHERE id = ?
");

$stmt->bind_param(
    "ssssssssi",
    $firstname,
    $lastname,
    $email,
    $contactnumber,
    $birthdate,
    $gender,
    $civilstatus,
    $address,
    $id
);

if ($stmt->execute()) {
    // Insert notification
    $notifStmt = $conn->prepare("
        INSERT INTO notifications (user_id, transaction, type, message, created_at)
        VALUES (?, 'Update info', 'new', 'User updated their information', NOW())
    ");
    $notifStmt->bind_param("i", $id);
    $notifStmt->execute();
    $notifStmt->close();

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
}

$stmt->close();
$conn->close();