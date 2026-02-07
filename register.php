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



$data = json_decode(file_get_contents("php://input"), true);

if (isset($data['firstname'], $data['middlename'], $data['lastname'], $data['email'], $data['address'], $data['birthdate'], $data['password'], $data['gender'])) {

    $firstname = $conn->real_escape_string($data['firstname']);
    $middlename = $conn->real_escape_string($data['middlename']);
    $lastname = $conn->real_escape_string($data['lastname']);
    $email = $conn->real_escape_string($data['email']);
    $address = $conn->real_escape_string($data['address']);
    $birthdate = $conn->real_escape_string($data['birthdate']);
    $password = password_hash(trim($data['password']), PASSWORD_DEFAULT);
    $gender = $conn->real_escape_string($data['gender']);

    $sql = "INSERT INTO registered (firstname, middlename, lastname, email, address, birthdate, password, gender)
            VALUES ('$firstname', '$middlename', '$lastname', '$email', '$address', '$birthdate', '$password', '$gender')";

    if ($conn->query($sql) === TRUE) {
        echo json_encode(["success" => true, "message" => "Registration successful"]);
    } else {
        echo json_encode(["success" => false, "message" => $conn->error]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Missing fields"]);
}

$conn->close();
?>