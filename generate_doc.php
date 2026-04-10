<?php
header("Access-Control-Allow-Origin: https://digibarangay.online");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require 'vendor/autoload.php';
require 'index.php';
use PhpOffice\PhpWord\TemplateProcessor;

// ----------------------
// 1️⃣ Get the ID and transaction from POST
// ----------------------
$id = $_POST['id'] ?? null;
$transaction = $_POST['transaction'] ?? null;
$purpose = $_POST['purpose'] ?? 'N/A';

if (!$id || !$transaction) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID or transaction type']);
    exit;
}

// ----------------------
// 2️⃣ Fetch user info
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
// 3️⃣ Compute age and fullname
// ----------------------
$birthDate = new DateTime($user['birthdate']);
$today = new DateTime();
$age = $today->diff($birthDate)->y;
$name = $user['firstname'] . ' ' . $user['middlename'] . ' ' . $user['lastname'];
// ----------------------
// 4️⃣ Map transaction to template
// ----------------------
$templates = [
    "Barangay ID" => "barangayID.docx",
    "Working clearance" => "working-clearance.docx",
    "Brgy. clearance" => "barangay-clearance.docx",
    "First job seeker" => "firstjobseeker.docx",
    "Business Permit" => "businessPermit.docx",
    "Solo Parent" => "soloParent.docx",
    "First Time Jobseeker" => "firstTimeJobseeker.docx",
    "Other" => "other.docx"
];

if (!isset($templates[$transaction])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid transaction type']);
    exit;
}

$templatePath = $_SERVER['DOCUMENT_ROOT'] . '/templates/' . '/api/' . $templates[$transaction];

if (!file_exists($templatePath)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Template file not found']);
    exit;
}

$template = new TemplateProcessor($templatePath);

// ----------------------
// 5️⃣ Fetch emergency contact info
// ----------------------
$emgStmt = $conn->prepare("SELECT emergency_name, emergency_relation, emergency_contact, emergency_address FROM emergency WHERE user_id = ?");
$emgStmt->bind_param("i", $id);
$emgStmt->execute();
$emgResult = $emgStmt->get_result();
$emg = $emgResult->fetch_assoc() ?? [
    'emergency_name' => '',
    'emergency_relation' => '',
    'emergency_contact' => '',
    'emergency_address' => ''
];

// ----------------------
// 5️⃣ Fetch additional info (height & weight)
// ----------------------
$addStmt = $conn->prepare("SELECT height, weight, tin, position, employer FROM additional_info WHERE user_id = ?");
$addStmt->bind_param("i", $id);
$addStmt->execute();
$addResult = $addStmt->get_result();
$additionalInfo = $addResult->fetch_assoc() ?? [
    'height' => '',
    'weight' => '',
    'tin' => '',
    'position' => '',
    'employer' => ''
];
// format and things
$name = $user['firstname'] . ' ' . $user['middlename'] . '. ' . $user['lastname'];
$address = $user['street'] . ', ' . $user['sitio'];
function dayWithSuffix($day) {
    if (!in_array(($day % 100), [11,12,13])) {
        switch ($day % 10) {
            case 1: return $day.'st';
            case 2: return $day.'nd';
            case 3: return $day.'rd';
        }
    }
    return $day.'th';
}
$today = new DateTime(); // or your specific date
$day = (int)$today->format('d');
$month = $today->format('F'); // Full month name
$year = $today->format('Y');

$formattedDate = dayWithSuffix($day) . " of " . $month . " " . $year;
// ----------------------
// 6️⃣ Set template values generically
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
$template->setValue('purpose', $purpose);
$template->setValue('name', $name);
$template->setValue('address', $address);
$template->setValue('formattedDate', $formattedDate);

// Add emergency contact values brgyid
$template->setValue('emergencyName', $emg['emergency_name']);
$template->setValue('emergencyRelation', $emg['emergency_relation']);
$template->setValue('emergencyContact', $emg['emergency_contact']);
$template->setValue('emergencyAddress', $emg['emergency_address']);

// Add additional info values brgyid
$template->setValue('height', $additionalInfo['height']);
$template->setValue('weight', $additionalInfo['weight']);
$template->setValue('tin', $additionalInfo['tin']);

// for working clearance
$template->setValue('position', $additionalInfo['position']);
$template->setValue('employer', $additionalInfo['employer']);

$today = new DateTime();
$expiry = (clone $today)->modify('+1 year');

$template->setValue('date', $today->format('Y-m-d'));   // or 'F j, Y' for readable format
$template->setValue('expiry', $expiry->format('Y-m-d'));

// ----------------------
// 7️⃣ Save and output
// ----------------------
$cleanTransaction = preg_replace('/[^A-Za-z0-9]/', '_', $transaction);
$cleanFirstname = preg_replace('/[^A-Za-z0-9]/', '', $user['firstname']);

$fileName = $cleanTransaction . '_' . $cleanFirstname . '.docx';

$tempFile = tempnam(sys_get_temp_dir(), $fileName);
rename($tempFile, $tempFile .= '.docx');

$template->saveAs($tempFile);

header("Content-Description: File Transfer");
header("Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document");
header('Content-Disposition: attachment; filename="' . $fileName . '"');
header("Content-Transfer-Encoding: binary");
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($tempFile));

readfile($tempFile);
unlink($tempFile);
exit;
?>