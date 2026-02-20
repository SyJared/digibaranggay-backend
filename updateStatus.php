<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'index.php'; // DB connection
require 'vendor/autoload.php'; // PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get input JSON
$input = json_decode(file_get_contents("php://input"), true);
$id = intval($input['id'] ?? 0);
$status = $input['status'] ?? '';

$validStatuses = ["Pending", "Accepted", "Rejected"];

if (!$id || !in_array($status, $validStatuses)) {
    echo json_encode(["success" => false, "message" => "Invalid input"]);
    exit;
}

// Update status
$stmt = $conn->prepare("UPDATE registered SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);
$stmt->execute();

$response = ["success" => true, "message" => "Status updated"];

if ($status === "Accepted") {
    // Generate 4-digit PIN
    $pin = rand(1000, 9999);

    // Save PIN
    $stmtPin = $conn->prepare("UPDATE registered SET pin=? WHERE id=?");
    $stmtPin->bind_param("ii", $pin, $id);
    $stmtPin->execute();

    // Fetch user name & email
    $stmtUser = $conn->prepare("SELECT firstname, middlename, lastname, email FROM registered WHERE id=?");
    $stmtUser->bind_param("i", $id);
    $stmtUser->execute();
    $result = $stmtUser->get_result();
    $user = $result->fetch_assoc();

    // Send email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'digibarangay@gmail.com'; // Gmail
        $mail->Password = 'ohnj phhf pcec rcmb';    // App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('digibarangay@gmail.com', 'DigiBarangay');
        $mail->addAddress($user['email'], "{$user['firstname']} {$user['lastname']}");

        $mail->isHTML(true);
        $mail->Subject = 'Your Account Has Been Accepted';
        $mail->Body = "
            <p>Hi {$user['firstname']},</p>
            <p>Your account has been <strong>accepted</strong>.</p>
            <p>Your 4-digit PIN: <strong>{$pin}</strong></p>
            <p>Please keep it safe.</p>
        ";

        $mail->send();
        $response['message'] .= " Email sent.";
        $response['pin'] = $pin; // optional, can return to frontend
    } catch (Exception $e) {
        $response['message'] .= " But email failed: {$mail->ErrorInfo}";
    }
}

echo json_encode($response);
