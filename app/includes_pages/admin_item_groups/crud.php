<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    include("../../includes/common.php");
    $data_raw = json_decode(file_get_contents("php://input"), true);

        if (isset($data_raw['action']) && $data_raw['action'] == 'add_group' ) {
            if (empty($data_raw['group_description'])) {
                echo json_encode(['danger' => true, 'message' => 'Missing Field Data.']);
                exit();
            }
                $data = sanInputs($data_raw);
                $database = new Database();
                $conn = $database->connect();
                $query = "INSERT INTO tblInventoryGroup (company_id, description) VALUES (:company_id, :group_description)";
                $bindings = array(
                    ':company_id' => $_SESSION['session_company_id'],
                    ':group_description' => $data['group_description']
                );
        $rowCount = 0;
        if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
            if ($rowCount > 0) {
               echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
            } else {
                echo json_encode(['warning' => true, 'message' => 'No changes made..']);
            }
        }
        $conn = null;
    }


    if (isset($data_raw['action']) && $data_raw['action'] == 'edit_group') {	
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $query = "UPDATE tblInventoryGroup SET description = :group_description WHERE id = :id AND company_id = :company_id";
                    $bindings = array(
                        ':id' => $data['id'],
                        ':group_description' => $data['group_description'],
                        ':company_id' => $_SESSION['session_company_id']
                        );
        $rowCount = 0;
        if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
            if ($rowCount > 0) {
               echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
            } else {
                echo json_encode(['warning' => true, 'message' => 'No changes made..']);
            }
        }
        $conn = null;
    }
    if (isset($data_raw['action']) && $data_raw['action'] == 'read_group') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblInventoryGroup WHERE id = :id";
        $result = $conn->prepare($query);
        $result->bindParam(':id', $data['group_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                'description' => $row['description'],
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
        $conn = null;
    }
if (isset($data_raw['action']) && $data_raw['action'] == 'toggle_active') {
    try {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();

        // Get the current active status
        $stmt = $conn->prepare("SELECT active FROM tblInventoryGroup WHERE id = :id AND company_id = :company_id");
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':company_id', $_SESSION['session_company_id']);
        $stmt->execute();
        $currentStatus = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

        // Toggle the status
        $newStatus = $currentStatus == 1 ? 0 : 1;

        // Update the active status
        $stmt = $conn->prepare("UPDATE tblInventoryGroup SET active = :new_status WHERE id = :id AND company_id = :company_id");
        $stmt->bindParam(':new_status', $newStatus);
        $stmt->bindParam(':id', $data['id']);
        $stmt->bindParam(':company_id', $_SESSION['session_company_id']);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'new_status' => $newStatus, 'message' => 'Status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made.']);
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "PDO Exception: " . $e->getMessage()]);
    }

    $conn = null;
    exit;
}

}
?>
