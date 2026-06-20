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

$database = new Database();
$conn = $database->connect();
// Checks if the request method is POST and 'tab_id' is set
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id'])) {
    $database = new Database();
    $conn = $database->connect();
    $tab_id = $_POST['tab_id'];
    
 if ($_POST['tab_id'] == 'production') {
     $query = "SELECT osi.id, osi.order_id, osi.order_group_id, osi.part_number, osi.description, osi.qty, osi.qty_unit, osi.pack_id, o.order_status_id
          FROM tblOrderSubItems osi
          JOIN tblOrders o ON osi.order_id = o.order_id
          JOIN tblOrderItems oi ON oi.id = osi.order_group_id AND oi.company_id = osi.company_id
          WHERE osi.company_id = :company_id 
            AND (osi.serial_number IS NULL OR osi.serial_number = '')
            AND (oi.purchased_item IS NULL OR oi.purchased_item = '' OR oi.purchased_item = 0)
            AND o.order_status_id > 4
          GROUP BY osi.order_id, osi.part_number
          ORDER BY osi.order_id, osi.part_number";


    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->execute();

    $data = '<div class="container-fluid">
              <div class="row">
                  <div class="col-lg-10">
                      <div class="card">
                          <div class="card-body">
                              <div class="d-flex justify-content-between align-items-start mb-2">
                                  <div>
                                      <h5 class="card-title">Production Orders</h5>
                                      <button type="button" class="btn btn-secondary mb-3" onclick="window.location.href=\'includes_pages/admin_production/export_production_csv.php\'" title="Export as CSV">
                                          Export CSV
                                      </button>
                                  </div>
                              </div>

                              <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                                  <div class="col-1 px-1">Order</div>
                                  <div class="col-2 px-1">Pack</div>
                                  <div class="col-2 px-1">Item#</div>
                                  <div class="col-3 px-1">Description</div>
                                  <div class="col-1 px-1">Quantity</div>
                                  <div class="col-1 px-1">Purchased</div>
                                  <div class="col-1 px-1">Match</div>
                              </div>';

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data .= '<div class="row align-items-center border-bottom py-1 hover-row bg-light production-row" data-order-item-id="' . htmlspecialchars($row['order_group_id']) . '">
                      <div class="col-1 px-1">' . htmlspecialchars($row['order_id']) . '</div>
                      <div class="col-2 px-1">' . htmlspecialchars($row['pack_id']) . '</div>
                      <div class="col-2 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                      <div class="col-3 px-1">' . htmlspecialchars($row['description']) . '</div>
                      <div class="col-1 px-1 text-end">' . getLengSum($row['order_group_id']) . '</div>
                      <div class="col-1 px-1 text-center">
                          <input type="checkbox"
                                 class="form-check-input production-purchased-checkbox"
                                 data-order-item-id="' . htmlspecialchars($row['order_group_id']) . '"
                                 data-order-id="' . htmlspecialchars($row['order_id']) . '"
                                 title="Mark this item as purchased and remove it from production">
                      </div>
                      <div class="col-1 px-1">
                          <button type="button" class="btn btn-sm btn-secondary print-btn" onclick="fromCoilModal(' . $row['id'] . ')">Match</button>
                      </div>
                  </div>';
    }

    $data .= '          </div>
                      </div>
                  </div>
              </div>
          </div>';

    echo $data;
}
   
 /*   
if ($_POST['tab_id'] == 'production') {
$query = "SELECT osi.id, osi.order_id, osi.order_group_id, osi.part_number, osi.description, osi.qty, osi.qty_unit, osi.pack_id, o.order_status_id
          FROM tblOrderSubItems osi
          JOIN tblOrders o ON osi.order_id = o.order_id
          WHERE osi.company_id = :company_id AND o.order_status_id = 5
          GROUP BY osi.order_id, osi.part_number
          ORDER BY osi.order_id, osi.part_number";



    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $statement->execute();

$data = '<div class="container-fluid">
              <div class="row">
                  <div class="col-lg-10">
                      <div class="card">
                          <div class="card-body">
                              <div class="d-flex justify-content-between align-items-start mb-2">
                                  <div>
                                      <h5 class="card-title">Production Orders</h5>
                                      <button type="button" class="btn btn-secondary mb-3" onclick="window.location.href=\'includes_pages/admin_production/export_production_csv.php\'" title="Export as CSV">
                                          Export CSV
                                      </button>
                                  </div>
                              </div>


                                      <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                                          <div class="col-1 px-1">Order</div>
                                          <div class="col-2 px-1">Pack</div>
                                          <div class="col-2 px-1">Item#</div>
                                          <div class="col-4 px-1">Description</div>
                                          <div class="col-1 px-1">Quantity</div>
                                          <div class="col-1 px-1">Match</div>
                                      </div>';


    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $data .= '<div class="row align-items-center border-bottom py-1 hover-row bg-light">
                          <div class="col-1 px-1">' . htmlspecialchars($row['order_id']) . '</div>
                          <div class="col-2 px-1">' . htmlspecialchars($row['pack_id']) . '</div>
                          <div class="col-2 px-1">' . htmlspecialchars($row['part_number']) . '</div>
                          <div class="col-4 px-1">' . htmlspecialchars($row['description']) . '</div>
                          <div class="col-1 px-1 text-end">' . getLengSum($row['order_group_id']) . '</div>
                          <div class="col-1 px-1">
                              <button type="button" class="btn btn-sm btn-secondary print-btn" onclick="fromCoilModal('.$row['id'].')">Match</button>
                          </div>
                      </div>';
        }

    $data .= '          </div>
                      </div>
                  </div>
              </div>
          </div>';

    echo $data;
}
*/
if ($_POST['tab_id'] == 'production_history') {
    $serial_number_filter = isset($_POST['serial_number']) ? $_POST['serial_number'] : '';

        $query = "SELECT * FROM tblProduction WHERE company_id = :company_id";
        if (!empty($serial_number_filter)) {
            $query .= " AND serial_number LIKE :serial_number";
        }
        $query .= " ORDER BY production_date";
        if (empty($serial_number_filter)) {
            $query .= " LIMIT 50";
        }

    $statement = $conn->prepare($query);
    $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    if (!empty($serial_number_filter)) {
        $statement->bindValue(':serial_number', '%' . $serial_number_filter . '%', PDO::PARAM_STR);
    }
    $statement->execute();

    $data = '<div class="container-fluid">
                  <div class="row">
                      <div class="col-lg-12">
                          <div class="card">
                              <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-start mb-2">
                                      <div>
                                          <h5 class="card-title">Production History</h5>
                                          <button type="button" class="btn btn-secondary mb-2" onclick="window.location.href=\'includes_pages/admin_production/export_production_history_csv.php\'" title="Export as CSV">
                                              Export CSV
                                          </button>
                                      </div>
                                      <div>
                                         <div class="mt-4">
                                            <div class="input-group">
                                                <input type="text" id="serial_number_search" class="form-control form-control-sm" placeholder="Search Serial">
                                                <button type="button" class="btn btn-sm btn-secondary" onclick="searchProductionHistory()">Search</button>
                                            </div>
                                        </div>
                                      </div>
                                  </div>
                                  <div class="container-fluid px-0">
                                      <div class="row fw-bold border-bottom mb-1" style="margin: 0;">
                                          <div class="col-1 px-1">Date</div>
                                          <div class="col-1 px-1">Order#</div>
                                          <div class="col-2 px-1">Description</div>
                                          <div class="col-2 px-1">Serial</div>
                                          <div class="col-1 px-1">Used</div>
                                          <div class="col-1 px-1">In</div>
                                          <div class="col-1 px-1">Waste</div>
                                          <div class="col-1 px-1">Qty</div>
                                          <div class="col-2 px-1">Option</div>
                                      </div>';

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data .= '<div class="row align-items-center border-bottom py-1 hover-row bg-light">
                      <div class="col-1 px-1">' . date_c($row['production_date']) . '</div>
                      <div class="col-1 px-1">' . htmlspecialchars($row['order_id']) . '</div>
                      <div class="col-2 px-2">' . htmlspecialchars($row['part_number']) . '</div>
                      <div class="col-2 px-2">' . htmlspecialchars($row['serial_number']) . '</div>
                      <div class="col-1 px-2">' . htmlspecialchars($row['stock_from_coil']) . '</div>
                      <div class="col-1 px-2">' . htmlspecialchars($row['stock_in_qty']) . '</div>
                      <div class="col-1 px-2">' . htmlspecialchars($row['waste_qty']) . '</div>
                      <div class="col-1 px-2">' . htmlspecialchars($row['order_qty']) . '</div>

                      <div class="col-2 px-2">
                          <button type="button" class="btn btn-sm btn-secondary print-btn" onclick="delProductionId(' . $row['id'] . ', \'' . addslashes($row['order_item_id']) . '\', ' . $row['order_qty'] . ')">Delete</button>
                      </div>
                  </div>';
    }

    $data .= '          </div>
                      </div>
                  </div>
              </div>
          </div>';

    echo $data;
}


}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['sub_tab_id'])) {
    $database = new Database();
    $conn = $database->connect();
    $sub_tab_id = $_POST['sub_tab_id'];
    
    
if ($_POST['sub_tab_id'] == 'select_from_coil') {
    $raw_mat = 1;
    $src_id = $_POST['source_id'];

    $output = '<div class="container-fluid">
               <input type="hidden" id="selected_value">
               <div class="row">
                   <div class="col-lg-12">
                       <div class="card">
                           <div class="card-body">
                               <h5 class="card-title">Select Coil Stock</h5>';

    // Query to get the selected inventory item
   // $sqlInv = "SELECT * FROM tblInventory WHERE company_id = :comp_id AND id = :id AND raw_material = :raw_mat ORDER BY part_number";
    $sqlInv = "SELECT * FROM tblInventory WHERE company_id = :comp_id AND id = :id AND raw_material = :raw_mat ORDER BY part_number";
    $stmtInv = $conn->prepare($sqlInv);
    $stmtInv->bindValue(':comp_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmtInv->bindValue(':id', $src_id, PDO::PARAM_INT);
    $stmtInv->bindValue(':raw_mat', $raw_mat, PDO::PARAM_INT);
    $stmtInv->execute();

    // Display only if there are inventory items
    if ($stmtInv->rowCount() > 0) {
        while ($invRow = $stmtInv->fetch(PDO::FETCH_ASSOC)) {
            // Query to get all items related to the current inventory
            $sqlItems = "SELECT * FROM tblInventoryItems WHERE inventory_id = :inv_id AND coil_finished = 0";
            $stmtItems = $conn->prepare($sqlItems);
            $stmtItems->bindValue(':inv_id', $invRow['id'], PDO::PARAM_INT);
            $stmtItems->execute();

            if ($stmtItems->rowCount() > 0) {
                $output .= '<div class="mb-2">';
                $output .= '<div class="font-weight-bold"><b>' . htmlspecialchars($invRow['description']) . '</b></div>';
                while ($itemRow = $stmtItems->fetch(PDO::FETCH_ASSOC)) {
                    $output .= '<div class="row align-items-center py-1 border-bottom">
                                  <div class="col-4">' . htmlspecialchars($itemRow['qty']) . '</div>
                                  <div class="col-7">' . htmlspecialchars($itemRow['serial_number']) . '</div>
                                  <div class="col-1 text-end">
                                      <input type="radio" name="selected_coil_item" value="' . htmlspecialchars($itemRow['id']) . '">
                                  </div>
                              </div>';
                }
                $output .= '</div>';
            }
        }
    }

    // Query and display alternative inventory items (only if there are items to display)
    $sqlAlt = "SELECT * FROM tblInventory WHERE company_id = :comp_id AND id != :id AND raw_material = :raw_mat ORDER BY part_number";
    $stmtAlt = $conn->prepare($sqlAlt);
    $stmtAlt->bindValue(':comp_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmtAlt->bindValue(':id', $src_id, PDO::PARAM_INT);
    $stmtAlt->bindValue(':raw_mat', $raw_mat, PDO::PARAM_INT);
    $stmtAlt->execute();

    while ($altRow = $stmtAlt->fetch(PDO::FETCH_ASSOC)) {
        // Query to get all items related to the alternative inventory
        $sqlItems = "SELECT * FROM tblInventoryItems WHERE inventory_id = :inv_id";
        $stmtItems = $conn->prepare($sqlItems);
        $stmtItems->bindValue(':inv_id', $altRow['id'], PDO::PARAM_INT);
        $stmtItems->execute();

        if ($stmtItems->rowCount() > 0) {
            $output .= '<div class="mb-2">';
            $output .= '<div class="d-flex justify-content-between align-items-center font-weight-bold border-bottom ">
                            <span><i>' . htmlspecialchars($altRow['description']) . '</i></span>
                            <a class="collapsed" data-bs-toggle="collapse" href="#collapse' . htmlspecialchars($altRow['id']) . '" role="button" aria-expanded="false" aria-controls="collapse' . htmlspecialchars($altRow['id']) . '">
                                <i class="bi bi-chevron-down"></i>
                            </a>
                        </div>';

            $output .= '<div class="collapse" id="collapse' . htmlspecialchars($altRow['id']) . '">';
            while ($itemRow = $stmtItems->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<div class="row align-items-center py-1 border-bottom">
                              <div class="col-4">' . htmlspecialchars($itemRow['qty']) . '</div>
                              <div class="col-7">' . htmlspecialchars($itemRow['serial_number']) . '</div>
                              <div class="col-1 text-end">
                                  <input type="radio" name="selected_coil_item" value="' . htmlspecialchars($itemRow['id']) . '">
                              </div>
                          </div>';
            }
            $output .= '</div>'; // Close collapse
            $output .= '</div>'; // Close row
        }
    }

    $output .= '</div>'; // Close card-body
    $output .= '</div>'; // Close card
    $output .= '</div>'; // Close col-lg-12
    $output .= '</div>'; // Close container-fluid

    echo $output;
}


if ($_POST['sub_tab_id'] == 'select_production_stock') {
    $sql = "SELECT *
            FROM tblInventoryItems
            WHERE company_id = :company_id AND order_id = :order_id AND  part_number= :part_number";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
    $stmt->bindValue(':part_number', $_POST['part_number'], PDO::PARAM_STR);
    $stmt->execute();

    $output = '<div class="container-fluid">
                   <input type="hidden" id="selected_value">
                   <div class="row mb-2">
                       <div class="col-lg-6">
                           <h5 class="card-title">Stock In</h5>
                       </div>
                       <div class="col-lg-6 text-end">
                           <button type="button" onclick="addStockModal()" class="btn btn-outline-secondary btn-sm">Add Stock In</button>
                       </div>
                   </div>';

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output .= '<div class="row border-bottom align-items-center">
                        <div class="col-4 px-1">' . htmlspecialchars($row['serial_number']) . '</div>
                        <div class="col-7 px-1">' . htmlspecialchars($row['qty']. ' X ' .$row['qty_unit']) . '</div>
                        <div class="col-1 px-1 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-id="' . htmlspecialchars($row['id']) . '" onclick="deleteStockItem(' . htmlspecialchars($row['id']) . ')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>';
    }
    $output .= '</div>'; 
    echo $output;
}

if ($_POST['sub_tab_id'] == 'select_stock') {
    $sql = "SELECT *
            FROM tblInventoryItems
            WHERE company_id = :company_id  AND part_number = :part_number";
    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
   // $stmt->bindValue(':order_id', $_POST['order_id'], PDO::PARAM_INT);
    $stmt->bindValue(':part_number', $_POST['part_number'], PDO::PARAM_STR);
    $stmt->execute();

    // Fetch all results at once to check if any results exist
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = '<div class="container-fluid">
                   <div class="row mb-2">
                       <div class="col-lg-12">
                           <h5 class="card-title">Select From Stock</h5>
                       </div>
                   </div>
                   <div class="card">
                           <div class="card-body">';

    if (count($results) > 0) {
        // If results are found, iterate through and display them
        $radioName = "selected_stock_item"; // Radio button group name to ensure only one item is selected
        foreach ($results as $row) {
            $qtyTotal = $row['qty'] * $row['qty_unit']; // Calculate the total

            $output .= '<div class="row border-bottom align-items-center">
                            <div class="col-5 px-1">' . htmlspecialchars($row['qty'] . ' X ' . $row['qty_unit']) . '</div>

                            <div class="col-2 d-flex justify-content-center align-items-center">
                                <input type="radio" class="form-check-input calculate-stock-length" 
                                    name="' . $radioName . '" 
                                    data-stock-item-id="' . $row['id'] . '" 
                                    data-length="' . $qtyTotal . '">
                            </div>
                        </div>';
        }
    } else {
        // If no results, show a "No items available" message
        $output .= '<div class="row">
                        <div class="col-12">
                            <p class="text-danger text-center">No items available.</p>
                        </div>
                    </div>';
    }

    $output .= '</div></div></div>';
    echo $output;
}





if ($_POST['sub_tab_id'] == 'select_order_items') {
    $oid = $_POST['order_id'];
    $pnum = $_POST['part_number'];

    // Query to get order sub-items
    $qry = "SELECT osi.* FROM tblOrderSubItems osi
            JOIN tblOrderItems oi ON oi.id = osi.order_group_id AND oi.company_id = osi.company_id
            WHERE osi.company_id = :cid
            AND osi.order_id = :oid
            AND osi.part_number = :pnum
            AND (oi.purchased_item IS NULL OR oi.purchased_item = '' OR oi.purchased_item = 0)";
    $stmt = $conn->prepare($qry);
    $stmt->bindValue(':cid', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->bindValue(':oid', $oid, PDO::PARAM_INT);
    $stmt->bindValue(':pnum', $pnum, PDO::PARAM_STR);
    $stmt->execute();
    $output = '<div class="container-fluid">
                 <input type="hidden" id="selected_value">
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-body">';

    if ($stmt->rowCount() > 0) {
        $output .= '<div class="row border-bottom py-1 bg-light">
                        <div class="col-12 d-flex justify-content-between align-items-center">
                            <b class="me-2">Select All</b>
                            <input type="checkbox" class="form-check-input" id="select-all">
                        </div>
                    </div>';

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $len = $row['qty'] * $row['qty_unit'];
            $isDisabled = !empty($row['serial_number']) ? 'disabled' : '';

            $output .= '<div class="row align-items-center border-bottom py-1">
                            <div class="col-4 d-flex align-items-center">' .  htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-4 d-flex align-items-center">' . htmlspecialchars($row['qty']) . ' X ' . htmlspecialchars($row['qty_unit']) . '</div>';

            if ($row['qty'] > 1) {
                $output .= '<div class="col-2 d-flex justify-content-center align-items-center">
                                <button type="button" class="btn btn-sm btn-success split-btn" data-id="' . $row['id'] . '" onclick="SplitProdModal(' . $row['id'] . ')" ' . $isDisabled . '>
                                    <i class="bi bi-scissors"></i>
                                </button>
                            </div>';
            } else {
                $output .= '<div class="col-2 d-flex justify-content-center align-items-center"></div>';
            }

            $output .= '<div class="col-2 d-flex justify-content-center align-items-center">
                            <input type="checkbox" class="form-check-input calculate-length" data-order-item-id="'.$row['id'].'" data-length="' . $len . '" ' . $isDisabled . '>
                        </div>
                        </div>';
        }

        $output .= '<div class="row align-items-center border-bottom py-1 bg-light">
                        <div id="total_length" class="col-12 text-right font-weight-bold">Total Length: 0.000</div>
                    </div>';
    } else {
        $output .= '<div class="row border p-2 mb-2">
                        <div class="col-12 text-center font-weight-bold text-danger">Not available.</div>
                    </div>';
    }

    $output .= '</div></div></div></div></div>';
    echo $output;
}


if ($_POST['sub_tab_id'] == 'stock_order_select_items') {
    $oid = $_POST['order_id'];
    $pnum = $_POST['part_number'];

    // Query to get order sub-items
    $qry = "SELECT osi.* FROM tblOrderSubItems osi
            JOIN tblOrderItems oi ON oi.id = osi.order_group_id AND oi.company_id = osi.company_id
            WHERE osi.company_id = :cid
            AND osi.order_id = :oid
            AND osi.part_number = :pnum
            AND (oi.purchased_item IS NULL OR oi.purchased_item = '' OR oi.purchased_item = 0)";
    $stmt = $conn->prepare($qry);
    $stmt->bindValue(':cid', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->bindValue(':oid', $oid, PDO::PARAM_INT);
    $stmt->bindValue(':pnum', $pnum, PDO::PARAM_STR);
    $stmt->execute();

    $output = '<div class="container-fluid">
                 <input type="hidden" id="selected_value">
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-body">Select Items From Order';

    if ($stmt->rowCount() > 0) {
        $radioName = "order_item"; // Radio button group name
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $len = $row['qty'] * $row['qty_unit'];
            $isDisabled = !empty($row['serial_number']) ? 'disabled' : '';

            $output .= '<div class="row align-items-center border-bottom py-1">
                            <div class="col-4 d-flex align-items-center">' . htmlspecialchars($row['part_number']) . '</div>
                            <div class="col-4 d-flex align-items-center">' . htmlspecialchars($row['qty']) . ' X ' . htmlspecialchars($row['qty_unit']) . '</div>
                            <div class="col-2 d-flex justify-content-center align-items-center"></div>';

            // Radio button to select only one row
            $output .= '<div class="col-2 d-flex justify-content-center align-items-center">
                            <input type="radio" class="form-check-input" name="' . $radioName . '" stock-data-order-item-id="' . $row['id'] . '" ' . $isDisabled . '>
                        </div>
                        </div>';
        }

    } else {
        $output .= '<div class="row border p-2 mb-2">
                        <div class="col-12 text-center font-weight-bold text-danger">Not available.</div>
                    </div>';
    }

    $output .= '</div></div></div></div></div>';
    echo $output;
}





}

?>
