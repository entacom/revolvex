<?php
// api.php
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../includes/common.php");

header('Content-Type: application/json');

// Allow requests from specific origins
$allowedOrigins = ['https://actionbuilders.com.au', 'http://actionbuilders.com.au'];

if (isset($_SERVER['HTTP_ORIGIN'])) {
    if (in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
        header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
        header("Vary: Origin");
        header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
        header("Access-Control-Allow-Credentials: true");
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Origin not allowed."]);
        exit();
    }
}

// Respond to preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // No further action needed for preflight
    exit();
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
    http_response_code(405);
    echo json_encode(["error" => "Method not allowed."]);
    exit();
}

// Get the JSON body from the request
$inputJSON = file_get_contents('php://input');
$data = json_decode($inputJSON, true);

if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON body."]);
    exit();
}

// Check for the token
$tokenUserId = isset($data['token']) ? verifyToken($data['token']) : false;
if (!$tokenUserId) {
    http_response_code(403);
    echo json_encode(["error" => "Unauthorized access."]);
    exit();
}

// Process the request
if (isset($data['action'])) {
    if ($data['action'] === 'add_client_activity') {
        $data['company_id'] = 2;
        $data['user_id'] = 3;
        echo json_encode(addClientActivity($data));
        exit();
    } elseif ($data['action'] === 'add_project') {
        if (empty($data['company_id'])) {
            http_response_code(400);
            echo json_encode(["error" => "Missing company_id."]);
            exit();
        }
        echo json_encode(addNewProj($data));
        exit();
    }
}

// If action not recognized
http_response_code(400);
echo json_encode(["error" => "Bad request."]);
?>
