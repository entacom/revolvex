<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'user_groups') {
	$database = new Database();
    $conn = $database->connect();
	$query = "SELECT * FROM tblSupportDocs WHERE id =1";
                    $result = $conn->prepare($query);
                    $result->execute();
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['description'];
		}


	}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'scheduler') {
	$database = new Database();
    $conn = $database->connect();
	$query = "SELECT * FROM tblSupportDocs WHERE id =2";
                    $result = $conn->prepare($query);
                    $result->execute();
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['description'];
		}


	}
?>
