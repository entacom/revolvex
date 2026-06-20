<?php
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

include("../../includes/common.php");

if(isset($_GET['read_table'])){
	    $database = new Database();
		$conn = $database->connect();
        $query = "SELECT * FROM tblUsers";
        $result = $conn->prepare($query);
        $result->execute();
    	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    	$array[] = $row;
		}

		$dataset = array(
			"echo" => 1,
			"totalrecords" => count($array),
			"totaldisplayrecords" => count($array),
			"data" => $array
		);
		echo json_encode($dataset);
}
?>