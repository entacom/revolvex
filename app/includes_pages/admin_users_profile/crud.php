<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
	$data_raw = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data_raw['action']) && $data_raw['action'] == 'save_user') {
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers 
              SET 
                email = :email,
                mobile = :mobile,
				job_position = :job_position,
				timezone = :timezone
                WHERE id = :user_id";
				$bindings = array(
					':email' => $data['email'],
					':mobile' => $data['mobile'],
					':job_position' => $data['job_position'],
					':timezone' => $data['timezone'],
					':user_id' => $_SESSION['session_user_id']
			);
    $rowCount = 0;
     if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
     else{
          http_response_code(500); // Internal Server Error
        echo json_encode(['error' => true, 'message' => 'An error occurred while updating.']);
     }
}
    
    


} // end of the POST data Group
?>
    