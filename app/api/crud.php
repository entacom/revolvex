<?
session_start();

$isUploadRequest = isset($_GET['upload_user_photo']) || isset($_GET['upload_job_photo']) || isset($_GET['upload_company_file']) || isset($_GET['upload_order_file']);
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

if ($isUploadRequest) {
    ob_start();
    register_shutdown_function(function () {
        $error = error_get_last();
        $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR);
        if ($error && in_array($error['type'], $fatalTypes, true)) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $diagnosticId = 'UP-' . date('YmdHis') . '-' . substr(md5($error['message'] . $error['file'] . $error['line']), 0, 8);
            error_log('Upload fatal error ' . $diagnosticId . ': ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store');
            header('X-Content-Type-Options: nosniff');
            echo json_encode(array(
                'success' => false,
                'error' => 'Upload failed on the server. Please contact support with diagnostic ' . $diagnosticId . '.'
            ));
        }
    });
}

include("../includes/common.php");
header('X-Content-Type-Options: nosniff');

function uploadJsonResponse($payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload);
    exit;
}

function rejectDisabledUpload($message) {
    uploadJsonResponse(array('success' => false, 'error' => $message), 410);
}

function requireUploadSession() {
    if (empty($_SESSION['session_user_id']) || empty($_SESSION['session_company_id'])) {
        uploadJsonResponse(array('success' => false, 'error' => 'Authentication required.'), 401);
    }
}

function requirePostUpload() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        uploadJsonResponse(array('success' => false, 'error' => 'POST required.'), 405);
    }
}

function getUploadedFileOrFail() {
    if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
        uploadJsonResponse(array('success' => false, 'error' => 'No file uploaded.'), 400);
    }

    $file = $_FILES['file'];
    if (!isset($file['error']) || is_array($file['error'])) {
        uploadJsonResponse(array('success' => false, 'error' => 'Invalid upload payload.'), 400);
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        uploadJsonResponse(array('success' => false, 'error' => 'Upload failed.'), 400);
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        uploadJsonResponse(array('success' => false, 'error' => 'Invalid uploaded file.'), 400);
    }

    $maxBytes = 10 * 1024 * 1024;
    if (empty($file['size']) || $file['size'] > $maxBytes) {
        uploadJsonResponse(array('success' => false, 'error' => 'File must be between 1 byte and 10 MB.'), 400);
    }

    return $file;
}

function detectUploadMimeType($tmpFile) {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mimeType = finfo_file($finfo, $tmpFile);
            finfo_close($finfo);
            if ($mimeType) {
                return $mimeType;
            }
        }
    }

    if (function_exists('mime_content_type')) {
        $mimeType = mime_content_type($tmpFile);
        if ($mimeType) {
            return $mimeType;
        }
    }

    return 'application/octet-stream';
}

function validateOrderUploadFile($file) {
    $originalName = basename($file['name']);
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $tmpFile = $file['tmp_name'];
    $mimeType = detectUploadMimeType($tmpFile);

    $allowedExtensions = array('pdf', 'jpg', 'jpeg', 'msg');
    if (!in_array($extension, $allowedExtensions, true)) {
        uploadJsonResponse(array('success' => false, 'error' => 'Only PDF, JPG/JPEG, and MSG files are allowed.'), 400);
    }

    if ($extension === 'pdf') {
        $header = file_get_contents($tmpFile, false, null, 0, 5);
        if ($header !== '%PDF-' || !in_array($mimeType, array('application/pdf', 'application/octet-stream', 'application/x-pdf'), true)) {
            uploadJsonResponse(array('success' => false, 'error' => 'Invalid PDF file.'), 400);
        }
    }

    if (in_array($extension, array('jpg', 'jpeg'), true)) {
        $imageInfo = @getimagesize($tmpFile);
        if ($imageInfo === false || (int)$imageInfo[2] !== IMAGETYPE_JPEG || $mimeType !== 'image/jpeg') {
            uploadJsonResponse(array('success' => false, 'error' => 'Invalid JPEG file.'), 400);
        }
    }

    if ($extension === 'msg') {
        $header = file_get_contents($tmpFile, false, null, 0, 8);
        $oleHeader = "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1";
        if ($header !== $oleHeader) {
            uploadJsonResponse(array('success' => false, 'error' => 'Invalid Outlook MSG file.'), 400);
        }
    }

    return array($originalName, $extension);
}

function requireOrderOwnership($conn, $order_id, $company_id) {
    $query = "SELECT COUNT(*) FROM tblOrders WHERE order_id = :order_id AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();

    if ((int)$stmt->fetchColumn() !== 1) {
        uploadJsonResponse(array('success' => false, 'error' => 'Order not found.'), 404);
    }
}

if (isset($_GET['upload_user_photo']) || isset($_GET['upload_job_photo']) || isset($_GET['upload_company_file'])) {
    rejectDisabledUpload('This upload endpoint is disabled. Uploads are currently only enabled for order attachments.');
}

if (isset($_GET['upload_order_file'])) {
    try {
        requirePostUpload();
        requireUploadSession();

        $order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
        if ($order_id < 1) {
            uploadJsonResponse(array('success' => false, 'error' => 'Invalid order id.'), 400);
        }

        $company_id = (int)$_SESSION['session_company_id'];
        $user_id = (int)$_SESSION['session_user_id'];
        $type_id = 15;
        $targetDir = "aws_S3_bucket";

        $file = getUploadedFileOrFail();
        list($fileName, $fileExtension) = validateOrderUploadFile($file);

        $database = new Database();
        $conn = $database->connect();
        requireOrderOwnership($conn, $order_id, $company_id);

        $randomizedFileName = bin2hex(random_bytes(16)) . '.' . $fileExtension;
        $remoteFile = 'order_files/' . $randomizedFileName;
        $uploadResult = uploadFileToS3($file['tmp_name'], $remoteFile);

        if (strpos($uploadResult, 'Error:') !== false) {
            error_log("Error uploading the file to S3: " . $uploadResult);
            uploadJsonResponse(array('success' => false, 'error' => 'Error uploading the file. Please try again later.'), 500);
        }

        $sql = "INSERT INTO tblOrderFiles (order_id, company_id, type_id, path, filename, description, added, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            error_log("Error preparing order file insert: " . $conn->errorInfo()[2]);
            uploadJsonResponse(array('success' => false, 'error' => 'Error saving file details.'), 500);
        }

        $description = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8');
        $stmt->bindValue(1, $order_id, PDO::PARAM_INT);
        $stmt->bindValue(2, $company_id, PDO::PARAM_INT);
        $stmt->bindValue(3, $type_id, PDO::PARAM_INT);
        $stmt->bindValue(4, $targetDir, PDO::PARAM_STR);
        $stmt->bindValue(5, $randomizedFileName, PDO::PARAM_STR);
        $stmt->bindValue(6, $description, PDO::PARAM_STR);
        $stmt->bindValue(7, time(), PDO::PARAM_INT);
        $stmt->bindValue(8, $user_id, PDO::PARAM_INT);

        if (!$stmt->execute()) {
            error_log("Failed to store order file details: " . $stmt->errorInfo()[2]);
            uploadJsonResponse(array('success' => false, 'error' => 'Failed to store file details.'), 500);
        }

        uploadJsonResponse(array(
            'success' => true,
            'message' => 'File uploaded successfully.',
            'filename' => $randomizedFileName
        ));
    } catch (Exception $e) {
        error_log('Upload exception: ' . $e->getMessage());
        uploadJsonResponse(array('success' => false, 'error' => 'Upload exception: ' . $e->getMessage()), 500);
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $rawBody = file_get_contents("php://input");
    $orderFileDeleteData = json_decode($rawBody, true);
    if (isset($orderFileDeleteData['action']) && $orderFileDeleteData['action'] === 'delete_order_file') {
        requireUploadSession();

        $file_id = isset($orderFileDeleteData['file_id']) ? (int)$orderFileDeleteData['file_id'] : 0;
        $order_id = isset($orderFileDeleteData['order_id']) ? (int)$orderFileDeleteData['order_id'] : 0;
        $company_id = (int)$_SESSION['session_company_id'];

        if ($file_id < 1 || $order_id < 1) {
            uploadJsonResponse(array('success' => false, 'error' => 'Invalid file or order id.'), 400);
        }

        $database = new Database();
        $conn = $database->connect();

        $query = "SELECT id, filename FROM tblOrderFiles WHERE id = :file_id AND order_id = :order_id AND company_id = :company_id";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
        $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $stmt->execute();
        $file = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$file) {
            uploadJsonResponse(array('success' => false, 'error' => 'File not found for this order.'), 404);
        }

        $deleteQuery = "DELETE FROM tblOrderFiles WHERE id = :file_id AND order_id = :order_id AND company_id = :company_id";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bindValue(':file_id', $file_id, PDO::PARAM_INT);
        $deleteStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $deleteStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);

        if (!$deleteStmt->execute()) {
            error_log("Failed to delete order file row: " . $deleteStmt->errorInfo()[2]);
            uploadJsonResponse(array('success' => false, 'error' => 'Failed to delete file record.'), 500);
        }

        uploadJsonResponse(array('success' => true, 'message' => 'File deleted from this order.'));
    }
}

// new global site post requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_raw = json_decode(file_get_contents("php://input"), true);
	
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data_raw = json_decode(file_get_contents("php://input"), true);

        if (isset($data_raw['action']) && $data_raw['action'] == 'check_accounting') {
            $return_arr = array();
            $accounting_script = '';
            if (getFieldColumn('myob_option', 'tblAccounting', 'company_id', $_SESSION['session_company_id'])) {
                $accounting_script = 'myob';
            } elseif (getFieldColumn('xero_option', 'tblAccounting', 'company_id', $_SESSION['session_company_id'])) {
                $accounting_script = 'xero';
            }

            $row_data = array(
                'accounting_script' => $accounting_script,
            );
            array_push($return_arr, $row_data);

            header('Content-Type: application/json');
            echo json_encode($return_arr);
        }
    }

    
	if (isset($data_raw['action']) && $data_raw['action'] == 'delete_project_file') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $fileId = $data['file_id'];
        
        // Retrieve file info for unlink
        $query = "SELECT path, filename FROM tblProjectFiles WHERE id = :fileId";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':fileId', $fileId, PDO::PARAM_INT);
        $stmt->execute();

        if ($file = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $filePath = $file['path'] . '/' . $file['filename'];
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file from the server
            }

            // Now, delete the record from the database
            $deleteQuery = "DELETE FROM tblProjectFiles WHERE id = :fileId";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->bindParam(':fileId', $fileId, PDO::PARAM_INT);

            if ($deleteStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'File deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete the record from the database.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'File not found.']);
        }

        $conn = null;
        exit;
    }
	
if (isset($data_raw['action']) && $data_raw['action'] == 'get_country_states') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblCountryStates WHERE active = 1";
    $result = $conn->prepare($query);
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'id' => $row['id'],
            'state' => $row['state'],
        );
        array_push($return_arr, $row_data);
    }
    header('Content-Type: application/json');
    echo json_encode($return_arr);
}

	if (isset($data_raw['action']) && $data_raw['action'] == 'get_vendor') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblVendor WHERE company_id= :company_id";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'vendor_name' => $row['vendor_name'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
	
	if (isset($data_raw['action']) && $data_raw['action'] == 'get_access_levels') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblUsersGroups WHERE group_id BETWEEN 11 AND 30 AND active =1 ";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
       // $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['group_id'],
             				'user_group' => $row['user_group'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
    
    if (isset($data_raw['action']) && $data_raw['action'] == 'get_users_company') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblUsers WHERE company_id= :company_id AND project_id =0 AND vendor_id = 0 AND active =1  ORDER BY first_lastname";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'fullname' => $row['first_lastname'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
	 if (isset($data_raw['action']) && $data_raw['action'] == 'get_source') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblClientSource WHERE company_id= :company_id  AND active =1  ORDER BY description";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'description' => $row['description'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
    if (isset($data_raw['action']) && $data_raw['action'] == 'get_order_status') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblOrderStatus WHERE company_id= :company_id  AND active =1  ORDER BY ordering";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
                            'ordering' => $row['ordering'],
             				'description' => $row['description'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
     if (isset($data_raw['action']) && $data_raw['action'] == 'get_purchase_status') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblPurchaseStatus WHERE company_id= :company_id  AND active =1  ORDER BY description";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'description' => $row['description'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }   
    if (isset($data_raw['action']) && $data_raw['action'] == 'get_inventory_group') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblInventoryGroup WHERE company_id= :company_id  AND active =1  ORDER BY description";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'description' => $row['description'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
    if (isset($data_raw['action']) && $data_raw['action'] == 'get_product_source') {
		$raw_material=1;
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblInventory WHERE company_id= :company_id  AND raw_material= :raw_material AND active =1  ORDER BY description";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
		$result->bindParam(':raw_material', $raw_material); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'part_number' => $row['part_number'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }

if (isset($data_raw['action']) && $data_raw['action'] == 'get_inventory_unit') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblItemUnits WHERE company_id= :company_id  AND active =1  ORDER BY description";
        $result = $conn->prepare($query);
        //$result->bindParam(':project_id', $data['project_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                            'id' => $row['id'],
             				'description' => $row['description'],
                
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }

    $conn = null; 
}
if (isset($data_raw['action']) && $data_raw['action'] == 'get_invoiced_part_numbers') {
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT id, part_number FROM tblInvoice WHERE company_id = :company_id GROUP BY part_number ORDER BY part_number";
    $result = $conn->prepare($query);
    $result->bindParam(':company_id', $_SESSION['session_company_id']); 
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'id' => $row['id'],
            'part_number' => $row['part_number'],
        );
        array_push($return_arr, $row_data);
    }
    header('Content-Type: application/json');
    echo json_encode($return_arr);
}
if (isset($_GET['upload_user_photo'])) {
    $targetDir = "../files/company/";
    $user_id = $_GET['user_id'];
	$original_filename =getTableField('user_image_path', 'tblUsers', $user_id);
	unlink($original_filename);

    if (!empty($_FILES)) {
        $tempFile = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Validate file type using MIME type
        $allowedMimeTypes = ['image/jpeg'];
        $fileMimeType = mime_content_type($tempFile);
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            die(json_encode(array("error" => "Invalid file type. Only JPEG files are allowed.")));
        }

        // Load the image using Exif
        $image = imagecreatefromjpeg($tempFile);

        // Check for EXIF data and get image orientation
        $exifData = @exif_read_data($tempFile);
        $orientation = isset($exifData['Orientation']) ? $exifData['Orientation'] : 1;

        // Apply appropriate rotation based on orientation
        switch ($orientation) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
            // Add more cases if needed to handle other orientations
        }

        // Get the original image dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Calculate new dimensions for cropping while maintaining center content
        $cropSize = min($originalWidth, $originalHeight);
        $cropWidth = 400;
        $cropHeight = 400;
        $cropX = ($originalWidth - $cropSize) / 2;
        $cropY = ($originalHeight - $cropSize) / 2;

        // Create a new image with the cropped content
        $croppedImage = imagecrop($image, ['x' => $cropX, 'y' => $cropY, 'width' => $cropSize, 'height' => $cropSize]);

        if ($croppedImage !== false) {
            // Generate a randomized filename
            $randomString = RandomString(24); // Change the length as needed
            $randomizedFileName = $randomString . '.' . $fileExtension;
            $targetFile = $targetDir . $randomizedFileName;

            // Resize the cropped image to the desired dimensions (1024x1024)
            $resizedCroppedImage = imagescale($croppedImage, $cropWidth, $cropHeight);

            // Save the resized cropped image with the randomized filename
            imagejpeg($resizedCroppedImage, $targetFile);

            // Free up memory
            imagedestroy($resizedCroppedImage);
            imagedestroy($croppedImage);
            imagedestroy($image);

            // File uploaded, cropped, resized, and orientation corrected successfully
            $database = new Database();
            $conn = $database->connect();

            $updateSql = "UPDATE tblUsers SET user_image_path = ? WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);

            if ($updateStmt === false) {
                die(json_encode(array("error" => "Error preparing update statement: " . $conn->errorInfo()[2])));
            }

            // Bind parameters using bindValue for PDO prepared statements
            $updateStmt->bindValue(1, $targetFile);
            $updateStmt->bindValue(2, $user_id);

            // Execute the update statement
            if ($updateStmt->execute()) {
                $response = array(
                    'image_path' => $targetFile,
                    'message' => "File uploaded, cropped, resized, orientation corrected, and user's id updated successfully."
                );
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                $error = "Failed to update user's id: " . $updateStmt->errorInfo()[2];
                echo json_encode(array("error" => $error));
            }

            // Close statement
            $updateStmt->closeCursor();
        } else {
            echo json_encode(array("error" => "Failed to store file details in the database."));
        }
    } else {
        echo json_encode(array("error" => "No file received."));
    }
	
	
}

if (isset($_GET['upload_job_photo'])) {
    $targetDir = "../files/job_photo/";
	$project_id = $_GET['project_id'];
	$company_id = $_SESSION['session_company_id'];
    if (!empty($_FILES)) {
        $tempFile = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Validate file type using MIME type
        $allowedMimeTypes = ['image/jpeg'];
        $fileMimeType = mime_content_type($tempFile);
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            die("Error: Invalid file type. Only JPEG files are allowed.");
        }

        // Load the image using Exif
        $image = imagecreatefromjpeg($tempFile);

        // Check for EXIF data and get image orientation
        $exifData = @exif_read_data($tempFile);
        $orientation = isset($exifData['Orientation']) ? $exifData['Orientation'] : 1;

        // Apply appropriate rotation based on orientation
        switch ($orientation) {
            case 3:
                $image = imagerotate($image, 180, 0);
                break;
            case 6:
                $image = imagerotate($image, -90, 0);
                break;
            case 8:
                $image = imagerotate($image, 90, 0);
                break;
            // Add more cases if needed to handle other orientations
        }

        // Get the original image dimensions
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Calculate new dimensions for cropping while maintaining center content
        $cropSize = min($originalWidth, $originalHeight);
        $cropWidth = 1024;
        $cropHeight = 1024;
        $cropX = ($originalWidth - $cropSize) / 2;
        $cropY = ($originalHeight - $cropSize) / 2;

        // Create a new image with the cropped content
        $croppedImage = imagecrop($image, ['x' => $cropX, 'y' => $cropY, 'width' => $cropSize, 'height' => $cropSize]);

        if ($croppedImage !== false) {
            // Generate a randomized filename
            $randomString = RandomString(24); // Change the length as needed
            $randomizedFileName = $randomString . '.' . $fileExtension;
            $targetFile = $targetDir . $randomizedFileName;

            // Resize the cropped image to the desired dimensions (1024x1024)
            $resizedCroppedImage = imagescale($croppedImage, $cropWidth, $cropHeight);

            // Save the resized cropped image with the randomized filename
            imagejpeg($resizedCroppedImage, $targetFile);

            // Free up memory
            imagedestroy($resizedCroppedImage);
            imagedestroy($croppedImage);
            imagedestroy($image);
			 // S3 Upload
            $remoteFile = $project_id.'/' . $randomizedFileName;
            $uploadResult = uploadFileToS3($targetFile, $remoteFile);
            if (strpos($uploadResult, 'Error:') === false) {
                // S3 upload successful, proceed with database insertion	
            // File uploaded, cropped, resized, and orientation corrected successfully
            $database = new Database();
            $conn = $database->connect();

            // Prepare and execute SQL statement using PDO
            $sql = "INSERT INTO tblProjectFiles (project_id, company_id, type_id, path, filename, description, added, user_id, private_file) VALUES (?, ?, ?, ? , ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            if ($stmt === false) {
                die("Error preparing statement: " . $conn->errorInfo()[2]);
            }
			$private_file=0;
			 $description = $fileName;
			//project_id=5000;
			$type_id=10;
            // Bind parameters using bindValue for PDO prepared statements
            $stmt->bindValue(1, $project_id);
			$stmt->bindValue(2, $company_id);
			$stmt->bindValue(3, $type_id);
			$stmt->bindValue(4, $targetDir);
			$stmt->bindValue(5, $randomizedFileName);
			$stmt->bindValue(6, $description);
			$stmt->bindValue(7, time());
			$stmt->bindValue(8, $_SESSION['session_user_id']);
			$stmt->bindValue(9, $private_file);
            // Execute the prepared statement
                       if ($stmt->execute()) {
                    echo "File uploaded to S3 and details stored in the database.";
                } else {
                    echo "Failed to store file details in the database: " . $stmt->errorInfo()[2];
                }
                $stmt->closeCursor();
            } else {
                echo "Error uploading the file to S3: " . $uploadResult;
            }

            // Delete the temporary file
            unlink($targetFile);
        } else {
            echo "Error cropping the image.";
        }
    }
}

if (isset($_GET['upload_order_file'])) {
    // Define the S3 bucket directory
    $targetDir = "aws_S3_bucket";
    $order_id = $_GET['order_id'];
    $company_id = $_SESSION['session_company_id'];
    $type_id = 15;

    if (!empty($_FILES)) {
        $tempFile = $_FILES['file']['tmp_name'];
        $fileName = basename($_FILES['file']['name']);
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowed MIME types and extensions
        $allowedMimeTypes = ['application/pdf', 'image/jpeg', 'application/vnd.ms-outlook'];
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'msg'];
        $fileMimeType = mime_content_type($tempFile);

        // Check if the file MIME type and extension are allowed
        if (!in_array($fileMimeType, $allowedMimeTypes) || !in_array($fileExtension, $allowedExtensions)) {
            die("Error: Invalid file type.");
        }

        // Optional: Scan the file for viruses (requires integration with an antivirus scanner)
        // Example: $scanResult = scanFileForViruses($tempFile);
        // if ($scanResult !== true) {
        //     die("Error: File contains a virus.");
        // }

        // Generate a secure random filename
        $randomString = bin2hex(random_bytes(16)); // Generates a 32-character random string
        $randomizedFileName = $randomString . '.' . $fileExtension;

        // S3 Upload
        $remoteFile = 'order_files/' . $randomizedFileName; // Define S3 path
        $uploadResult = uploadFileToS3($tempFile, $remoteFile);
        if (strpos($uploadResult, 'Error:') === false) {
            $database = new Database();
            $conn = $database->connect();

            // SQL statement
            $sql = "INSERT INTO tblOrderFiles (order_id, company_id, type_id, path, filename, description, added, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                error_log("Error preparing statement: " . $conn->errorInfo()[2]);
                die("Error preparing statement. Please try again later.");
            }

            $description = htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8'); // Sanitize description

            // Bind and execute
            $stmt->bindValue(1, $order_id, PDO::PARAM_INT);
            $stmt->bindValue(2, $company_id, PDO::PARAM_INT);
            $stmt->bindValue(3, $type_id, PDO::PARAM_INT);
            $stmt->bindValue(4, $targetDir, PDO::PARAM_STR);
            $stmt->bindValue(5, $randomizedFileName, PDO::PARAM_STR);
            $stmt->bindValue(6, $description, PDO::PARAM_STR);
            $stmt->bindValue(7, time(), PDO::PARAM_INT);
            $stmt->bindValue(8, $_SESSION['session_user_id'], PDO::PARAM_INT);

            if ($stmt->execute()) {
                echo "File uploaded to S3 and details stored in the database.";
            } else {
                error_log("Failed to store file details in the database: " . $stmt->errorInfo()[2]);
                echo "Failed to store file details in the database. Please try again later.";
            }
            $stmt->closeCursor();
        } else {
            // S3 upload failed
            error_log("Error uploading the file to S3: " . $uploadResult);
            echo "Error uploading the file to S3. Please try again later.";
        }
    }
}

if (isset($_GET['upload_company_file'])) {
    // Define the S3 bucket directory
    $targetDir = "aws_S3_bucket";
    $company_id = $_SESSION['session_company_id'];
    if (!empty($_FILES)) {
        $tempFile = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

        // Validate file type using MIME type
        $allowedMimeTypes = ['application/pdf'];
        $fileMimeType = mime_content_type($tempFile);
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            die("Error: Invalid file type.");
        }
        $randomString = RandomString(24); // Function to generate a random string
        $randomizedFileName = $randomString . '.' . $fileExtension;

        // S3 Upload
        $remoteFile = $randomizedFileName; // Define S3 path
        $uploadResult = uploadFileToS3($tempFile, $remoteFile);
        if (strpos($uploadResult, 'Error:') === false) {
            // File uploaded to S3 successfully

            $database = new Database();
            $conn = $database->connect();

            // SQL statement
            $sql = "INSERT INTO tblCompanyFiles (company_id, description, vendor_access, path, filename, added, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $conn->errorInfo()[2]);
            }

            $vendor_access = 0;
            $description = $fileName;

            // Bind and execute
            $stmt->bindValue(1, $company_id);
            $stmt->bindValue(2, $description);
            $stmt->bindValue(3, $vendor_access);
			$stmt->bindValue(4, $targetDir);
            $stmt->bindValue(5, $randomizedFileName);
            $stmt->bindValue(6, time());
            $stmt->bindValue(7, $_SESSION['session_user_id']);


            // Execute the prepared statement
            if ($stmt->execute()) {
                echo "File uploaded to S3 and details stored in the database.";
            } else {
                echo "Failed to store file details in the database: " . $stmt->errorInfo()[2];
            }
            $stmt->closeCursor();
        } else {
            // S3 upload failed
            echo "Error uploading the file to S3: " . $uploadResult;
        }
    }
}
