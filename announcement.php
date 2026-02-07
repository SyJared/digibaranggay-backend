<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

include 'index.php';


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
if ($conn->connect_error) {
  echo json_encode(["success" => false, "message" => "DB failed"]);
  exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){

$action = $_POST['action'] ?? '';
if($action === 'create'){
  if(!empty($_POST['title']) && !empty($_POST['body'])){
  $title = trim($_POST['title']);
  $body = trim($_POST['body']);

  if($title === '' || $body === ''){
      echo json_encode([
          'success' => false,
          'message' => 'Please fill the fields'
      ]);
      exit();
  }

  $sql='INSERT INTO announcements (title, body) VALUES (?, ?)';
  $stmt = $conn->prepare($sql);
  if(!$stmt){
    echo json_encode([
      'success' => false,
      'message' => 'failed to fetch database'
    ]);
  }
  $stmt->bind_param('ss', $title, $body);
  $stmt->execute();

  echo json_encode([
    'success' => true,
    'message' => 'Announcement posted' 
  ]);
  
  }else{
    echo json_encode([
    'success' => false,
    'message' => 'Please fill the fields' 
  ]);
  exit();
  }
}
if($action === 'edit'){
  if(!empty($_POST['title']) && $_POST['body']){
    $id =$_POST['id'];
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);

    if($title === '' || $body === ''){
        echo json_encode([
            'success' => false,
            'message' => 'Please fill the fields'
        ]);
        exit();
    }

    $sql = "UPDATE announcements SET title =? , body =? WHERE id =?";
    $stmt =$conn->prepare($sql);
    if(!$stmt){
      echo json_encode([
      'success' => false,
      'message' => 'failed to fetch database'
    ]);
    }
    $stmt->bind_param('ssi', $title, $body, $id);
    $stmt->execute();

    echo json_encode([
      'success' => true,
      'message' => 'Updated successfully'
    ]);

  }else{
    echo json_encode([
    'success' => false,
    'message' => 'Please fill the fields' 
  ]);
  exit();
  }
}
if($action === 'remove'){
  $id = $_POST['id'];

  $sql = "DELETE FROM announcements WHERE id =?";
  $stmt = $conn->prepare($sql);
  if(!$stmt){
    echo json_encode([
      'success' => false,
      'message' => 'failed to fetch database'
    ]);
  }
  $stmt->bind_param('i', $id);
  $stmt->execute();

  echo json_encode([
    'success' => true,
    'message' => 'Announcement removed'
  ]);
};

}

if($_SERVER['REQUEST_METHOD'] === 'GET'){
$sql = "SELECT * from announcements";
$result = $conn->query($sql);

$data =[];

if(!$result){
  echo json_encode([
    'success' => false,
    'message' => 'query failed'
  ]);
  exit();
}
while($row = $result->fetch_assoc()){
  $data[]=$row;
};
if(count($data)===0){
  echo json_encode([
    'success' => false,
    'message' => 'There are no announcement'
  ]);
}
echo json_encode([
  'success' => true,
  'data'=> $data
]);

}
