<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

include("../../includes/common.php");
requireLoggedInJson();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['sales_invoice_report'])) {
        $database = new Database();
        $conn = $database->connect();
        $query = "
            SELECT 
                part_number,
                SUM(qty) as total_quantity,
                SUM(weight) as total_weight,
                SUM(rate * qty) as total_value,
                part_number_description as part_number_description
            FROM 
                tblInvoice 
            WHERE 
                company_id = :company_id
        ";
        $params = array(':company_id' => $_SESSION['session_company_id']);

        if (isset($_POST['from_date']) && isset($_POST['to_date'])) {
            $from_date = strtotime($_POST['from_date']);
            $to_date = strtotime($_POST['to_date']);
            $query .= " AND transaction_invoice_date BETWEEN :from_date AND :to_date";
            $params[':from_date'] = $from_date;
            $params[':to_date'] = $to_date;
        }

        if (isset($_POST['part_number']) && !empty($_POST['part_number'])) {
            $part_number = $_POST['part_number'];
            $query .= " AND part_number = :part_number";
            $params[':part_number'] = $part_number;
        }

        $query .= " GROUP BY part_number ORDER BY part_number";

        error_log("Query: $query");
        error_log("Params: " . json_encode($params));

        $statement = $conn->prepare($query);
        foreach ($params as $key => &$val) {
            $statement->bindParam($key, $val);
        }
        $statement->execute();
        $rowCount = $statement->rowCount();
        error_log("Row Count: $rowCount");
        if ($rowCount > 0) {
            $total_quantity = 0;
            $total_weight = 0;
            $total_value = 0;

            $output = '<div class="card report-panel">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h5 class="card-title mb-0">Items Invoice Report</h5>
                                    <div class="text-muted small">Grouped invoice quantities, weights, and values by part number.</div>
                                </div>
                            </div>
                            <div class="report-filter-bar">
                                <div class="row g-2 align-items-end">
                                    <div class="col-12 col-md-3">
                                        <label for="select_part_number" class="form-label small text-muted">Part Number</label>
                                        <select name="select_part_number" id="select_part_number" class="form-select">
                                            <option>Select a part number</option>
                                            <option value="">----Select----</option>
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label for="from_date" class="form-label small text-muted">From Date</label>
                                        <input type="text" id="from_date" class="form-control datepicker" placeholder="dd-mm-yy">
                                    </div>
                                    <div class="col-6 col-md-2">
                                        <label for="to_date" class="form-label small text-muted">To Date</label>
                                        <input type="text" id="to_date" class="form-control datepicker" placeholder="dd-mm-yy">
                                    </div>
                                    <div class="col-12 col-md-auto">
                                        <button id="filter_date_range" class="btn btn-secondary w-100"><i class="bx bx-filter"></i> Filter</button>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Part Number</th>
                                            <th scope="col">Description</th>
                                            <th scope="col" class="text-end">Total Quantity</th>
                                            <th scope="col" class="text-end">Total Weight</th>
                                            <th scope="col" class="text-end">Total Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>';
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $total_quantity += $row['total_quantity'];
                $total_weight += $row['total_weight'];
                $total_value += $row['total_value'];

                $output .= '<tr>
                                <td>' . htmlspecialchars($row['part_number']) . '</td>
                                <td>' . htmlspecialchars($row['part_number_description']) . '</td>
                                <td class="text-end">' . htmlspecialchars($row['total_quantity']) . '</td>
                                <td class="text-end">' . htmlspecialchars($row['total_weight']) . '</td>
                                <td class="text-end">$' . number_format($row['total_value'], 2) . '</td>
                            </tr>';
            }
            $output .= '</tbody>
                        <tfoot>
                            <tr>
                                <th colspan="2" class="text-end">Totals</th>
                                <th class="text-end">' . htmlspecialchars($total_quantity) . '</th>
                                <th class="text-end">' . htmlspecialchars($total_weight) . '</th>
                                <th class="text-end">$' . number_format($total_value, 2) . '</th>
                            </tr>
                        </tfoot>
                        </table>
                        </div>
                        </div>
                        </div>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No records found</p></div></div>';
        }
    } elseif (isset($_POST['customer_report'])) {
        echo '<div class="card"><div class="card-body"><p>Customer report content goes here.</p></div></div>';
    }
    $conn = null;
}
$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['stock_report'])) {
        if (!isset($conn) || !$conn) {
            echo '<div class="card"><div class="card-body"><p>Database connection error</p></div></div>';
            exit;
        }

        try {
            $finished_filter = isset($_POST['finished_filter']) ? $_POST['finished_filter'] : 'all';
            if (!in_array($finished_filter, array('all', '0', '1'), true)) {
                $finished_filter = 'all';
            }

            // Main inventory query
            $inventory_query = "
                SELECT * 
                FROM tblInventory
                WHERE company_id = :company_id AND raw_material = 1
            ";
            if ($finished_filter !== 'all') {
                $inventory_query .= " AND item_finished = :item_finished";
            }
            $inventory_query .= " ORDER BY part_number";
            $inventory_statement = $conn->prepare($inventory_query);
            $inventory_statement->bindParam(':company_id', $_SESSION['session_company_id']);
            if ($finished_filter !== 'all') {
                $inventory_statement->bindValue(':item_finished', (int)$finished_filter, PDO::PARAM_INT);
            }
            $inventory_statement->execute();

            $output = '
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0">Stock Report</h5>
                            <div class="d-flex align-items-center gap-2">
                                <label for="stock_finished_filter" class="form-label mb-0 small text-muted">Finished</label>
                                <select id="stock_finished_filter" class="form-select form-select-sm" onchange="StockReport(this.value)">
                                    <option value="all"' . ($finished_filter === 'all' ? ' selected' : '') . '>All</option>
                                    <option value="1"' . ($finished_filter === '1' ? ' selected' : '') . '>Finished</option>
                                    <option value="0"' . ($finished_filter === '0' ? ' selected' : '') . '>Not Finished</option>
                                </select>
                            </div>
                        </div>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Part Number</th>
                                    <th>Description</th>
                                    <th class="text-center">Finished</th>
                                    <th class="text-end">Mtrs</th>
                                    <th class="text-end">Mtr/Unit</th>
                                    <th class="text-end">Stock</th>
                                    <th>Value</th>
                               
                                </tr>
                            </thead>
                            <tbody>
            ';
            while ($inventory_row = $inventory_statement->fetch(PDO::FETCH_ASSOC)) {
                $finished_tick = !empty($inventory_row['item_finished']) ? '<i class="bi bi-check-circle-fill text-success"></i>' : '';
                $output .= '<tr>
                                <td>' . htmlspecialchars($inventory_row['part_number']) . '</td>
                                <td>' . htmlspecialchars($inventory_row['description']) . '</td>
                                <td class="text-center">' . $finished_tick . '</td>
                                <td class="text-end"></td>
                                <td class="text-end"></td>
                                <td class="text-end"></td>
                                <td></td>
                                <td></td>
                            </tr>';
                $items_query = "
                    SELECT * 
                    FROM tblInventoryItems
                    WHERE inventory_id = :inventory_id
                ";

                $items_statement = $conn->prepare($items_query);
                $items_statement->bindParam(':inventory_id', $inventory_row['id']);
                $items_statement->execute();

                while ($item_row = $items_statement->fetch(PDO::FETCH_ASSOC)) {

                    // --- AMENDED BLOCK START (fix DivisionByZeroError) ---
                    // Safely compute ratio and value even when metre_unit is zero/null
                    $qty = isset($item_row['qty']) ? (float)$item_row['qty'] : 0.0;
                    $metre_unit = isset($inventory_row['metre_unit']) ? (float)$inventory_row['metre_unit'] : 0.0;
                    $buy_rate = isset($inventory_row['buy_rate']) ? (float)$inventory_row['buy_rate'] : 0.0;

                    $ratio = ($metre_unit > 0)
                        ? ($qty / $metre_unit)
                        : 0.0;

                    $value_total = $ratio * $buy_rate;
                    $original_received_qty = isset($item_row['purchased_qty']) ? $item_row['purchased_qty'] : 0;
                    // --- AMENDED BLOCK END ---

                    // Display sub-item row with access to all data
                    $output .= '<tr>
                                    <td class="text-end"></td>
                                    <td class="text-muted">'
                                        . htmlspecialchars($item_row['serial_number'])
                                        . ' (' . htmlspecialchars($item_row['part_number']) . ')</td>
                                    <td class="text-center"></td>
                                    <td class="text-end">' . htmlspecialchars($item_row['qty']) . ' /FIX LTR ' . $original_received_qty . '</td>
                                    <td class="text-end">' . htmlspecialchars($inventory_row['metre_unit']) . '</td>
                                    <td class="text-end">' . (($metre_unit > 0) ? number_format($ratio, 2) : 'N/A') . '</td>
                                    <td class="text-end">$' . number_format($value_total, 2) . '</td>
                                </tr>';
                }
            }
            $output .= '</tbody>
                        </table>
                    </div>
                </div>';

            echo $output;

        } catch (Exception $e) {
            echo '<div class="card"><div class="card-body"><p>Error: ' . $e->getMessage() . '</p></div></div>';
        }
    } elseif (isset($_POST['customer_report'])) {
        echo '<div class="card"><div class="card-body"><p>Customer report content goes here.</p></div></div>';
    } elseif (isset($_POST['closed_coil_report'])) {
        try {
            $database = new Database();
            $conn = $database->connect();

            $query = "
                SELECT 
                    serial_number,
                    part_number,
                    qty,
                    purchased_qty
                FROM tblInventoryItems
                WHERE 
                    company_id = :company_id 
                    AND coil_finished = 1
            ";

            $statement = $conn->prepare($query);
            $statement->bindParam(':company_id', $_SESSION['session_company_id']);
            $statement->execute();

            $output = '
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Closed Coils Report</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Serial Number</th>
                                        <th>Part Number</th>
                                        <th class="text-end">Purchased Qty</th>
                                        <th class="text-end">Remaining Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
            ';

            $hasRows = false;
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $hasRows = true;
                $output .= '
                    <tr>
                        <td>' . htmlspecialchars($row['serial_number']) . '</td>
                        <td>' . htmlspecialchars($row['part_number']) . '</td>
                        <td class="text-end">' . htmlspecialchars($row['purchased_qty']) . '</td>
                        <td class="text-end">' . htmlspecialchars($row['qty']) . '</td>
                    </tr>';
            }

            if (!$hasRows) {
                $output .= '<tr><td colspan="4" class="text-center">No closed coils found.</td></tr>';
            }

            $output .= '
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>';

            echo $output;
        } catch (Exception $e) {
            echo '<div class="card"><div class="card-body"><p>Error: ' . $e->getMessage() . '</p></div></div>';
        } finally {
            $conn = null;
        }
    } elseif (isset($_POST['sales_report'])) {
        try {
            // Only render the UI for Sales Report. CSV will be generated via AJAX to crud.php.
            echo '
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12 col-lg-8 col-xl-6">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Sales Report (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <form id="sales-report-form" class="row g-3">
                                    <div class="col-auto">
                                        <label for="report_date" class="form-label">Select date</label>
                                        <input type="date" class="form-control" id="report_date" name="report_date" value="' . htmlspecialchars(date('Y-m-d')) . '" required>
                                        <div class="form-text">Report covers the 12 months up to this date.</div>
                                    </div>
                                    <div class="col-auto d-flex align-items-end">
                                        <button type="button" class="btn btn-primary" id="btn-generate-csv">Generate CSV</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Columns: Customer, ID, Order #, Date, Item, Qty, Price, Total ex, Status, Invoice Date.</p>
                    </div>
                </div>
            </div>';
        } catch (Exception $e) {
            echo '<div class="card"><div class="card-body"><p>Error: ' . $e->getMessage() . '</p></div></div>';
        } finally {
            // no db use here
        }
    }
    // =========================
    // COIL REPORT RENDER (AMENDED: join tblPurchaseOrders)
    // =========================
    elseif (isset($_POST['coil_report'])) {
        try {
            $database = new Database();
            $conn = $database->connect();

            // Default: last 12 months up to today
            $toTs = strtotime(date('Y-m-d') . ' 23:59:59');
            $fromTs = strtotime('-12 months', $toTs) + 1;

            // Join tblPurchaseInvoice (aliased pi) to tblPurchaseOrders (aliased po) on pid = po.id
            $sql = "
                SELECT
                    pi.pid,
                    pi.bill_date,
                    pi.part_number,
                    pi.serial_number,
                    pi.description,
                    pi.rate,
                    pi.qty,
                    po.vendor_name,
                    po.order_date_required,
                    po.order_receive_date,
                    po.order_receive_ref,
                    po.order_receive_note,
                    po.invoice_date
                FROM tblPurchaseInvoice pi
                LEFT JOIN tblPurchaseOrders po
                  ON po.id = pi.pid
                WHERE pi.company_id = :company_id
                  AND pi.part_number LIKE '%COIL%'
                  AND CAST(pi.bill_date AS UNSIGNED) BETWEEN :from_ts AND :to_ts
                ORDER BY CAST(pi.bill_date AS UNSIGNED) DESC, pi.pid DESC
            ";
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
            $stmt->bindValue(':from_ts', $fromTs, PDO::PARAM_INT);
            $stmt->bindValue(':to_ts', $toTs, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo '
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card mb-3">
                            <div class="card-header">
                                <h5 class="mb-0">Coil Report (Last 12 Months)</h5>
                            </div>
                            <div class="card-body">
                                <form id="coil-report-form" class="row g-3">
                                    <div class="col-auto">
                                        <label for="coil_report_date" class="form-label">Select date</label>
                                        <input type="date" class="form-control" id="coil_report_date" name="coil_report_date" value="' . htmlspecialchars(date('Y-m-d')) . '" required>
                                        <div class="form-text">Report covers the 12 months up to this date.</div>
                                    </div>
                                    <div class="col-auto d-flex align-items-end">
                                        <button type="button" class="btn btn-primary" id="btn-coil-csv">Download CSV</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm">
                                        <thead>
                                            <tr>
                                                <th>PID</th>
                                                <th>Bill Date</th>
                                                <th>Part Number</th>
                                                <th>Serial Number</th>
                                                <th>Description</th>
                                                <th>Vendor</th>
                                                <th>Order Required</th>
                                                <th>Receive Date</th>
                                                <th>Receive Ref</th>
                                                <th>Receive Note</th>
                                                <th>Invoice Date</th>
                                                <th class="text-end">Rate</th>
                                                <th class="text-end">Qty</th>
                                            </tr>
                                        </thead>
                                        <tbody>';

            if (!empty($rows)) {
                foreach ($rows as $r) {
                    $billDateFmt = (ctype_digit((string)$r['bill_date']) && (int)$r['bill_date'] > 0)
                        ? date('d-m-Y', (int)$r['bill_date']) : '';

                    $ordReqFmt = (ctype_digit((string)$r['order_date_required']) && (int)$r['order_date_required'] > 0)
                        ? date('d-m-Y', (int)$r['order_date_required']) : '';

                    $recvDateFmt = (ctype_digit((string)$r['order_receive_date']) && (int)$r['order_receive_date'] > 0)
                        ? date('d-m-Y', (int)$r['order_receive_date']) : '';

                    $invDateFmt = (ctype_digit((string)$r['invoice_date']) && (int)$r['invoice_date'] > 0)
                        ? date('d-m-Y', (int)$r['invoice_date']) : '';

                    echo '<tr>
                            <td>' . htmlspecialchars($r['pid']) . '</td>
                            <td>' . htmlspecialchars($billDateFmt) . '</td>
                            <td>' . htmlspecialchars($r['part_number']) . '</td>
                            <td>' . htmlspecialchars($r['serial_number']) . '</td>
                            <td>' . htmlspecialchars($r['description']) . '</td>
                            <td>' . htmlspecialchars($r['vendor_name']) . '</td>
                            <td>' . htmlspecialchars($ordReqFmt) . '</td>
                            <td>' . htmlspecialchars($recvDateFmt) . '</td>
                            <td>' . htmlspecialchars($r['order_receive_ref']) . '</td>
                            <td>' . htmlspecialchars($r['order_receive_note']) . '</td>
                            <td>' . htmlspecialchars($invDateFmt) . '</td>
                            <td class="text-end">' . htmlspecialchars(number_format((float)$r['rate'], 2, '.', '')) . '</td>
                            <td class="text-end">' . htmlspecialchars(rtrim(rtrim(number_format((float)$r['qty'], 3, '.', ''), '0'), '.')) . '</td>
                          </tr>';
                }
            } else {
                echo '<tr><td colspan="13" class="text-center">No coil records found for the last 12 months.</td></tr>';
            }

            echo '                      </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>';
        } catch (Exception $e) {
            echo '<div class="card"><div class="card-body"><p>Error: ' . $e->getMessage() . '</p></div></div>';
        } finally {
            if (isset($conn)) { $conn = null; }
        }
    }


    $conn = null;
}

?>
