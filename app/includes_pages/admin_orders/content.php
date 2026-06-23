<?php
session_start();
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
include("../../includes/common.php");
requireLoggedInJson();

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function orderContentPurchaseColumnExists($conn, $columnName) {
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

function renderLinkedPurchaseBadge($conn, $pid, $company_id) {
    $hasConfirmationColumns = orderContentPurchaseColumnExists($conn, 'order_confirmation_required')
        && orderContentPurchaseColumnExists($conn, 'order_confirmation_received');

    $selectConfirmation = $hasConfirmationColumns
        ? ", po.order_confirmation_required, po.order_confirmation_received"
        : ", 0 AS order_confirmation_required, 0 AS order_confirmation_received";

    $stmt = $conn->prepare("
        SELECT po.id, po.order_date, po.order_date_required, ps.description AS status_name
               " . $selectConfirmation . "
        FROM tblPurchaseOrders po
        LEFT JOIN tblPurchaseStatus ps
          ON ps.id = po.order_status_id
         AND ps.company_id = po.company_id
        WHERE po.id = :pid
          AND po.company_id = :company_id
        LIMIT 1
    ");
    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->execute();
    $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$purchase) {
        return '<span class="badge bg-secondary">PO #' . (int)$pid . '</span>';
    }

    $status = trim((string)$purchase['status_name']);
    $statusKey = strtolower($status);
    $dateOverdue = !empty($purchase['order_date_required'])
        && (int)$purchase['order_date_required'] < strtotime('today')
        && !in_array($statusKey, array('received', 'invoiced'), true);
    $badgeClass = 'bg-light text-dark border';
    $style = '';

    if ($dateOverdue || $statusKey === 'overdue') {
        $badgeClass = 'bg-danger';
        if ($status === '') {
            $status = 'Overdue';
        }
    } elseif ($statusKey === 'ordered' || $statusKey === 'order') {
        $badgeClass = 'bg-warning text-dark';
    } elseif ($statusKey === 'confirmed') {
        $badgeClass = 'text-dark';
        $style = ' style="background:#fd7e14;"';
    } elseif ($statusKey === 'received') {
        $badgeClass = 'bg-success';
    } elseif ($statusKey === 'invoiced') {
        $badgeClass = 'bg-primary';
    }

    $confirmationOverdue = !empty($purchase['order_confirmation_required'])
        && empty($purchase['order_confirmation_received'])
        && !empty($purchase['order_date'])
        && ((int)$purchase['order_date'] < (time() - (48 * 60 * 60)));

    $html = '<a href="?p=admin_purchasing&pid=' . (int)$purchase['id'] . '" class="badge text-decoration-none ' . $badgeClass . '"' . $style . '>PO #' . (int)$purchase['id'];
    if ($status !== '') {
        $html .= ' ' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
    }
    $html .= '</a>';

    if ($confirmationOverdue) {
        $html .= '<div class="mt-1"><span class="badge bg-danger">Confirmation overdue</span></div>';
    }

    return $html;
}

// Checks if the request method is POST and 'tab_id' is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id'])) {
$database = new Database();
$conn = $database->connect();

function orderContentColumnExists($conn, $columnName) {
    static $cache = array();
    $key = 'tblOrderItems.' . $columnName;
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    $stmt = $conn->prepare("SHOW COLUMNS FROM tblOrderItems LIKE :column_name");
    $stmt->bindValue(':column_name', $columnName);
    $stmt->execute();
    $cache[$key] = (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    return $cache[$key];
}

function syncOrderCompletedItemsFromLinkedPurchases($conn, $order_id, $company_id) {
    if (!orderContentColumnExists($conn, 'item_completed')) {
        return;
    }

    $fields = "oi.item_completed = 1";
    if (orderContentColumnExists($conn, 'item_completed_at')) {
        $fields .= ", oi.item_completed_at = COALESCE(NULLIF(oi.item_completed_at, 0), :completed_at)";
    }
    if (orderContentColumnExists($conn, 'item_completed_by')) {
        $fields .= ", oi.item_completed_by = COALESCE(NULLIF(oi.item_completed_by, 0), :completed_by)";
    }

    $stmt = $conn->prepare("
        UPDATE tblOrderItems oi
        INNER JOIN tblPurchaseOrders po
            ON po.id = oi.purchased_item
           AND po.company_id = oi.company_id
        LEFT JOIN tblPurchaseStatus ps
            ON ps.id = po.order_status_id
           AND ps.company_id = po.company_id
        LEFT JOIN (
            SELECT DISTINCT company_id, pid
            FROM tblPurchaseInvoice
        ) pi
            ON pi.pid = po.id
           AND pi.company_id = po.company_id
        SET {$fields}
        WHERE oi.company_id = :company_id
          AND oi.order_id = :order_id
          AND oi.purchased_item IS NOT NULL
          AND oi.purchased_item <> ''
          AND oi.purchased_item <> 0
          AND COALESCE(oi.item_completed, 0) = 0
          AND (
                LOWER(COALESCE(ps.description, '')) LIKE '%receiv%'
                OR LOWER(COALESCE(ps.description, '')) LIKE '%invoic%'
                OR pi.pid IS NOT NULL
              )
    ");
    if (orderContentColumnExists($conn, 'item_completed_at')) {
        $stmt->bindValue(':completed_at', time(), PDO::PARAM_INT);
    }
    if (orderContentColumnExists($conn, 'item_completed_by')) {
        $stmt->bindValue(':completed_by', (int)$_SESSION['session_user_id'], PDO::PARAM_INT);
    }
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
}
    $tab_id = $_POST['tab_id'];
    if ($tab_id == 'home') {
            $data = ' <div class="row">
                <div class="col-lg-4">
                    <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Customer </h5>
                            <div class="row mb-0 align-items-center">
                                <label for="inputText" class="col-sm-3 col-form-label">Cash Sale</label>
                                <div class="col-sm-9 d-flex align-items-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="cash_sale_checkbox">
                                    </div>
                                </div>
                            </div>
                          <div class="row mb-0">
                            <label for="inputText" class="col-sm-3 col-form-label">Customer</label>
                            <div class="col-sm-9">
                                <input type="text" id="customer_search" class="form-control form-control-sm" placeholder="Search Business or Surname">
                                <input type="hidden" id="customer_uid" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Contact</label>
                            <div class="col-sm-9">
                                <input type="text" id="customer_contact" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Address</label>
                            <div class="col-sm-9">
                                <input type="text" id="customer_address" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Suburb</label >
                            <div class="col-sm-9">
                                <input type="text" id="customer_suburb" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">State</label>
                            <div class="col-sm-9">
                                <input type="text" id="customer_state" class="form-control form-control-sm">
                            </div>
                        </div>    
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Postcode</label>
                            <div class="col-sm-9">
                                <input type="number" id="customer_postcode" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Email</label>
                            <div class="col-sm-9">
                                <input type="text" id="customer_email" class="form-control form-control-sm">
                            </div>
                        </div>    
                        <div class="row mb-0">
                            <label for="inputNumber" class="col-sm-3 col-form-label">Phone</label>
                            <div class="col-sm-9">
                                <input type="number" id="customer_phone" class="form-control form-control-sm">
                            </div>
                        </div>
                        
                        <div class="row mb-0">
                            <label for="inputText" class="col-sm-12 col-form-label">Customer Notes</label>
                            <div class="col-sm-12">
                                <textarea type="text" rows="4" id="customer_notes" class="form-control form-control-sm" placeholder="Example: Customer Notes"></textarea>
                            </div>
                        </div>
                    </div>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Details</h5>
                      <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Date:</label>
                          <div class="col-sm-9">
                              <input type="text" name="order_date" id="order_date" class="order_date_picker form-control-sm form-control"> 
                          </div>
                      </div>
                      <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Order#:</label>
                          <div class="col-sm-9">
                              <input type="text" name="order_number" id="order_number" class="form-control-sm form-control"> 
                          </div>
                      </div>
                      <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Sales:</label>
                          <div class="col-sm-9">
                              <select name="orderuser" id="order_user" class="form-control-sm form-control"></select>
                              <input type="hidden" id="order_user_id">
                          </div>
                      </div>
                       <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Source:</label>
                          <div class="col-sm-9">
                              <select name="order_source" id="order_source" class="form-control-sm form-control"></select>
                              <input type="hidden" id="order_source_id">
                          </div>
                      </div>
                      <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Status:</label>
                          <div class="col-sm-9">
                              <select name="order_status" id="order_status" class="form-control-sm form-control"></select>
                              <input type="hidden" id="order_status_id">
                          </div>
                      </div>
                      <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Price:</label>
                          <div class="col-sm-9">
                               <input type="text" disabled class="form-control-sm form-control" id="price_level">
                          </div>
                      </div>
 
                                <input type="hidden" disabled class="form-control-sm form-control" id="payment_terms_text">
                               <input type="hidden" disabled class="form-control-sm form-control" id="payment_terms">
         
                      <div class="row mb-0 align-items-center">
                                <label for="inputText" class="col-sm-3 col-form-label">Pickup</label>
                                <div class="col-sm-9 d-flex align-items-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="pickup_checkbox">
                                    </div>
                                </div>
                            </div>
                      <div class="row mb-0">
                        <label for="delivery_rate" class="col-sm-3 col-form-label">Delivery:</label>
                        <div class="col-sm-9">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input type="number" name="delivery_rate" id="delivery_rate" class="form-control" placeholder="Delivery Rate Inc GST">
                             </div>
                        </div>
                    </div>

                    </div>
                  </div>
                </div>
                <div class="col-lg-4">
                  <div class="card">
                    <div class="card-body">
                      <h5 class="card-title">Delivery </h5>
                      

                      <div class="row mb-0">
                          <label for="inputEmail" class="col-sm-3 col-form-label">Delivery:</label>
                          <div class="col-sm-9">
                              <input type="text" name="order_delivery_date" id="order_delivery_date" class="delivery_date form-control-sm form-control" placeholder="Delivery Date"> 
                          </div>
                      </div>
                       <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Address</label>
                            <div class="col-sm-9">
                                <input type="text" id="site_address" class="form-control form-control-sm">
                            </div>
                        </div>
                        <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Suburb</label >
                            <div class="col-sm-9">
                                <input type="text" id="site_suburb" class="form-control form-control-sm">
                            </div>
                        </div>
                      <div class="row mb-0">
                            <label for="inputPassword" class="col-sm-3 col-form-label">Note</label >
                            <div class="col-sm-9">
                                <input type="text" id="deliver_note" class="form-control form-control-sm">
                            </div>
                        </div>

                      
                        <div class="row mb-0">
                            <label for="inputText" class="col-sm-3 col-form-label">Contact</label>
                            <div class="col-sm-9">
                                <input type="text" id="site_contact" class="form-control form-control-sm">
                            </div>
                        </div>
                       
   
                        <div class="row mb-0">
                            <label for="inputNumber" class="col-sm-3 col-form-label">Phone</label>
                            <div class="col-sm-9">
                                <input type="number" id="site_phone" class="form-control form-control-sm">
                            </div>
                        </div>
                         <div class="row mb-0">
                            <label for="inputText" class="col-sm-12 col-form-label">Delivery / Pickup Notes</label>
                            <div class="col-sm-12">
                                <textarea type="text" rows="4" id="deliver_instructions" class="form-control form-control-sm" placeholder="Example:  Site Access, Delivery Time etc"></textarea>
                            </div>
                        </div>
                       <div class="row">
                        <div class="col-auto">
                            <button onclick="copyContactSite()" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-clipboard"></i> Copy
                            </button>
                        </div>
                        <div class="col-auto" id="saveContactsDiv">
                            <button id="saveCreateBtn" class="btn btn-outline-secondary btn-sm">
                                <i class="bi bi-save"></i> <span id="buttonText">Save</span>
                            </button>
                        </div>
                    </div>

  
                    </div>
                  </div>
                </div>
                </div>';
     sendJsonResponse(['html' => $data]);
    }




  if ($tab_id == 'order_items') {
    $hasItemCompleted = orderContentColumnExists($conn, 'item_completed');
    if ($hasItemCompleted) {
        syncOrderCompletedItemsFromLinkedPurchases($conn, (int)$_POST['order_id'], (int)$_SESSION['session_company_id']);
    }
    $query = "SELECT * FROM tblOrderItems  WHERE company_id = :company_id AND order_id = :order_id ";
    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
    $statement->execute();
    $rowCount = $statement->rowCount();
    if ($rowCount > 0) {
        $completionSummary = '';
        if ($hasItemCompleted) {
            $completionStmt = $conn->prepare("
                SELECT
                    COUNT(*) AS total_items,
                    COALESCE(SUM(CASE WHEN item_completed = 1 THEN 1 ELSE 0 END), 0) AS completed_items
                FROM tblOrderItems
                WHERE company_id = :company_id
                  AND order_id = :order_id
            ");
            $completionStmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
            $completionStmt->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
            $completionStmt->execute();
            $completionRow = $completionStmt->fetch(PDO::FETCH_ASSOC);
            $completionSummary = '<span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" id="order_items_completion_badge">'
                . (int)$completionRow['completed_items'] . '/' . (int)$completionRow['total_items'] . ' complete</span>';
        }

        $output = '  <div class="card">
                    <div class="card-body">
                       <div class="d-flex justify-content-between align-items-start mb-2">
                           <div>
                               <h5 class="card-title">Order Items ' . $completionSummary . '</h5>
                           </div>
                           <div class="row align-items-center">
                               <div class="col-auto">
                                   <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrderItemsModal()">Add Item</button>
                               </div>
                               <div class="col-auto">
                                   <button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyOrderPurModal()">Copy Tagged Items To PO</button>
                               </div>

                           </div>
                       </div>
                       <div class="container-fluid px-0">
                           <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                               <div class="col-1 px-1">Item#</div>
                               <div class="col-3 px-1">Description</div>
                               <div class="col-1 px-1">Qty</div>
                               <div class="col-1 px-1">Price</div>
                               <div class="col-1 px-1">Unit</div>
                               <div class="col-2 px-1">Total(Ex)</div>
                               <div class="col-1 px-1">PO</div>
                               <div class="col-1 px-1">Done</div>
                               <div class="col-1 px-1">Actions</div>
                           </div>';

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if($row['has_items']){
                $row_value=getLengSum($row['id']);
                $row_value_c = getLengSum($row['id'])*$row['rate'];
                }
                else{
                   $row_value= $row['qty'];
                   $row_value_c= $row['qty']*$row['rate'];
                }
                $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
                $purchaseBadge = '';
                if (!empty($row['purchased_item'])) {
                    $purchaseBadge = renderLinkedPurchaseBadge($conn, (int)$row['purchased_item'], (int)$_SESSION['session_company_id']);
                }
                $completedCheckbox = $hasItemCompleted
                    ? '<input class="form-check-input" type="checkbox" ' . (!empty($row['item_completed']) ? 'checked' : '') . ' onchange="toggleOrderItemCompleted(' . (int)$row['id'] . ', this.checked)" title="Mark this order item completed">'
                    : '<span class="text-muted small">-</span>';
                    $output .= '<div class="row fw-bold align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                        <div class="col-1 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                        <div class="col-3 px-1">' . htmlspecialchars($row['description']) . '</div>
                        <div class="col-1 px-1">' . $row_value . '</div>
                        <div class="col-1 px-1">$' . number_format($row['rate'], 2) . '</div>
                        <div class="col-1 px-1">' . htmlspecialchars($unit) . '</div>
                        <div class="col-2 px-1">$' . number_format($row_value_c, 2) . '</div>
                        <div class="col-1 px-1">' . $purchaseBadge . '</div>
                        <div class="col-1 px-1 text-center">' . $completedCheckbox . '</div>
                        <div class="col-1 px-1 d-flex justify-content-between">
                            <button class="btn btn-sm btn-outline-secondary" onclick="editOrderItem(' . htmlspecialchars($row['id']) . ')">
                                <i class="bx bx-edit"></i>
                            </button>
                            <button 
                                id="tag-btn-' . htmlspecialchars($row['id']) . '" 
                                class="btn btn-sm ' . ($row['tag'] ? 'btn-success' : 'btn-outline-secondary') . '" 
                                onclick="toggleTag(' . htmlspecialchars($row['id']) . ')">
                                <i class="bx bx-purchase-tag-alt"></i>
                            </button>
                        </div>
                    </div>';
            if($row['has_items']){
            // Fetch the related items from tblInventoryItems
            $subQuery = "SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id ORDER BY qty_unit";
            $subStatement = $conn->prepare($subQuery);
            $subStatement->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
            $subStatement->execute();

            while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<div class="row align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                                <div class="col-2 px-2">' . htmlspecialchars($subRow['mark']). '</div>
                                <div class="col-3 px-2">' . htmlspecialchars($subRow['punch']). '</div>    
                                <div class="col-2 px-2">' . htmlspecialchars($subRow['serial_number']). '</div> 
                                <div class="col-3 px-2">' . htmlspecialchars($subRow['qty']. ' x ' .$subRow['qty_unit']) . '</div>
       
                            
                                <div class="col-1 px-1"></div>
                                <div class="col-1 px-1">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="editOrderSubItem(' . $subRow['id'] . ')"><i class="bx bx-edit"></i></button>
                                </div>
                            </div>';
            }
        }
        }
        $output .= '    </div>
                   </div></div>';
        sendJsonResponse(['html' => $output]);
    } else {
        sendJsonResponse(['html' => ' <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrderItemsModal()">Add Item</button><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyOrderItemsModal()">Copy Items To Order</button><div class="card-body"><h5 class="card-title">No Items Found</h5></div>']);
    }
}
if ($tab_id == 'production') {
    $order_status_id = getTableColField('order_status_id', 'tblOrders', 'order_id', $_POST['order_id']);
    $customer_contact = getTableColField('customer_contact', 'tblOrders', 'order_id', $_POST['order_id']);
    $status_id = getTableColField('ordering', 'tblOrderStatus', 'id', $order_status_id);

    if ($status_id < 4) {
        // Display "waiting on order" inside a styled card
        $output = '<div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-warning">Waiting on Order</h5>
                        <p>The order is not yet ready for production. Please check the order status.</p>
                    </div>
                   </div>';
        sendJsonResponse(['html' => $output]); // Return the response as JSON
        exit;
    }
    if ($status_id >= 4) {
        $query = "SELECT * FROM tblOrderItems WHERE company_id = :company_id AND order_id = :order_id";
        $statement = $conn->prepare($query);
        $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
        $statement->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
        $statement->execute();
        $rowCount = $statement->rowCount();

        if ($rowCount > 0) {
            $output = '<div class="card">
                        <div class="card-body">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                               <div>
                                   <h5 class="card-title">Production - Manufactured Items Only</h5>
                                   <button type="button" class="btn btn-sm btn-outline-secondary" onclick="PrintProdCardAll(\'' . htmlspecialchars(addslashes($_POST['order_id'])) . '\')">Print All Cards</button>
                               </div>
                           </div>
                           <div class="container-fluid px-0">
                               <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                                   <div class="col-1 px-1">Item#</div>
                                   <div class="col-4 px-1">Description</div>
                                   <div class="col-1 px-1">Unit</div>
                                   <div class="col-1 px-1">Qty</div>
                               </div>';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $inventory_group_id = getTabFieCol('group_id', 'tblInventory', 'part_number', $row['part_number'], $_SESSION['session_company_id']);
                if ($inventory_group_id == 1) {
                    $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
                    $output .= '<div class="row align-items-center border-bottom py-1 hover-row bg-light">
                                    <div class="col-1 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                                    <div class="col-4 px-1">' . htmlspecialchars($row['description']) . '</div>
                                    <div class="col-1 px-1">' . htmlspecialchars($unit) . '</div>
                                    <div class="col-1 px-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="generateCSV(\'' . addslashes($row['part_number']) . '\', \'' . addslashes($customer_contact) . '\')">CSV</button>

                                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="PrintProdCard(\'' . htmlspecialchars(addslashes($row['order_id'])) . '\', \'' . htmlspecialchars(addslashes($row['part_number'])) . '\')">Print</button>
                                    </div>
                                </div>';

                    if ($row['has_items']) {
                        // Fetch related items from tblInventoryItems
                        $subQuery = "SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id ORDER BY pack_id";
                        $subStatement = $conn->prepare($subQuery);
                        $subStatement->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
                        $subStatement->execute();

                        while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
                            $output .= '<div class="row align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                                            <div class="col-2 px-2">Pack ' . htmlspecialchars($subRow['pack_id']) . '</div>
                                            <div class="col-2 px-2">' . htmlspecialchars($subRow['mark']) . '</div>
                                            <div class="col-2 px-2">' . htmlspecialchars($subRow['punch']) . '</div>
                                            <div class="col-2 px-6">' . htmlspecialchars($subRow['qty'] . ' x ' . $subRow['qty_unit']) . '</div>
                                        </div>';
                        }
                    }
                }
            }
            $output .= '</div></div></div>';
            sendJsonResponse(['html' => $output]);
        } else {
            sendJsonResponse(['html' => '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrderItemsModal()">Add Item</button><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyOrderItemsModal()">Copy Items To Order</button><div class="card-body"><h5 class="card-title">No Items Found</h5></div>']);
        }
    }
}



if ($tab_id == 'activity') {
        $query = "SELECT * FROM tblOrderActivity WHERE order_id = :order_id AND company_id = :company_id ORDER BY action_date DESC";
        $result = $conn->prepare($query);
        $result->bindParam(':order_id', $_POST['order_id']);
        $result->bindParam(':company_id', $_SESSION['session_company_id']);
        $result->execute();
        $canDeleteActivity = userCanPermission('orders.activity.delete');
        $data = '
        <div class="order-activity-panel">
            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                <h5 class="order-activity-title mb-0">Order Activity</h5>
                <button onclick="AddOrderActivityModal()" class="btn btn-sm btn-outline-secondary">Add Activity</button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm order-activity-table align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="activity-col-status">Type</th>
                            <th class="activity-col-date">Date</th>
                            <th class="activity-col-user">User</th>
                            <th>Action</th>
                            <th class="activity-col-buttons"></th>
                        </tr>
                    </thead>
                    <tbody>';

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $description = htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8');
            $actionDate = !empty($row['action_date']) ? date('d/m/y g:i A', (int)$row['action_date']) : '';
            $userName = htmlspecialchars(getUserFullName($row['user_id']), ENT_QUOTES, 'UTF-8');
            $typeBadge = '<span class="badge rounded-pill activity-badge"><i class="bx bx-check-circle"></i> Public</span>';
            $buttons = '<button class="btn btn-xs btn-outline-secondary" onclick="EditOrderActivity('.(int)$row['id'].')" title="Edit activity"><i class="bx bx-edit"></i></button>';
            if ($canDeleteActivity) {
                $buttons .= '<button class="btn btn-xs btn-outline-danger" onclick="delOrderActivity('.(int)$row['id'].')" title="Delete activity"><i class="bx bxs-trash"></i></button>';
            }

            $data .= '<tr>';
            $data .= '<td>' . $typeBadge . '</td>';
            $data .= '<td class="text-nowrap">' . $actionDate . '</td>';
            $data .= '<td class="text-nowrap">' . $userName . '</td>';
            $data .= '<td class="activity-description">' . $description . '</td>';
            $data .= '<td class="text-end"><div class="activity-actions">' . $buttons . '</div></td>';
            $data .= '</tr>';
        }

        $data .= '</tbody></table></div></div>';
        sendJsonResponse(['html' => $data]);

    }
if ($tab_id == 'pack_tab') {
    $order_status_id = getTableColField('order_status_id', 'tblOrders', 'order_id', $_POST['order_id']);
    $status_id = getTableColField('ordering', 'tblOrderStatus', 'id', $order_status_id);

    if ($status_id < 4) {
        // Display "waiting on order" message inside a styled card
        $output = '<div class="card">
                      <div class="card-body">
                          <h5 class="card-title text-warning">Waiting on Order</h5>
                          <p>The order is not yet ready for packing. Please check the order status.</p>
                      </div>
                   </div>';
        sendJsonResponse(['html' => $output]);
        exit; // Stop further execution if the status is not sufficient
    }
    
    $query = "
        SELECT * FROM tblOrderSubItems 
        WHERE company_id = :company_id AND order_id = :order_id 
        ORDER BY 
            CASE WHEN pack_id = 0 THEN 0 ELSE 1 END, 
            pack_id, 
            part_number
    ";

    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
    $statement->execute();
    $rowCount = $statement->rowCount();

    if ($rowCount > 0) {
        $output = '<div class="container-fluid">
                      <div class="row">
                          <div class="col-lg-8">
                              <div class="card">
                                  <div class="card-body">
                                      <div class="d-flex justify-content-between align-items-start mb-2">
                                          <div>
                                              <h5 class="card-title">Drag Items to Packs</h5>
                                          </div>
                                      </div>
                                      <div class="container-fluid px-0">
                                          <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                                              <div class="col-2 px-1">Pack</div>
                                              <div class="col-2 px-1">Item#</div>
                                              <div class="col-3 px-1">Description</div>
                                              <div class="col-1 px-1">Mark</div>
                                              <div class="col-2 px-1">Quantity</div>
                                              <div class="col-2 px-1">Drag</div>
                                          </div>';

        $previousPackId = null;

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            if ($previousPackId !== null && $previousPackId != $row['pack_id']) {
                $output .= '<div  style="background-color: #666; height: 2px;"></div>'; // Add styled break between different pack_id groups
            }
            
            if ($row['pack_id'] == 0) {
                $pack_id = '';
                $bg = 'bg-info text-white';
            } else {
                $pack_id = $row['pack_id'];
                $bg = '';
            }
            $output .= '<div class="' . $bg . ' row align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                            <div class="col-2 px-1">' . htmlspecialchars($pack_id) . '</div>
                            <div class="col-2 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-3 px-1">' . htmlspecialchars($row['description']) . '</div>
                            <div class="col-1 px-1">' . htmlspecialchars($row['mark']) . '</div>
                            <div class="col-2 px-1">' . htmlspecialchars($row['qty'] . ' x ' . $row['qty_unit']) . '</div>
                            <div class="col-2 px-1"><i class="text-secondary bx bx-move move-icon draggable" data-id="' . $row['id'] . '"></i></div>
                        </div>';

            $previousPackId = $row['pack_id'];
        }

        $output .= '       </div>
                         </div>
                      </div>
                  </div>
                  <div class="col-lg-4">
                      <div class="sticky-container">
                          <div class="card">
                              <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                      <div>
                                          <h5 class="card-title">Available Packs</h5>
                                      </div>
                                      <div>
                                          <button class="btn btn-sm btn-outline-secondary" onclick="addNewPack()">Add Pack</button>
                                      </div>
                                       <button class="btn btn-sm btn-outline-secondary" onclick="PrintPackAll(' . $_POST['order_id'] . ')"><i class="bx bx-printer"></i> All Dymo </button>
                                       <button class="btn btn-sm btn-outline-primary" onclick="PrintPackAllZeb(' . $_POST['order_id'] . ')"><i class="bx bx-printer"></i> All Zebra</button>
                                  </div>
                                  <div class="container-fluid px-0">
                                      <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                                          <div class="col-2 px-1"><i class="bx bx-target-lock"></i></div>
                                          <div class="col-2 px-1">Pack</div>
                                          <div class="col-3 px-2">Kg</div>
                                          <div class="col-5 px-2"></div>
                                      </div>';

        // Fetch and display pack items here
        $packQuery = "SELECT * FROM tblOrderPacks WHERE company_id = :company_id AND order_id = :order_id ORDER BY pack_number";
        $packStatement = $conn->prepare($packQuery);
        $packStatement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
        $packStatement->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
        $packStatement->execute();

        while ($packRow = $packStatement->fetch(PDO::FETCH_ASSOC)) {
            $output .= '<div class="row align-items-center border-bottom py-1 hover-row droppable" style="margin: 0;" data-pack-id="' . htmlspecialchars($packRow['pack_number']) . '">
                             <div class="col-2 px-1 drop-target"><i class="bx bx-target-lock"></i></div>
                             <div class="col-2 px-1">' . htmlspecialchars($packRow['pack_number']) . '</div>
                             <div class="col-3 px-2">' . getSumWei($packRow['pack_number'], $_SESSION['session_company_id'], $_POST['order_id']) . '</div>
                             <div class="col-5 px-1">
                                 <button class="btn btn-sm btn-outline-secondary" onclick="PrintPack(' . $packRow['order_id'] . ',' . $packRow['pack_number'] . ')"><i class="bx bx-printer"></i></button>
                                  <button class="btn btn-sm btn-outline-primary" onclick="PrintPackZeb(' . $packRow['order_id'] . ',' . $packRow['pack_number'] . ')"><i class="bx bx-printer"></i></button>
                                 <button class="btn btn-sm btn-outline-secondary" onclick="PrintPack_dl(' . $packRow['order_id'] . ',' . $packRow['pack_number'] . ')"><i class="bx bx-download"></i></button>
                                 <button class="btn btn-sm btn-outline-secondary" onclick="delPack(' . $packRow['id'] . ',' . $packRow['pack_number'] . ')"><i class="bx bxs-trash"></i></button>
                             </div>
                         </div>';
        }

        $output .= '           </div>
                          </div>
                       </div>
                    </div>
                </div>
                <style>
                  .sticky-container {
                      position: -webkit-sticky;
                      position: sticky;
                      top: 10px;
                  }
                  .sticky-container .card {
                      max-height: calc(100vh - 20px);
                      overflow-y: auto;
                  }
                </style>';
        sendJsonResponse(['html' => $output]);
    } else {
        sendJsonResponse(['html' => '<div class="card-body"><h5 class="card-title">No Items Found</h5></div>']);
    }
}





if (isset($_POST['tab_id']) && $_POST['tab_id'] == 'attachments') {
    try {
        // Fetch the list of all uploaded files from the database
        $sql = "SELECT * FROM tblOrderFiles WHERE order_id = :order_id AND company_id = :company_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
        $stmt->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
        $stmt->execute();
        $files = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Generate the HTML for the files list and the Dropzone
        $output = '<div class="card">
                       <div class="card-body">
                           <div class="d-flex justify-content-between align-items-start mb-2">
                               <div>
                                   <h5 class="card-title">Attachments</h5>
                               </div>
                           </div>
                           <div class="container-fluid px-0">
                               <div class="row">
                                   <div class="col-md-8">
                                       <h6>Uploaded Files</h6>
                                       <ul class="list-group">';
        
        if (!empty($files)) {
            foreach ($files as $file) {
                $downloadUrl = generatePreSignedUrl('order_files/'.$file['filename'], $file['description']);
                $output .= '<li class="list-group-item d-flex justify-content-between align-items-center" data-file-id="' . (int)$file['id'] . '" data-order-id="' . (int)$_POST['order_id'] . '" data-file-key="' . htmlspecialchars($file['filename'], ENT_QUOTES, 'UTF-8') . '">
                                <span>' . htmlspecialchars($file['description']) . '</span>
                                <div class="btn-group">
                                    <a href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bx bxs-cloud-download"></i></a>
                                    <button type="button" class="btn btn-sm btn-outline-secondary delete-file"><i class="bx bxs-trash"></i></button>
                                </div>
                            </li>';
            }
        } else {
            $output .= '<li class="list-group-item">No files uploaded yet.</li>';
        }

        $output .= '          </ul>
                                   </div>
                                   <div class="col-md-4">
                                       <div id="dropzone_orders" class="dropzone"></div>
                                   </div>
                               </div>
                           </div>
                       </div>';
        
        sendJsonResponse(['html' => $output]);
    } catch (PDOException $e) {
        sendJsonResponse(['html' => '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>']);
    }
}
if ($tab_id == 'invoice') {
    $order_status_id = getTableColField('order_status_id', 'tblOrders', 'order_id', $_POST['order_id']);
    $status_id = getTableColField('ordering', 'tblOrderStatus', 'id', $order_status_id);

    if ($status_id < 9) {
        // Display "waiting on order" message inside a styled card
        $output = '<div class="card">
                      <div class="card-body">
                          <h5 class="card-title text-warning">Waiting on Delivery or Collection</h5>
                          <p>The order is not yet ready for Invoicing. Please check the order status.</p>
                      </div>
                   </div>';
        sendJsonResponse(['html' => $output]);
        exit; // Stop further execution if the status is not sufficient
    }
    $sub_total = 0.0;
    $delivery_rate = getTableFieldSum('delivery_rate', 'tblOrders', 'order_id', $_POST['order_id']);
    $gst_rate = 0.1; // Assuming 10% GST, adjust as needed

    $query = "SELECT * FROM tblInvoice WHERE company_id = :company_id AND order_id = :order_id";
    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
    $statement->execute();
    $rowCount = $statement->rowCount();

    if ($rowCount > 0) {

$customer_email = getTabFieCol('customer_email', 'tblOrders', 'order_id', $_POST['order_id'], $_SESSION['session_company_id']);

$output = '<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
                <h5 class="card-title">Invoice Items</h5>
            </div>
            <div class="d-flex">
                <div id="deleteInvoiceModal" class="mr-2"></div>
                <div id="processInvoice" class="mr-2"></div>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="EmailSalesOrder(' . $_POST['order_id'] . ', \'' . addslashes($customer_email) . '\')">Email</button>
            </div>
        </div>

        <div class="container-fluid px-0">
            <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                <div class="col-1 px-1">Qty</div>
                <div class="col-1 px-1">Unit</div>
                <div class="col-1 px-1">Item#</div>
                <div class="col-5 px-1">Description</div>
                <div class="col-1 px-1">Rate</div>
                <div class="col-2 px-1 text-end">Total(Ex)</div>
                <div class="col-1 px-1">Actions</div>
            </div>';



        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $row_total = $row['qty'] * $row['rate'];
            $sub_total += $row_total;
            
            $output .= '<div class="row align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                            <div class="col-1 px-1">' . htmlspecialchars($row['qty']) . '</div>
                            <div class="col-1 px-1">' . htmlspecialchars(getTabFieCol('description', 'tblItemUnits', 'id', $row['unit_id'], $_SESSION['session_company_id'])) . '</div>
                            <div class="col-1 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-5 px-1">' . htmlspecialchars($row['description']) . '</div>
                            <div class="col-1 px-1">$' . number_format($row['rate'], 2) . '</div>
                            <div class="col-2 px-1 text-end">$' . number_format($row_total, 2) . '</div>
                            <div class="col-1 px-1 text-center">
                                <button class="btn btn-sm btn-outline-secondary" onclick="editInvoiceItem(' . htmlspecialchars($row['id']) . ')"><i class="bx bx-edit"></i></button>
                            </div>
                        </div>';
        }

        // Calculate GST on the subtotal without delivery rate
        $gst = $sub_total * $gst_rate;
        // Calculate total including delivery rate and GST
        $total = $sub_total + $gst + $delivery_rate;

        $output .= '<div class="row border-bottom align-items-center py-1" style="margin: 0;">
                        <div class="col-8 px-1"></div>
                        <div class="col-1 px-1 fw-bold text-end">Subtotal</div>
                        <div class="col-2 px-1 text-end">$' . number_format($sub_total, 2) . '</div>
                    </div>
                    
                    <div class="row border-bottom align-items-center py-1" style="margin: 0;">
                        <div class="col-8 px-1"></div>
                        <div class="col-1 px-1 fw-bold text-end">GST</div>
                        <div class="col-2 px-1 text-end">$' . number_format($gst, 2) . '</div>
                    </div>
					<div class="row border-bottom align-items-center py-1" style="margin: 0;">
                        <div class="col-8 px-1"></div>
                        <div class="col-1 px-1 fw-bold text-end">Freight</div>
                        <div class="col-2 px-1 text-end">$' . number_format($delivery_rate, 2) . '</div>
                    </div>
                    <div class="row border-bottom align-items-center py-1" style="margin: 0;">
                        <div class="col-8 px-1"></div>
                        <div class="col-1 px-1 fw-bold text-end">Total</div>
                        <div class="col-2 px-1 text-end">$' . number_format($total, 2) . '</div>
                    </div>
                    ';

        $output .= '    </div>
                   </div></div>';
        sendJsonResponse(['html' => $output]);
    } else {
        sendJsonResponse(['html' => '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="importOrder()">Import Items to Invoice</button><div class="card-body"><h5 class="card-title">No Items Found</h5></div>']);
    }
}


    $conn = null; // Close database connection
}
