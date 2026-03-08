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

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$transaction = $data['transaction'] ?? null;
$status = $data['status'] ?? null;
$pickup = $data['pickup'] ?? null;
$pay = $data['pay'] ?? null;
$action = $data['action'] ?? null;
$responseText = $data['response'] ?? null;
$dateUpdated = date("Y-m-d H:i:s");

/* =========================================================
   ✅ ALLOW REQUEST AGAIN
   ========================================================= */
if ($action === "allow_again") {

  if (!$id || !$transaction) {
    echo json_encode(['Success' => false, 'message' => 'Missing required data']);
    exit;
  }

  $sql = "UPDATE requests 
          SET request_again = 1, dateupdated = ?
          WHERE id = ? AND transaction = ?";

  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    echo json_encode(['Success' => false, 'message' => 'Database error']);
    exit;
  }

  $stmt->bind_param("sis", $dateUpdated, $id, $transaction);
  $stmt->execute();

  echo json_encode([
    'Success' => true,
    'message' => 'User can now request this document again.'
  ]);
  exit;
}


/* =========================================================
   ✅ NORMAL STATUS UPDATE (APPROVE / REJECT)
   ========================================================= */
if ($action === "update_status") {

  if (!$id || !$transaction || !$status || $pay === null) {
    echo json_encode(['Success' => false, 'message' => 'Incomplete data']);
    exit;
  }

  // Require pickup only if Approved
if ($status === "Approved") {
  if ($pickup === '' || $pickup === '0000-00-00' || !$pickup) {
    echo json_encode(['Success' => false, 'message' => 'Pickup date is required for approval']);
    exit;
  }
}
if ($status === "Rejected") {
  $pickup = null;
}

    $sql = "UPDATE requests 
            SET status = ?, 
                dateupdated = ?, 
                pay = ?, 
                pickup = ?, 
                response = ?, 
                request_again = 0
            WHERE id = ? AND transaction = ?";

  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    echo json_encode(['Success' => false, 'message' => 'Database error']);
    exit;
  }

  $stmt->bind_param("ssissis", $status, $dateUpdated, $pay, $pickup, $responseText, $id, $transaction);
  $stmt->execute();

  echo json_encode([
    'Success' => true,
    'message' => 'Updated successfully'
  ]);
  exit;
}


/* =========================================================
   ❌ FALLBACK
   ========================================================= */
echo json_encode([
  'Success' => false,
  'message' => 'Invalid request'
]);