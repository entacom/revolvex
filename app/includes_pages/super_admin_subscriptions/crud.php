<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
	$data_raw = json_decode(file_get_contents("php://input"), true);
	
if (isset($data_raw['action']) && $data_raw['action'] == 'read_company_profile') {
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id";
    $result = $conn->prepare($query);
    $result->bindParam(':company_id', $data['company_id']); 
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'company_name' => $row['company_name'],
            'company_address' => $row['company_address'],
            'company_suburb' => $row['company_suburb'],
            'company_state' => $row['company_state'],
            'company_postode' => $row['company_postode'],
            'company_country' => $row['company_country'],
			'company_date_format' => $row['company_date_format'],
            'company_phone' => $row['company_phone'],
            'company_email' => $row['company_email'],
            'company_contact' => $row['company_contact'],
            'company_accounts_email' => $row['company_accounts_email'],
            'company_image_path' => $row['company_image_path'],
            'company_url' => $row['company_url'],
			'domain_name' => $row['domain_name'],
            'company_abn' => $row['company_abn'],
            'company_acn' => $row['company_acn'],
			'subscription_plan' => $row['subscription_plan'],
			'subscription_monthly_rate' => $row['subscription_monthly_rate'],
			'subscription_amount' => $row['subscription_amount'],
			'subscription_renew' => date_c($row['subscription_renew'],'d-m-Y'),
			'subscription_commencement' => date_c($row['subscription_commencement'],'d-m-Y'),
			'subscription_count' => countSubs($data['company_id'], 1),
            'bank_account_name' => $row['bank_account_name'],
            'bank_name' => $row['bank_name'],
            'bank_branch' => $row['bank_branch'],
            'bank_bsb' => $row['bank_bsb'],
            'bank_account' => $row['bank_account'],
        );
        array_push($return_arr, $row_data);
    }
	//print_r($return_arr);
    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}



if (isset($data_raw['action']) && $data_raw['action'] == 'update_company') {
    try {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $query = "UPDATE tblCompany 
                  SET 
                    company_name = :company_name,
                    company_address = :company_address,
                    company_suburb = :company_suburb,
                    company_state = :company_state,
                    company_postode = :company_postode,
                    company_phone = :company_phone,
                    company_email = :company_email,
                    company_contact = :company_contact,
                    company_accounts_email = :company_accounts_email,
                    domain_name = :domain_name,
                    subscription_plan = :subscription_plan,
                    subscription_renew = :subscription_renew,
                    subscription_monthly_rate = :subscription_monthly_rate
                  WHERE id = :company_id";
        $bindings = array(
            ':company_name' => $data['company_name'], 
            ':company_address' => $data['company_address'], 
            ':company_suburb' => $data['company_suburb'],
            ':company_state' => $data['company_state'],
            ':company_postode' => $data['company_postode'],
            ':company_phone' => $data['company_phone'],
            ':company_email' => $data['company_email'],
            ':company_contact' => $data['company_contact'],
            ':company_accounts_email' => $data['company_accounts_email'],
            ':domain_name' => $data['domain_name'],
            ':subscription_plan' => $data['subscription_plan'],
            ':subscription_renew' => strtotime($data['subscription_renew']),
            ':subscription_monthly_rate' => $data['subscription_monthly_rate'],
            ':company_id' => $data['company_id'],
        );
        $rowCount = 0;
        if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
            if ($rowCount > 0) {
                echo json_encode(['success' => true, 'message' => 'Data updated successfully.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No changes were made.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error occurred while updating data.']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Internal Server Error: ' . $e->getMessage()]);
    }

    $conn = null;
}


    
}
?>
