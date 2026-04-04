<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'vendor/autoload.php'; // Composer autoload
require 'index.php'; // include your MySQLi connection

use PhpOffice\PhpWord\TemplateProcessor;

// ----------------------
// 1️⃣ Get the ID from POST
// ----------------------
$id = $_POST['id'] ?? null;

if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No ID provided']);
    exit;
}

// ----------------------
// 2️⃣ Fetch user info using MySQLi
// ----------------------
$stmt = $conn->prepare("SELECT firstname, middlename, lastname, contactnumber, civilstatus, housenumber, birthdate, gender, sitio, street FROM registered WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// ----------------------
// 3️⃣ Compute age from birthdate
// ----------------------
$birthDate = new DateTime($user['birthdate']);
$today = new DateTime();
$age = $today->diff($birthDate)->y;

// ----------------------
// 4️⃣ Load DOCX template
// ----------------------
$templatePath = __DIR__ . '/templates/barangayID.docx';
if (!file_exists($templatePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Template not found']);
    exit;
}

$template = new TemplateProcessor($templatePath);

// ----------------------
// 5️⃣ Set values in template
// ----------------------
$template->setValue('firstname', $user['firstname']);
$template->setValue('middlename', $user['middlename']);
$template->setValue('lastname', $user['lastname']);
$template->setValue('contactnumber', $user['contactnumber']);
$template->setValue('civilstatus', $user['civilstatus']);
$template->setValue('housenumber', $user['housenumber']);
$template->setValue('birthdate', $user['birthdate']);
$template->setValue('gender', $user['gender']);
$template->setValue('age', $age);
$template->setValue('sitio', $user['sitio']);
$template->setValue('street', $user['street']);

// ----------------------
// 6️⃣ Save and output
// ----------------------
$tempFile = tempnam(sys_get_temp_dir(), 'BarangayID_'.$user['firstname']) . '.docx';
$template->saveAs($tempFile);

header("Content-Description: File Transfer");
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header('Content-Disposition: attachment; filename="BarangayID.docx"');
header("Content-Transfer-Encoding: binary");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFile));

readfile($tempFile);
unlink($tempFile);
exit;