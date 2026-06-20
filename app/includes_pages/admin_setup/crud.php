<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
$data_raw = json_decode(file_get_contents("php://input"), true);
	
if (isset($data_raw['action']) && $data_raw['action'] == 'budget_sub_ordering') {
    $database = new Database();
    $conn = $database->connect();
    $newOrder = $data_raw['order']; // Use $data_raw here

    try {
        foreach ($newOrder as $order => $id) {
            $query = "UPDATE tblBudgetBuildSub SET ordering = :ordering WHERE id = :id";
            $statement = $conn->prepare($query);
            $statement->bindParam(':ordering', $order, PDO::PARAM_INT);
            $statement->bindParam(':id', $id, PDO::PARAM_INT);
            $statement->execute();
        }
        echo json_encode(['success' => true, 'message' => 'Data updated successfully.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Error updating order: ' . $e->getMessage()]);
    }
}

if (isset($data_raw['action']) && $data_raw['action'] == 'get_trade') {
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();

    $query = "SELECT * FROM tblTrades WHERE id = :trade_id";
    $result = $conn->prepare($query);
    $result->bindParam(':trade_id', $data['trade_id']); // Correct the parameter name here
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
	
        $row_data = array(
            'trade_name' => $row['trade_name'],
            'rate' => number_format($row['rate'],2),

        );
        array_push($return_arr, $row_data);
    }
	header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'update_trade') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $sql = "UPDATE tblTrades SET 
            trade_name = :trade_name,
            rate = :rate
            WHERE id = :trade_id";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bindParam(':trade_name', $data['trade_name'], PDO::PARAM_STR);
        $stmt->bindParam(':rate', $data['rate'], PDO::PARAM_STR);
        $stmt->bindParam(':trade_id', $data['trade_id'], PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Trade updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes were made.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error executing the statement.']);
        }
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Error in preparing the statement.']);
        exit;
    }
}

if (isset($data_raw['action']) && $data_raw['action'] == 'add_trade') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $active = 1;  // Ensure this variable is defined

    $query = "INSERT INTO tblTrades (company_id, trade_name, rate, active) VALUES (:company_id, :trade_name, :rate, :active)";
   
    $result = $conn->prepare($query);
			if ($result) {
	try {
            $result->bindParam(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
            $result->bindParam(':trade_name', $data['trade_name'], PDO::PARAM_STR);
            $result->bindParam(':rate', $data['rate'], PDO::PARAM_STR);
            $result->bindParam(':active', $active, PDO::PARAM_INT);
		if ($result->execute()) {
    	

		if ($result->rowCount() > 0) {
			echo json_encode(['success' => true, 'message' => 'Data updated successfully.']);

		} else {
			echo json_encode(['success' => false, 'message' => 'No changes were made.']);
		}
			} else {
				echo json_encode(['success' => false, 'message' => 'Error executing the statement.']);
			}
						} catch (PDOException $e) {
							 http_response_code(500);
							echo "PDO Exception: " . $e->getMessage(); // Handle PDO exceptions
						}
					} else {
						 http_response_code(500);
						echo "Error in preparing the statement."; // Preparation failed
					}
}

}
?>
