<?php
session_start();
ini_set('display_errors', 'Off');
include("../../includes/common.php");
requireLoggedInJson();

function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Checks if the request method is POST and 'tab_id' is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id'])) {
    $database = new Database();
    $conn = $database->connect();
    $tab_id = $_POST['tab_id'];
    
    
if ($tab_id == 'home') {
    $data = '<div class="row">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Vendor</h5>
                            <div class="row mb-0">
                                <label for="inputText" class="col-sm-4 col-form-label">Name</label>
                                <div class="col-sm-8">
                                    <input type="text" id="vendor_search" class="form-control form-control-sm" placeholder="Search Vendor">
                                    <input type="hidden" id="vendor_uid" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputPassword" class="col-sm-4 col-form-label">Address</label>
                                <div class="col-sm-8">
                                    <input type="text" id="vendor_address" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputPassword" class="col-sm-4 col-form-label">Suburb</label>
                                <div class="col-sm-8">
                                    <input type="text" id="vendor_suburb" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputPassword" class="col-sm-4 col-form-label">State</label>
                                <div class="col-sm-8">
                                    <input type="text" id="vendor_state" class="form-control form-control-sm">
                                </div>
                            </div>    
                            <div class="row mb-0">
                                <label for="inputPassword" class="col-sm-4 col-form-label">Postcode</label>
                                <div class="col-sm-8">
                                    <input type="number" id="vendor_postcode" class="form-control form-control-sm">
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputPassword" class="col-sm-4 col-form-label">Email</label>
                                <div class="col-sm-8">
                                    <input type="text" id="vendor_email" class="form-control form-control-sm">
                                </div>
                            </div>    
                            <div class="row mb-0">
                                <label for="inputNumber" class="col-sm-4 col-form-label">Phone</label>
                                <div class="col-sm-8">
                                    <input type="number" id="vendor_phone" class="form-control form-control-sm">
                                </div>
                            </div>
                            
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Order Details</h5>
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Order#:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="order_number" id="order_number" disabled class="form-control-sm form-control"> 
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Date:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="order_date" id="order_date" disabled class="order_date_picker form-control-sm form-control"> 
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Required:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="order_date_required" id="order_date_required" class="order_date_required_picker form-control-sm form-control"> 
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Est Arrival:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="estimated_arrival_date" id="estimated_arrival_date" class="estimated_arrival_date_picker form-control-sm form-control"> 
                                </div>
                            </div>
                            
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Freight:</label>
                                <div class="col-sm-8">
                                    <input type="text" name="freight" id="freight" class="form-control-sm form-control"> 
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Vendor Invoice#:</label>
                                <div class="col-sm-8">
                                    <input type="text" id="ven_inv_number"  class="form-control-sm form-control">
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputText" class="col-sm-12 col-form-label">Notes</label>
                                <div class="col-sm-12">
                                    <textarea type="text" rows="2" id="order_notes" class="form-control form-control-sm" placeholder="Example: Order Notes for supplier (shown on Purchase Order)"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                           <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Status:</label>
                                <div class="col-sm-8">
                                    <select name="order_status" id="order_status" class="form-control-sm form-control"></select>
                                    <input type="hidden" id="order_status_id">
                                </div>
                            </div>
                            
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Contact:</label>
                                <div class="col-sm-8">
                                    <select name="orderuser" id="purchaser_user" class="form-control-sm form-control"></select>
                                    <input type="hidden" id="purchaser_user_id">
                                </div>
                            </div>
                            <div class="row mb-0">
                                <label for="inputEmail" class="col-sm-4 col-form-label">Delivery:</label>
                                <div class="col-sm-8">
                                    <textarea class="form-control form-control-sm" id="delivery_address" rows="3"></textarea>
                                </div>
                            </div>

                                    <input type="hidden" class="form-control form-control-sm" id="payment_terms_day" />

          
                                    <input type="hidden" class="form-control form-control-sm" id="payment_terms_type"/>
                
                           <div class="row mb-0">
                                <label for="inputText" class="col-sm-12 col-form-label">Notes</label>
                                <div class="col-sm-12">
                                    <textarea type="text" rows="3" id="additional_notes" class="form-control form-control-sm" placeholder="Example: Internal Notes"></textarea>
                                </div>
                            </div>
                            

                            <div class="d-flex  align-items-center">
                                <div id="saveContactsDiv" class="me-2">
                                    <button id="saveCreateBtn" class="btn btn-outline-secondary btn-sm">
                                        <i class="bi bi-save"></i> <span id="buttonText">Save</span>
                                    </button>
                                </div>
                                
                            </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>';
    sendJsonResponse(['html' => $data]);
}
    
}
    // Checks if the request method is POST and 'tab_id' is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sub_tab_id'])) {
    $database = new Database();
    $conn = $database->connect();
    $sub_tab_id = $_POST['sub_tab_id'];
    

if ($sub_tab_id == 'ordered_items') {
    $query = "SELECT * FROM tblPurchaseItems WHERE company_id = :company_id AND pid = :pid ORDER BY part_number";
    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->bindValue(':pid', $_POST['pid'], PDO::PARAM_INT);
    $statement->execute();
    $rowCount = $statement->rowCount();

    if ($rowCount > 0) {
        $subtotal = 0;

        $output = '<div class="card">
                    <div class="card-body ">
                      <div class="d-flex justify-content-between align-items-center mb-2 alert alert-warning p-1">
    <!-- Left-aligned Order text -->
    <div>
        <h5 class="card-title mb-0">Order</h5>
    </div>

            <!-- Right-aligned buttons container -->
            <div class="ms-auto d-flex gap-2">

                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrderItemsModal()">Add Item to Purchase Order</button>
                <button type="button" class="btn btn-sm btn-success" onclick="OpenProcessPurchaseModal('.$_POST['pid'].')"><i class="bx bxs-factory"></i> Process Purchase</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="convertBill()">Convert Bill</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="PrintPurchaseOrder('.$_POST['pid'].')">Print</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="PrintPurchaseDel('.$_POST['pid'].')">Print Delivery</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="EmailPurchaseOrder('.$_POST['pid'].')">Email</button>
            </div>
        </div>


                       <div class="container-fluid px-0">
                           <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                               <div class="col-1 px-1">Item#</div>
                               <div class="col-5 px-1">Description</div>
                               <div class="col-1 px-1">Qty</div>
                               <div class="col-1 px-1">Price</div>
                               <div class="col-1 px-1">Unit</div>
                               <div class="col-2 px-1 text-end">Total(Ex)</div>
                               <div class="col-1 px-1">Actions</div>
                           </div>';

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $row_value = $row['has_items'] ? getLengSumPur($row['id']) : $row['qty'];
            $row_value_c = $row_value * $row['rate'];
            $subtotal += $row_value_c;
            $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);

            $output .= '<div class="row fw-bold align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                            <div class="col-1 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-5 px-1">' . htmlspecialchars($row['description']) . '</div>
                            <div class="col-1 px-1">' . $row_value . '</div>
                            <div class="col-1 px-1">$' . number_format($row['rate'], 2) . '</div>
                            <div class="col-1 px-1">' . htmlspecialchars($unit) . '</div>
                            <div class="col-2 px-1 text-end">$' . number_format($row_value_c, 2) . '</div>
                            <div class="col-1 px-1">
                                <button class="btn btn-sm btn-outline-secondary" onclick="editOrderItemModal(' . htmlspecialchars($row['id']) . ')"><i class="bx bx-edit"></i></button>
                            </div>
                        </div>';

            if ($row['has_items']) {
                $subQuery = "SELECT * FROM tblPurchaseSubItems WHERE order_group_id = :order_group_id ORDER BY qty_unit";
                $subStatement = $conn->prepare($subQuery);
                $subStatement->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
                $subStatement->execute();

                while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
                    $output .= '<div class="row align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                                    <div class="col-1 px-2"></div>    
                                    <div class="col-4 px-6">' . htmlspecialchars($subRow['qty'] . ' x ' . $subRow['qty_unit']) . '</div>
                                    <div class="col-6 px-2">M '.$subRow['mark'].'</div> 
                                    <div class="col-1 px-1">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editOrderSubItemModal(' . $subRow['id'] . ')"><i class="bx bx-edit"></i></button>
                                    </div>
                                </div>';
                }
            }
        }

        $GST = $subtotal * 0.1; // Assuming GST is 10%
        $total = $subtotal + $GST;

        $output .= '    <div class="row fw-bold align-items-center border-top border-bottom py-2" style="margin: 0;">
                            <div class="col-9 text-end">Subtotal:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($subtotal, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom" style="margin: 0;">
                            <div class="col-9 text-end">GST:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($GST, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom" style="margin: 0;">
                            <div class="col-9 text-end">Total:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($total, 2) . '</div>
                        </div>
                    </div>
                   </div></div>';
        sendJsonResponse(['html' => $output]);
    } else {
        sendJsonResponse(['html' => '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrderItemsModal()">Add Item</button><button type="button" class="btn btn-sm btn-outline-secondary" onclick="copyOrderItemsModal()">Copy Order</button><div class="card-body"><h5 class="card-title">No Items Found</h5></div>']);
    }
}

if ($sub_tab_id == 'order_bill') {
    $query = "SELECT * FROM tblBillItems WHERE company_id = :company_id AND pid = :pid ORDER BY part_number";
    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->bindValue(':pid', $_POST['pid'], PDO::PARAM_INT);
    $statement->execute();

    if ($statement->rowCount() > 0) {
        $subtotal = 0;

        $output = '<div class="card">
                    <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2 alert alert-info p-1">
    <!-- Left-aligned Items Received text -->
    <div>
        <h5 class="card-title mb-0">Items Received</h5>
    </div>

    <!-- Right-aligned buttons container -->
    <div class="ms-auto d-flex gap-2">
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addReceivedItemsModal()">Add Item to Bill</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="receiveItemsModal('.$_POST['pid'].')">Receive Items     </button>
        
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="processInvButton()">Invoice Items</button>
    </div>
</div>

                       <div class="container-fluid px-0">
                           <div class="row fw-bold border-bottom mb-1 m-0">
                               <div class="col-1 px-1">Item#</div>
                               <div class="col-5 px-1">Description</div>
                               <div class="col-1 px-1">Qty</div>
                               <div class="col-1 px-1">Price</div>
                               <div class="col-1 px-1">Unit</div>
                               <div class="col-2 px-1 text-end">Total(Ex)</div>
                               <div class="col-1 px-1">Actions</div>
                           </div>';

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $row_value = $row['has_items'] ? getLengSumBill($row['id']) : $row['qty'];
            $row_value_c = $row_value * $row['rate'];
            $subtotal += $row_value_c;
            $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
            $sn = $row['serial_number'] ? " (" . htmlspecialchars($row['serial_number']) . ")" : '';

            $output .= '<div class="row fw-bold align-items-center border-bottom py-1 hover-row m-0">
                            <div class="col-1 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-5 px-1">' . htmlspecialchars($row['description']) . $sn . '</div>
                            <div class="col-1 px-1">' . htmlspecialchars($row_value) . '</div>
                            <div class="col-1 px-1">$' . number_format($row['rate'], 2) . '</div>
                            <div class="col-1 px-1">' . htmlspecialchars($unit) . '</div>
                            <div class="col-2 px-1 text-end">$' . number_format($row_value_c, 2) . '</div>
                            <div class="col-1 px-1">
                                <button class="btn btn-sm btn-outline-secondary" onclick="editBIllItemModal(' . htmlspecialchars($row['id']) . ')"><i class="bx bx-edit"></i></button>
                            </div>
                        </div>';

            if ($row['has_items']) {
                $subQuery = "SELECT * FROM tblBillSubItems WHERE order_group_id = :order_group_id ORDER BY qty_unit";
                $subStatement = $conn->prepare($subQuery);
                $subStatement->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
                $subStatement->execute();

                while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
                    $output .= '<div class="row align-items-center border-bottom py-1 hover-row m-0">
                                    <div class="col-1 px-2"></div>    
                                    <div class="col-6 px-6">' . htmlspecialchars($subRow['qty'] . ' x ' . $subRow['qty_unit']) . '</div>
                                    <div class="col-4 px-2">M '.$subRow['mark'].'</div> 
                                    <div class="col-1 px-1">
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editBillSubItemModal(' . htmlspecialchars($subRow['id']) . ')"><i class="bx bx-edit"></i></button>
                                    </div>
                                </div>';
                }
            }
        }

        $GST = $subtotal * 0.1; // Assuming GST is 10%
        $total = $subtotal + $GST;

        $output .= '    <div class="row fw-bold align-items-center border-top border-bottom py-2 m-0">
                            <div class="col-9 text-end">Subtotal:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($subtotal, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom m-0">
                            <div class="col-9 text-end">GST:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($GST, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom m-0">
                            <div class="col-9 text-end">Total:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($total, 2) . '</div>
                        </div>
                    </div>
                   </div></div>';
        sendJsonResponse(['html' => $output]);
    } else {
        sendJsonResponse(['html' => '<button type="button" class="btn btn-sm btn-outline-secondary" onclick="addBillItemsModal()">Add Item</button><div class="card-body"><h5 class="card-title">No Items Found</h5></div>']);
    }
}

if ($sub_tab_id == 'order_invoice') {
    // Fetch invoice items
    $query = "SELECT * FROM tblPurchaseInvoice WHERE company_id = :company_id AND pid = :pid ORDER BY part_number";
    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->bindValue(':pid', $_POST['pid'], PDO::PARAM_INT);
    $statement->execute();
    $rowCount = $statement->rowCount();
	$bill_date=getTabFieCol('bill_date', 'tblPurchaseInvoice', 'pid', $_POST['pid'], $_SESSION['session_company_id']);
    $transaction_due_date=getTabFieCol('transaction_due_date', 'tblPurchaseInvoice', 'pid', $_POST['pid'], $_SESSION['session_company_id']);
    $transaction_uid=getTabFieCol('transaction_uid', 'tblPurchaseInvoice', 'pid', $_POST['pid'], $_SESSION['session_company_id']);
    if ($rowCount > 0) {
        $subtotal = 0;
        $output = '<div class="card">
                    <div class="card-body">
                  <div class="card-body">
        <div class="d-flex justify-content-between align-items-center alert alert-success p-1 mb-2">
            <!-- Title Section -->
            <h5 class="card-title mb-0">Invoice</h5>

            <!-- Information Section -->
            <div class="d-flex align-items-center ms-auto">
                <!-- Invoiced Date -->
                <div class="small-alert me-3" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    Invoiced Converted ' . date_c($bill_date) . '
                </div>

                <!-- Due Date -->
                <div class="small-alert me-3" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    Due ' . date_c($transaction_due_date) . '
                </div>

                <!-- ID -->
                <div class="small-alert me-3" role="alert">
                    <i class="bi bi-check-circle me-1"></i>
                    ID ' . $transaction_uid . '
                </div>

                <!-- Button -->
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="receiveInvoiceModal(' . $_POST['pid'] . ')">Invoice Items</button>
                 <button type="button" class="btn btn-sm btn-outline-secondary" onclick="delInvoiceModal(' . $_POST['pid'] . ')">Delete Invoice </button>
            </div>
        </div>

                       <div class="container-fluid px-0">
                           <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                               <div class="col-1 px-1">Item#</div>
                               <div class="col-5 px-1">Description</div>
                               <div class="col-1 px-1">Qty</div>
                               <div class="col-1 px-1">Price</div>
                               <div class="col-1 px-1">Unit</div>
                               <div class="col-2 px-1 text-end">Total(Ex)</div>
                           </div>';

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $description = $row['description'];
            $rate = $row['rate'];
            $qty = $row['qty_total'];
            $row_value_c = $qty * $rate;
            $subtotal += $row_value_c;
            $unit_id= getTabFieCol('unit_id', 'tblInventory', 'part_number' ,$row['part_number'], $_SESSION['session_company_id']);
            $unit= getTableColField('description' ,'tblItemUnits', 'id', $unit_id);
            $output .= '<div class="row fw-bold align-items-center border-bottom py-1 hover-row" style="margin: 0;">
                            <div class="col-1 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-5 px-1">' . htmlspecialchars($description) . '</div>
                            <div class="col-1 px-1">' . $qty . '</div>
                            <div class="col-1 px-1">$' . number_format($rate, 2) . '</div>
                            <div class="col-1 px-1">'.$unit.'</div>
                            <div class="col-2 px-1 text-end">$' . number_format($row_value_c, 2) . '</div>
  
                        </div>';
        }
        $freight=getTabFieCol('freight', 'tblPurchaseOrders', 'id', $_POST['pid'],$_SESSION['session_company_id']);
        $GST = $subtotal * 0.1; // Assuming GST is 10%
        $total = $subtotal + $GST +$freight;
        $output .= '<div class="row fw-bold align-items-center border-top border-bottom py-2" style="margin: 0;">
                            <div class="col-9 text-end">Subtotal:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($subtotal, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom" style="margin: 0;">
                            <div class="col-9 text-end">GST:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($GST, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom" style="margin: 0;">
                            <div class="col-9 text-end">Freight:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($freight, 2) . '</div>
                        </div>
                        <div class="row fw-bold align-items-center py-2 border-bottom" style="margin: 0;">
                            <div class="col-9 text-end">Total:</div>
                            <div class="col-2 px-1 text-end">$' . number_format($total, 2) . '</div>
                        </div>
                    </div>
                   </div></div>';
        sendJsonResponse(['html' => $output]);
    } else {
        $output = '<div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2 alert alert-warning p-1">
                            <div>
                                <h5 class="card-title mb-0">Purchase Order</h5>
                            </div>
                            <div class="ms-auto d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="addOrderItemsModal()">Add Item to Purchase Order</button>
                                <button type="button" class="btn btn-sm btn-success" onclick="OpenProcessPurchaseModal('.$_POST['pid'].')"><i class="bx bxs-factory"></i> Process Purchase</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="PrintPurchaseOrder('.$_POST['pid'].')">Print</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="PrintPurchaseDel('.$_POST['pid'].')">Print Delivery</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="EmailPurchaseOrder('.$_POST['pid'].')">Email</button>
                            </div>
                        </div>
                        <h5 class="card-title">No Items Found</h5>
                    </div>
                   </div>';
        sendJsonResponse(['html' => $output]);
    }
}

if ($sub_tab_id == 'purchase_activity') {
    $query = "SELECT * FROM tblPurchaseActivity WHERE pid = :pid AND company_id = :company_id ORDER BY action_date DESC";
    $result = $conn->prepare($query);
    $result->bindParam(':pid', $_POST['pid']);
    $result->bindParam(':company_id', $_SESSION['session_company_id']);
    $result->execute();

    $data = '
        <div class="row dashboard">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Purchase Activity</h5>
                            <button onclick="AddPurchaseActivityModal()" class="btn btn-sm btn-outline-secondary">Add Activity</button>
                        </div>
                        <div class="list-group">';

    if ($result->rowCount() === 0) {
        $data .= '<div class="list-group-item text-muted">No activity recorded yet.</div>';
    }

    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $description = htmlspecialchars($row['description']);
        $userName = htmlspecialchars((string)getUserFullName($row['user_id']));
        $data .= '<div class="list-group-item list-group-item-action">';
        $data .= '<div class="row align-items-center">';
        $data .= '<div class="col-md-2 sm-text"><span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Activity</span></div>';
        $data .= '<div class="col-md-2 sm-text">' . date_c($row['action_date']) . '</div>';
        $data .= '<div class="col-md-2 sm-text">' . $userName . '</div>';
        $data .= '<div class="col-md-5 sm-text">' . $description . '</div>';
        $data .= '<div class="col-md-1 sm-text"><button class="btn btn-sm btn-outline-secondary" onclick="EditPurchaseActivity(' . $row['id'] . ')"><i class="bx bx-edit"></i></button><button class="btn btn-sm btn-outline-secondary" onclick="delPurchaseActivity(' . $row['id'] . ')"><i class="bx bxs-trash"></i></button></div>';
        $data .= '</div>';
        $data .= '</div>';
    }

    $data .= '</div></div></div></div></div>';
    sendJsonResponse(['html' => $data]);
}


    $conn = null; // Close database connection
}
