<?php

date_default_timezone_set('Australia/Hobart');
require_once '/home/revolvexcom/web_config_ft.php'; 
class Database {
    private $host = DB_HOST;
    private $db_name = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASSWORD;
    private $conn;
    public function connect() {
        $this->conn = null;
        try { 
            $this->conn = new PDO('mysql:host=' . $this->host . ';dbname=' . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
            // Setting the SQL mode
            $this->conn->exec("SET SESSION sql_mode = 'ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
			
        } catch(PDOException $e) {
            echo 'Connection Error: ' . $e->getMessage();
        }
        return $this->conn;
    }
}

require_once '/home/revolvexcom/vendor/autoload.php';
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
class AwsConfig {
    public static function getS3Client() {
        return new S3Client([
            'version' => 'latest',
            'region' => AWS_REGION,
            'credentials' => [
                'key' => AWS_ACCESS_KEY,
                'secret' => AWS_SECRET_KEY,
            ],
        ]);
    }
}

function isJson($string) {
    if (!is_string($string)) return false;
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

function isLoggedInSession() {
    return !empty($_SESSION['session_user_id']) && !empty($_SESSION['session_company_id']);
}

function requireLoggedInJson($message = 'Authentication required.') {
    if (!isLoggedInSession()) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode(array('success' => false, 'error' => $message));
        exit;
    }
}

function requireLoggedInDownload($message = 'Authentication required.') {
    if (!isLoggedInSession()) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo $message;
        exit;
    }
}


function uploadFileToS3($local_file, $remote_file) {
    $s3 = AwsConfig::getS3Client();
    try {
        $result = $s3->putObject([
            'Bucket' => AWS_BUCKET,
            'Key'    => $remote_file,
            'SourceFile' => $local_file,
            'StorageClass' => 'STANDARD'
        ]);
        return $result->get('ObjectURL');
    } catch (AwsException $e) {
        return "Error: " . $e->getMessage();
    }
}

function downloadFileFromS3($remote_file, $local_file) {
    $s3 = AwsConfig::getS3Client();
    try {
        $s3->getObject([
            'Bucket' => AWS_BUCKET,
            'Key' => $remote_file,
            'SaveAs' => $local_file,
        ]);
        return true;
    } catch (AwsException $e) {
        return "Error: " . $e->getMessage();
    }
}
function generatePreSignedUrl($remote_file, $download_filename = null) {
    // Check if remote_file is empty
    if (empty($remote_file)) {
        return "Error: The file path cannot be empty.";
    }

    $remote_file = str_replace('\\', '/', $remote_file);
    $remote_file = preg_replace('#^\./+#', '', $remote_file);
    $remote_file = ltrim($remote_file, '/');

    $s3 = AwsConfig::getS3Client();
    try {
        $fileExtension = strtolower(pathinfo($remote_file, PATHINFO_EXTENSION));
        $contentTypes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
            // Add more mappings as needed
        ];

        $cmdOptions = [
            'Bucket' => AWS_BUCKET,
            'Key' => $remote_file,
            'ResponseContentType' => $contentTypes[$fileExtension] ?? 'application/octet-stream'
        ];

        if ($download_filename !== null) {
            $cmdOptions['ResponseContentDisposition'] = 'inline; filename="' . basename($download_filename) . '"';
        }

        $command = $s3->getCommand('GetObject', $cmdOptions);
        $request = $s3->createPresignedRequest($command, '+15 minutes');
        return (string) $request->getUri();
    } catch (AwsException $e) {
        return "Error: " . $e->getMessage();
    }
}

function executeDatabaseQuery($conn, $query, $bindings, &$rowCount) {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt) {
            foreach ($bindings as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            if ($stmt->execute()) {
                $rowCount = $stmt->rowCount();
                return true;
            } else {
                echo "Error executing the statement.";
                return false;
            }
        } else {
            http_response_code(500);
            echo "Error in preparing the statement.";
            return false;
        }
    } catch (PDOException $e) {
        http_response_code(500);
        echo "PDO Exception: " . $e->getMessage();
        return false;
    }
}

/*
function confirmUsers($username, $password) {
    $database = new Database();
    $conn = $database->connect();
	$active=1;
    // Use prepared statements to prevent SQL injection
    $query = "SELECT id, password FROM tblUsers WHERE username = :username AND active =:active";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
	$stmt->bindParam(':active', $active, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hashedPassword = $row['password'];
        
        // Use password_verify to check if the provided password matches the stored hash
        if (password_verify($password, $hashedPassword)) {
            // Return the user ID if the login is successful
            return $row['id'];  
        }
    }
    return false;  
}
*/

function getNextOrderId() {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT MAX(order_id)  AS max_id FROM tblOrders";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $max_id = $result['max_id'];
    return $max_id + 1;
}

function getNextPurchaseId() {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT MAX(id)  AS max_id FROM tblPurchaseOrders";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $max_id = $result['max_id'];
    return $max_id + 1;
}
function confirmUsers($username, $password) {
    $database = new Database();
    $conn = $database->connect();

    // Simplified query without vendor checks
    $query = "SELECT * FROM tblUsers WHERE username = :username";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $hashedPassword = $row['password'];

        // Use password_verify to check the password
        if (password_verify($password, $hashedPassword)) {
            return $row['id'];
        } else {
            error_log("Password verification failed.");
        }
    } else {
        error_log("No user found with username: $username");
    }
    return false;
}
function verifyCsrfToken($token) {
    return hash_equals($_SESSION['csrf_token'], $token);
}
function sanInputs($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = sanInputs($value);
        }
    } else {
        // Check if the data is not null before trimming
        if (!is_null($data)) {
            $data = trim($data);
            $data = preg_replace('/[^a-zA-Z0-9_@.\/& -]/', '', $data); // Allow ampersands
            $data = substr($data, 0, 255);
            $data = strip_tags($data);
        }
    }

    return $data;
}

function RandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';

    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }

    return $randomString;
}
function formatPhoneNumber($phoneNumber, $type) {
    // Remove any non-digit characters
    $cleaned = preg_replace('/[^0-9]/', '', $phoneNumber);

    if ($type === 'm') {
        // Format mobile number: 1234 123 123
        if (strlen($cleaned) >= 9) {
            $formatted = sprintf('%s %s %s',
                substr($cleaned, 0, 4),
                substr($cleaned, 4, 3),
                substr($cleaned, 7, 3)
            );
        } else {
            // Return the original number if it doesn't have at least 9 digits
            $formatted = $phoneNumber;
        }
    } elseif ($type === 'l') {
        // Format landline number: (12) 1234 1234 or 1234 1234
        if (strlen($cleaned) == 10) {
            $formatted = sprintf('(%s) %s %s',
                substr($cleaned, 0, 2),
                substr($cleaned, 2, 4),
                substr($cleaned, 6, 4)
            );
        } elseif (strlen($cleaned) == 8) {
            $formatted = sprintf('%s %s',
                substr($cleaned, 0, 4),
                substr($cleaned, 4, 4)
            );
        } else {
            // Return the original number if it doesn't match the expected lengths
            $formatted = $phoneNumber;
        }
    } else {
        // Return the original number if the type is not recognized
        $formatted = $phoneNumber;
    }

    return $formatted;
}



function retrievePage( $page = "" ) {
	// Let's see if the page exists as a .php file
	if( file_exists( "includes/" . $page . ".php") ) {
		// If it does, stop here and we'll use it
		include("includes/" . $page . ".php");
		}
		else {
			if ($_SESSION['session_group_id'] == 1){
			include ("includes/super_admin_subscriptions.php");
			}
			if (in_array($_SESSION['session_group_id'], [11, 12, 13, 14, 15])) {
			include ("includes/admin_dashboard.php");
			}

			 if (in_array($_SESSION['session_group_id'], [16, 17, 18])) {
			include ("includes/admin_dashboard.php");
			}
			if ($_SESSION['session_group_id'] == 80){
			include ("includes/client_dashboard.php");
			}
			
	}
}

function getAppDocumentTitle() {
    $page = isset($_GET['p']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['p']) : 'admin_dashboard';
    $companyName = '';

    if (!empty($_SESSION['session_company_id'])) {
        $companyName = getTableField('company_name', 'tblCompany', $_SESSION['session_company_id']);
    }

    if (empty($companyName)) {
        $companyName = 'RevolveX';
    }

    $titleMap = array(
        'admin_dashboard' => 'Dashboard',
        'company_dashboard' => 'Dashboard',
        'client_dashboard' => 'Dashboard',
        'admin_orders_list' => 'Orders',
        'admin_orders' => 'Order',
        'admin_purchasing_list' => 'Purchases',
        'admin_purchasing' => 'Purchase',
        'admin_inventory' => 'Inventory',
        'admin_production' => 'Production',
        'admin_reports' => 'Reports',
        'admin_git_update' => 'Git Update',
        'admin_company' => 'Company',
        'admin_setup' => 'Setup',
        'admin_source' => 'Sources',
        'admin_order_status' => 'Order Status',
        'admin_purchase_status' => 'Purchase Status',
        'admin_item_groups' => 'Item Groups',
        'admin_item_units' => 'Item Units',
        'admin_myob_connect' => 'MYOB',
        'admin_xero_connect' => 'Xero',
        'admin_users_profile' => 'Profile',
        'super_admin_profile' => 'Profile',
        'super_admin_subscriptions' => 'Subscriptions',
        'super_admin_plans' => 'Plans'
    );

    $title = isset($titleMap[$page]) ? $titleMap[$page] : ucwords(str_replace('_', ' ', $page));

    if ($page === 'admin_orders_list' && isset($_GET['t'])) {
        if ($_GET['t'] == '1') {
            $title = 'Quotes';
        } elseif ($_GET['t'] == '2') {
            $title = 'Orders';
        }
    }

    if ($page === 'admin_orders' && !empty($_GET['order_id']) && !empty($_SESSION['session_company_id'])) {
        $orderId = (int)$_GET['order_id'];
        $customer = getTabFieCol('customer_company', 'tblOrders', 'order_id', $orderId, $_SESSION['session_company_id']);
        $title = 'Order #' . $orderId;
        if (!empty($customer)) {
            $title .= ' - ' . $customer;
        }
    }

    if ($page === 'admin_purchasing' && !empty($_GET['pid']) && !empty($_SESSION['session_company_id'])) {
        $pid = (int)$_GET['pid'];
        $vendor = getTabFieCol('vendor_name', 'tblPurchaseOrders', 'id', $pid, $_SESSION['session_company_id']);
        $title = 'Purchase #' . $pid;
        if (!empty($vendor)) {
            $title .= ' - ' . $vendor;
        }
    }

    return htmlspecialchars($title . ' | ' . $companyName, ENT_QUOTES, 'UTF-8');
}
function verifyToken($token) {
    $database = new Database();
    $conn = $database->connect();

    // Your SQL query to check if the token exists in the database
    $query = "SELECT id FROM tblUsers WHERE token = :token AND active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();

    // Fetch the result
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if the token is valid
    if ($result) {
        // Token exists, return the user ID
        return $result['id'];
    } else {
        // Token does not exist or is invalid, return false
        return false;
    }
}

function startUserSession($user_id) {
    $_SESSION['session_user_id'] = $user_id;
    $_SESSION['session_first_lastname'] = getTableField('first_lastname', 'tblUsers', $user_id);
    $_SESSION['session_company_id'] = getTableField('company_id', 'tblUsers', $user_id);
    $_SESSION['session_group_id'] = getTableField('group_id', 'tblUsers', $user_id);
    $_SESSION['token'] = getTableField('token', 'tblUsers', $user_id);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function createSetupToken($user_id, $ttl_seconds = 86400) {
    $expires = time() + $ttl_seconds;
    $secret = bin2hex(random_bytes(32));
    $publicToken = 'setup_' . $expires . '_' . $secret;
    $storedToken = 'setup_' . $expires . '_' . hash('sha256', $secret);

    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers SET token = :token WHERE id = :id AND active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':token', $storedToken);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);

    if (!$stmt->execute()) {
        return false;
    }

    return $publicToken;
}

function verifySetupToken($token) {
    if (!preg_match('/^setup_(\d{10})_([a-f0-9]{64})$/', $token, $matches)) {
        return false;
    }

    $expires = (int)$matches[1];
    if ($expires < time()) {
        return false;
    }

    $storedToken = 'setup_' . $expires . '_' . hash('sha256', $matches[2]);

    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT id, password FROM tblUsers WHERE token = :token AND active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':token', $storedToken);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result || !empty($result['password'])) {
        return false;
    }

    return $result['id'];
}

function consumeSetupToken($user_id) {
    $emptyToken = '';
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblUsers SET token = :token WHERE id = :id AND token LIKE 'setup_%'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':token', $emptyToken);
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    return $stmt->execute();
}

function generateAccessToken($company_id, $project_id, $group_id){
    $database = new Database();
    $conn = $database->connect();

    $first_lastname = getJobField('primary_contact', 'tblProjects', $project_id, $company_id);
    $username = getJobField('primary_email', 'tblProjects', $project_id, $company_id);
    //$password = RandomString(10);
    //$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $user_added = time();
    $active = 1;

 // Check if the username already exists in the database
    $checkUsernameSql = $conn->prepare("SELECT COUNT(*) AS count FROM tblUsers WHERE username = :username AND vendor_id=0 ");
    $checkUsernameSql->bindParam(':username', $username);
    $checkUsernameSql->execute();
    $usernameExists = $checkUsernameSql->fetch(PDO::FETCH_ASSOC);
     if ($usernameExists['count'] > 0) {
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'message' => 'Username already exists'));
        exit();
    	}
	if (!$username) {
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'message' => 'Missing Email'));
        exit();
    	}
    // Generate a random access token
    $token = bin2hex(random_bytes(64)); // Change the byte size according to your needs

    // If the token doesn't exist and username is unique, insert it into the database for the new project_id
    $insertSql = $conn->prepare("INSERT INTO tblUsers (first_lastname, username, company_id, group_id, project_id, email, token, user_added, active) VALUES (:first_lastname, :username, :company_id, :group_id, :project_id, :email, :token, :user_added, :active)");
    $insertSql->bindParam(':first_lastname', $first_lastname);
    $insertSql->bindParam(':username', $username);
    $insertSql->bindParam(':company_id', $company_id);
    $insertSql->bindParam(':group_id', $group_id);
    $insertSql->bindParam(':project_id', $project_id);
    $insertSql->bindParam(':email', $username);
    $insertSql->bindParam(':token', $token);
    $insertSql->bindParam(':user_added', $user_added);
    $insertSql->bindParam(':active', $active);

    $insertSql->execute();
    $response = array('success' => true, 'token' => $token);
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
function generateVendorAccess($company_id,$vendor_id,$firstname,$lastname,$email){
    $database = new Database();
    $conn = $database->connect();
   // $password = RandomString(10);
   // $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
    $user_added = time();
    $active = 1;
	$group_id=31;
    $token = bin2hex(random_bytes(64)); // Change the byte size according to your needs
	$first_lastname=$firstname. ' ' .$lastname;
    $insertSql = $conn->prepare("INSERT INTO tblUsers (first_lastname, firstname, lastname ,username,  company_id, vendor_id, group_id,  email, token, user_added, active) VALUES (:first_lastname, :firstname,:lastname, :username, :company_id, :vendor_id, :group_id, :email, :token, :user_added, :active)");
    $insertSql->bindParam(':first_lastname', $first_lastname);
	$insertSql->bindParam(':firstname', $firstname);
	$insertSql->bindParam(':lastname', $lastname);
    $insertSql->bindParam(':username', $email);
    //$insertSql->bindParam(':password', $hashedPassword);
    $insertSql->bindParam(':company_id', $company_id);
	$insertSql->bindParam(':vendor_id', $vendor_id);
    $insertSql->bindParam(':group_id', $group_id);
    $insertSql->bindParam(':email', $email);
    $insertSql->bindParam(':token', $token);
    $insertSql->bindParam(':user_added', $user_added);
    $insertSql->bindParam(':active', $active);
    $insertSql->execute();
    exit();
}
function formatDate($epoch, $dateFormat = 'd-m-Y') {
    // Check if epoch is numeric and not empty
    if (!empty($epoch) && is_numeric($epoch)) {
        return date($dateFormat, $epoch);
    } else {
        // Log error and return a default value if the epoch is invalid
        error_log("Invalid or empty epoch provided: " . $epoch);
        return ''; // or return any default value you see fit
    }
}

function date_c($field, $dateFormat = 'd-m-Y') {
    if (!empty($field)) {
        $new_date = date($dateFormat, $field);
    } else {
        $new_date = '';
    }

    return $new_date;
}

function date_c_full($field, $dateFormat) {
    // Set default format to 'Y-m-d' if $dateFormat is empty
    if (empty($dateFormat)) {
        $dateFormat = 'Y-m-d';
    }

    if (!empty($field)) {
        // Append time format to the date format
        $new_date = date($dateFormat . ' H:i', $field);
    } else {
        $new_date = '';
    }

    return $new_date;
}
function UpdatePurStatus($id,$order_status_id) {
    $database = new Database();
    $conn = $database->connect();
    $updateStmt = $conn->prepare("UPDATE tblPurchaseOrders SET order_status_id = :order_status_id WHERE id = :id");
    $updateStmt->bindParam(':order_status_id', $order_status_id, PDO::PARAM_INT);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();
}
function revokeId($table, $id) {
    $database = new Database();
    $conn = $database->connect();

    // Fetch the current 'active' status
    $selectStmt = $conn->prepare("SELECT active FROM $table WHERE id = :id");
    $selectStmt->bindParam(':id', $id);
    $selectStmt->execute();
    $currentActive = $selectStmt->fetch(PDO::FETCH_ASSOC)['active'];

    // Toggle the 'active' status
    $newActive = $currentActive ? 0 : 1;

    // Update with the new 'active' status
    $updateStmt = $conn->prepare("UPDATE $table SET active = :active WHERE id = :id");
    $updateStmt->bindParam(':active', $newActive, PDO::PARAM_INT);
    $updateStmt->bindParam(':id', $id, PDO::PARAM_INT);
    $updateStmt->execute();
}
function deleteId($table, $id) {
    $database = new Database();
    $conn = $database->connect();

    $stmt = $conn->prepare("DELETE FROM $table WHERE id = :id");
    if ($stmt) {
        try {
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Item Deleted successfully.']);
            } else {
                echo json_encode(["error" => "No changes were made."]); // No rows affected
            }
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(["error" => "PDO Exception: " . $e->getMessage()]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error in preparing the statement."]);
    }
}

function appTableExists($conn, $tableName) {
    static $cache = array();

    if (isset($cache[$tableName])) {
        return $cache[$tableName];
    }

    try {
        $stmt = $conn->prepare("SHOW TABLES LIKE :table_name");
        $stmt->bindValue(':table_name', $tableName);
        $stmt->execute();
        $cache[$tableName] = (bool)$stmt->fetch(PDO::FETCH_NUM);
    } catch (Exception $e) {
        $cache[$tableName] = false;
    }

    return $cache[$tableName];
}

function userCanPermission($permissionKey, $defaultAllowedGroups = array(1, 11, 12, 13)) {
    $groupId = isset($_SESSION['session_group_id']) ? (int)$_SESSION['session_group_id'] : 0;
    $companyId = isset($_SESSION['session_company_id']) ? (int)$_SESSION['session_company_id'] : 0;

    if ($groupId <= 0 || $companyId <= 0) {
        return false;
    }

    $database = new Database();
    $conn = $database->connect();

    if (appTableExists($conn, 'tblUserGroupPermissions')) {
        $stmt = $conn->prepare("
            SELECT allowed
            FROM tblUserGroupPermissions
            WHERE company_id = :company_id
              AND group_id = :group_id
              AND permission_key = :permission_key
            LIMIT 1
        ");
        $stmt->bindValue(':company_id', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':group_id', $groupId, PDO::PARAM_INT);
        $stmt->bindValue(':permission_key', $permissionKey);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            return (int)$row['allowed'] === 1;
        }
    }

    return in_array($groupId, $defaultAllowedGroups, true);
}

function deleteFieldCol($table, $column, $column_value) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL statement
    $sql = "DELETE FROM $table WHERE $column = :column_value";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        try {
            // Bind the column value
            $stmt->bindParam(':column_value', $column_value);
            $stmt->execute();

            // Check if any rows were affected
            if ($stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Item deleted successfully.']);
            } else {
                echo json_encode(['error' => true, 'message' => 'No changes were made.']); // No rows affected
            }
        } catch (PDOException $e) {
            // Handle PDO exceptions
            http_response_code(500);
            echo json_encode(['error' => true, 'message' => 'PDO Exception: ' . $e->getMessage()]);
        }
    } else {
        // Handle errors in preparing the statement
        http_response_code(500);
        echo json_encode(['error' => true, 'message' => 'Error in preparing the statement.']);
    }
}

function orderActivityWorkflowColumnExists($conn) {
    static $exists = null;

    if ($exists !== null) {
        return $exists;
    }

    try {
        $stmt = $conn->query("SHOW COLUMNS FROM tblOrderActivity LIKE 'workflow_type'");
        $exists = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $exists = false;
    }

    return $exists;
}

function addOrderActivity($order_id, $company_id, $type_id, $description, $user_id, $action_date, $workflow_type = '') {
    // Sanitize inputs
    $order_id = sanInputs($order_id);
    $company_id = sanInputs($company_id);
    $type_id = sanInputs($type_id);
    $description = sanInputs($description);
    $user_id = sanInputs($user_id);
    $action_date = sanInputs($action_date);
    $workflow_type = sanInputs($workflow_type);
    if (empty($description)) {
        http_response_code(400); // Set HTTP response code to 400
        echo json_encode(["error" => "Description field cannot be empty!"]);
        exit();
    }

    $database = new Database();
    $conn = $database->connect();

    if (!empty($action_date)) {
        $action_date = strtotime($action_date);
    } else {
        $action_date = time();
    }

    $hasWorkflowType = !empty($workflow_type) && orderActivityWorkflowColumnExists($conn);
    if ($hasWorkflowType) {
        $sql = "INSERT INTO tblOrderActivity (order_id, company_id, type_id, action_date, description, user_id, workflow_type) VALUES (:order_id, :company_id, :type_id, :action_date, :description, :user_id, :workflow_type)";
    } else {
        $sql = "INSERT INTO tblOrderActivity (order_id, company_id, type_id, action_date, description, user_id) VALUES (:order_id, :company_id, :type_id, :action_date, :description, :user_id)";
    }
    $stmt = $conn->prepare($sql);

            $stmt->bindParam(':order_id', $order_id);
            $stmt->bindParam(':company_id', $company_id);
            $stmt->bindParam(':type_id', $type_id);
            $stmt->bindParam(':action_date', $action_date);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':action_date', $action_date);
            if ($hasWorkflowType) {
                $stmt->bindParam(':workflow_type', $workflow_type);
            }
            $stmt->execute();  
}

function addPurchaseActivity($pid, $company_id, $type_id, $description, $user_id, $action_date) {
    $pid = sanInputs($pid);
    $company_id = sanInputs($company_id);
    $type_id = sanInputs($type_id);
    $description = sanInputs($description);
    $user_id = sanInputs($user_id);
    $action_date = sanInputs($action_date);

    if (empty($description)) {
        http_response_code(400);
        echo json_encode(["error" => "Description field cannot be empty!"]);
        exit();
    }

    $database = new Database();
    $conn = $database->connect();

    if (!empty($action_date)) {
        $action_date = strtotime($action_date);
    } else {
        $action_date = time();
    }

    $sql = "INSERT INTO tblPurchaseActivity (pid, company_id, type_id, action_date, description, user_id) VALUES (:pid, :company_id, :type_id, :action_date, :description, :user_id)";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':pid', $pid);
    $stmt->bindParam(':company_id', $company_id);
    $stmt->bindParam(':type_id', $type_id);
    $stmt->bindParam(':action_date', $action_date);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
}


function getUserFullName($id) {
    $database = new Database();
    $conn = $database->connect();

    $sql = $conn->prepare("SELECT first_lastname FROM tblUsers WHERE id = :id");
    $sql->bindParam(':id', $id); // Bind the ID parameter
    $sql->execute();
    $userData = $sql->fetch(PDO::FETCH_ASSOC); 
    if ($userData !== false && isset($userData['first_lastname'])) {
        $fullName = $userData['first_lastname'];
        return $fullName; // Return the full name 
    } else {
        return null; 
    }

    $conn = null; 
}
function countTableFieldX2($field, $table, $column1, $column_value1, $column2, $column_value2) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT COUNT($field) AS field_count FROM $table WHERE $column1 = :column_value1 AND $column2 = :column_value2");
    $sql->bindParam(':column_value1', $column_value1); // Bind the first column value parameter
    $sql->bindParam(':column_value2', $column_value2); // Bind the second column value parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); // Fetch the data as an associative array

    // Check if data is retrieved
    if ($data !== false && isset($data['field_count'])) {
        $count = $data['field_count']; // Access the count value
        return $count;
    } else {
        return 0; // Return 0 if no data is found
    }

    $conn = null; 
}
function countSubs($company_id, $active) {
    $database = new Database();
    $conn = $database->connect();
    $sql = $conn->prepare("SELECT COUNT(id) AS field_count FROM tblUsers WHERE company_id = :company_id AND project_id = 0 AND active = :active");
    $sql->bindParam(':company_id', $company_id); 
    $sql->bindParam(':active', $active); 

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC);
    if ($data !== false && isset($data['field_count'])) {
        $count = $data['field_count']; 
        return $count;
    } else {
        return 0; 
    }

    $conn = null; 
}
function getTableField($field, $table, $id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT $field FROM $table WHERE id = :id");
    $sql->bindParam(':id', $id); // Bind the ID parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data[$field])) {
        $return = $data[$field]; // Access the field by name
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getTableFieldCount($field, $table, $column_name, $column_val) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT COUNT($field) AS total FROM $table WHERE $column_name = :column_val");
    $sql->bindParam(':column_val', $column_val); // Bind the column value parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getTableFieldSum($field, $table, $column_name, $column_val) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM($field) AS total FROM $table WHERE $column_name = :column_val");
    $sql->bindParam(':column_val', $column_val); // Bind the column value parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getLengSumInv($order_id,$inventory_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM(qty*qty_unit) AS total FROM tblInventoryItems WHERE order_id = :order_id AND inventory_id = :inventory_id");
    $sql->bindParam(':order_id', $order_id); // Bind the column value parameter
    $sql->bindParam(':inventory_id', $inventory_id); // Bind the column value parameter
    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getLengSumInve($inventory_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM(qty*qty_unit) AS total FROM tblInventoryItems WHERE  inventory_id = :inventory_id");
    $sql->bindParam(':inventory_id', $inventory_id); // Bind the column value parameter
    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getLengSum($order_group_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM(qty*qty_unit) AS total FROM tblOrderSubItems WHERE order_group_id = :order_group_id");
    $sql->bindParam(':order_group_id', $order_group_id); // Bind the column value parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getLengSumPur($order_group_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM(qty*qty_unit) AS total FROM tblPurchaseSubItems WHERE order_group_id = :order_group_id");
    $sql->bindParam(':order_group_id', $order_group_id); // Bind the column value parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getLengSumBill($order_group_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM(qty*qty_unit) AS total FROM tblBillSubItems WHERE order_group_id = :order_group_id");
    $sql->bindParam(':order_group_id', $order_group_id); // Bind the column value parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['total'])) {
        $return = $data['total']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}
function getSumWei($pack_id,$company_id,$order_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT SUM(weight) AS weight FROM tblOrderSubItems WHERE pack_id = :pack_id AND company_id = :company_id AND order_id = :order_id");
    $sql->bindParam(':pack_id', $pack_id); // Bind the column value parameter
     $sql->bindParam(':company_id', $company_id); // Bind the column value parameter
     $sql->bindParam(':order_id', $order_id); // Bind the column value parameter
    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data['weight'])) {
        $return = $data['weight']; // Access the total sum
        return $return;
    } else {
        return null; // Return null if no data is found
    }

    $conn = null; 
}

function getFieldColumn($field, $table, $column_name, $column_val) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT $field FROM $table WHERE $column_name = :column_val");
    $sql->bindParam(':column_val', $column_val); // Bind the ID parameter

    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC); 

    // Check if data is retrieved
    if ($data !== false && isset($data[$field])) {
        return $data[$field]; // Access the field by name
    } else {
        return null; // Return null if no data is found
    }
}

function getTableColField($field, $table, $column, $id) {
    $database = new Database();
    $conn = $database->connect();
    
    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT $field FROM $table WHERE $column = :id");
    $sql->bindParam(':id', $id); // Bind the ID parameter
    
    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC);
    
    // Check if data is retrieved
    if ($data !== false && isset($data[$field])) {
        $return = $data[$field]; 
        return $return;
    } else {
        return null; 
    }
    $conn = null;
}
function getTableColFieldX2($field, $table, $column1, $value1, $column2, $value2) {
    $database = new Database();
    $conn = $database->connect();
    
    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT $field FROM $table WHERE $column1 = :value1 AND $column2 = :value2");
    $sql->bindParam(':value1', $value1); 
    $sql->bindParam(':value2', $value2); 
    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC);
    
    // Check if data is retrieved
    if ($data !== false && isset($data[$field])) {
        $return = $data[$field]; 
        return $return;
    } else {
        return null; 
    }
    $conn = null;
}
function getTabFieCol($field, $table, $column, $column_value, $company_id) {
    $database = new Database();
    $conn = $database->connect();

    // Prepare the SQL query with placeholders
    $sql = $conn->prepare("SELECT $field FROM $table WHERE $column = :column_value AND company_id = :company_id");
    $sql->bindParam(':column_value', $column_value);
    $sql->bindParam(':company_id', $company_id);
    $sql->execute();
    $data = $sql->fetch(PDO::FETCH_ASSOC);

    // Check if data is retrieved
    if ($data !== false && isset($data[$field])) {
        $return = $data[$field];
        $conn = null;
        return $return;
    } else {
        $conn = null;
        return null;
    }
}

function getMaxField($field, $table, $company_id) {
    $database = new Database();
    $conn = $database->connect();
    
    try {
        $sql = $conn->prepare("SELECT MAX($field) AS max_value FROM $table WHERE company_id = :company_id");
        $sql->bindParam(':company_id', $company_id); 
        $sql->execute();
        $data = $sql->fetch(PDO::FETCH_ASSOC); 
        
        // Check if data is retrieved and handle potential NULL result
        if ($data !== false && isset($data['max_value']) && $data['max_value'] !== null) {
            $return = $data['max_value']; // Access the field by name
            $conn = null; // Close the connection
            return $return;
        } else {
            $conn = null; // Close the connection
            return null; // Return null if no valid data is found
        }
    } catch (PDOException $e) {
        // Handle exceptions or errors (e.g., log the error, return an error message, etc.)
        // Example: log error
        error_log("Error: " . $e->getMessage());
        $conn = null; // Close the connection
        return null; // Return null or an error message
    }
}
function checkSmsBalance() {
    $username = 'entacom';           // Your username
    $password = 'action94Builders';  // Your password
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.smsbroadcast.com.au/api-adv.php?action=balance&username=" . urlencode($username) . "&password=" . urlencode($password));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        return null;
    }
    // Close cURL session
    curl_close($ch);
    // Split the response by colon
    $parts = explode(':', $response);
    // Return the number part if it exists
    return isset($parts[1]) ? trim($parts[1]) : null;
}
function sendSMS($content) {
    $ch = curl_init('https://api.smsbroadcast.com.au/api.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
    }
    curl_close($ch);
    return $output;    
}
function sendVerificationSMS($destination, $text) {
    $username = 'entacom';
    $password = 'action94Builders';
    $destination = $destination;
    $source = 'ActionBuild';
    $ref = 'abc123';
    $content =  'username='.rawurlencode($username).
                '&password='.rawurlencode($password).
                '&to='.rawurlencode($destination).
                '&from='.rawurlencode($source).
                '&message='.rawurlencode($text).
                '&ref='.rawurlencode($ref);

    $smsbroadcast_response = sendSMS($content);
    $response_lines = explode("\n", $smsbroadcast_response);
    foreach( $response_lines as $data_line) {
        $message_data = explode(':', $data_line);
        if ($message_data[0] == "OK") {
            return "The message to ".$message_data[1]." was successful, with reference ".$message_data[2]."\n";
        } elseif ($message_data[0] == "BAD") {
            return "The message to ".$message_data[1]." was NOT successful. Reason: ".$message_data[2]."\n";
        } elseif ($message_data[0] == "ERROR") {
            return "There was an error with this request. Reason: ".$message_data[1]."\n";
        }
    }
}
function insertLogin($session_user_id) {
	$first_lastname=getTableField('first_lastname','tblUsers',$session_user_id);
        $date_now = time();
	$database = new Database();
    $conn = $database->connect();
        $sql = "INSERT INTO tblUsersLogins (user_id, username, login_date) VALUES (:user_id, :username, :login_date)";
        $stmt = $conn->prepare($sql);

            $stmt->bindParam(':user_id', $session_user_id);
            $stmt->bindParam(':username',$first_lastname);
            $stmt->bindParam(':login_date', $date_now);
			 $stmt->execute();


}
function getMaxPackOrder($order_id, $company_id) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT MAX(pack_id) AS max_pack FROM tblOrderSubItems WHERE order_id = :order_id AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $max_id = $result['max_pack'];
    return $max_id;
}
function getWeightPackOrder($order_id, $company_id,$pack_id) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT SUM(weight) AS weight_pack FROM tblOrderSubItems WHERE order_id = :order_id AND company_id = :company_id AND pack_id = :pack_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':pack_id', $pack_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $weight_pack = $result['weight_pack'];
    return $weight_pack;
}
function gePartNumberPack($order_id, $company_id,$pack_id) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT part_number AS part_number FROM tblOrderSubItems WHERE order_id = :order_id AND company_id = :company_id AND pack_id = :pack_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':pack_id', $pack_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $part_number = $result['part_number'];
    return $part_number;
}
function gePartDescriptionPack($order_id, $company_id,$pack_id) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT description AS description FROM tblOrderSubItems WHERE order_id = :order_id AND company_id = :company_id AND pack_id = :pack_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':pack_id', $pack_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $description = $result['description'];
    return $description;
}
function getSumItemLength($order_id, $company_id, $part_number) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT SUM(qty * qty_unit) AS total_length FROM tblOrderSubItems WHERE order_id = :order_id AND company_id = :company_id AND part_number = :part_number";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':part_number', $part_number, PDO::PARAM_STR); // Use PARAM_STR for part_number
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_length = $result['total_length'] ?? 0; // Handle null case
    return $total_length;
}
function getSumItemLengthPur($pid, $company_id, $part_number) {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT SUM(qty * qty_unit) AS total_length FROM tblPurchaseSubItems WHERE pid = :pid AND company_id = :company_id AND part_number = :part_number";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':part_number', $part_number, PDO::PARAM_STR); // Use PARAM_STR for part_number
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_length = $result['total_length'] ?? 0; // Handle null case
    return $total_length;
}
?>
