<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
//include("../../includes/common_mail.php");
	$data_raw = json_decode(file_get_contents("php://input"), true);
	
if (isset($data_raw['action']) && $data_raw['action'] == 'read_company_profile') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id";
    $result = $conn->prepare($query);
    $result->bindParam(':company_id', $_SESSION['session_company_id']);
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $accounting_script = '';
        if (getFieldColumn('myob_option', 'tblAccounting', 'company_id', $_SESSION['session_company_id'])) {
            $accounting_script = 'myob';
        } elseif (getFieldColumn('xero_option', 'tblAccounting', 'company_id', $_SESSION['session_company_id'])) {
            $accounting_script = 'xero';
        }
        $row_data = array(
            'company_name' => $row['company_name'],
            'company_address' => $row['company_address'],
            'company_suburb' => $row['company_suburb'],
            'company_state' => $row['company_state'],
            'company_postcode' => $row['company_postcode'],
            'company_country' => $row['company_country'],
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
            'subscription_amount' => $row['subscription_amount'],
            'subscription_count' => countSubs($_SESSION['session_company_id'], 1),
            'bank_account_name' => $row['bank_account_name'],
            'bank_name' => $row['bank_name'],
            'bank_branch' => $row['bank_branch'],
            'bank_bsb' => $row['bank_bsb'],
            'bank_account' => $row['bank_account'],
            'payable_account_name' => getFieldColumn('payable_account_name', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            'payable_account_code' => getFieldColumn('payable_account_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            
            'payable_account_tax' => getFieldColumn('payable_account_tax', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            'payable_account_tax_code' => getFieldColumn('payable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            
            
            'receivable_account_name' => getFieldColumn('receivable_account_name', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            'receivable_account_code' => getFieldColumn('receivable_account_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            
            'receivable_account_tax' => getFieldColumn('receivable_account_tax', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            'receivable_account_tax_code' => getFieldColumn('receivable_account_tax_code', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            
            
            'cash_sale_customer' => getFieldColumn('cash_sale_customer', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            'cash_sale_uid' => getFieldColumn('cash_sale_uid', 'tblAccounting', 'company_id', $_SESSION['session_company_id']),
            'accounting_script' => $accounting_script,
        );
        array_push($return_arr, $row_data);
    }
    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}


if (isset($data_raw['action']) && $data_raw['action'] == 'read_user_profile') {
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();

    $query = "SELECT * FROM tblUsers WHERE id = :user_id";
    $result = $conn->prepare($query);
    $result->bindParam(':user_id', $data['user_id']); // Correct the parameter name here
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
				if($row['user_image_path']){
				$user_image_path = $row['user_image_path'];
				}
				else{
					$user_image_path = getTableColField('company_image_path', 'tblCompany', 'id' ,$_SESSION['session_company_id']);
				}
		
        $row_data = array(
            'first_lastname' => $row['first_lastname'],
            'username' => $row['username'],
            'company_id' => $row['company_id'],
			'email' => $row['email'],
			'mobile' => $row['mobile'],
            'security_group_id' => $row['group_id'],
            'security_group' => getTableColField('user_group', 'tblUsersGroups', 'group_id' ,$row['group_id']),
			'user_image_path' => $user_image_path,
			'job_position' => $row['job_position'],
			'signature_file' => $row['signature_file'],
			'timesheet_rate' => $row['timesheet_rate'],
			'timezone' => $row['timezone'],
			'last_login' => $row['last_login'],
			'active' => $row['active'],
        );
        array_push($return_arr, $row_data);
    }
	header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}
	
if (isset($data_raw['action']) && $data_raw['action'] == 'add_user') {	
	$data = sanInputs($data_raw);
    $username = $data['username'] . '@' . $data['domain_name_user'];
    // Check if the username already exists
    $database = new Database();
    $conn = $database->connect();
    $checkQuery = "SELECT COUNT(*) AS count FROM tblUsers WHERE username = :username";
    $checkResult = $conn->prepare($checkQuery);
    $checkResult->bindParam(':username', $username);
    $checkResult->execute();
    $userCount = $checkResult->fetch(PDO::FETCH_ASSOC)['count'];

    if ($userCount > 0) {
    // Username already exists, send an error response
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Username already exists']);
    exit;
	}
	
	   $fieldNames = [
			'firstname' => 'First Name',
			'lastname' => 'Last Name',
			'username' => 'Username',
			'email' => 'Email',
			'mobile' => 'Mobile Number',
			'job_position' => 'Job Position',
		    'user_access_level_id' => 'User Access Level',
			'timezone' => 'Time Zone'
		];

    // Required fields
    $requiredFields = ['firstname', 'lastname', 'username', 'email', 'mobile', 'job_position', 'user_access_level_id', 'timezone'];
    $missingFields = [];

    // Check for missing fields
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missingFields[] = $fieldNames[$field];
        }
    }
	// Check if email is in correct format
    if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $missingFields[] = "Invalid Email Format";
    }

    // If there are missing fields, return an error
    if (!empty($missingFields)) {
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Missing fields: ' . implode(', ', $missingFields)]);
        exit;
    }


    // If the username doesn't exist, proceed with user insertion
    $active = 1;
	$group_id = 11;
    $user_added = time();
	$token = bin2hex(random_bytes(32)); 
	$first_lastname=$data['firstname'].' '.$data['lastname'];
    $query = "INSERT INTO tblUsers 
              (first_lastname,firstname, lastname, username, company_id, group_id, email, token, mobile, job_position, timezone, user_added, active)
              VALUES 
              (:first_lastname, :firstname, :lastname, :username, :company_id, :group_id, :email, :token, :mobile, :job_position, :timezone, :user_added, :active)";

    $result = $conn->prepare($query);
			if ($result) {
	try {
	$result->bindParam(':first_lastname', $first_lastname);
    $result->bindParam(':firstname', $data['firstname']);
    $result->bindParam(':lastname', $data['lastname']);
    $result->bindParam(':username', $username);
    $result->bindParam(':company_id', $_SESSION['session_company_id']);
	$result->bindParam(':group_id',$data['user_access_level_id']);
    $result->bindParam(':email', $data['email']);
	$result->bindParam(':token', $token);
    $result->bindParam(':mobile', $data['mobile']);
    $result->bindParam(':job_position', $data['job_position']);
    $result->bindParam(':timezone', $data['timezone']);
    $result->bindParam(':user_added', $user_added);
    $result->bindParam(':active', $active);

		if ($result->execute()) {
    	$newUserId = $conn->lastInsertId();

    if ($result->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Data updated successfully.', 'newUserId' => $newUserId]);
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


if (isset($data_raw['action']) && $data_raw['action'] == 'update_user_password') {
    $data = $data_raw;
    $newPassword = $data['newPassword1']; // Assuming newPassword1 is the name attribute of the input field for the new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers 
              SET 
              password = :password
              WHERE id = :user_id";
    $result = $conn->prepare($query);
			if ($result) {
	try {
    $result->bindParam(':password', $hashedPassword);
    $result->bindParam(':user_id', $data['user_id']); // Assuming user_id is stored in session
    		if ($result->execute()) {
					if ($result->rowCount() > 0) {
						echo "Data updated successfully.";
					} else {
						echo "No changes were made.";
					}
				} else {
					echo "Error executing the statement."; // Execution failed
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
if (isset($data_raw['action']) && $data_raw['action'] == 'update_profile') {	
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblCompany 
              SET 
                company_address = :company_address,
                company_suburb = :company_suburb,
                company_state = :company_state,
                company_postcode = :company_postcode,
                company_country = :company_country,
                company_phone = :company_phone,
                company_email = :company_email,
                company_contact = :company_contact,
                company_accounts_email = :company_accounts_email
                WHERE id = :company_id";
				$bindings = array(
					':company_address' => $data['company_address'], 
					':company_suburb' => $data['company_suburb'],
					':company_state' => $data['company_state'],
					':company_postcode' => $data['company_postcode'],
					':company_country' => $data['company_country'],
					':company_phone' => $data['company_phone'],
					':company_email' => $data['company_email'],
					':company_contact' => $data['company_contact'],
					':company_accounts_email' => $data['company_accounts_email'],
					':company_id' => $_SESSION['session_company_id'],
					
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
if (isset($data_raw['action']) && $data_raw['action'] == 'update_bank') {	
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblCompany 
              SET 
                bank_account_name = :bank_account_name,
                bank_name = :bank_name,
                bank_branch = :bank_branch,
                bank_bsb = :bank_bsb,
                bank_account = :bank_account
                WHERE id = :company_id";
				$bindings = array(
					':bank_account_name' => $data['bank_account_name'], 
					':bank_name' => $data['bank_name'],
					':bank_branch' => $data['bank_branch'],
					':bank_bsb' => $data['bank_bsb'],
					':bank_account' => $data['bank_account'],
					':company_id' => $_SESSION['session_company_id'],
				
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
if (isset($data_raw['action']) && $data_raw['action'] == 'update_accounting') {	
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblAccounting 
              SET 
                payable_account_name = :payable_account_name,
                payable_account_code = :payable_account_code,
                payable_account_tax = :payable_account_tax,
                payable_account_tax_code = :payable_account_tax_code,
                receivable_account_name = :receivable_account_name,
                receivable_account_code = :receivable_account_code,
                receivable_account_tax = :receivable_account_tax,
                receivable_account_tax_code = :receivable_account_tax_code,
                cash_sale_uid = :cash_sale_uid,
                cash_sale_customer = :cash_sale_customer
                WHERE company_id = :company_id";
				$bindings = array(
					':payable_account_name' => $data['payable_account_name'], 
					':payable_account_code' => $data['payable_account_code'],
                    ':payable_account_tax' => $data['payable_account_tax'],
                    ':payable_account_tax_code' => $data['payable_account_tax_code'],
					':receivable_account_name' => $data['receivable_account_name'],
					':receivable_account_code' => $data['receivable_account_code'],
                    ':receivable_account_tax' => $data['receivable_account_tax'],
					':receivable_account_tax_code' => $data['receivable_account_tax_code'],
					':cash_sale_uid' => $data['cash_sale_uid'],
                    ':cash_sale_customer' => $data['cash_sale_customer'],
					':company_id' => $_SESSION['session_company_id'],
				
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
 if (isset($data_raw['action']) && $data_raw['action'] == 'update_user') {
	$data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers 
              SET 
                first_lastname = :first_lastname,
                email = :email,
                mobile = :mobile,
				job_position = :job_position,
                group_id = :group_id,
				timezone = :timezone
                WHERE id = :user_id";
				$bindings = array(
					':first_lastname' => $data['first_lastname'], 
					':email' => $data['email'],
					':mobile' => $data['mobile'],
					':job_position' => $data['job_position'],
                    ':group_id' => $data['security_group_id'],
					':timezone' => $data['timezone'],
					':user_id' => $data['user_id']
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
if (isset($data_raw['action']) && $data_raw['action'] == 'disable_user') {
    $data = sanInputs($data_raw);
    $active = 0;
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers SET active = :active WHERE id = :user_id";
    $bindings = array(
						':active' => $active, 
						':user_id' => $data['user_id']
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
if (isset($data_raw['action']) && $data_raw['action'] == 'enable_user') {
    $data = sanInputs($data_raw);
    $active = 1;
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers SET active = :active WHERE id = :user_id";
    $bindings = array(
						':active' => $active, 
						':user_id' => $data['user_id']
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
