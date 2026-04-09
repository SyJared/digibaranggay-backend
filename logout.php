<?php
try {
    session_start();

    // Unset all session variables
    $_SESSION = [];

    // Destroy the session cookie in the browser
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"],
            $params["samesite"] ?? 'None'
        );
    }

    // Destroy the server-side session
    session_destroy();

    header("Access-Control-Allow-Origin: https://digibarangay.online");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Content-Type: application/json");

    echo json_encode(["success" => true]);
    exit;

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
    exit;
}