<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    include("../../includes/common.php");
    requireLoggedInJson();

    $data_raw = json_decode(file_get_contents("php://input"), true);

function purchaseActivityValueChanged($oldValue, $newValue) {
    return trim((string)$oldValue) !== trim((string)$newValue);
}

function purchaseActivityDateText($timestamp) {
    return !empty($timestamp) ? date_c($timestamp) : 'Not set';
}

function purchaseOrderColumnExists($conn, $columnName) {
    static $cache = array();
    $key = 'tblPurchaseOrders.' . $columnName;

    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }

    $stmt = $conn->prepare("SHOW COLUMNS FROM tblPurchaseOrders LIKE :column_name");
    $stmt->bindValue(':column_name', $columnName);
    $stmt->execute();
    $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);

    return $cache[$key];
}

function purchaseConfirmationColumnsExist($conn) {
    return purchaseOrderColumnExists($conn, 'order_confirmation_required')
        && purchaseOrderColumnExists($conn, 'order_confirmation_received')
        && purchaseOrderColumnExists($conn, 'estimated_arrival_date')
        && purchaseOrderColumnExists($conn, 'confirmation_file_key')
        && purchaseOrderColumnExists($conn, 'confirmation_file_name')
        && purchaseOrderColumnExists($conn, 'confirmation_uploaded_at');
}

function purchaseFilesTableExists($conn) {
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }

    $stmt = $conn->prepare("SHOW TABLES LIKE 'tblPurchaseFiles'");
    $stmt->execute();
    $exists = (bool)$stmt->fetch(PDO::FETCH_NUM);

    return $exists;
}

function getPurchaseStatusActivityLabel($status_id, $company_id) {
    if (empty($status_id)) {
        return 'Not set';
    }
    $label = getTabFieCol('description', 'tblPurchaseStatus', 'id', $status_id, $company_id);
    return !empty($label) ? $label : 'Status #' . $status_id;
}

function findPurchaseStatusIdByNames($conn, $company_id, $statusNames) {
    if (empty($statusNames)) {
        return null;
    }

    $placeholders = array();
    $params = array(':company_id' => $company_id);

    foreach ($statusNames as $index => $statusName) {
        $key = ':status_' . $index;
        $placeholders[] = $key;
        $params[$key] = strtolower($statusName);
    }

    $stmt = $conn->prepare("
        SELECT id, description
        FROM tblPurchaseStatus
        WHERE company_id = :company_id
          AND LOWER(TRIM(description)) IN (" . implode(',', $placeholders) . ")
        ORDER BY id ASC
        LIMIT 1
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getPurchaseActivityRow($conn, $pid, $company_id) {
    $query = "SELECT * FROM tblPurchaseOrders WHERE id = :pid AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->execute(array(
        ':pid' => $pid,
        ':company_id' => $company_id
    ));
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function buildPurchaseActivityDescriptions($oldPurchase, $newData, $order_date_required, $order_status_id, $company_id) {
    $descriptions = array();

    if (purchaseActivityValueChanged($oldPurchase['order_status_id'], $order_status_id)) {
        $oldStatus = getPurchaseStatusActivityLabel($oldPurchase['order_status_id'], $company_id);
        $newStatus = getPurchaseStatusActivityLabel($order_status_id, $company_id);
        $descriptions[] = 'Status changed: ' . $oldStatus . ' -> ' . $newStatus;
    }

    if (purchaseActivityValueChanged($oldPurchase['order_date_required'], $order_date_required)) {
        $descriptions[] = 'Required date changed: ' . purchaseActivityDateText($oldPurchase['order_date_required']) . ' -> ' . purchaseActivityDateText($order_date_required);
    }

    $groups = array(
        'Vendor/details updated' => array(
            'vendor_name' => 'Vendor',
            'vendor_address' => 'Vendor address',
            'vendor_suburb' => 'Vendor suburb',
            'vendor_state' => 'Vendor state',
            'vendor_postcode' => 'Vendor postcode',
            'vendor_phone' => 'Vendor phone',
            'vendor_email' => 'Vendor email',
            'purchaser_user_id' => 'Purchaser',
            'ven_inv_number' => 'Vendor invoice number',
            'freight' => 'Freight'
        ),
        'Delivery details updated' => array(
            'delivery_address_line1' => 'Address',
            'delivery_address_suburb' => 'Suburb',
            'delivery_postcode' => 'Postcode',
            'delivery_state' => 'State'
        ),
        'Notes updated' => array(
            'order_notes' => 'Order notes',
            'additional_notes' => 'Additional notes'
        )
    );

    foreach ($groups as $prefix => $fields) {
        $changed = array();
        foreach ($fields as $field => $label) {
            if (array_key_exists($field, $oldPurchase) && array_key_exists($field, $newData) && purchaseActivityValueChanged($oldPurchase[$field], $newData[$field])) {
                $changed[] = $label;
            }
        }
        if (!empty($changed)) {
            $descriptions[] = $prefix . ': ' . implode(', ', $changed);
        }
    }

    return $descriptions;
}

function purchaseProcessActivityActions() {
    return array(
        'purchase_delivery_docket_printed' => 'Printed: Purchase delivery docket',
        'purchase_confirmation_requested' => 'Purchase order confirmation requested',
        'purchase_confirmation_requested_cleared' => 'Purchase order confirmation request cleared',
        'purchase_confirmation_received' => 'Purchase order confirmation received',
        'purchase_order_printed' => 'Printed: Purchase order',
        'purchase_order_emailed' => 'Email sent: Purchase order'
    );
}

function purchaseProcessActivityAliases() {
    return array(
        'purchase_delivery_docket_printed' => array('Printed: Purchase delivery docket', 'Printed Purchase delivery docket'),
        'purchase_confirmation_requested' => array('Purchase order confirmation requested', 'Purchase order confirmation request cleared'),
        'purchase_confirmation_requested_cleared' => array('Purchase order confirmation request cleared'),
        'purchase_confirmation_received' => array('Purchase order confirmation received'),
        'purchase_order_printed' => array('Printed: Purchase order', 'Printed Purchase order'),
        'purchase_order_emailed' => array('Email sent: Purchase order', 'Email sent Purchase order')
    );
}

function getPurchaseProcessActivity($conn, $pid, $company_id) {
    $aliases = purchaseProcessActivityAliases();
    $activityTimestamps = array();
    $history = array(
        'purchase_delivery_docket_printed' => null,
        'purchase_confirmation_requested' => null,
        'purchase_confirmation_received' => null,
        'purchase_order_printed' => null,
        'purchase_order_emailed' => null,
        'meta' => array()
    );

    if (purchaseConfirmationColumnsExist($conn)) {
        $metaStmt = $conn->prepare("
            SELECT order_confirmation_required, order_confirmation_received, estimated_arrival_date, confirmation_file_name
            FROM tblPurchaseOrders
            WHERE id = :pid
              AND company_id = :company_id
            LIMIT 1
        ");
        $metaStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
        $metaStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $metaStmt->execute();
        $meta = $metaStmt->fetch(PDO::FETCH_ASSOC);

        if ($meta) {
            $history['meta'] = array(
                'confirmation_required' => (int)$meta['order_confirmation_required'],
                'confirmation_received' => (int)$meta['order_confirmation_received'],
                'estimated_arrival_date_input' => !empty($meta['estimated_arrival_date']) ? date('Y-m-d', (int)$meta['estimated_arrival_date']) : '',
                'confirmation_file_name' => $meta['confirmation_file_name'],
                'confirmation_overdue' => false
            );
        }
    }

    $params = array(
        ':pid' => $pid,
        ':company_id' => $company_id
    );
    $likeParts = array();
    $index = 0;

    foreach ($aliases as $typeAliases) {
        foreach ($typeAliases as $alias) {
            $key = ':label_' . $index;
            $likeParts[] = "description LIKE " . $key;
            $params[$key] = $alias . '%';
            $index++;
        }
    }

    $stmt = $conn->prepare("
        SELECT action_date, user_id, description
        FROM tblPurchaseActivity
        WHERE pid = :pid
          AND company_id = :company_id
          AND (" . implode(' OR ', $likeParts) . ")
        ORDER BY action_date DESC, id DESC
    ");
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $workflowType = '';
        foreach ($aliases as $type => $typeAliases) {
            foreach ($typeAliases as $alias) {
                if (strpos($row['description'], $alias) === 0) {
                    $workflowType = $type;
                    break 2;
                }
            }
        }

        if ($workflowType === 'purchase_confirmation_requested_cleared') {
            $workflowType = 'purchase_confirmation_requested';
        }

        if (!empty($workflowType) && array_key_exists($workflowType, $history) && $history[$workflowType] === null) {
            $activityTimestamps[$workflowType] = (int)$row['action_date'];
            $history[$workflowType] = array(
                'description' => $row['description'],
                'date' => date('d/m/Y g:i A', (int)$row['action_date']),
                'user' => getUserFullName($row['user_id'])
            );
        }
    }

    if (!empty($history['meta']['confirmation_required']) && empty($history['meta']['confirmation_received'])) {
        $dueBase = 0;
        foreach (array('purchase_order_emailed', 'purchase_order_printed', 'purchase_confirmation_requested') as $type) {
            if (!empty($activityTimestamps[$type])) {
                $dueBase = max($dueBase, (int)$activityTimestamps[$type]);
            }
        }

        if ($dueBase > 0) {
            $history['meta']['confirmation_due_at'] = date('d/m/Y g:i A', $dueBase + (48 * 60 * 60));
            $history['meta']['confirmation_overdue'] = ($dueBase < (time() - (48 * 60 * 60)));
        }
    }

    if (!empty($history['meta']['confirmation_overdue']) && !empty($history['purchase_confirmation_requested'])) {
        $history['purchase_confirmation_requested']['confirmation_overdue'] = true;
    }

    return $history;
}
	
if (isset($_POST['action']) && $_POST['action'] === 'save_purchase_confirmation') {
    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->connect();
    $company_id = (int)$_SESSION['session_company_id'];
    $pid = isset($_POST['pid']) ? (int)$_POST['pid'] : 0;
    $estimatedArrivalDate = isset($_POST['estimated_arrival_date']) ? trim((string)$_POST['estimated_arrival_date']) : '';

    if ($pid <= 0 || empty($estimatedArrivalDate)) {
        echo json_encode(['success' => false, 'message' => 'Missing purchase order or estimated arrival date.']);
        exit;
    }

    if (!purchaseConfirmationColumnsExist($conn)) {
        echo json_encode(['success' => false, 'message' => 'Purchase confirmation columns have not been added to the database yet.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM tblPurchaseOrders WHERE id = :pid AND company_id = :company_id LIMIT 1");
    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Purchase order not found.']);
        exit;
    }

    $etaTimestamp = strtotime($estimatedArrivalDate);
    if (!$etaTimestamp) {
        echo json_encode(['success' => false, 'message' => 'Invalid estimated arrival date.']);
        exit;
    }

    $fileKey = null;
    $fileName = null;
    $uploadedAt = null;

    if (!empty($_FILES['confirmation_file']) && $_FILES['confirmation_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['confirmation_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'Confirmation file upload failed.']);
            exit;
        }

        if ((int)$_FILES['confirmation_file']['size'] > (10 * 1024 * 1024)) {
            echo json_encode(['success' => false, 'message' => 'Confirmation file is too large. Maximum size is 10 MB.']);
            exit;
        }

        $allowedExtensions = array('pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt');
        $fileName = basename($_FILES['confirmation_file']['name']);
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions, true)) {
            echo json_encode(['success' => false, 'message' => 'Confirmation file type is not allowed.']);
            exit;
        }

        $safeName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $fileName);
        if ($safeName === '' || $safeName === '_' || $safeName === '.' || $safeName === '..') {
            $safeName = 'confirmation.' . $extension;
        }
        $fileKey = 'purchase_confirmations/' . $pid . '_' . bin2hex(random_bytes(8)) . '_' . $safeName;
        try {
            $uploadResult = uploadFileToS3($_FILES['confirmation_file']['tmp_name'], $fileKey);
        } catch (Throwable $e) {
            error_log("Could not upload purchase confirmation for PO {$pid}: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Could not upload confirmation file.']);
            exit;
        }

        if (is_string($uploadResult) && strpos($uploadResult, 'Error:') === 0) {
            error_log("Could not upload purchase confirmation for PO {$pid}: " . $uploadResult);
            echo json_encode(['success' => false, 'message' => 'Could not upload confirmation file.']);
            exit;
        }

        $uploadedAt = time();

        if (purchaseFilesTableExists($conn)) {
            try {
                $filesStmt = $conn->prepare("
                    INSERT INTO tblPurchaseFiles (pid, company_id, type_id, path, filename, description, added, user_id)
                    VALUES (:pid, :company_id, :type_id, :path, :filename, :description, :added, :user_id)
                ");
                $filesStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                $filesStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                $filesStmt->bindValue(':type_id', 16, PDO::PARAM_INT);
                $filesStmt->bindValue(':path', 'purchase_confirmations');
                $filesStmt->bindValue(':filename', basename($fileKey));
                $filesStmt->bindValue(':description', $fileName);
                $filesStmt->bindValue(':added', $uploadedAt, PDO::PARAM_INT);
                $filesStmt->bindValue(':user_id', $_SESSION['session_user_id'], PDO::PARAM_INT);
                $filesStmt->execute();
            } catch (Throwable $e) {
                error_log("Could not save purchase confirmation file reference for PO {$pid}: " . $e->getMessage());
            }
        }
    }

    $confirmedStatus = findPurchaseStatusIdByNames($conn, $company_id, array('Confirmed'));
    $statusSql = $confirmedStatus ? ", order_status_id = :order_status_id" : "";

    if ($fileKey) {
        $update = $conn->prepare("
            UPDATE tblPurchaseOrders
            SET order_confirmation_received = 1,
                estimated_arrival_date = :estimated_arrival_date,
                confirmation_file_key = :confirmation_file_key,
                confirmation_file_name = :confirmation_file_name,
                confirmation_uploaded_at = :confirmation_uploaded_at
                " . $statusSql . "
            WHERE id = :pid
              AND company_id = :company_id
        ");
        $update->bindValue(':confirmation_file_key', $fileKey);
        $update->bindValue(':confirmation_file_name', $fileName);
        $update->bindValue(':confirmation_uploaded_at', $uploadedAt, PDO::PARAM_INT);
    } else {
        $update = $conn->prepare("
            UPDATE tblPurchaseOrders
            SET order_confirmation_received = 1,
                estimated_arrival_date = :estimated_arrival_date
                " . $statusSql . "
            WHERE id = :pid
              AND company_id = :company_id
        ");
    }

    $update->bindValue(':estimated_arrival_date', $etaTimestamp, PDO::PARAM_INT);
    if ($confirmedStatus) {
        $update->bindValue(':order_status_id', (int)$confirmedStatus['id'], PDO::PARAM_INT);
    }
    $update->bindValue(':pid', $pid, PDO::PARAM_INT);
    $update->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $update->execute();

    addPurchaseActivity($pid, $company_id, 5, 'Purchase order confirmation received' . ($fileName ? ': ' . $fileName : ''), $_SESSION['session_user_id'], 0);

    echo json_encode([
        'success' => true,
        'message' => 'Purchase confirmation saved.',
        'file_name' => $fileName,
        'estimated_arrival_date_display' => date_c($etaTimestamp),
        'history' => getPurchaseProcessActivity($conn, $pid, $company_id)
    ]);
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'read_purchase') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    
    // Query to fetch purchase order data
    $query = "SELECT * FROM tblPurchaseOrders WHERE id = :pid AND company_id = :company_id";
    $result = $conn->prepare($query);
    $result->bindParam(':pid', $data['pid']);
    $result->bindParam(':company_id', $_SESSION['session_company_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        // Check if the purchase has been invoiced
        $purchase_invoiced = getTabFieCol('transaction_uid', 'tblPurchaseInvoice', 'pid', $data['pid'], $_SESSION['session_company_id']);
        $status_id =  $row['order_status_id'];

        // Combine the address into the required format
        $delivery_address = $row['delivery_address_line1'] . "\n" .
                            $row['delivery_address_suburb'] . "\n" .
                            $row['delivery_postcode'] . ', ' . $row['delivery_state'];

        // Prepare the response array
        $row_data = array(
    'order_number' => $row['id'],
    'vendor_uid' => $row['vendor_uid'],
    'vendor_name' => $row['vendor_name'],
    'vendor_address' => $row['vendor_address'],
    'vendor_suburb' => $row['vendor_suburb'],
    'vendor_state' => $row['vendor_state'],
    'vendor_postcode' => $row['vendor_postcode'],
    'vendor_phone' => $row['vendor_phone'],
    'vendor_email' => $row['vendor_email'],
    'order_status_id' => $status_id,
    'order_status' => getTabFieCol('description', 'tblPurchaseStatus', 'id', $status_id, $_SESSION['session_company_id']),
    'purchaser_user_id' => $row['purchaser_user_id'],
    'purchaser_user' => getTableField('first_lastname', 'tblUsers', $row['purchaser_user_id']),
    'order_date' => date_c($row['order_date']),
    'order_date_required' => date_c($row['order_date_required']),
    'estimated_arrival_date' => (array_key_exists('estimated_arrival_date', $row) && !empty($row['estimated_arrival_date'])) ? date_c($row['estimated_arrival_date']) : '',
    'order_confirmation_required' => array_key_exists('order_confirmation_required', $row) ? (int)$row['order_confirmation_required'] : 0,
    'order_confirmation_received' => array_key_exists('order_confirmation_received', $row) ? (int)$row['order_confirmation_received'] : 0,
    'confirmation_file_name' => array_key_exists('confirmation_file_name', $row) ? $row['confirmation_file_name'] : '',
    'ven_inv_number' => $row['ven_inv_number'],
    'freight' => $row['freight'],
    'order_notes' => $row['order_notes'],
    'additional_notes' => $row['additional_notes'],
    'purchase_invoiced' => $purchase_invoiced,
    'order_receive_date' => date_c($row['order_receive_date']),
    'order_receive_ref' => $row['order_receive_ref'], 
    'order_receive_note' => $row['order_receive_note'], 
    'invoice_date' => date_c($row['invoice_date']), 
    'invoice_ref' => $row['invoice_ref'], 
    'invoice_note' => $row['invoice_note'], 
            
    
    // Make sure these fields are included
    'delivery_address_line1' => $row['delivery_address_line1'],
    'delivery_address_suburb' => $row['delivery_address_suburb'],
    'delivery_postcode' => $row['delivery_postcode'],
    'delivery_state' => $row['delivery_state']
);


        // Add the result to the return array
        array_push($return_arr, $row_data);
    }

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($return_arr);
}

if (isset($data_raw['action']) && $data_raw['action'] == 'record_purchase_process_activity') {
    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->connect();
    $company_id = (int)$_SESSION['session_company_id'];
    $pid = isset($data_raw['pid']) ? (int)$data_raw['pid'] : 0;
    $workflow_type = isset($data_raw['workflow_type']) ? trim((string)$data_raw['workflow_type']) : '';
    $actions = purchaseProcessActivityActions();

    if ($pid <= 0 || !array_key_exists($workflow_type, $actions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid purchase process activity.']);
        exit;
    }

    $stmt = $conn->prepare("SELECT id FROM tblPurchaseOrders WHERE id = :pid AND company_id = :company_id LIMIT 1");
    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();

    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Purchase order not found.']);
        exit;
    }

    addPurchaseActivity($pid, $company_id, 5, $actions[$workflow_type], $_SESSION['session_user_id'], 0);

    if (purchaseConfirmationColumnsExist($conn) && in_array($workflow_type, array('purchase_confirmation_requested', 'purchase_confirmation_requested_cleared'), true)) {
        $required = $workflow_type === 'purchase_confirmation_requested' ? 1 : 0;
        $updateRequired = $conn->prepare("
            UPDATE tblPurchaseOrders
            SET order_confirmation_required = :order_confirmation_required
            WHERE id = :pid
              AND company_id = :company_id
        ");
        $updateRequired->bindValue(':order_confirmation_required', $required, PDO::PARAM_INT);
        $updateRequired->bindValue(':pid', $pid, PDO::PARAM_INT);
        $updateRequired->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $updateRequired->execute();
    }

    echo json_encode([
        'success' => true,
        'history' => getPurchaseProcessActivity($conn, $pid, $company_id)
    ]);
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'get_purchase_process_activity') {
    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->connect();
    $company_id = (int)$_SESSION['session_company_id'];
    $pid = isset($data_raw['pid']) ? (int)$data_raw['pid'] : 0;

    if ($pid <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid purchase order id.']);
        exit;
    }

    echo json_encode([
        'success' => true,
        'history' => getPurchaseProcessActivity($conn, $pid, $company_id)
    ]);
    exit;
}

    
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get raw POST data
    $data_raw = json_decode(file_get_contents('php://input'), true);

    if (isset($data_raw['action']) && $data_raw['action'] == 'save_purchase') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $company_id = $_SESSION['session_company_id'];
        $existingPurchase = getPurchaseActivityRow($conn, $data['pid'], $company_id);
        $order_date_required = strtotime($data['order_date_required']);
        $estimated_arrival_date = !empty($data['estimated_arrival_date']) ? strtotime($data['estimated_arrival_date']) : null;
        // Check ven_inv_number to decide order_status_id
        if (!empty($data['ven_inv_number'])) {
            $order_status_id = 10;
        } else {
            $order_status_id = $data['order_status_id'];
        }
        // Prepare SQL query to update the purchase order
        $setEstimatedArrival = purchaseOrderColumnExists($conn, 'estimated_arrival_date') ? ", estimated_arrival_date = :estimated_arrival_date" : "";
        $query = "UPDATE tblPurchaseOrders SET 
                    vendor_uid = :vendor_uid,
                    vendor_name = :vendor_name,
                    vendor_address = :vendor_address,
                    vendor_suburb = :vendor_suburb,
                    vendor_state = :vendor_state,
                    vendor_postcode = :vendor_postcode, 
                    vendor_phone = :vendor_phone,
                    vendor_email = :vendor_email,
                    purchaser_user_id = :purchaser_user_id,
                    ven_inv_number = :ven_inv_number,
                    order_notes = :order_notes,
                    additional_notes = :additional_notes,
                    freight = :freight,
                    order_date_required = :order_date_required,
                    delivery_address_line1 = :delivery_address_line1,
                    delivery_address_suburb = :delivery_address_suburb,
                    delivery_postcode = :delivery_postcode,
                    delivery_state = :delivery_state,
                    order_status_id = :order_status_id
                    " . $setEstimatedArrival . "
                  WHERE id = :pid AND company_id = :company_id";

        // Bind parameters to the query
        $bindings = array(
            ':pid' => $data['pid'],
            ':vendor_uid' => $data['vendor_uid'],
            ':vendor_name' => $data['vendor_name'],
            ':vendor_address' => $data['vendor_address'],
            ':vendor_suburb' => $data['vendor_suburb'],
            ':vendor_state' => $data['vendor_state'],
            ':vendor_postcode' => $data['vendor_postcode'],
            ':vendor_phone' => $data['vendor_phone'],
            ':vendor_email' => $data['vendor_email'],
            ':purchaser_user_id' => $data['purchaser_user_id'],
            ':ven_inv_number' => $data['ven_inv_number'],
            ':order_notes' => $data['order_notes'],
            ':additional_notes' => $data['additional_notes'],
            ':freight' => $data['freight'],
            ':order_date_required' => $order_date_required,
            ':delivery_address_line1' => $data['delivery_address_line1'],
            ':delivery_address_suburb' => $data['delivery_address_suburb'],
            ':delivery_postcode' => $data['delivery_postcode'],
            ':delivery_state' => $data['delivery_state'],
            ':order_status_id' => $order_status_id,
            ':company_id' => $company_id
        );
        if (purchaseOrderColumnExists($conn, 'estimated_arrival_date')) {
            $bindings[':estimated_arrival_date'] = $estimated_arrival_date;
        }

        // Execute the SQL query
        $stmt = $conn->prepare($query);
        $stmt->execute($bindings);
        $rowCount = $stmt->rowCount();

        // Respond with success or failure based on the result
        if ($rowCount > 0) {
            if ($existingPurchase) {
                $activityDescriptions = buildPurchaseActivityDescriptions($existingPurchase, $data, $order_date_required, $order_status_id, $company_id);
                foreach ($activityDescriptions as $activityDescription) {
                    addPurchaseActivity($data['pid'], $company_id, 5, $activityDescription, $_SESSION['session_user_id'], 0);
                }
            }
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made.']);
        }
    }
}



if (isset($data_raw['action']) && $data_raw['action'] == 'create_purchase') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $order_date = time();
    $order_status_id = 1;
    $query = "INSERT INTO tblPurchaseOrders (
                company_id,
                vendor_uid,
                vendor_name,
                vendor_address,
                vendor_suburb,
                vendor_state,
                vendor_postcode,
                vendor_phone,
                vendor_email,
                order_date,
                order_status_id,
                purchaser_user_id,
                ven_inv_number,
                order_notes,
                additional_notes,
                payment_terms_day,
                payment_terms_type
              ) VALUES (
                :company_id,
                :vendor_uid,
                :vendor_name,
                :vendor_address,
                :vendor_suburb,
                :vendor_state,
                :vendor_postcode,
                :vendor_phone,
                :vendor_email,
                :order_date,
                :order_status_id,
                :purchaser_user_id,
                :ven_inv_number,
                :order_notes,
                :additional_notes,
                :payment_terms_day,
                :payment_terms_type
              )";

    $bindings = array(
        ':company_id' => $_SESSION['session_company_id'],
        ':vendor_uid' => $data['vendor_uid'],
        ':vendor_name' => $data['vendor_name'],
        ':vendor_address' => $data['vendor_address'],
        ':vendor_suburb' => $data['vendor_suburb'],
        ':vendor_state' => $data['vendor_state'],
        ':vendor_postcode' => $data['vendor_postcode'],
        ':vendor_phone' => $data['vendor_phone'],
        ':vendor_email' => $data['vendor_email'],
        ':order_date' => $order_date,
        ':order_status_id' => $order_status_id,
        ':purchaser_user_id' => $_SESSION['session_user_id'],
        ':ven_inv_number' => $data['ven_inv_number'],
        ':order_notes' => $data['order_notes'],
        ':additional_notes' => $data['additional_notes'],
        ':payment_terms_day' => $data['payment_terms_day'],
        ':payment_terms_type' => $data['payment_terms_type'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        $pid = $conn->lastInsertId(); // Retrieve the last inserted ID
        if ($rowCount > 0) {
            if (purchaseOrderColumnExists($conn, 'order_confirmation_required')) {
                $requiredStmt = $conn->prepare("
                    UPDATE tblPurchaseOrders
                    SET order_confirmation_required = 1
                    WHERE id = :pid
                      AND company_id = :company_id
                ");
                $requiredStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                $requiredStmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
                $requiredStmt->execute();
            }
            addPurchaseActivity($pid, $_SESSION['session_company_id'], 5, 'New purchase created', $_SESSION['session_user_id'], 0);
            echo json_encode(['success' => true, 'message' => 'Order created successfully.', 'pid' => $pid]);
        } else {
            echo json_encode(['error' => true, 'message' => 'Failed to create order.']);
        }
    }
}
 
    
if (isset($data_raw['action']) && $data_raw['action'] == 'add_order_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    // Fetch necessary fields from the inventory
    $description = getFieldColumn('description', 'tblInventory', 'part_number', $data['part_number']);
    $unit_id = getFieldColumn('unit_id', 'tblInventory', 'part_number', $data['part_number']);
    $weight_unit = getFieldColumn('weight_unit', 'tblInventory', 'part_number', $data['part_number']);

    // Check if the part number already exists in tblPurchaseItems for the same pid and company_id
    $checkQuery = "SELECT id FROM tblPurchaseItems WHERE pid = :pid AND company_id = :company_id AND part_number = :part_number";
    $checkBindings = array(
        ':pid' => $data['pid'],
        ':company_id' => $_SESSION['session_company_id'],
        ':part_number' => $data['part_number']
    );

    $stmt = $conn->prepare($checkQuery);
    $stmt->execute($checkBindings);

    if ($stmt->rowCount() > 0) {
        // Part number already exists, get the existing order_group_id
        $existingOrderGroup = $stmt->fetch(PDO::FETCH_ASSOC);
        $orderGroupId = $existingOrderGroup['id'];

        // Insert into tblPurchaseSubItems if has_items is 1
        if ($data['has_items'] == 1) {
            $queryItems = "INSERT INTO tblPurchaseSubItems 
                           (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
                           VALUES 
                           (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)";

            $bindingsItems = array(
                ':company_id' => $_SESSION['session_company_id'],
                ':pid' => $data['pid'],
                ':order_group_id' => $orderGroupId,
                ':part_number' => $data['part_number'],
                ':description' => $description,
                ':qty' => $data['qty'],
                ':qty_unit' => $data['qty_unit'],
                ':mark' => $data['mark']
            );

            $stmtItems = $conn->prepare($queryItems);
            $stmtItems->execute($bindingsItems);
        }

        echo json_encode(['success' => true, 'message' => 'Purchase sub-item added successfully.']);
    } else {
        // Part number does not exist, insert into tblPurchaseItems
        $query = "INSERT INTO tblPurchaseItems 
                  (pid, company_id, part_number, description, qty, rate, unit, unit_id, has_items)
                  VALUES 
                  (:pid, :company_id, :part_number, :description, :qty, :rate, :unit, :unit_id, :has_items)";

        $bindings = array(
            ':pid' => $data['pid'],
            ':company_id' => $_SESSION['session_company_id'],
            ':part_number' => $data['part_number'],
            ':description' => $description,
            ':qty' => $data['qty_item'],
            ':rate' => $data['rate'],
            ':unit' => '',  // Assuming `unit` is provided or left empty
            ':unit_id' => $unit_id,
            ':has_items' => $data['has_items']
        );

        $stmt = $conn->prepare($query);
        if ($stmt->execute($bindings)) {
            $orderGroupId = $conn->lastInsertId(); // Get the last inserted ID

            // Insert into tblPurchaseSubItems if has_items is 1
            if ($data['has_items'] == 1) {
                $queryItems = "INSERT INTO tblPurchaseSubItems 
                               (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
                               VALUES 
                               (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)";

                $bindingsItems = array(
                    ':company_id' => $_SESSION['session_company_id'],
                    ':pid' => $data['pid'],
                    ':order_group_id' => $orderGroupId,
                    ':part_number' => $data['part_number'],
                    ':description' => $description,
                    ':qty' => $data['qty'],
                    ':qty_unit' => $data['qty_unit'],
                    ':mark' => $data['mark']
                );

                $stmtItems = $conn->prepare($queryItems);
                $stmtItems->execute($bindingsItems);
            }

            echo json_encode(['success' => true, 'message' => 'Purchase item and sub-item added successfully.']);
        } else {
            echo json_encode(['error' => true, 'message' => 'An error occurred while adding the purchase item.']);
        }
    }
}
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_invoice') {
    $data = sanInputs($data_raw);

    $pid = (int)$data['pid'];
    $company_id = $_SESSION['session_company_id'];

    // optional checkbox (1/0)
    $delete_from_accounting = !empty($data['delete_from_accounting']) ? 1 : 0;

    $database = new Database();
    $conn = $database->connect();

    // delete invoice rows
    $stmt = $conn->prepare("DELETE FROM tblPurchaseInvoice WHERE pid = :pid AND company_id = :company_id");
    $stmt->execute([
        ':pid' => $pid,
        ':company_id' => $company_id
    ]);

    // clear invoice fields on the PO
    $stmt2 = $conn->prepare("UPDATE tblPurchaseOrders SET invoice_date = NULL, invoice_ref = '', invoice_note = '' 
                             WHERE id = :pid AND company_id = :company_id");
    $stmt2->execute([
        ':pid' => $pid,
        ':company_id' => $company_id
    ]);

    // optional: delete from accounting (stub)
    if ($delete_from_accounting) {
        // call your accounting delete here if you want
        // processBillDelete($pid); or deleteAccountingInvoice(...)
    }

    echo json_encode(['success' => true, 'message' => 'Invoice deleted.']);
    exit;
}
       
if (isset($data_raw['action']) && $data_raw['action'] == 'add_bill_items') {
    $data = sanInputs($data_raw); // Sanitize input data
    $database = new Database();
    $conn = $database->connect();
    $unit_id = getFieldColumn('unit_id', 'tblInventory', 'part_number', $data['part_number']);
    
    if ($data['has_items'] == 1) {
        // Check if the part_number already exists with the same pid in tblBillItems
        $queryCheck = "SELECT id FROM tblBillItems WHERE part_number = :part_number AND pid = :pid";
        $stmtCheck = $conn->prepare($queryCheck);
        $stmtCheck->execute([
            ':part_number' => $data['part_number'],
            ':pid' => $data['pid']
        ]);

        if ($stmtCheck->rowCount() > 0) {
            // part_number already exists with the same pid, insert only into tblBillSubItems
            $row = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            $gid = $row['id']; // Get the existing id from tblBillItems

            $qty = $data['rate_units'];
            $qty_unit = $data['rate_units_qty'];
            $queryItems = "INSERT INTO tblBillSubItems 
                           (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
                           VALUES 
                           (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)";

            $bindingsItems = array(
                ':company_id' => $_SESSION['session_company_id'],
                ':pid' => $data['pid'],
                ':order_group_id' => $gid,
                ':part_number' => $data['part_number'],
                ':description' => $data['description'],
                ':qty' => $qty,
                ':qty_unit' => $qty_unit,
                ':mark' => $data['mark'],
            );

            $stmtItems = $conn->prepare($queryItems);
            $stmtItems->execute($bindingsItems);

            echo json_encode(['success' => true, 'message' => 'Sub-items inserted successfully.']);
            return; // End execution here since we only needed to insert into tblBillSubItems
        }
    }

    // If has_items is 0 or if has_items is 1 and the part_number does not exist, insert into tblBillItems
    $query = "INSERT INTO tblBillItems(company_id, pid, part_number, serial_number, has_items, description, qty, rate, unit_id) 
              VALUES(:company_id, :pid, :part_number, :serial_number, :has_items, :description, :qty, :rate, :unit_id)";

    $bindings = array(
        ':company_id' => $_SESSION['session_company_id'],
        ':pid' => $data['pid'],
        ':part_number' => $data['part_number'],
        ':serial_number' => $data['serial_number'],
        ':description' => $data['description'],
        ':has_items' => $data['has_items'],
        ':qty' => $data['qty_item'],
        ':rate' => $data['rate'],
        ':unit_id' => $unit_id,
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            $gid = $conn->lastInsertId(); // Get the last inserted ID from tblBillItems

            if ($data['has_items'] == 1) {
                // Insert into tblBillSubItems since has_items is 1
                $qty = $data['rate_units'];
                $qty_unit = $data['rate_units_qty'];
                $queryItems = "INSERT INTO tblBillSubItems 
                               (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
                               VALUES 
                               (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)";

                $bindingsItems = array(
                    ':company_id' => $_SESSION['session_company_id'],
                    ':pid' => $data['pid'],
                    ':order_group_id' => $gid,
                    ':part_number' => $data['part_number'],
                    ':description' => $data['description'],
                    ':qty' => $qty,
                    ':qty_unit' => $qty_unit,
                    ':mark' => $data['mark'],
                );

                $stmtItems = $conn->prepare($queryItems);
                $stmtItems->execute($bindingsItems);
            }

            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    }
}




    
if (isset($data_raw['action']) && $data_raw['action'] == 'read_order_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblPurchaseItems WHERE id = :item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':item_id', $data['item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'part_number' => $row['part_number'],
            'description' => $row['description'], 
            'has_items' => $row['has_items'],
            'unit_id' => $row['unit_id'],
            'unit' => $row['unit'],
            'rate' => $row['rate'],
            'qty' => $row['qty'] 
        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}
if (isset($data_raw['action']) && $data_raw['action'] == 'read_order_sub_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblPurchaseSubItems WHERE id = :sub_item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':sub_item_id', $data['sub_item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'part_number' => $row['part_number'],
            'description' => $row['description'], 
            'qty' => $row['qty'],
            'qty_unit' => $row['qty_unit'],
            'mark' => $row['mark'] 
        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}
 if (isset($data_raw['action']) && $data_raw['action'] == 'read_bill_sub_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblBillSubItems WHERE id = :sub_item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':sub_item_id', $data['sub_item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'part_number' => $row['part_number'],
            'description' => $row['description'], 
            'qty' => $row['qty'],
            'qty_unit' => $row['qty_unit'],
            'mark' => $row['mark'] 
        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}   
if (isset($data_raw['action']) && $data_raw['action'] == 'save_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblPurchaseItems SET 
                part_number = :part_number, 
                description = :description,
                qty = :qty,
                rate = :rate
                WHERE id = :item_id";
    $bindings = array(
        ':item_id' => $data['item_id'],
        ':part_number' => $data['part_number'],
        ':description' => $data['description'],
        ':qty' => $data['qty'],
        ':rate' => $data['rate'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
    }
if (isset($data_raw['action']) && $data_raw['action'] == 'save_sub_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblPurchaseSubItems SET 
                mark = :mark,
                qty = :qty,
                qty_unit = :qty_unit
                WHERE id = :item_id";
    $bindings = array(
		':item_id' => $data['item_id'],
        ':qty' => $data['qty'],
        ':qty_unit' => $data['qty_unit'],
        ':mark' => $data['mark'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
    }
if (isset($data_raw['action']) && $data_raw['action'] == 'save_bill_sub_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblBillSubItems SET 
                mark = :mark,
                qty = :qty,
                qty_unit = :qty_unit
                WHERE id = :sub_item_id";
    $bindings = array(
		':sub_item_id' => $data['sub_item_id'],
        ':mark' => $data['mark'],
        ':qty' => $data['qty'],
        ':qty_unit' => $data['qty_unit'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
    }    
if (isset($data_raw['action']) && $data_raw['action'] == 'read_bill_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblBillItems WHERE id = :bill_item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':bill_item_id', $data['bill_item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
			'part_number' => $row['part_number'],
            'serial_number' => $row['serial_number'],
            'description' => $row['description'], 
            'qty' => $row['qty'],
            'rate' => $row['rate'] 
        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}	
if (isset($data_raw['action']) && $data_raw['action'] == 'save_bill_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblBillItems SET 
                part_number = :part_number, 
				serial_number = :serial_number,
                description = :description,
                qty = :qty,
                rate = :rate
                WHERE id = :bill_item_id";
    $bindings = array(
        ':bill_item_id' => $data['bill_item_id'],
        ':part_number' => $data['part_number'],
		':serial_number' => $data['serial_number'],
        ':description' => $data['description'],
        ':qty' => $data['qty'],
        ':rate' => $data['rate'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
    }	
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_order_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_item_id'])) {
        $id = $data['del_item_id'];
        deleteId('tblPurchaseItems', $id);
    } else {
        echo json_encode(["error" => "del_item_id is not set."]);
    }
}
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_order_sub_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_sub_item_id'])) {
        $id = $data['del_sub_item_id'];
        deleteId('tblPurchaseSubItems', $id);
    } else {
        echo json_encode(["error" => "del_sub_item_id is not set."]);
    }
}
    if (isset($data_raw['action']) && $data_raw['action'] == 'delete_bill_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_item_id'])) {
        $id = $data['del_item_id'];
        deleteId('tblBillItems', $id);
    } else {
        echo json_encode(["error" => "del_item_id is not set."]);
    }
}
 if (isset($data_raw['action']) && $data_raw['action'] == 'delete_bill_sub_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_item_id'])) {
        $id = $data['del_item_id'];
        deleteId('tblBillSubItems', $id);
    } else {
        echo json_encode(["error" => "del_item_id is not set."]);
    }
}
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_sub_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_item_id'])) {
        $id = $data['del_item_id'];
        deleteId('tblPurchaseSubItems', $id);
    } else {
        echo json_encode(["error" => "del_item_id is not set."]);
    }
}
if (isset($data_raw['action']) && $data_raw['action'] == 'get_part_number') {
    $database = new Database();
    $conn = $database->connect();
    $term = sanInputs($data_raw['term']);
    $query = "SELECT raw_material ,part_number, description, buy_rate, qty, unit, unit_id, has_sub_items FROM tblInventory WHERE part_number LIKE :term OR description LIKE :term AND active = 1 ";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$term%";
    $stmt->bindParam(':term', $searchTerm);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($result);
    $conn = null;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'receive_items') {
    $pid = $data_raw['pid'];
    $company_id = $_SESSION['session_company_id'];
    $database = new Database();
    $conn = $database->connect();
    $data = sanInputs($data_raw);
    $existingPurchase = getPurchaseActivityRow($conn, $pid, $company_id);
       $query = "UPDATE tblPurchaseOrders SET 
                order_receive_date = :order_receive_date, 
                order_receive_ref = :order_receive_ref,
                order_receive_note = :order_receive_note
                WHERE id = :pid AND company_id = :company_id";
              
    $bindings = array(
        ':order_receive_date' => strtotime($data['order_receive_date']),
        ':order_receive_ref' => $data['order_receive_ref'],
        ':order_receive_note' => $data['order_receive_note'],
        ':pid' => $pid,
        ':company_id' => $company_id
    );
    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            if ($existingPurchase) {
                addPurchaseActivity($pid, $company_id, 5, 'Receive details updated: Date ' . purchaseActivityDateText($existingPurchase['order_receive_date']) . ' -> ' . purchaseActivityDateText(strtotime($data['order_receive_date'])) . ', Reference/notes updated', $_SESSION['session_user_id'], 0);
            }
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
 	
}
if (isset($data_raw['action']) && $data_raw['action'] == 'receive_invoice') {
    $pid = $data_raw['pid'];
    $company_id = $_SESSION['session_company_id'];
    $database = new Database();
    $conn = $database->connect();
    $data = sanInputs($data_raw);
    $existingPurchase = getPurchaseActivityRow($conn, $pid, $company_id);
       $query = "UPDATE tblPurchaseOrders SET 
                invoice_date = :invoice_date, 
                invoice_ref = :invoice_ref,
                invoice_note = :invoice_note
                WHERE id = :pid AND company_id = :company_id";
              
    $bindings = array(
        ':invoice_date' => strtotime($data['invoice_date']),
        ':invoice_ref' => $data['invoice_ref'],
        ':invoice_note' => $data['invoice_note'],
        ':pid' => $pid,
        ':company_id' => $company_id
    );
    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            if ($existingPurchase) {
                addPurchaseActivity($pid, $company_id, 5, 'Invoice details updated: Date ' . purchaseActivityDateText($existingPurchase['invoice_date']) . ' -> ' . purchaseActivityDateText(strtotime($data['invoice_date'])) . ', Reference/notes updated', $_SESSION['session_user_id'], 0);
            }
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        }
 	
}
if (isset($data_raw['action']) && $data_raw['action'] == 'convert_to_bill') {
    $pid = $data_raw['pid'];
    $company_id = $_SESSION['session_company_id'];
    $database = new Database();
    $conn = $database->connect();

    try {
        // Check if the combination of pid and company_id already exists in tblBillItems
        $checkQuery = "SELECT COUNT(*) FROM tblBillItems WHERE company_id = :company_id AND pid = :pid";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $checkStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
        $checkStmt->execute();
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            echo json_encode(['warning' => true, 'message' => 'This order has already been converted to a bill.']);
            exit;
        }

        // Retrieve all items from tblPurchaseItems for this pid and company_id
        $fetchItemsQuery = "SELECT * FROM tblPurchaseItems WHERE company_id = :company_id AND pid = :pid";
        $fetchItemsStmt = $conn->prepare($fetchItemsQuery);
        $fetchItemsStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $fetchItemsStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
        $fetchItemsStmt->execute();
        $items = $fetchItemsStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($items as $item) {
            // Insert each item into tblBillItems
            $insertBillItemQuery = "INSERT INTO tblBillItems (company_id, pid, part_number, has_items, description, qty, rate, unit, unit_id)
                                    VALUES (:company_id, :pid, :part_number, :has_items, :description, :qty, :rate, :unit, :unit_id)";
            $insertBillItemStmt = $conn->prepare($insertBillItemQuery);
            $insertBillItemStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
            $insertBillItemStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
            $insertBillItemStmt->bindValue(':part_number', $item['part_number'], PDO::PARAM_STR);
            $insertBillItemStmt->bindValue(':has_items', $item['has_items'], PDO::PARAM_INT);
            $insertBillItemStmt->bindValue(':description', $item['description'], PDO::PARAM_STR);
            $insertBillItemStmt->bindValue(':qty', $item['qty'], PDO::PARAM_STR);
            $insertBillItemStmt->bindValue(':rate', $item['rate'], PDO::PARAM_STR);
            $insertBillItemStmt->bindValue(':unit', $item['unit'], PDO::PARAM_STR);
            $insertBillItemStmt->bindValue(':unit_id', $item['unit_id'], PDO::PARAM_STR);
            $insertBillItemStmt->execute();

            // Get the last inserted ID from tblBillItems (new bill's id)
            $billItemId = $conn->lastInsertId();

            // Now, duplicate corresponding sub-items
            $fetchSubItemsQuery = "SELECT * FROM tblPurchaseSubItems WHERE company_id = :company_id AND pid = :pid AND order_group_id = :order_group_id";
            $fetchSubItemsStmt = $conn->prepare($fetchSubItemsQuery);
            $fetchSubItemsStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
            $fetchSubItemsStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
            $fetchSubItemsStmt->bindValue(':order_group_id', $item['id'], PDO::PARAM_INT);
            $fetchSubItemsStmt->execute();
            $subItems = $fetchSubItemsStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($subItems as $subItem) {
                // Insert each sub-item into tblBillSubItems
                $insertBillSubItemQuery = "INSERT INTO tblBillSubItems (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
                                           VALUES (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)";
                $insertBillSubItemStmt = $conn->prepare($insertBillSubItemQuery);
                $insertBillSubItemStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                $insertBillSubItemStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                $insertBillSubItemStmt->bindValue(':order_group_id', $billItemId, PDO::PARAM_INT); // Reference to the new bill item ID
                $insertBillSubItemStmt->bindValue(':part_number', $subItem['part_number'], PDO::PARAM_STR);
                $insertBillSubItemStmt->bindValue(':description', $subItem['description'], PDO::PARAM_STR);
                $insertBillSubItemStmt->bindValue(':mark', $subItem['mark'], PDO::PARAM_STR);
                $insertBillSubItemStmt->bindValue(':qty', $subItem['qty'], PDO::PARAM_STR);
                $insertBillSubItemStmt->bindValue(':qty_unit', $subItem['qty_unit'], PDO::PARAM_STR);
                
                $insertBillSubItemStmt->execute();
            }
        }

        addPurchaseActivity($pid, $company_id, 5, 'Bill converted: Purchase order items copied to bill', $_SESSION['session_user_id'], 0);
        echo json_encode(['success' => true, 'message' => 'Data duplicated successfully.']);

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.', 'error' => $e->getMessage()]);
    }
}

  if (isset($data_raw['action']) && $data_raw['action'] == 'convert_to_invoice') {
    $pid = $data_raw['pid'];
    $company_id = $_SESSION['session_company_id'];
    $database = new Database();
    $conn = $database->connect();

    try {
        // Check if the invoice already exists
        $checkQuery = "SELECT COUNT(*) FROM tblPurchaseInvoice WHERE company_id = :company_id AND pid = :pid";
        $checkStmt = $conn->prepare($checkQuery);
        $checkStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $checkStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
        $checkStmt->execute();
        $exists = $checkStmt->fetchColumn();

        if ($exists > 0) {
            // If it exists, return an error message
            echo json_encode(['warning' => true, 'message' => 'This bill has already been converted to an invoice.']);
            exit;
        }

        // Fetch order items
        $query = "SELECT * FROM tblBillItems WHERE company_id = :company_id AND pid = :pid";
        $statement = $conn->prepare($query);
        $statement->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $statement->bindValue(':pid', $pid, PDO::PARAM_INT);
        $statement->execute();

        $results = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$results) {
            echo json_encode(['success' => false, 'message' => 'No items found.']);
            exit;
        }

        // Prepare insert statement for invoice
        $invoiceQuery = "INSERT INTO tblPurchaseInvoice (company_id, pid, part_number, serial_number, description, rate, qty, qty_unit, qty_total, bill_date, vendor_uid) 
                         VALUES (:company_id, :pid, :part_number, :serial_number, :description, :rate, :qty, :qty_unit, :qty_total, :bill_date, :vendor_uid)";
        $invoiceStmt = $conn->prepare($invoiceQuery);

        $bill_date = time();
        $vendor_uid = getTabFieCol('vendor_uid', 'tblPurchaseOrders', 'id', $pid, $company_id);

        foreach ($results as $row) {
            $part_number = $row['part_number'];
            $description = $row['description'];
            $rate = $row['rate'];
            $serial_number = $row['serial_number'];
            if ($row['has_items'] == 1) {
                // Fetch sub items for the current item
                $subQuery = "SELECT * FROM tblBillSubItems WHERE company_id = :company_id AND order_group_id = :order_group_id";
                $subStatement = $conn->prepare($subQuery);
                $subStatement->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                $subStatement->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
                $subStatement->execute();

                $subResults = $subStatement->fetchAll(PDO::FETCH_ASSOC);

                // Process and insert sub items
                foreach ($subResults as $subRow) {
                    $subDescription = $subRow['description'];
                    $subQty = $subRow['qty'];
                    $subQtyUnit = $subRow['qty_unit'];
                    $fullDescription =  $subQty . ' x ' . $subQtyUnit . ' ' . $description;
                    $qty = $subQty * $subQtyUnit;

                    $invoiceStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                    $invoiceStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                    $invoiceStmt->bindValue(':part_number', $part_number, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':serial_number', $serial_number, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':description', $fullDescription, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':rate', $rate, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':qty', $subQty, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':qty_unit', $subQtyUnit, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':qty_total', $qty, PDO::PARAM_STR);
                    $invoiceStmt->bindValue(':bill_date', $bill_date, PDO::PARAM_INT);
                    $invoiceStmt->bindValue(':vendor_uid', $vendor_uid, PDO::PARAM_STR);
                    $invoiceStmt->execute();
                }
            } else {
                // If no sub items, insert the main item
                $qty = $row['qty'];
                $invoiceStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':pid', $pid, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':part_number', $part_number, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':serial_number', $serial_number, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':description', $description, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':rate', $rate, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':qty', $qty, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':qty_unit', 1, PDO::PARAM_STR); // Simplified, assuming unit is 1 if no sub-items
                $invoiceStmt->bindValue(':qty_total', $qty, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':bill_date', $bill_date, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':vendor_uid', $vendor_uid, PDO::PARAM_STR);
                $invoiceStmt->execute();
            }
        }

        addPurchaseActivity($pid, $company_id, 5, 'Invoice converted: Bill items copied to purchase invoice', $_SESSION['session_user_id'], 0);
        echo json_encode(['success' => true, 'message' => 'Invoice conversion successful.']);

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.', 'error' => $e->getMessage()]);
    }
}


if (isset($data_raw['action']) && $data_raw['action'] == 'insert_stock_inv') {
    $data = sanInputs($data_raw);
    $company_id = $_SESSION['session_company_id'];
    $pid = $data['pid'];

    $database = new Database();
    $conn = $database->connect();

    try {
        $checkSql = "SELECT COUNT(*) FROM tblInventoryItems WHERE company_id = :company_id AND purchase_id = :pid";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $checkStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['warning' => true, 'message' => 'Items already exist in Inventory.']);
            exit;
        }
        $rowsInserted = 0;

        // Fetch main items where has_items = 0 or 1
        $fetchMainSql = "SELECT * FROM tblBillItems WHERE company_id = :company_id AND pid = :pid";
        $fetchMainStmt = $conn->prepare($fetchMainSql);
        $fetchMainStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $fetchMainStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $fetchMainStmt->execute();

        // Prepare the insert statement for main items
        $insertMainSql = "INSERT INTO tblInventoryItems (
                            company_id,
                            purchase_id,
                            inventory_id,
                            serial_number,
                            part_number,
                            item_unit_id,
                            purchased_qty,
                            qty,
                            qty_unit
                        ) VALUES (
                            :company_id,
                            :pid,
                            :inventory_id,
                            :serial_number,
                            :part_number,
                            :item_unit_id,
                            :purchased_qty,
                            :qty,
                            :qty_unit
                        )";
        $insertMainStmt = $conn->prepare($insertMainSql);

        // Process each main item
        while ($mainRow = $fetchMainStmt->fetch(PDO::FETCH_ASSOC)) {
            // Get inventory and unit ID for the main item
            $inventory_id = getTabFieCol('id', 'tblInventory', 'part_number', $mainRow['part_number'], $company_id);
            $unit_id = getTabFieCol('unit_id', 'tblInventory', 'part_number', $mainRow['part_number'], $company_id);
            $mtr_kg = getTabFieCol('weight_unit', 'tblInventory', 'part_number', $mainRow['part_number'], $company_id);
            $metre_unit = getTabFieCol('metre_unit', 'tblInventory', 'part_number', $mainRow['part_number'], $company_id);
            $purchased_qty = $mainRow['qty'];
            // Check if the quantity is valid before inserting
            if ($mainRow['qty'] > 0) {
            
                    if ($unit_id == 3) {
                        //$main_qty = ($mainRow['qty'] * 1000) / $mtr_kg;
                        $main_qty = ($mainRow['qty'] * $metre_unit);
                        } else {
                            $main_qty = $mainRow['qty'];
                        }

                // Bind and execute insertion for the main item
                $insertMainStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
                $insertMainStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
                $insertMainStmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
                $insertMainStmt->bindParam(':serial_number', $mainRow['serial_number']);
                $insertMainStmt->bindParam(':part_number', $mainRow['part_number']);
                $insertMainStmt->bindParam(':item_unit_id', $unit_id, PDO::PARAM_INT);
                $insertMainStmt->bindParam(':purchased_qty', $purchased_qty, PDO::PARAM_INT);
                $insertMainStmt->bindParam(':qty', $main_qty, PDO::PARAM_STR);
                $insertMainStmt->bindParam(':qty_unit', $mainRow['qty_unit'], PDO::PARAM_STR);

                $insertMainStmt->execute();
                $rowsInserted++;
            }

            // If the item has sub-items, fetch and insert them
            if ($mainRow['has_items'] == 1) {
                $fetchSubSql = "SELECT * FROM tblBillSubItems WHERE company_id = :company_id AND pid = :pid AND order_group_id = :order_group_id";
                $fetchSubStmt = $conn->prepare($fetchSubSql);
                $fetchSubStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
                $fetchSubStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
                $fetchSubStmt->bindParam(':order_group_id', $mainRow['id'], PDO::PARAM_INT);
                $fetchSubStmt->execute();

                // Prepare the insert statement for sub-items
                $insertSubSql = "INSERT INTO tblInventoryItems (
                                    company_id,
                                    purchase_id,
                                    inventory_id,
                                    part_number,
                                    item_unit_id,
                                    qty,
                                    qty_unit
                                ) VALUES (
                                    :company_id,
                                    :pid,
                                    :inventory_id,
                                    :part_number,
                                    :item_unit_id,
                                    :qty,
                                    :qty_unit
                                )";
                $insertSubStmt = $conn->prepare($insertSubSql);

                // Process each sub-item
                while ($subRow = $fetchSubStmt->fetch(PDO::FETCH_ASSOC)) {
                    // Get inventory and unit ID for the sub-item
                    $inventory_id = getTabFieCol('id', 'tblInventory', 'part_number', $subRow['part_number'], $company_id);
                    $unit_id = getTabFieCol('unit_id', 'tblInventory', 'part_number', $subRow['part_number'], $company_id);
                    $mtr_kg = getTabFieCol('weight_unit', 'tblInventory', 'part_number', $subRow['part_number'], $company_id);
                   

                    // Only insert if qty and qty_unit are valid
                    if ($subRow['qty'] > 0 && $subRow['qty_unit'] > 0) {
                         if($unit_id==3){
                        $qty =($subRow['qty']*1000)/$mtr_kg ;
                        }
                        else{
                            $qty = $subRow['qty'];
                        }
                        // Bind and execute insertion for the sub-item
                        $insertSubStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
                        $insertSubStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
                        $insertSubStmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
                        $insertSubStmt->bindParam(':part_number', $subRow['part_number']);
                        $insertSubStmt->bindParam(':item_unit_id', $unit_id, PDO::PARAM_INT);
                        $insertSubStmt->bindParam(':qty', $qty, PDO::PARAM_STR);
                        $insertSubStmt->bindParam(':qty_unit', $subRow['qty_unit'], PDO::PARAM_STR);

                        $insertSubStmt->execute();
                        $rowsInserted++;
                    }
                }
            }
        }

        if ($rowsInserted > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
            UpdatePurStatus($pid,3);
            addPurchaseActivity($pid, $company_id, 5, 'Stock received: ' . $rowsInserted . ' inventory row(s) created', $_SESSION['session_user_id'], 0);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes, Not Invoiced?.']);
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => true, 'message' => 'An error occurred.', 'details' => $e->getMessage()]);
    } finally {
        $conn = null; // Ensure the connection is closed
    }
}


if (isset($data_raw['action']) && $data_raw['action'] == 'reverse_receive_items') {
    $data = sanInputs($data_raw);

    $pid = (int)$data['pid'];
    $company_id = (int)$_SESSION['session_company_id'];

    $database = new Database();
    $conn = $database->connect();

    try {
        $conn->beginTransaction();

        $deleteQuery = "DELETE FROM tblInventoryItems
                        WHERE company_id = :company_id
                        AND purchase_id = :pid";

        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->execute(array(
            ':company_id' => $company_id,
            ':pid' => $pid
        ));

        $deletedRows = $deleteStmt->rowCount();

        $updateQuery = "UPDATE tblPurchaseOrders
                        SET order_receive_date = NULL,
                            order_receive_ref = '',
                            order_receive_note = '',
                            order_status_id = 2
                        WHERE id = :pid
                        AND company_id = :company_id";

        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute(array(
            ':pid' => $pid,
            ':company_id' => $company_id
        ));

        $conn->commit();

        addPurchaseActivity($pid, $company_id, 5, 'Receive reversed: ' . $deletedRows . ' inventory row(s) removed', $_SESSION['session_user_id'], 0);

        echo json_encode(array(
            'success' => true,
            'message' => 'Receive reversed. Stock rows removed: ' . $deletedRows
        ));
        exit;
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        echo json_encode(array(
            'success' => false,
            'message' => 'Reverse receive failed: ' . $e->getMessage()
        ));
        exit;
    }
}
    
if (isset($data_raw['action']) && $data_raw['action'] == 'add_purchase_activity') {
    addPurchaseActivity($data_raw['pid'], $_SESSION['session_company_id'], 5, $data_raw['description'], $_SESSION['session_user_id'], $data_raw['action_date']);
    echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'delete_purchase_activity') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "DELETE FROM tblPurchaseActivity WHERE id = :activity_id AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->execute(array(
        ':activity_id' => $data['id'],
        ':company_id' => $_SESSION['session_company_id']
    ));
    echo json_encode(['success' => true, 'message' => 'Deleted successfully.']);
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'get_purchase_activity') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblPurchaseActivity WHERE id = :activity_id AND company_id = :company_id";
    $result = $conn->prepare($query);
    $result->execute(array(
        ':activity_id' => $data['activity_id'],
        ':company_id' => $_SESSION['session_company_id']
    ));

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $return_arr[] = array(
            'description' => $row['description'],
            'action_date' => date_c($row['action_date'])
        );
    }
    echo json_encode($return_arr);
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'save_purchase_activity') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $action_date = strtotime($data['action_date']);
    $query = "UPDATE tblPurchaseActivity SET 
                description = :description, 
                action_date = :action_date
                WHERE id = :activity_id AND company_id = :company_id";
    $bindings = array(
        ':activity_id' => $data['activity_id'],
        ':description' => $data['description'],
        ':action_date' => $action_date,
        ':company_id' => $_SESSION['session_company_id']
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    }
    exit;
}

 if (isset($data_raw['action']) && $data_raw['action'] == 'insert_stock_inv_old') {
    $data = sanInputs($data_raw);
    $company_id = $_SESSION['session_company_id'];
    $pid = $data['pid'];

    $db = new Database();
    $conn = $db->connect();

    try {
        // Check if items already exist in inventory
        $checkSql = "SELECT COUNT(*) FROM tblInventoryItems WHERE company_id = :company_id AND purchase_id = :pid";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $checkStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            echo json_encode(['warning' => true, 'message' => 'Items already exist in Inventory.']);
            exit;
        }
        // Fetch purchase invoice items
        $fetchSql = "SELECT * FROM tblPurchaseInvoice WHERE company_id = :company_id AND pid = :pid";
        $fetchStmt = $conn->prepare($fetchSql);
        $fetchStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
        $fetchStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
        $fetchStmt->execute();
        $rowsInserted = 0;

        // Prepare insert statement
        $insertSql = "INSERT INTO tblInventoryItems (
                            company_id,
                            purchase_id,
                            inventory_id,
                            serial_number,
                            part_number,
                            item_unit_id,
                            qty,
                            qty_unit
                        ) VALUES (
                            :company_id,
                            :pid,
                            :inventory_id,
                            :serial_number,
                            :part_number,
                            :item_unit_id,
                            :qty,
                            :qty_unit
                        )";
        $insertStmt = $conn->prepare($insertSql);
        while ($row = $fetchStmt->fetch(PDO::FETCH_ASSOC)) {
            $inventory_id = getTabFieCol('id', 'tblInventory', 'part_number', $row['part_number'], $company_id);
            $unit_id = getTabFieCol('unit_id', 'tblInventory', 'part_number', $row['part_number'], $company_id);

            // Bind parameters for insertion
            $insertStmt->bindParam(':company_id', $company_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':pid', $pid, PDO::PARAM_INT);
            $insertStmt->bindParam(':inventory_id', $inventory_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':serial_number', $row['serial_number']);
            $insertStmt->bindParam(':part_number', $row['part_number']);
            $insertStmt->bindParam(':item_unit_id', $unit_id, PDO::PARAM_INT);
            $insertStmt->bindParam(':qty', $row['qty'], PDO::PARAM_INT);
            $insertStmt->bindParam(':qty_unit', $row['qty_unit']);

            if ($insertStmt->execute()) {
                $rowsInserted++;
            } else {
                error_log("Failed to insert record: " . json_encode($insertStmt->errorInfo()));
            }
        }

        if ($rowsInserted > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes, Not Invoiced?.']);
        }

    } catch (Exception $e) {
        error_log($e->getMessage());
        echo json_encode(['error' => true, 'message' => 'An error occurred.', 'details' => $e->getMessage()]);
    } finally {
        $conn = null; // Ensure the connection is closed
    }
}
    if (isset($data_raw['action']) && $data_raw['action'] == 'get_order_copy') {
    $database = new Database();
    $conn = $database->connect();

    $term = sanInputs($data_raw['term']);

    $query = "SELECT *
              FROM tblPurchaseOrders 
              WHERE (id LIKE :term  OR ven_inv_number LIKE :term)
             
              ORDER BY id DESC 
              LIMIT 20";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$term%";
    $stmt->bindParam(':term', $searchTerm);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formatted = [];

    foreach ($result as $row) {
        $formatted[] = [
            'label' => $row['id'] . ' - ' . $row['vendor_name'] . ' - ' . $row['ven_inv_number'],
            'value' => $row['id'],
            'vendor_name' => $row['vendor_name'],
            'ven_inv_number' => $row['ven_inv_number']
        ];
    }

    echo json_encode($formatted);
    $conn = null;
    exit;
}
    if (isset($data_raw['action']) && $data_raw['action'] == 'get_order_items') {
    $database = new Database();
    $conn = $database->connect();

    $pid = intval($data_raw['copy_order_id']); // Ensure it's numeric

    $query = "SELECT part_number, description 
              FROM tblPurchaseItems 
              WHERE pid = :pid 
              ORDER BY pid ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':pid', $pid, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);

    $conn = null;
    exit;
}
/* --- ADD THIS BLOCK: COPY ITEMS FROM ONE PURCHASE (pid) TO ANOTHER EXISTING pid --- */
if (isset($data_raw['action']) && $data_raw['action'] == 'copy_purchase_items') {
    // Expected JSON:
    // {
    //   "action": "copy_purchase_items",
    //   "source_pid": 123,
    //   "target_pid": 456
    // }
    $data = sanInputs($data_raw);
    $company_id = $_SESSION['session_company_id'] ?? null;

    if (!$company_id) {
        echo json_encode(['error' => true, 'message' => 'Missing company context.']);
        exit;
    }

    $source_pid = (int)($data['source_pid'] ?? 0);
    $target_pid = (int)($data['target_pid'] ?? 0);

    if ($source_pid <= 0 || $target_pid <= 0) {
        echo json_encode(['error' => true, 'message' => 'Invalid source_pid or target_pid.']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    try {
        // Validate both POs exist and belong to the same company
        $checkPoSql = "SELECT id FROM tblPurchaseOrders WHERE id = :pid AND company_id = :company_id";
        $checkPoStmt = $conn->prepare($checkPoSql);

        $checkPoStmt->execute([':pid' => $source_pid, ':company_id' => $company_id]);
        if ($checkPoStmt->rowCount() === 0) {
            echo json_encode(['error' => true, 'message' => 'Source purchase not found for this company.']);
            exit;
        }

        $checkPoStmt->execute([':pid' => $target_pid, ':company_id' => $company_id]);
        if ($checkPoStmt->rowCount() === 0) {
            echo json_encode(['error' => true, 'message' => 'Target purchase not found for this company.']);
            exit;
        }

        // Start transaction
        $conn->beginTransaction();

        // Fetch all parent items from source
        $fetchItemsSql = "SELECT * FROM tblPurchaseItems WHERE company_id = :company_id AND pid = :pid ORDER BY id ASC";
        $fetchItemsStmt = $conn->prepare($fetchItemsSql);
        $fetchItemsStmt->execute([':company_id' => $company_id, ':pid' => $source_pid]);

        // Prepare insert for parent items
        $insertItemSql = "INSERT INTO tblPurchaseItems
            (pid, company_id, part_number, description, qty, rate, unit, unit_id, has_items)
            VALUES
            (:pid, :company_id, :part_number, :description, :qty, :rate, :unit, :unit_id, :has_items)";
        $insertItemStmt = $conn->prepare($insertItemSql);

        // Prepare fetch sub-items per parent
        $fetchSubSql = "SELECT * FROM tblPurchaseSubItems
                        WHERE company_id = :company_id AND pid = :pid AND order_group_id = :order_group_id
                        ORDER BY id ASC";
        $fetchSubStmt = $conn->prepare($fetchSubSql);

        // Prepare insert sub-items
        $insertSubSql = "INSERT INTO tblPurchaseSubItems
            (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
            VALUES
            (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)";
        $insertSubStmt = $conn->prepare($insertSubSql);

        $copied_parent_count = 0;
        $copied_sub_count = 0;

        // Map old parent id -> new parent id for linking sub-items
        $idMap = [];

        while ($item = $fetchItemsStmt->fetch(PDO::FETCH_ASSOC)) {
            // Insert parent item for target pid
            $insertItemStmt->execute([
                ':pid' => $target_pid,
                ':company_id' => $company_id,
                ':part_number' => $item['part_number'],
                ':description' => $item['description'],
                ':qty' => $item['qty'],
                ':rate' => $item['rate'],
                ':unit' => $item['unit'],
                ':unit_id' => $item['unit_id'],
                ':has_items' => $item['has_items'],
            ]);

            $newParentId = (int)$conn->lastInsertId();
            $oldParentId = (int)$item['id'];
            $idMap[$oldParentId] = $newParentId;
            $copied_parent_count++;


            // If the item has sub-items, copy them with remapped order_group_id
            if ((int)$item['has_items'] === 1) {
                $fetchSubStmt->execute([
                    ':company_id' => $company_id,
                    ':pid' => $source_pid,
                    ':order_group_id' => $oldParentId
                ]);

                while ($sub = $fetchSubStmt->fetch(PDO::FETCH_ASSOC)) {
                    $insertSubStmt->execute([
                        ':company_id' => $company_id,
                        ':pid' => $target_pid,
                        ':order_group_id' => $newParentId,
                        ':part_number' => $sub['part_number'],
                        ':description' => $sub['description'],
                        ':mark' => $sub['mark'],
                        ':qty' => $sub['qty'],
                        ':qty_unit' => $sub['qty_unit'],
                    ]);
                    $copied_sub_count++;
                }
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Items copied successfully.',
            'source_pid' => $source_pid,
            'target_pid' => $target_pid,
            'copied_parents' => $copied_parent_count,
            'copied_sub_items' => $copied_sub_count
        ]);
        exit;

    } catch (Exception $e) {
        if ($conn && $conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log($e->getMessage());
        echo json_encode(['error' => true, 'message' => 'An error occurred while copying items.', 'details' => $e->getMessage()]);
        exit;
    }
}
/* --- END ADD BLOCK --- */

}
?>
