<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include_once("../../includes/common.php");
requireLoggedInJson();

function insertInvoiceItems($company_id, $order_id) {
    $database = new Database();
    $conn = $database->connect();
    $invoice_date = time();
    $invoice_date = time();

    // Fetch order items
    $query = "SELECT * FROM tblOrderItems WHERE company_id = :company_id AND order_id = :order_id";
    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $statement->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $statement->execute();

    // Prepare insert statement for invoice
    $invoiceQuery = "INSERT INTO tblInvoice 
        (company_id, order_id, invoice_date, part_number, part_number_description, unit_id, description, qty, rate, source_id, customer_uid, qty_length, weight, order_user_id) 
        VALUES 
        (:company_id, :order_id, :invoice_date, :part_number, :part_number_description, :unit_id, :description, :qty, :rate, :source_id, :customer_uid, :qty_length, :weight, :order_user_id)";
    $invoiceStmt = $conn->prepare($invoiceQuery);

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $order_group_id           = $row['id'];
        $unit_id                  = $row['unit_id'];
        $has_items                = $row['has_items'];
        $partNumber               = $row['part_number'];
        $description              = $row['description'];
        $unit                     = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
        $source_id                = getTabFieCol('client_source_id', 'tblOrders', 'order_id', $row['order_id'], $_SESSION['session_company_id']);
        $customer_uid             = getTabFieCol('customer_uid', 'tblOrders', 'order_id', $row['order_id'], $_SESSION['session_company_id']);
        $order_user_id            = getTabFieCol('order_user_id', 'tblOrders', 'order_id', $row['order_id'], $_SESSION['session_company_id']);
        
        // Always get this from the order item's own description
        $part_number_description  = $row['description'];
        
        $rate_x                   = $row['rate'];

        if ($has_items) {
            $subQuery = "SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id";
            $subStatement = $conn->prepare($subQuery);
            $subStatement->bindValue(':order_group_id', $order_group_id, PDO::PARAM_INT);
            $subStatement->execute();

            while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
                $subQty          = $subRow['qty'];
                $subQtyUnit      = $subRow['qty_unit'];
                $subMark         = $subRow['mark'];
                $subDescription  = $subRow['punch'];
                $weight          = $subRow['weight'];
                $qty_length      = $subQty * $subQtyUnit;
                $qty             = $qty_length;
                $fullDescription = $subMark . ' | ' . $subQty . ' x ' . $subQtyUnit . ' ' . $description . ' | Per ' . $unit;

                $invoiceStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':invoice_date', $invoice_date, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':part_number', $partNumber, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':part_number_description', $part_number_description, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':unit_id', $unit_id, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':description', $fullDescription, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':qty', $qty, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':rate', $rate_x, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':source_id', $source_id, PDO::PARAM_INT);
                $invoiceStmt->bindValue(':customer_uid', $customer_uid, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':qty_length', $qty_length, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':weight', $weight, PDO::PARAM_STR);
                $invoiceStmt->bindValue(':order_user_id', $order_user_id, PDO::PARAM_INT);

                if (!$invoiceStmt->execute()) {
                    error_log('Invoice insert failed (has_items): ' . implode(' | ', $invoiceStmt->errorInfo()));
                }
            }
        } else {
            $qty_raw        = (float)$row['qty'];
            $qty            = number_format($qty_raw, 3);
            $weight         = 0;
            $qty_length     = $qty_raw;
            $fullDescription = $qty . ' x ' . $description . ' | Per ' . $unit;

            $invoiceStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
            $invoiceStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
            $invoiceStmt->bindValue(':invoice_date', $invoice_date, PDO::PARAM_INT);
            $invoiceStmt->bindValue(':part_number', $partNumber, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':part_number_description', $part_number_description, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':unit_id', $unit_id, PDO::PARAM_INT);
            $invoiceStmt->bindValue(':description', $fullDescription, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':qty', $qty, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':rate', $rate_x, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':source_id', $source_id, PDO::PARAM_INT);
            $invoiceStmt->bindValue(':customer_uid', $customer_uid, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':qty_length', $qty_length, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':weight', $weight, PDO::PARAM_STR);
            $invoiceStmt->bindValue(':order_user_id', $order_user_id, PDO::PARAM_INT);

            if (!$invoiceStmt->execute()) {
                error_log('Invoice insert failed (no_items): ' . implode(' | ', $invoiceStmt->errorInfo()));
            }
        }
    }

    return json_encode(['success' => true, 'message' => 'Updated successfully.']);
}

function orderActivityValueChanged($oldValue, $newValue) {
    $oldValue = trim((string)$oldValue);
    $newValue = trim((string)$newValue);

    if (($oldValue === '' || $oldValue === '0') && ($newValue === '' || $newValue === '0')) {
        return false;
    }

    return $oldValue !== $newValue;
}

function orderActivityDateText($timestamp) {
    return !empty($timestamp) ? date('d-m-Y', (int)$timestamp) : 'Not set';
}

function getOrderStatusActivityLabel($status_id, $company_id) {
    $label = getTabFieCol('description', 'tblOrderStatus', 'id', $status_id, $company_id);
    return !empty($label) ? $label : 'Status #' . $status_id;
}

function buildOrderContactActivityDescriptions($oldOrder, $newData, $customer_uid, $order_date, $delivery_date, $company_id) {
    if (empty($oldOrder) || empty($newData['order_id'])) {
        return array();
    }

    $descriptions = array();

    if (orderActivityValueChanged($oldOrder['order_status_id'], $newData['order_status_id'])) {
        $oldStatus = getOrderStatusActivityLabel($oldOrder['order_status_id'], $company_id);
        $newStatus = getOrderStatusActivityLabel($newData['order_status_id'], $company_id);
        $descriptions[] = 'Status changed: ' . $oldStatus . ' -> ' . $newStatus;
    }

    if ((int)$oldOrder['delivery_date'] !== (int)$delivery_date) {
        $descriptions[] = 'Delivery changed: ' . orderActivityDateText($oldOrder['delivery_date']) . ' -> ' . orderActivityDateText($delivery_date);
    }

    if ((int)$oldOrder['order_date'] !== (int)$order_date) {
        $descriptions[] = 'Created date changed: ' . orderActivityDateText($oldOrder['order_date']) . ' -> ' . orderActivityDateText($order_date);
    }

    $noteFields = array(
        'deliver_note' => 'Delivery note',
        'deliver_instructions' => 'Delivery instructions',
        'customer_notes' => 'Customer notes'
    );
    $changedNotes = array();
    foreach ($noteFields as $field => $label) {
        if (array_key_exists($field, $oldOrder) && orderActivityValueChanged($oldOrder[$field], $newData[$field])) {
            $changedNotes[] = $label;
        }
    }
    if (!empty($changedNotes)) {
        $descriptions[] = 'Notes updated: ' . implode(', ', array_unique($changedNotes));
    }

    $trackedFields = array(
        'order_number' => array('label' => 'Order number', 'new' => $newData['order_number']),
        'cash_sale' => array('label' => 'Cash sale', 'new' => $newData['cash_sale']),
        'customer_uid' => array('label' => 'Customer', 'new' => $customer_uid),
        'order_user_id' => array('label' => 'Sales', 'new' => $newData['order_user_id']),
        'customer_contact' => array('label' => 'Customer contact', 'new' => $newData['customer_contact']),
        'customer_address' => array('label' => 'Customer address', 'new' => $newData['customer_address']),
        'customer_suburb' => array('label' => 'Customer suburb', 'new' => $newData['customer_suburb']),
        'customer_state' => array('label' => 'Customer state', 'new' => $newData['customer_state']),
        'customer_postcode' => array('label' => 'Customer postcode', 'new' => $newData['customer_postcode']),
        'customer_email' => array('label' => 'Customer email', 'new' => $newData['customer_email']),
        'customer_phone' => array('label' => 'Customer phone', 'new' => $newData['customer_phone']),
        'site_contact' => array('label' => 'Delivery contact', 'new' => $newData['site_contact']),
        'site_address' => array('label' => 'Delivery address', 'new' => $newData['site_address']),
        'site_suburb' => array('label' => 'Delivery suburb', 'new' => $newData['site_suburb']),
        'site_phone' => array('label' => 'Delivery phone', 'new' => $newData['site_phone']),
        'delivery_rate' => array('label' => 'Delivery rate', 'new' => $newData['delivery_rate']),
        'pickup_checkbox' => array('label' => 'Pickup', 'new' => $newData['pickup_checkbox']),
        'client_source_id' => array('label' => 'Source', 'new' => $newData['client_source_id'])
    );

    $changedLabels = array();
    foreach ($trackedFields as $field => $meta) {
        if (array_key_exists($field, $oldOrder) && orderActivityValueChanged($oldOrder[$field], $meta['new'])) {
            $changedLabels[] = $meta['label'];
        }
    }

    if (!empty($changedLabels)) {
        $descriptions[] = 'Customer/details updated: ' . implode(', ', array_unique($changedLabels));
    }

    return $descriptions;
}

function findOrderStatusIdByNames($conn, $company_id, $statusNames) {
    $placeholders = array();
    $params = array(':company_id' => $company_id);

    foreach ($statusNames as $index => $statusName) {
        $key = ':status_' . $index;
        $placeholders[] = $key;
        $params[$key] = strtolower($statusName);
    }

    $sql = "
        SELECT id, description
        FROM tblOrderStatus
        WHERE company_id = :company_id
          AND active = 1
          AND LOWER(TRIM(description)) IN (" . implode(',', $placeholders) . ")
        ORDER BY ordering ASC, id ASC
        LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();

    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function processOrderWorkflowActions() {
    return array(
        'upload_original_opened' => 'Upload original opened',
        'order_confirmation_printed' => 'Printed: Order confirmation',
        'order_confirmation_emailed' => 'Email sent: Order confirmation',
        'quote_printed' => 'Printed: Quote',
        'quote_emailed' => 'Email sent: Quote',
        'quote_payment_required' => 'Payment required to proceed',
        'quote_payment_required_cleared' => 'Payment required cleared',
        'quote_payment_received' => 'Payment received',
        'quote_payment_received_cleared' => 'Payment received cleared',
        'quote_converted_to_order' => 'Quote converted to order',
        'production_cards_printed' => 'Printed: Production cards',
        'production_csv_saved' => 'Saved: Production CSV files',
        'labels_dymo_printed' => 'Printed: Dymo labels',
        'labels_zebra_printed' => 'Printed: Zebra labels',
        'delivery_docket_printed' => 'Printed: Delivery docket',
        'order_processed' => 'Order processed'
    );
}

function processOrderWorkflowAliases() {
    return array(
        'upload_original_opened' => array('Upload original opened'),
        'order_confirmation_printed' => array('Printed: Order confirmation', 'Printed Order confirmation'),
        'order_confirmation_emailed' => array('Email sent: Order confirmation', 'Email sent Order confirmation'),
        'quote_printed' => array('Printed: Quote', 'Printed Quote'),
        'quote_emailed' => array('Email sent: Quote', 'Email sent Quote'),
        'quote_payment_required' => array('Payment required to proceed', 'Payment required cleared'),
        'quote_payment_required_cleared' => array('Payment required cleared'),
        'quote_payment_received' => array('Payment received', 'Payment received cleared'),
        'quote_payment_received_cleared' => array('Payment received cleared'),
        'quote_converted_to_order' => array('Quote converted to order'),
        'production_cards_printed' => array('Printed: Production cards', 'Printed Production cards'),
        'production_csv_saved' => array('Saved: Production CSV files', 'Saved Production CSV files'),
        'labels_dymo_printed' => array('Printed: Dymo labels', 'Printed Dymo labels'),
        'labels_zebra_printed' => array('Printed: Zebra labels', 'Printed Zebra labels'),
        'delivery_docket_printed' => array('Printed: Delivery docket', 'Printed Delivery docket'),
        'order_processed' => array('Order processed')
    );
}

function getProcessOrderWorkflowActivity($conn, $order_id, $company_id) {
    $actions = processOrderWorkflowActions();
    $aliases = processOrderWorkflowAliases();
    $types = array_keys($actions);
    $history = array();

    foreach ($types as $type) {
        $history[$type] = null;
    }

    $hasWorkflowType = orderActivityWorkflowColumnExists($conn);

    $params = array(
        ':order_id' => $order_id,
        ':company_id' => $company_id
    );
    $likeParts = array();
    $aliasIndex = 0;
    foreach ($aliases as $type => $typeAliases) {
        foreach ($typeAliases as $alias) {
            $key = ':label_' . $aliasIndex;
            $likeParts[] = "description LIKE " . $key;
            $params[$key] = $alias . '%';
            $aliasIndex++;
        }
    }

    if ($hasWorkflowType) {
        $placeholders = array();

        foreach ($types as $index => $type) {
            $key = ':type_' . $index;
            $placeholders[] = $key;
            $params[$key] = $type;
        }

        $sql = "
            SELECT workflow_type, action_date, user_id, description
            FROM tblOrderActivity
            WHERE order_id = :order_id
              AND company_id = :company_id
              AND (
                    workflow_type IN (" . implode(',', $placeholders) . ")
                    OR " . implode(' OR ', $likeParts) . "
                  )
            ORDER BY action_date DESC, id DESC
        ";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    } else {
        $sql = "
            SELECT '' AS workflow_type, action_date, user_id, description
            FROM tblOrderActivity
            WHERE order_id = :order_id
              AND company_id = :company_id
              AND (" . implode(' OR ', $likeParts) . ")
            ORDER BY action_date DESC, id DESC
        ";
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
    }

    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $workflowType = $row['workflow_type'];

        if (empty($workflowType)) {
            foreach ($aliases as $type => $typeAliases) {
                foreach ($typeAliases as $alias) {
                    if (strpos($row['description'], $alias) === 0) {
                        $workflowType = $type;
                        break 2;
                    }
                }
            }
        }
        if ($workflowType === 'quote_payment_required_cleared') {
            $workflowType = 'quote_payment_required';
        } elseif ($workflowType === 'quote_payment_received_cleared') {
            $workflowType = 'quote_payment_received';
        }

        if (!empty($workflowType) && array_key_exists($workflowType, $history) && $history[$workflowType] === null) {
            $history[$workflowType] = array(
                'description' => $row['description'],
                'date' => date('d/m/Y g:i A', (int)$row['action_date']),
                'user' => getUserFullName($row['user_id'])
            );
        }
    }

    return $history;
}

function getProcessOrderSummary($conn, $order_id, $company_id) {
    $summary = array(
        'packs' => 0,
        'manufactured_items' => 0,
        'total_items' => 0,
        'weight_kg' => 0
    );

    $packStmt = $conn->prepare("
        SELECT pack_number
        FROM tblOrderPacks
        WHERE order_id = :order_id
          AND company_id = :company_id
        ORDER BY pack_number
    ");
    $packStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $packStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $packStmt->execute();
    $packNumbers = $packStmt->fetchAll(PDO::FETCH_COLUMN);
    $summary['packs'] = count($packNumbers);

    if ($summary['packs'] <= 0) {
        $packFallbackStmt = $conn->prepare("
            SELECT DISTINCT pack_id
            FROM tblOrderSubItems
            WHERE order_id = :order_id
              AND pack_id > 0
            ORDER BY pack_id
        ");
        $packFallbackStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $packFallbackStmt->execute();
        $packNumbers = $packFallbackStmt->fetchAll(PDO::FETCH_COLUMN);
        $summary['packs'] = count($packNumbers);
    }

    $itemStmt = $conn->prepare("
        SELECT part_number
        FROM tblOrderItems
        WHERE order_id = :order_id
    ");
    $itemStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $itemStmt->execute();
    $partNumbers = $itemStmt->fetchAll(PDO::FETCH_COLUMN);
    $summary['total_items'] = count($partNumbers);

    foreach ($partNumbers as $partNumber) {
        if ((int)getTabFieCol('group_id', 'tblInventory', 'part_number', $partNumber, $company_id) === 1) {
            $summary['manufactured_items']++;
        }
    }

    foreach ($packNumbers as $packNumber) {
        $summary['weight_kg'] += (float)getSumWei($packNumber, $company_id, $order_id);
    }

    if ($summary['weight_kg'] <= 0) {
        $weightStmt = $conn->prepare("
            SELECT COALESCE(SUM(weight), 0)
            FROM tblOrderSubItems
            WHERE order_id = :order_id
        ");
        $weightStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $weightStmt->execute();
        $summary['weight_kg'] = (float)$weightStmt->fetchColumn();
    }

    $summary['weight_kg'] = round((float)$summary['weight_kg'], 1);

    return $summary;
}





if (isset($_POST['action']) && $_POST['action'] === 'toggle_order_item_tag') {
    if (!isset($_POST['item_id']) || !is_numeric($_POST['item_id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    $item_id = (int) $_POST['item_id'];

    $sql = "UPDATE tblOrderItems SET tag = IF(tag = 1, 0, 1) WHERE id = :item_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':item_id', $item_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'toggled' => true, 'item_id' => $item_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Query failed']);
    }
    exit;
}



// Example usage
//insertInvoiceItems(2, 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    include_once("../../includes/common.php");

    $data_raw = json_decode(file_get_contents("php://input"), true);

if (isset($data_raw['action']) && $data_raw['action'] === 'process_order_to_production') {
    header('Content-Type: application/json');

    $data = sanInputs($data_raw);
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $company_id = (int)$_SESSION['session_company_id'];

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing order id.']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    try {
        $orderStmt = $conn->prepare("
            SELECT order_id, order_status_id
            FROM tblOrders
            WHERE order_id = :order_id
              AND company_id = :company_id
            LIMIT 1
        ");
        $orderStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $orderStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        $productionStatus = findOrderStatusIdByNames($conn, $company_id, array('In Production', 'Production'));

        if (!$productionStatus) {
            echo json_encode(['success' => false, 'message' => 'No active order status named In Production was found.']);
            exit;
        }

        if ((int)$order['order_status_id'] === (int)$productionStatus['id']) {
            echo json_encode(['success' => true, 'message' => 'Order is already in production.']);
            exit;
        }

        $update = $conn->prepare("
            UPDATE tblOrders
            SET order_status_id = :order_status_id
            WHERE order_id = :order_id
              AND company_id = :company_id
        ");
        $update->bindValue(':order_status_id', (int)$productionStatus['id'], PDO::PARAM_INT);
        $update->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $update->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $update->execute();

        addOrderActivity($order_id, $company_id, 5, 'Order processed: moved to ' . $productionStatus['description'], $_SESSION['session_user_id'], 0, 'order_processed');

        echo json_encode([
            'success' => true,
            'message' => 'Order processed and moved to ' . $productionStatus['description'] . '.',
            'order_status_id' => (int)$productionStatus['id'],
            'order_status' => $productionStatus['description'],
            'history' => getProcessOrderWorkflowActivity($conn, $order_id, $company_id)
        ]);
        exit;
    } catch (Exception $e) {
        error_log('process_order_to_production error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error while processing order.']);
        exit;
    }
}

if (isset($data_raw['action']) && $data_raw['action'] === 'convert_quote_to_order') {
    header('Content-Type: application/json');

    $data = sanInputs($data_raw);
    $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
    $company_id = (int)$_SESSION['session_company_id'];
    $payment_required = !empty($data['payment_required']);
    $payment_received = !empty($data['payment_received']);

    if ($order_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Missing order id.']);
        exit;
    }

    if ($payment_required && !$payment_received) {
        echo json_encode(['success' => false, 'message' => 'Payment must be received before converting this quote.']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    try {
        $orderStmt = $conn->prepare("
            SELECT order_id, order_status_id
            FROM tblOrders
            WHERE order_id = :order_id
              AND company_id = :company_id
            LIMIT 1
        ");
        $orderStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $orderStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        $orderStatus = findOrderStatusIdByNames($conn, $company_id, array('Order'));

        if (!$orderStatus) {
            echo json_encode(['success' => false, 'message' => 'No active order status named Order was found.']);
            exit;
        }

        if ($payment_required && $payment_received) {
            addOrderActivity($order_id, $company_id, 5, 'Payment received', $_SESSION['session_user_id'], 0, 'quote_payment_received');
        }

        if ((int)$order['order_status_id'] !== (int)$orderStatus['id']) {
            $update = $conn->prepare("
                UPDATE tblOrders
                SET order_status_id = :order_status_id
                WHERE order_id = :order_id
                  AND company_id = :company_id
            ");
            $update->bindValue(':order_status_id', (int)$orderStatus['id'], PDO::PARAM_INT);
            $update->bindValue(':order_id', $order_id, PDO::PARAM_INT);
            $update->bindValue(':company_id', $company_id, PDO::PARAM_INT);
            $update->execute();
        }

        addOrderActivity($order_id, $company_id, 5, 'Quote converted to order', $_SESSION['session_user_id'], 0, 'quote_converted_to_order');

        echo json_encode([
            'success' => true,
            'message' => 'Quote converted to order.',
            'order_status_id' => (int)$orderStatus['id'],
            'order_status' => $orderStatus['description'],
            'history' => getProcessOrderWorkflowActivity($conn, $order_id, $company_id)
        ]);
        exit;
    } catch (Exception $e) {
        error_log('convert_quote_to_order error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error while converting quote.']);
        exit;
    }
}

if (isset($data_raw['action']) && $data_raw['action'] === 'record_process_order_activity') {
    header('Content-Type: application/json');

    try {
        $database = new Database();
        $conn = $database->connect();
        $company_id = (int)$_SESSION['session_company_id'];
        $order_id = isset($data_raw['order_id']) ? (int)$data_raw['order_id'] : 0;
        $workflow_type = isset($data_raw['workflow_type']) ? trim((string)$data_raw['workflow_type']) : '';
        $actions = processOrderWorkflowActions();

        if ($order_id <= 0 || !array_key_exists($workflow_type, $actions)) {
            echo json_encode(['success' => false, 'message' => 'Invalid process order activity.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT order_id FROM tblOrders WHERE order_id = :order_id AND company_id = :company_id LIMIT 1");
        $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $stmt->execute();
        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        addOrderActivity($order_id, $company_id, 5, $actions[$workflow_type], $_SESSION['session_user_id'], 0, $workflow_type);

        echo json_encode([
            'success' => true,
            'history' => getProcessOrderWorkflowActivity($conn, $order_id, $company_id),
            'summary' => getProcessOrderSummary($conn, $order_id, $company_id)
        ]);
        exit;
    } catch (Exception $e) {
        error_log('record_process_order_activity error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error while recording process activity.']);
        exit;
    }
}

if (isset($data_raw['action']) && $data_raw['action'] === 'get_process_order_activity') {
    header('Content-Type: application/json');

    try {
        $database = new Database();
        $conn = $database->connect();
        $company_id = (int)$_SESSION['session_company_id'];
        $order_id = isset($data_raw['order_id']) ? (int)$data_raw['order_id'] : 0;

        if ($order_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order id.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'history' => getProcessOrderWorkflowActivity($conn, $order_id, $company_id)
        ]);
        exit;
    } catch (Exception $e) {
        error_log('get_process_order_activity error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error while loading process activity.']);
        exit;
    }
}

if (isset($data_raw['action']) && $data_raw['action'] === 'get_process_order_summary') {
    header('Content-Type: application/json');

    try {
        $database = new Database();
        $conn = $database->connect();
        $company_id = (int)$_SESSION['session_company_id'];
        $order_id = isset($data_raw['order_id']) ? (int)$data_raw['order_id'] : 0;

        if ($order_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order id.']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'summary' => getProcessOrderSummary($conn, $order_id, $company_id)
        ]);
        exit;
    } catch (Exception $e) {
        error_log('get_process_order_summary error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error while loading process summary.']);
        exit;
    }
}

if (isset($data_raw['action']) && $data_raw['action'] === 'get_process_order_csv_parts') {
    header('Content-Type: application/json');

    try {
        $database = new Database();
        $conn = $database->connect();
        $company_id = (int)$_SESSION['session_company_id'];
        $order_id = isset($data_raw['order_id']) ? (int)$data_raw['order_id'] : 0;

        if ($order_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid order id.']);
            exit;
        }

        $orderStmt = $conn->prepare("
            SELECT order_id, customer_contact
            FROM tblOrders
            WHERE order_id = :order_id
              AND company_id = :company_id
            LIMIT 1
        ");
        $orderStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $orderStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            echo json_encode(['success' => false, 'message' => 'Order not found.']);
            exit;
        }

        $stmt = $conn->prepare("
            SELECT DISTINCT oi.part_number
            FROM tblOrderItems oi
            JOIN tblInventory i
              ON i.part_number = oi.part_number
             AND i.company_id = oi.company_id
            WHERE oi.order_id = :order_id
              AND oi.company_id = :company_id
              AND i.group_id = 1
              AND (oi.purchased_item IS NULL OR oi.purchased_item = '' OR oi.purchased_item = 0)
            ORDER BY oi.part_number
        ");
        $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $stmt->execute();
        $parts = $stmt->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'parts' => $parts,
            'customer_contact' => $order['customer_contact']
        ]);
        exit;
    } catch (Exception $e) {
        error_log('get_process_order_csv_parts error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error while loading production CSV parts.']);
        exit;
    }
}

    
if (isset($data_raw['action']) && $data_raw['action'] === 'create_po_from_tagged') {

    header('Content-Type: application/json');

    $database = new Database();
    $conn = $database->connect();

    $company_id = $_SESSION['session_company_id'];
    $purchaser_user_id = $_SESSION['session_user_id'];

    $order_id = (int)$data_raw['order_id'];
    $vendor_source_po_id = (int)$data_raw['vendor_source_po_id'];

    if ($order_id <= 0 || $vendor_source_po_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid order or vendor selection.']);
        exit;
    }

    try {

        $conn->beginTransaction();

        $srcStmt = $conn->prepare("
            SELECT
                vendor_uid,
                vendor_name,
                vendor_address,
                vendor_suburb,
                vendor_state,
                vendor_postcode,
                vendor_phone,
                vendor_email
            FROM tblPurchaseOrders
            WHERE id = :id
            AND company_id = :company_id
            LIMIT 1
        ");
        $srcStmt->bindValue(':id', $vendor_source_po_id, PDO::PARAM_INT);
        $srcStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $srcStmt->execute();
        $src = $srcStmt->fetch(PDO::FETCH_ASSOC);

        if (!$src) {
            throw new Exception('Source PO not found.');
        }

        $orderStmt = $conn->prepare("
            SELECT delivery_date
            FROM tblOrders
            WHERE order_id = :order_id
            AND company_id = :company_id
            LIMIT 1
        ");
        $orderStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $orderStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $orderStmt->execute();
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new Exception('Order not found.');
        }

        $requiredDate = !empty($order['delivery_date']) ? (int)$order['delivery_date'] : null;

        $insertPO = $conn->prepare("
            INSERT INTO tblPurchaseOrders (
                company_id,
                vendor_uid,
                vendor_name,
                vendor_address,
                vendor_suburb,
                vendor_state,
                vendor_postcode,
                vendor_phone,
                vendor_email,
                ven_inv_number,
                order_date,
                order_date_required,
                delivery_address_line1,
                delivery_address_suburb,
                delivery_postcode,
                delivery_state,
                purchaser_user_id,
                freight,
                order_notes,
                additional_notes,
                order_receive_date,
                order_receive_ref,
                order_receive_note,
                invoice_date,
                invoice_ref,
                invoice_note,
                payment_terms_day,
                payment_terms_type,
                order_status_id
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
                '',
                :order_date,
                :order_date_required,
                '',
                '',
                '',
                '',
                :purchaser_user_id,
                0.00,
                :order_notes,
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                NULL,
                NULL,
                1
            )
        ");

        $insertPO->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $insertPO->bindValue(':vendor_uid', $src['vendor_uid']);
        $insertPO->bindValue(':vendor_name', $src['vendor_name']);
        $insertPO->bindValue(':vendor_address', $src['vendor_address']);
        $insertPO->bindValue(':vendor_suburb', $src['vendor_suburb']);
        $insertPO->bindValue(':vendor_state', $src['vendor_state']);
        $insertPO->bindValue(':vendor_postcode', $src['vendor_postcode']);
        $insertPO->bindValue(':vendor_phone', $src['vendor_phone']);
        $insertPO->bindValue(':vendor_email', $src['vendor_email']);
        $insertPO->bindValue(':order_date', time());
        $insertPO->bindValue(':order_date_required', $requiredDate, $requiredDate === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $insertPO->bindValue(':purchaser_user_id', $purchaser_user_id, PDO::PARAM_INT);
        $insertPO->bindValue(':order_notes', "Copied from customer order " . $order_id);

        $insertPO->execute();

        $new_pid = (int)$conn->lastInsertId();

        $itemsStmt = $conn->prepare("
            SELECT *
            FROM tblOrderItems
            WHERE company_id = :company_id
            AND order_id = :order_id
            AND tag = 1
            AND (purchased_item IS NULL OR purchased_item = '' OR purchased_item = 0)
        ");
        $itemsStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
        $itemsStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$items) {
            throw new Exception('No uncopied tagged items found.');
        }

        $copiedItemIds = array();

        foreach ($items as $row) {

            $invStmt = $conn->prepare("
                SELECT buy_rate
                FROM tblInventory
                WHERE part_number = :part_number
                LIMIT 1
            ");
            $invStmt->bindValue(':part_number', $row['part_number']);
            $invStmt->execute();
            $inv = $invStmt->fetch(PDO::FETCH_ASSOC);

            $buy_rate = ($inv && $inv['buy_rate'] !== null) ? $inv['buy_rate'] : 0.00;

            $insertPI = $conn->prepare("
                INSERT INTO tblPurchaseItems
                (company_id, pid, part_number, description, has_items, qty, rate, unit_id)
                VALUES
                (:company_id, :pid, :part_number, :description, :has_items, :qty, :rate, :unit_id)
            ");

            $insertPI->bindValue(':company_id', $company_id, PDO::PARAM_INT);
            $insertPI->bindValue(':pid', $new_pid, PDO::PARAM_INT);
            $insertPI->bindValue(':part_number', $row['part_number']);
            $insertPI->bindValue(':description', $row['description']);
            $insertPI->bindValue(':has_items', $row['has_items'], PDO::PARAM_INT);
            $insertPI->bindValue(':rate', $buy_rate);
            $insertPI->bindValue(':unit_id', $row['unit_id']);

            if ((int)$row['has_items'] === 1) {

                $insertPI->bindValue(':qty', 0);
                $insertPI->execute();
                $new_pi_id = (int)$conn->lastInsertId();

                $subStmt = $conn->prepare("
                    SELECT *
                    FROM tblOrderSubItems
                    WHERE company_id = :company_id
                    AND order_id = :order_id
                    AND order_group_id = :group_id
                ");
                $subStmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                $subStmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
                $subStmt->bindValue(':group_id', $row['id'], PDO::PARAM_INT);
                $subStmt->execute();
                $subs = $subStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($subs as $sub) {

                    $insertSub = $conn->prepare("
                        INSERT INTO tblPurchaseSubItems
                        (company_id, pid, order_group_id, part_number, description, mark, qty, qty_unit)
                        VALUES
                        (:company_id, :pid, :order_group_id, :part_number, :description, :mark, :qty, :qty_unit)
                    ");

                    $insertSub->bindValue(':company_id', $company_id, PDO::PARAM_INT);
                    $insertSub->bindValue(':pid', $new_pid, PDO::PARAM_INT);
                    $insertSub->bindValue(':order_group_id', $new_pi_id, PDO::PARAM_INT);
                    $insertSub->bindValue(':part_number', $sub['part_number']);
                    $insertSub->bindValue(':description', $sub['description']);
                    $insertSub->bindValue(':mark', $sub['mark']);
                    $insertSub->bindValue(':qty', $sub['qty']);
                    $insertSub->bindValue(':qty_unit', $sub['qty_unit']);
                    $insertSub->execute();
                }

            } else {

                $insertPI->bindValue(':qty', $row['qty']);
                $insertPI->execute();
            }

            $copiedItemIds[] = (int)$row['id'];
        }

        if (!empty($copiedItemIds)) {
            $placeholders = implode(',', array_fill(0, count($copiedItemIds), '?'));
            $updateItems = $conn->prepare("
                UPDATE tblOrderItems
                SET purchased_item = ?, tag = 0
                WHERE company_id = ?
                AND order_id = ?
                AND id IN ($placeholders)
            ");

            $params = array_merge(array($new_pid, $company_id, $order_id), $copiedItemIds);
            $updateItems->execute($params);
        }

        addOrderActivity($order_id, $company_id, 5, 'Purchase order created: PO #' . $new_pid . ' created from tagged items', $_SESSION['session_user_id'], 0);
        addPurchaseActivity($new_pid, $company_id, 5, 'Created from customer order #' . $order_id . ' tagged items', $_SESSION['session_user_id'], 0);

        $conn->commit();

        echo json_encode(['success' => true, 'pid' => $new_pid, 'copied_items' => count($copiedItemIds)]);
        exit;

    } catch (Exception $e) {

        if ($conn->inTransaction()) {
            $conn->rollBack();
        }

        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
	
if (isset($data_raw['action']) && $data_raw['action'] == 'get_vendors_grouped') {
    $database = new Database();
    $conn = $database->connect();
$query = "SELECT 
            MAX(order_date) AS order_date,
            MAX(id) AS id,
            TRIM(vendor_name) AS vendor_name
          FROM tblPurchaseOrders
          WHERE vendor_name IS NOT NULL AND vendor_name <> ''
          GROUP BY TRIM(vendor_name)
          ORDER BY MAX(order_date) DESC, MAX(id) DESC";



    $stmt = $conn->prepare($query);
    $stmt->execute();

    $vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($vendors);

    $conn = null;
    exit;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_invoice') {
    // get & sanitise
    $data = sanInputs($data_raw);

    // del_in_id is the ORDER ID you want to wipe from tblInvoice
    if (!isset($data['del_in_id']) || !is_numeric($data['del_in_id'])) {
        echo json_encode(['error' => true, 'message' => 'Missing or invalid order id.']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    // do the delete
    $query = "DELETE FROM tblInvoice 
              WHERE company_id = :company_id 
                AND order_id   = :order_id";

    $bindings = array(
        ':company_id' => $_SESSION['session_company_id'],
        ':order_id'   => (int)$data['del_in_id']
    );

    $rowCount = 0;
    try {
        if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
            echo json_encode(['success' => true, 'message' => 'Invoice deleted.', 'rows_affected' => $rowCount]);
        } else {
            echo json_encode(['error' => true, 'message' => 'Failed to delete invoice.']);
        }
    } catch (Exception $e) {
        error_log('delete_invoice error: ' . $e->getMessage());
        echo json_encode(['error' => true, 'message' => 'Server error while deleting invoice.']);
    }
}


    
if (isset($data_raw['action']) && $data_raw['action'] == 'read_order') {
        $data = sanInputs($data_raw);
        $database = new Database();
        $conn = $database->connect();
        $return_arr = array();
        $query = "SELECT * FROM tblOrders WHERE order_id= :order_id AND company_id = :company_id";
        $result = $conn->prepare($query);
        $result->bindParam(':order_id', $data['order_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
    
    

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $row_data = array(
                'order_date' => date_c($row['order_date']),
                'order_number' => $row['order_number'],
                'cash_sale' => $row['cash_sale'],
				'client_source_id' => $row['client_source_id'],

                'client_source' => getTabFieCol('description', 'tblClientSource', 'id', $row['client_source_id'],$_SESSION['session_company_id']),
                'order_user_id' => $row['order_user_id'],
                'order_user' => getTableField('first_lastname', 'tblUsers', $row['order_user_id']),
                'customer_company' => $row['customer_company'],
                'customer_uid' => $row['customer_uid'],
                'customer_contact' => $row['customer_contact'],
				'customer_address' => $row['customer_address'],
				'customer_suburb' => $row['customer_suburb'],
				'customer_state' => $row['customer_state'],
				'customer_postcode' => $row['customer_postcode'],
				'customer_phone' => $row['customer_phone'],
				'customer_email' => $row['customer_email'],
                
                'order_status_id' => $row['order_status_id'],
                
                'order_status' => getTabFieCol('description', 'tblOrderStatus', 'id', $row['order_status_id'],$_SESSION['session_company_id']),
                
				'site_address_full' => $row['site_address'].','.$row['site_suburb'],
				'site_contact' => $row['site_contact'],
				'site_address' => $row['site_address'],
				'site_suburb' => $row['site_suburb'],
				'deliver_note' => $row['deliver_note'],
				'deliver_instructions' => $row['deliver_instructions'],
				'site_phone' => $row['site_phone'],
                'customer_notes' => $row['customer_notes'],
                
                'delivery_rate' => $row['delivery_rate'],
                'delivery_date' => date_c($row['delivery_date']),
                'pickup_checkbox' => $row['pickup_checkbox'],
                
                'order_invoiced' => getTabFieCol('transaction_uid', 'tblInvoice', 'order_id', $data['order_id'], $_SESSION['session_company_id']),
                'order_invoiced_date' => getTabFieCol('invoice_date', 'tblInvoice', 'order_id', $data['order_id'], $_SESSION['session_company_id']),
                'price_level' => $row['price_level'],
				
            );
            array_push($return_arr, $row_data);
        }
        header('Content-Type: application/json');
        echo json_encode($return_arr);
    }
    
if (isset($data_raw['action']) && $data_raw['action'] == 'save_contacts') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    
    if ($data['cash_sale'] == 1) {
        $customer_uid = getFieldColumn('cash_sale_uid', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
    } else {
        $customer_uid = $data['customer_uid'];
    }

    $order_date = strtotime($data['order_date']);
    $delivery_date = strtotime($data['delivery_date']);

    $existingOrder = null;
    $existingQuery = "SELECT * FROM tblOrders WHERE order_id = :order_id AND company_id = :company_id";
    $existingStmt = $conn->prepare($existingQuery);
    $existingStmt->bindValue(':order_id', $data['order_id'], PDO::PARAM_INT);
    $existingStmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $existingStmt->execute();
    $existingOrder = $existingStmt->fetch(PDO::FETCH_ASSOC);
    
    $query = "UPDATE tblOrders SET 
                order_date = :order_date,
                order_number = :order_number,
                cash_sale = :cash_sale,
                customer_uid = :customer_uid,
                order_user_id = :order_user_id,
                customer_contact = :customer_contact,
                customer_address = :customer_address,
                customer_suburb = :customer_suburb,
                customer_state = :customer_state,
                customer_postcode = :customer_postcode,
                customer_email = :customer_email,
                customer_phone = :customer_phone,
                site_contact = :site_contact,
                site_address = :site_address,
                site_suburb = :site_suburb,
                deliver_note = :deliver_note,
                deliver_instructions = :deliver_instructions,
                site_phone = :site_phone,
                customer_notes = :customer_notes,
                delivery_date = :delivery_date,
                delivery_rate = :delivery_rate,
                pickup_checkbox = :pickup_checkbox,
                client_source_id = :client_source_id,
                order_status_id = :order_status_id
                WHERE order_id = :order_id AND company_id = :company_id";

    $bindings = array(
        ':order_id' => $data['order_id'],
        ':order_date' => $order_date,
        ':order_number' => $data['order_number'],
        ':cash_sale' => $data['cash_sale'],
        ':customer_uid' => $customer_uid,
        ':order_user_id' => $data['order_user_id'],
        ':company_id' => $_SESSION['session_company_id'],
        ':customer_contact' => $data['customer_contact'],
        ':customer_address' => $data['customer_address'],
        ':customer_suburb' => $data['customer_suburb'],
        ':customer_state' => $data['customer_state'],
        ':customer_postcode' => $data['customer_postcode'],
        ':customer_email' => $data['customer_email'],
        ':customer_phone' => $data['customer_phone'],
        ':site_contact' => $data['site_contact'],
        ':site_address' => $data['site_address'],
        ':site_suburb' => $data['site_suburb'],
        ':deliver_note' => $data['deliver_note'],
        ':deliver_instructions' => $data['deliver_instructions'],
        ':site_phone' => $data['site_phone'],
        ':customer_notes' => $data['customer_notes'],
        ':delivery_date' => $delivery_date,
        ':delivery_rate' => $data['delivery_rate'],
        ':pickup_checkbox' => $data['pickup_checkbox'],
        ':client_source_id' => $data['client_source_id'],
        ':order_status_id' => $data['order_status_id']
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            $activityDescriptions = buildOrderContactActivityDescriptions($existingOrder, $data, $customer_uid, $order_date, $delivery_date, $_SESSION['session_company_id']);
            foreach ($activityDescriptions as $activityDescription) {
                addOrderActivity($data['order_id'], $_SESSION['session_company_id'], 5, $activityDescription, $_SESSION['session_user_id'], 0);
            }
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made.']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Failed to update the order.']);
    }
}

if (isset($data_raw['action']) && $data_raw['action'] == 'create_order') {
    $database = new Database();
    $conn = $database->connect();

    //  Extract JSON string version of payment_terms before sanitising
    $paymentTermsJson = json_encode($data_raw['payment_terms']);

    // Now sanitise everything else
    $data = sanInputs($data_raw);

    // Put raw JSON back into the array
    $data['payment_terms'] = $paymentTermsJson;

    // Get the next order_id
    $order_id = getNextOrderId();

    if ($data['cash_sale'] == 1) {
        $customer_uid = getFieldColumn('cash_sale_uid', 'tblAccounting', 'company_id', $_SESSION['session_company_id']);
        $order_number = $data['site_contact'];
    } else {
        $customer_uid = $data['customer_uid'];
        $order_number = $data['order_number'];
    }

    $order_date = time();
    $order_status_id = $data['order_status_id'];
    $delivery_date = strtotime($data['delivery_date']);

    $query = "INSERT INTO tblOrders (
                order_id,
                order_date,
                order_number,
                cash_sale,
                customer_uid,
                order_status_id,
                order_user_id,
                company_id,
                customer_company,
                price_level,
                customer_contact,
                customer_address,
                customer_suburb,
                customer_state,
                customer_postcode,
                customer_email,
                customer_phone,
                site_contact,
                site_address,
                site_suburb,
                deliver_note,
                deliver_instructions,
                site_phone,
                customer_notes,
                delivery_date,
                delivery_rate,
                payment_terms
              ) VALUES (
                :order_id,
                :order_date,
                :order_number,
                :cash_sale,
                :customer_uid,
                :order_status_id,
                :order_user_id,
                :company_id,
                :customer_company,
                :price_level,
                :customer_contact,
                :customer_address,
                :customer_suburb,
                :customer_state,
                :customer_postcode,
                :customer_email,
                :customer_phone,
                :site_contact,
                :site_address,
                :site_suburb,
                :deliver_note,
                :deliver_instructions,
                :site_phone,
                :customer_notes,
                :delivery_date,
                :delivery_rate,
                :payment_terms
              )";

    $bindings = array(
        ':order_id' => $order_id,
        ':order_date' => $order_date,
        ':order_number' => $order_number,
        ':cash_sale' => $data['cash_sale'],
        ':customer_uid' => $customer_uid,
        ':order_status_id' => $order_status_id,
        ':order_user_id' => $_SESSION['session_user_id'],
        ':company_id' => $_SESSION['session_company_id'],
        ':customer_company' => $data['customer_company'],
        ':price_level' => $data['price_level'],
        ':customer_contact' => $data['customer_contact'],
        ':customer_address' => $data['customer_address'],
        ':customer_suburb' => $data['customer_suburb'],
        ':customer_state' => $data['customer_state'],
        ':customer_postcode' => $data['customer_postcode'],
        ':customer_email' => $data['customer_email'],
        ':customer_phone' => $data['customer_phone'],
        ':site_contact' => $data['site_contact'],
        ':site_address' => $data['site_address'],
        ':site_suburb' => $data['site_suburb'],
        ':deliver_note' => $data['deliver_note'],
        ':deliver_instructions' => $data['deliver_instructions'],
        ':site_phone' => $data['site_phone'],
        ':customer_notes' => $data['customer_notes'],
        ':delivery_date' => $delivery_date,
        ':delivery_rate' => $data['delivery_rate'],
        ':payment_terms' => $data['payment_terms'], // <-- stays JSON
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Order created successfully.',
                'order_id' => $order_id
            ]);
            addOrderActivity($order_id, $_SESSION['session_company_id'], 5, 'New order created', $_SESSION['session_user_id'], 0);
        } else {
            echo json_encode([
                'error' => true,
                'message' => 'Failed to create order.'
            ]);
        }
    }
}

   
    
if (isset($data_raw['action']) && $data_raw['action'] == 'add_order_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $price_level = getTableColFieldX2('price_level','tblOrders','order_id',$data['order_id'], 'company_id',$_SESSION['session_company_id']);

    //NEW: Map price level to correct rate column
// AMENDED BLOCK: Map price level to correct rate column
$priceLevelMap = [
    'levela' => 'rateLevelA',
    'levelb' => 'rateLevelB',
    'levelc' => 'rateLevelC',
    'leveld' => 'rateLevelD',
    'levele' => 'rateLevelE',
    'levelf' => 'rateLevelF',
    'levelg' => 'rateLevelG',
    'levelh' => 'rateLevelH',
    'leveli' => 'rateLevelI',
    'levelj' => 'rateLevelJ',
];

$normalized_level = strtolower(preg_replace('/\s+/', '', trim((string)$price_level)));
$rate_field = isset($priceLevelMap[$normalized_level]) ? $priceLevelMap[$normalized_level] : 'rate';

$rate = getFieldColumn($rate_field, 'tblInventory', 'part_number', $data['part_number']);

if ($rate === null || $rate === '') {
    $rate = getFieldColumn('rate', 'tblInventory', 'part_number', $data['part_number']);
}

$description = getFieldColumn('description', 'tblInventory', 'part_number', $data['part_number']);
    $unit_id = getFieldColumn('unit_id', 'tblInventory', 'part_number', $data['part_number']);
    $weight_unit = getFieldColumn('weight_unit', 'tblInventory', 'part_number', $data['part_number']);

    // Check if the part number already exists in tblOrderItems for the same order_id and company_id
    $checkQuery = "SELECT id FROM tblOrderItems WHERE order_id = :order_id AND company_id = :company_id AND part_number = :part_number";
    $checkBindings = array(
        ':order_id' => $data['order_id'],
        ':company_id' => $_SESSION['session_company_id'],
        ':part_number' => $data['part_number']
    );

    $stmt = $conn->prepare($checkQuery);
    $stmt->execute($checkBindings);

    if ($stmt->rowCount() > 0) {
        // Part number already exists, get the existing order_group_id
        $existingOrderGroup = $stmt->fetch(PDO::FETCH_ASSOC);
        $orderGroupId = $existingOrderGroup['id'];

        // Insert into tblOrderSubItems
            $queryItems = "INSERT INTO tblOrderSubItems 
               (company_id, order_id, order_group_id, part_number, description, mark, punch, qty, qty_unit, weight)
               VALUES 
               (:company_id, :order_id, :order_group_id, :part_number, :description, :mark, :punch, :qty, :qty_unit, :weight)";

        $bindingsItems = array(
            ':company_id' => $_SESSION['session_company_id'],
            ':order_id' => $data['order_id'],
            ':order_group_id' => $orderGroupId,
            ':part_number' => $data['part_number'],
            ':description' => $description,
            ':mark' => $data['mark'],
            ':punch' => $data['punch'],
            ':qty' => $data['qty'],
            ':qty_unit' => $data['qty_unit'],
            ':weight' => ($data['qty'] * $data['qty_unit']) * $weight_unit
        );

        $rowCountItems = 0;
        executeDatabaseQuery($conn, $queryItems, $bindingsItems, $rowCountItems);

        echo json_encode(['success' => true, 'message' => 'Order item added successfully.']);
    } else {
        // Part number does not exist, insert into tblOrderItems with has_items set to 1
        $query = "INSERT INTO tblOrderItems 
                  (order_id, company_id, part_number, description, qty, rate, unit_id, has_items)
                  VALUES 
                  (:order_id, :company_id, :part_number, :description, :qty, :rate, :unit_id, :has_items)";

        $bindings = array(
            ':order_id' => $data['order_id'],
            ':has_items' => $data['has_items'],
            ':company_id' => $_SESSION['session_company_id'],
            ':part_number' => $data['part_number'],
            ':description' => $description,
            ':qty' => $data['qty_item'],
            ':rate' => $rate,
            ':unit_id' => $unit_id,
        );

        $rowCount = 0;
        if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
            if ($rowCount > 0) {
                $orderGroupId = $conn->lastInsertId(); // Get the last inserted ID

                // Insert into tblOrderSubItems
                $queryItems = "INSERT INTO tblOrderSubItems 
               (company_id, order_id, order_group_id, part_number, description, mark, punch, qty, qty_unit, weight)
               VALUES 
               (:company_id, :order_id, :order_group_id, :part_number, :description, :mark, :punch, :qty, :qty_unit, :weight)";

                  $bindingsItems = array(
                ':company_id' => $_SESSION['session_company_id'],
                ':order_id' => $data['order_id'],
                ':order_group_id' => $orderGroupId,
                ':part_number' => $data['part_number'],
                ':description' => $description,
                ':mark' => $data['mark'],
                ':punch' => $data['punch'],
                ':qty' => $data['qty'],
                ':qty_unit' => $data['qty_unit'],
                ':weight' => ($data['qty'] * $data['qty_unit']) * $weight_unit
            );

                $rowCountItems = 0;
                executeDatabaseQuery($conn, $queryItems, $bindingsItems, $rowCountItems);

                echo json_encode(['success' => true, 'message' => 'Order item added successfully.']);
            } else {
                echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'An error occurred while adding the order item.']);
        }
    }
}




if (isset($data_raw['action']) && $data_raw['action'] == 'read_order_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblOrderItems WHERE id = :item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':item_id', $data['item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'part_number' => $row['part_number'],
            'description' => $row['description'],  
            'rate' => $row['rate'],  
            'qty' => $row['qty'],
            'has_items' => $row['has_items']
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
    $query = "SELECT * FROM tblOrderSubItems WHERE id = :sub_item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':sub_item_id', $data['sub_item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'mark' => $row['mark'],
            'punch' => $row['punch'],
            'qty' => $row['qty'],
            'qty_unit' => $row['qty_unit'],  // Ensure this column exists in your database

        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}
if (isset($data_raw['action']) && $data_raw['action'] == 'read_invoice_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblInvoice WHERE id = :invoice_item_id";
    $result = $conn->prepare($query);
    $result->bindParam(':invoice_item_id', $data['invoice_item_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'description' => $row['description'],
            'qty' => $row['qty'],
            'rate' => $row['rate']

        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}
// AMENDED BLOCK: replaces your read_invoice action
if (isset($data_raw['action']) && $data_raw['action'] == 'read_invoice') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();

    // Get latest invoice row for the order (1 row)
    $query = "SELECT invoice_date
              FROM tblInvoice
              WHERE order_id = :invoice_id
              ORDER BY id DESC
              LIMIT 1";
    $result = $conn->prepare($query);
    $result->bindParam(':invoice_id', $data['invoice_id'], PDO::PARAM_INT);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $return_arr[] = array(
            // format for the jQuery UI datepicker (dd-mm-YYYY)
            'invoice_date' => !empty($row['invoice_date'])
                ? date('d-m-Y', (int)$row['invoice_date'])
                : ''
        );
    }

    echo json_encode($return_arr);
}

if (isset($data_raw['action']) && $data_raw['action'] == 'save_invoice_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblInvoice SET 
                description = :description, 
                qty = :qty,
                rate = :rate
                WHERE id = :invoice_item_id";
    
    $bindings = array(
        ':invoice_item_id' => $data['invoice_item_id'],
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
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_invoice_item') {	
	deleteId('tblInvoice', $data_raw['id']);
}    
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_order_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_item_id'])) {
        $id = $data['del_item_id'];
        deleteFieldCol('tblOrderSubItems', 'order_group_id', $id);
        deleteId('tblOrderItems', $id);
        
    } else {
        echo json_encode(["error" => "del_item_id is not set."]);
    }
}
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_order_sub_item') {
    $data = sanInputs($data_raw);
    if (isset($data['del_sub_item_id'])) {
        $id = $data['del_sub_item_id'];
        deleteId('tblOrderSubItems', $id);
    } else {
        echo json_encode(["error" => "del_sub_item_id is not set."]);
    }
}     
if (isset($data_raw['action']) && $data_raw['action'] == 'get_part_number') {

    $database = new Database();
    $conn = $database->connect();

    $term = sanInputs($data_raw['term']);

    // Make order_id safe even if the div contains extra text/spaces
    $order_id_raw = isset($data_raw['order_id']) ? $data_raw['order_id'] : '';
    $order_id = (int)preg_replace('/[^0-9]/', '', (string)$order_id_raw);

    $price_level = getTableColFieldX2(
        'price_level',
        'tblOrders',
        'order_id',
        $order_id,
        'company_id',
        $_SESSION['session_company_id']
    );

    // Normalized map keys: "Level E" => "levele"
    $priceLevelMap = [
        'levela' => 'rateLevelA',
        'levelb' => 'rateLevelB',
        'levelc' => 'rateLevelC',
        'leveld' => 'rateLevelD',
        'levele' => 'rateLevelE',
        'levelf' => 'rateLevelF',
        'levelg' => 'rateLevelG',
        'levelh' => 'rateLevelH',
        'leveli' => 'rateLevelI',
        'levelj' => 'rateLevelJ',
        
    ];

    $normalized_level = strtolower(preg_replace('/\s+/', '', trim((string)$price_level)));
    $rate_field = isset($priceLevelMap[$normalized_level]) ? $priceLevelMap[$normalized_level] : 'rate';

    $query = "
        SELECT
            part_number,
            description,
            rate,
            rateLevelA,
            rateLevelB,
            rateLevelC,
            rateLevelD,
            rateLevelE,
            rateLevelF,
            rateLevelG,
            rateLevelH,
            rateLevelI,
            rateLevelJ,
            has_sub_items
        FROM tblInventory
        WHERE part_number LIKE :term
        AND active = 1
    ";

    $stmt = $conn->prepare($query);
    $searchTerm = "%$term%";
    $stmt->bindParam(':term', $searchTerm);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result as &$row) {

        // Always keep the base rate available (does not break your JS)
        $row['base_rate'] = $row['rate'];

        // Use level rate if the field exists and is not NULL/blank
        if ($rate_field !== 'rate' && array_key_exists($rate_field, $row) && $row[$rate_field] !== null && $row[$rate_field] !== '') {
            $row['rate'] = $row[$rate_field];
        }

        // Optional debug fields (safe to leave, your JS will ignore them)
        $row['price_level'] = $price_level;
        $row['rate_field'] = $rate_field;
    }

    echo json_encode($result);
    $conn = null;
}


if (isset($data_raw['action']) && $data_raw['action'] == 'get_order_copy') {
    $database = new Database();
    $conn = $database->connect();

    $term = sanInputs($data_raw['term']);

    $query = "SELECT order_id, customer_company, order_number
              FROM tblOrders 
              WHERE (order_id LIKE :term   OR order_number LIKE :term)
             
              ORDER BY order_id DESC 
              LIMIT 20";
    $stmt = $conn->prepare($query);
    $searchTerm = "%$term%";
    $stmt->bindParam(':term', $searchTerm);
    $stmt->execute();

    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $formatted = [];

    foreach ($result as $row) {
        $formatted[] = [
            'label' => $row['order_id'] . ' - ' . $row['customer_company'] . ' - ' . $row['order_number'],
            'value' => $row['order_id'],
            'customer_company' => $row['customer_company'],
            'order_number' => $row['order_number']
        ];
    }

    echo json_encode($formatted);
    $conn = null;
    exit;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'get_pi_copy') {
    $database = new Database();
    $conn = $database->connect();

    $term = sanInputs($data_raw['term']);
    $query = "SELECT id, vendor_name
          FROM tblPurchaseOrders 
          WHERE (id LIKE :term OR vendor_name LIKE :term)
          AND order_status_id IN (1,2)
          AND order_date >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 1 MONTH))
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
            'label' => $row['id'] . ' - ' . $row['vendor_name'],
            'value' => $row['id'],
            'vendor_name' => $row['vendor_name'],
        ];
    }

    echo json_encode($formatted);
    $conn = null;
    exit;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'get_order_items') {
    $database = new Database();
    $conn = $database->connect();

    $order_id = intval($data_raw['copy_order_id']); // Ensure it's numeric

    $query = "SELECT part_number, description 
              FROM tblOrderItems 
              WHERE order_id = :order_id 
              ORDER BY id ASC";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($items);

    $conn = null;
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'copy_order_items') {
    $database = new Database();
    $conn = $database->connect();

    $from_order_id = intval($data_raw['copy_order_id']);
    $to_order_id = intval($data_raw['this_order_id']);

    if ($from_order_id === $to_order_id || !$from_order_id || !$to_order_id) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid source or destination order ID.']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Step 1: Copy tblOrderItems rows
        $query = "SELECT * FROM tblOrderItems WHERE order_id = :from_order_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':from_order_id', $from_order_id, PDO::PARAM_INT);
        $stmt->execute();
        $orderItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orderItems as $item) {
            $insert = $conn->prepare("INSERT INTO tblOrderItems 
                (company_id, order_id, part_number, has_items, description, qty, rate, unit_id) 
                VALUES (:company_id, :order_id, :part_number, :has_items, :description, :qty, :rate, :unit_id)");

            $insert->execute([
                ':company_id' => $item['company_id'],
                ':order_id' => $to_order_id,
                ':part_number' => $item['part_number'],
                ':has_items' => $item['has_items'],
                ':description' => $item['description'],
                ':qty' => $item['qty'],
                ':rate' => $item['rate'],
                ':unit_id' => $item['unit_id']
            ]);

            $new_order_item_id = $conn->lastInsertId();

            // Step 2: Copy related tblOrderSubItems rows
            $subItemQuery = $conn->prepare("SELECT * FROM tblOrderSubItems 
                                            WHERE order_id = :from_order_id AND order_group_id = :group_id");
            $subItemQuery->execute([
                ':from_order_id' => $from_order_id,
                ':group_id' => $item['id']
            ]);
            $subItems = $subItemQuery->fetchAll(PDO::FETCH_ASSOC);

            foreach ($subItems as $sub) {
                $subInsert = $conn->prepare("INSERT INTO tblOrderSubItems 
                    (company_id, order_id, order_group_id, part_number,  description, mark, punch, qty, qty_unit, weight)
                    VALUES (:company_id, :order_id, :order_group_id, :part_number, :description, :mark, :punch, :qty, :qty_unit, :weight)");

                $subInsert->execute([
                    ':company_id' => $sub['company_id'],
                    ':order_id' => $to_order_id,
                    ':order_group_id' => $new_order_item_id,
                    ':part_number' => $sub['part_number'],
                    ':description' => $sub['description'],
                    ':mark' => $sub['mark'],
                    ':punch' => $sub['punch'],
                    ':qty' => $sub['qty'],
                    ':qty_unit' => $sub['qty_unit'],
                    ':weight' => $sub['weight']
                ]);
            }
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Copy failed: ' . $e->getMessage()]);
    }

    $conn = null;
    exit;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'save_order_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblOrderItems SET 
                description = :description, 
                qty = :qty,
                rate = :rate
                WHERE id = :item_id";
        $bindings = array(
        ':item_id' => $data['item_id'],
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
if (isset($data_raw['action']) && $data_raw['action'] == 'save_order_sub_items') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $part_number = getFieldColumn('part_number', 'tblOrderSubItems', 'id', $data['sub_item_id']);
    $weight_unit = getFieldColumn('weight_unit', 'tblInventory', 'part_number', $part_number);
    $weight = ($data['qty']*$data['qty_unit'])*$weight_unit;
    $query = "UPDATE tblOrderSubItems SET 
                mark = :mark, 
                punch = :punch,
                qty = :qty, 
                qty_unit = :qty_unit,
                weight = :weight
                WHERE id = :sub_item_id";
    
    $bindings = array(
        ':sub_item_id' => $data['sub_item_id'],
        ':mark' => $data['mark'],
        ':punch' => $data['punch'],
        ':qty' => $data['qty'],
        ':qty_unit' => $data['qty_unit'],
        ':weight' => $weight
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
if (isset($data_raw['action']) && $data_raw['action'] == 'add_activity') {
			addOrderActivity($data_raw['order_id'], $_SESSION['session_company_id'], 5, $data_raw['description'], $_SESSION['session_user_id'], $data_raw['action_date']);
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
	}
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_order_activity') {
    header('Content-Type: application/json');

    if (!userCanPermission('orders.activity.delete')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete order activity.']);
        exit;
    }

    $activity_id = isset($data_raw['id']) ? (int)$data_raw['id'] : 0;
    if ($activity_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid activity id.']);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();
    $query = "DELETE FROM tblOrderActivity WHERE id = :id AND company_id = :company_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $activity_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Activity deleted.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Activity was not found or could not be deleted.']);
    }
    exit;
}
 if (isset($data_raw['action']) && $data_raw['action'] == 'get_activity') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $return_arr = array();
    $query = "SELECT * FROM tblOrderActivity WHERE id = :activity_id";
    $result = $conn->prepare($query);
    $result->bindParam(':activity_id', $data['activity_id']);
    $result->execute();

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'description' => $row['description'],
            'action_date' => date_c($row['action_date'])  
        );
        array_push($return_arr, $row_data);
    }
    echo json_encode($return_arr);
}   
if (isset($data_raw['action']) && $data_raw['action'] == 'save_activity') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    $action_date=strtotime($data['action_date']);
    $query = "UPDATE tblOrderActivity SET 
                description = :description, 
                action_date = :action_date
                WHERE id = :activity_id";
    $bindings = array(
        ':activity_id' => $data['activity_id'],
        ':description' => $data['description'],
        ':action_date' => $action_date
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
 if (isset($data_raw['action']) && $data_raw['action'] == 'import_to_invoice') {
     insertInvoiceItems($_SESSION['session_company_id'],$data_raw['order_id']);
    } 
    
if (isset($data_raw['action']) && $data_raw['action'] == 'add_pack') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    // Retrieve all pack_numbers for the given order_id and company_id
    $query = "SELECT pack_number FROM tblOrderPacks WHERE order_id = :order_id AND company_id = :company_id ORDER BY pack_number";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':order_id', $data['order_id'], PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Determine the next pack_number in sequence
    $pack_number = 1; // Start with the first pack number
    foreach ($results as $number) {
        if ($number != $pack_number) {
            break; // Found the first missing number in the sequence
        }
        $pack_number++;
    }

    // Insert the new pack with the next pack_number
    $query = "INSERT INTO tblOrderPacks (company_id, order_id, pack_number)
              VALUES (:company_id, :order_id, :pack_number)";
    
    $bindings = array(
        ':company_id' => $_SESSION['session_company_id'],
        ':order_id' => $data['order_id'],
        ':pack_number' => $pack_number,
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Added successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Failed to execute query.']);
    }
}

if (isset($data_raw['action']) && $data_raw['action'] == 'update_pack_item') {
    
   
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $query = "UPDATE tblOrderSubItems SET 
                pack_id = :pack_id
              WHERE id = :id";
    
    $bindings = array(
        ':id' => $data['id'],
        ':pack_id' => $data['pack_id']
    );

    $rowCount = 0;
    try {
        if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
            if ($rowCount > 0) {
                echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
            } else {
                echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Failed to execute query.']);
        }
    } catch (Exception $e) {
        error_log("Error updating pack item: " . $e->getMessage());
        echo json_encode(['error' => true, 'message' => 'An error occurred while updating the pack item.']);
    }
} 
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_pack') {  
    $database = new Database();
    $conn = $database->connect();
    $query = "UPDATE tblOrderSubItems SET 
              pack_id = 0
              WHERE  company_id = :company_id AND order_id= :order_id AND pack_id =:pack_id";
    
    $bindings = array(
        ':company_id' => $_SESSION['session_company_id'],
        ':order_id' => $data_raw['order_id'],
        ':pack_id' => $data_raw['pack_number']
    );
    
    $rowCount = 0;
    executeDatabaseQuery($conn, $query, $bindings, $rowCount);
    deleteId('tblOrderPacks', $data_raw['pack_id']);
}



}

?>
