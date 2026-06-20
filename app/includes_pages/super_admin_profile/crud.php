<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
$company_id=1;
$action = isset($_POST['action']) ? $_POST['action'] : '';

function requireProfileCsrf() {
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        http_response_code(403);
        echo json_encode(array('error' => 'CSRF token validation failed'));
        exit();
    }
}

if ($action == 'read_company_profile') {
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id";
    $result = $conn->prepare($query);
    $result->bindParam(':company_id', $company_id); 
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'company_name' => $row['company_name'],
            'company_address' => $row['company_address'],
            'company_suburb' => $row['company_suburb'],
            'company_state' => $row['company_state'],
            'company_postode' => $row['company_postode'],
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
			'subscription_renew' => date_correct_dmy($row['subscription_renew']),
			'subscription_commencement' => date_correct_dmy($row['subscription_commencement']),
			'subscription_count' => countTableFieldX2('id','tblUsers','company_id',$company_id,'active', '1'),
            'bank_account_name' => $row['bank_account_name'],
            'bank_name' => $row['bank_name'],
            'bank_branch' => $row['bank_branch'],
            'bank_bsb' => $row['bank_bsb'],
            'bank_account' => $row['bank_account'],
        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
    $conn = null;
}
if ($action == 'update_profile') {
    requireProfileCsrf();
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblCompany 
              SET 
			  	company_name = :company_name,
                company_address = :company_address,
                company_suburb = :company_suburb,
                company_state = :company_state,
                company_postode = :company_postode,
                company_country = :company_country,
                company_phone = :company_phone,
                company_email = :company_email,
                company_contact = :company_contact,
                company_accounts_email = :company_accounts_email,
                company_url = :company_url,
				domain_name = :domain_name,
                company_abn = :company_abn,
                company_acn = :company_acn,
                bank_account_name = :bank_account_name,
                bank_name = :bank_name,
                bank_branch = :bank_branch,
                bank_bsb = :bank_bsb,
                bank_account = :bank_account
                WHERE id = :company_id";

    $result = $conn->prepare($query);
	$result->bindParam(':company_name', $_POST['company_name']);
    $result->bindParam(':company_address', $_POST['company_address']);
    $result->bindParam(':company_suburb', $_POST['company_suburb']);
    $result->bindParam(':company_state', $_POST['company_state']);
    $result->bindParam(':company_postode', $_POST['company_postode']);
    $result->bindParam(':company_country', $_POST['company_country']);
    $result->bindParam(':company_phone', $_POST['company_phone']);
    $result->bindParam(':company_email', $_POST['company_email']);
    $result->bindParam(':company_contact', $_POST['company_contact']);
    $result->bindParam(':company_accounts_email', $_POST['company_accounts_email']);

    $result->bindParam(':company_url', $_POST['company_url']);
	$result->bindParam(':domain_name', $_POST['domain_name']);

    $result->bindParam(':company_abn', $_POST['company_abn']);
    $result->bindParam(':company_acn', $_POST['company_acn']);
    $result->bindParam(':bank_account_name', $_POST['bank_account_name']);
    $result->bindParam(':bank_name', $_POST['bank_name']);
    $result->bindParam(':bank_branch', $_POST['bank_branch']);
    $result->bindParam(':bank_bsb', $_POST['bank_bsb']);
    $result->bindParam(':bank_account', $_POST['bank_account']);
    $result->bindParam(':company_id', $company_id); // Correct the parameter name here
    $result->execute();
    if ($result->execute()) {
			echo json_encode(['message' => 'User profile updated successfully']);
			} else {
			echo json_encode(['error' => 'Failed to update user profile']);
			}
	$conn = null;

}
if ($action == 'add_user') {
    requireProfileCsrf();
    $username = $_POST['username'] . '@' . $_POST['domain_name_user'];

    // Check if the username already exists
    $database = new Database();
    $conn = $database->connect();
    $checkQuery = "SELECT COUNT(*) AS count FROM tblUsers WHERE username = :username";
    $checkResult = $conn->prepare($checkQuery);
    $checkResult->bindParam(':username', $username);
    $checkResult->execute();
    $userCount = $checkResult->fetch(PDO::FETCH_ASSOC)['count'];

    if ($userCount > 0) {
        // Username already exists, handle error
        $response = ['error' => 'Username already exists'];
        echo json_encode($response);
        exit;
    }

    // If the username doesn't exist, proceed with user insertion
    $active = 1;
    $user_added = time();
    $password = RandomString(10);
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $query = "INSERT INTO tblUsers 
              (firstname, lastname, username, password, company_id, email, mobile, job_position, timezone, user_added, active)
              VALUES 
              (:firstname, :lastname, :username, :password, :company_id, :email, :mobile, :job_position, :timezone, :user_added, :active)";

    $result = $conn->prepare($query);
    $result->bindParam(':firstname', $_POST['firstname']);
    $result->bindParam(':lastname', $_POST['lastname']);
    $result->bindParam(':username', $username);
    $result->bindParam(':password', $hashedPassword);
    $result->bindParam(':company_id', $_SESSION['session_company_id']);
    $result->bindParam(':email', $_POST['email']);
    $result->bindParam(':mobile', $_POST['mobile']);
    $result->bindParam(':job_position', $_POST['job_position']);
    $result->bindParam(':timezone', $_POST['timezone']);
    $result->bindParam(':user_added', $user_added);
    $result->bindParam(':active', $active);

    if ($result->execute()) {
        $lastInsertId = $conn->lastInsertId();
        sendNewUserEmail($_POST['email']);
        $response = ['message' => 'User profile inserted successfully', 'user_id' => $lastInsertId];
    } else {
        header('HTTP/1.1 500 Internal Server Error'); // Set appropriate HTTP status code for error
        $response = ['error' => 'Failed to insert user profile'];
    }

    $conn = null;
    echo json_encode($response);
}


if ($action == 'update_user') {
    requireProfileCsrf();
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers 
              SET 
                firstname = :firstname,
                lastname = :lastname,
                email = :email,
                mobile = :mobile,
				job_position = :job_position,
              timezone = :timezone
              WHERE id = :user_id";

    $result = $conn->prepare($query);
    $result->bindParam(':firstname', $_POST['firstname']);
    $result->bindParam(':lastname', $_POST['lastname']);
    $result->bindParam(':email', $_POST['email']);
    $result->bindParam(':mobile', $_POST['mobile']);
	$result->bindParam(':job_position', $_POST['job_position']);
    $result->bindParam(':timezone', $_POST['timezone']);
    $result->bindParam(':user_id', $_POST['user_id']);

    if ($result->execute()) {
        echo json_encode(['message' => 'User profile updated successfully']);
    } else {
        echo json_encode(['error' => 'Failed to update user profile']);
    }
    $conn = null;
}

if ($action == 'update_user_password') {
    requireProfileCsrf();

    // Validate and sanitize input data
    $newPassword = $_POST['newPassword1']; // Assuming newPassword1 is the name attribute of the input field for the new password
    // Perform additional validation if needed (e.g., password complexity checks)

    // Hash the new password using bcrypt
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);

    // Proceed with updating the user's password in the database
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers 
              SET 
              password = :password
              WHERE id = :user_id";

    $result = $conn->prepare($query);
    $result->bindParam(':password', $hashedPassword);
    $result->bindParam(':user_id', $_POST['user_id']); // Assuming user_id is stored in session

    if ($result->execute()) {
        echo json_encode(['success' => true, 'message' => 'User password updated successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to update user password']);
    }
    $conn = null;
}




if ($action == 'delete_user') {
    requireProfileCsrf();
    $database = new Database();
    $conn = $database->connect();
    $query = "DELETE FROM tblUsers WHERE id = :user_id";

    $result = $conn->prepare($query);

    $result->bindParam(':user_id', $_POST['user_id']);

    if ($result->execute()) {
        echo json_encode(['message' => 'User deleted  successfully']);
    } else {
        echo json_encode(['error' => 'Failed to delete user profile']);
    }
    $conn = null;
}




if ($action == 'read_user_profile') {
    requireProfileCsrf();
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();

    $query = "SELECT * FROM tblUsers WHERE id = :user_id";
    $result = $conn->prepare($query);
    $result->bindParam(':user_id', $_POST['user_id']); // Correct the parameter name here

    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		if($row['user_image_path']){
				$user_image_path = $row['user_image_path'];
				}
				else{
					$user_image_path = getTableColField('company_image_path', 'tblCompany', 'id' ,$row['company_id']);
				}
		
        $row_data = array(
            'firstname' => $row['firstname'],
            'lastname' => $row['lastname'],
            'username' => $row['username'],
            'password' => $row['password'],
            'company_id' => $row['company_id'],
			'email' => $row['email'],
			'mobile' => $row['mobile'],
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
    echo json_encode($return_arr);
    $conn = null;
}
?>
