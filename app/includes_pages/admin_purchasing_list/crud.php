<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
	
include("../../includes/common.php");
$database = new Database();
$conn = $database->connect();	
$data_raw = json_decode(file_get_contents("php://input"), true);
	
}
?>
