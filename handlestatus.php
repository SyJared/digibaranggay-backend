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
$id = $data['id'];
$transaction = $data['transaction'];
$status = $data['status'];
$pickup = $data['pickup'];
$pay = $data['pay'];
$dateUpdated = date("Y-m-d H:i:s");

if (isset($data['status'], $data['transaction'], $data['id'], $data['pay'])) {
  if ($pickup === '' || $pickup === '0000-00-00') {
    echo json_encode(['Success' => false, 'message' => 'Pickup date is required']);
    exit;
  }

  $sql = "UPDATE requests SET status =?, dateupdated =?, pay =?, pickup= ? where id =? AND transaction =?";

  $stmt = $conn->prepare($sql);

  if (!$stmt) {
    echo json_encode(['Success' => false, 'message' => 'failed to fetch database']);
  }

  $stmt->bind_param('ssisis', $status, $dateUpdated, $pay, $pickup, $id, $transaction);
  $stmt->execute();
  
  echo json_encode(['Success' => true, 'message' => 'Updated successfully',]);
} else {
  echo json_encode(['Success' => false, 'message' => 'data is empty']);
}
