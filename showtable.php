<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Content-Type: application/json; charset=UTF-8");

include 'index.php';

if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB failed"]);
  exit;
}

$sql = "SELECT * FROM registered";
$result = $conn->query($sql);

$data = [];
if(!$result){
  echo json_encode([
    'success' => false,
    'message' => 'query failed'
  ]);
  exit();
}
while ($row = $result->fetch_assoc()) {
  $data[] = $row;
}

echo json_encode([
  "success" => true,
  "data" => $data
]);