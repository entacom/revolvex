<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
$database = new Database();
$conn = $database->connect();

function getStatusOptions() {
    global $conn;
    $query = "SELECT id, description FROM tblPurchaseStatus WHERE active = 1";
    $statement = $conn->prepare($query);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'order_list') {
    $currentPage = isset($_POST['current_page']) ? (int)$_POST['current_page'] : 1;
    $recordsPerPage = 20;
    $searchQuery = isset($_POST['search']) ? $_POST['search'] : '';
    $orderStatusId = isset($_POST['order_status_id']) ? $_POST['order_status_id'] : '';
    $sortField = isset($_POST['sort_field']) ? $_POST['sort_field'] : '';
    $sortOrder = isset($_POST['sort_order']) ? strtolower($_POST['sort_order']) : 'desc';
    $offset = ($currentPage - 1) * $recordsPerPage;

    $searchSQL = !empty($searchQuery) ? " AND (vendor_name LIKE :searchQuery 
                                            OR id LIKE :searchQuery 
                                            OR vendor_phone LIKE :searchQuery 
                                            OR order_notes LIKE :searchQuery
                                            OR ven_inv_number LIKE :searchQuery
                                            OR delivery_address_line1 LIKE :searchQuery
                                            OR invoice_ref LIKE :searchQuery
                                            OR order_receive_ref LIKE :searchQuery)" : '';
    $statusSQL = !empty($orderStatusId) ? " AND order_status_id = :order_status_id" : '';
    $sortColumns = array(
        'id' => 'id',
        'date' => 'order_date',
        'required' => 'order_date_required'
    );
    $orderBy = 'order_date DESC, id DESC';
    if (isset($sortColumns[$sortField])) {
        $sortDirection = ($sortOrder === 'asc') ? 'ASC' : 'DESC';
        $orderBy = $sortColumns[$sortField] . ' ' . $sortDirection . ', id DESC';
    }

    try {
        $query = "SELECT * FROM tblPurchaseOrders WHERE company_id = :company_id {$searchSQL} {$statusSQL} ORDER BY {$orderBy} LIMIT :offset, :recordsPerPage";
        $statement = $conn->prepare($query);
        $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);

        if (!empty($searchQuery)) {
            $likeSearchQuery = "%{$searchQuery}%";
            $statement->bindValue(':searchQuery', $likeSearchQuery, PDO::PARAM_STR);
        }
        if (!empty($orderStatusId)) {
            $statement->bindValue(':order_status_id', $orderStatusId, PDO::PARAM_INT);
        }
        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->bindValue(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
        $statement->execute();
        $rowCount = $statement->rowCount();

        $output = '<style>
            .purchase-filter-bar {
                background: #fff;
                border: 1px solid #e1e8f0;
                border-radius: 8px;
                box-shadow: 0 8px 18px rgba(33, 37, 41, 0.05);
                margin-top: 1rem;
                padding: 0.85rem;
            }
            .purchase-filter-bar .form-control,
            .purchase-filter-bar .form-select {
                border-color: #d5dee9;
                font-size: 0.92rem;
            }
            .purchase-primary-action {
                background: #0b3158;
                border-color: #0b3158;
                color: #fff;
            }
            .purchase-primary-action:hover {
                background: #154a80;
                border-color: #154a80;
                color: #fff;
            }
            .purchase-board {
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                overflow: hidden;
                background: #fff;
            }
            .purchase-board-table {
                margin-bottom: 0;
                border-collapse: separate;
                border-spacing: 0 0.45rem;
                background: #f6f8fb;
            }
            .purchase-board-table thead th {
                background: #f6f8fb;
                border: 0;
                color: #546070;
                font-size: 0.74rem;
                font-weight: 800;
                letter-spacing: 0.02em;
                padding: 0.65rem 0.8rem;
                text-transform: uppercase;
            }
            .purchase-board-table tbody tr {
                cursor: pointer;
                transition: transform 0.15s ease, box-shadow 0.15s ease;
            }
            .purchase-board-table tbody tr:hover {
                transform: translateY(-1px);
                box-shadow: 0 8px 18px rgba(33, 37, 41, 0.08);
            }
            .purchase-board-table tbody td {
                background: #fff;
                border-bottom: 1px solid #e7edf5;
                border-top: 1px solid #e7edf5;
                padding: 0.8rem;
                vertical-align: middle;
            }
            .purchase-board-table tbody td:first-child {
                border-left: 4px solid var(--purchase-accent, #0b3158);
                border-radius: 8px 0 0 8px;
            }
            .purchase-board-table tbody td:last-child {
                border-right: 1px solid #e7edf5;
                border-radius: 0 8px 8px 0;
            }
            .purchase-id-badge {
                background: #eef4ff;
                border-radius: 999px;
                color: #0b3158;
                display: inline-block;
                font-size: 0.76rem;
                font-weight: 800;
                padding: 0.25rem 0.55rem;
            }
            .purchase-main {
                color: #1f2d3d;
                font-size: 0.95rem;
                font-weight: 800;
                line-height: 1.25;
            }
            .purchase-sub {
                color: #6c757d;
                font-size: 0.76rem;
                line-height: 1.35;
                margin-top: 0.18rem;
            }
            .purchase-date-label {
                color: #8a94a6;
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
            }
            .purchase-date-block {
                color: #1f2d3d;
                font-size: 0.9rem;
                font-weight: 700;
                white-space: nowrap;
            }
            .purchase-status-pill {
                background: var(--status-soft, #eef0ff);
                border: 1px solid var(--status-border, #dfe3ff);
                border-radius: 999px;
                color: var(--status-color, #4154f1);
                display: inline-block;
                font-size: 0.76rem;
                font-weight: 800;
                padding: 0.28rem 0.65rem;
                white-space: nowrap;
            }
            .purchase-sort-button {
                color: #546070 !important;
                font-size: 0.74rem;
                font-weight: 800 !important;
                text-transform: uppercase;
            }
        </style>
        <div class="purchase-filter-bar">
            <div class="row align-items-center g-2">
                    <div class="col-12 col-md-6 col-lg-5">
                        <input type="text" id="vendorSearch" onkeypress="handleKeyPress(event)" class="form-control" value="' . htmlspecialchars($searchQuery) . '" placeholder="Search by Vendor, Invoice Number, Address, Ref...">
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary" onclick="resetSearch()">Reset</button>
                    </div>
                    <div class="col-auto">
                        <select id="orderStatusFilter" class="form-select" onchange="filterByStatus()">
                            <option value="">Filter</option>';
                            $statuses = getStatusOptions();
                            foreach ($statuses as $status) {
                                $selected = ((string)$status['id'] === (string)$orderStatusId) ? ' selected' : '';
                                $output .= '<option value="' . $status['id'] . '"' . $selected . '>' . htmlspecialchars($status['description']) . '</option>';
                            }
        $output .= '</select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn purchase-primary-action" onclick="redirectTo(\'p=admin_purchasing\')">Add New Purchase</button>
                    </div>
                </div>
            </div>';

        if ($rowCount > 0) {
            $output .= '<div class="table-responsive purchase-board mt-3">
                <table class="table purchase-board-table">
                    <thead>
                        <tr>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-decoration-none purchase-sort-button" onclick="sortPurchasesBy(\'id\')">PO# <i class="bi bi-arrow-down-up"></i></button></th>
                            <th scope="col">Vendor</th>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-decoration-none purchase-sort-button" onclick="sortPurchasesBy(\'date\')">Date <i class="bi bi-arrow-down-up"></i></button></th>
                            <th scope="col"><button type="button" class="btn btn-link p-0 text-decoration-none purchase-sort-button" onclick="sortPurchasesBy(\'required\')">Required <i class="bi bi-arrow-down-up"></i></button></th>
                            <th scope="col">Details</th>
                            <th scope="col">Status</th>
                        </tr>
                    </thead>
                    <tbody>';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $orderDate = !empty($row['order_date']) ? date_c($row['order_date']) : '-';
                $orderRequired = !empty($row['order_date_required']) ? date_c($row['order_date_required']) : '-';
                $statusDescription = getTabFieCol('description','tblPurchaseStatus', 'id', $row['order_status_id'], $_SESSION['session_company_id']);
                $statusLower = strtolower((string)$statusDescription);
                $statusStyle = '--status-soft:#eef0ff;--status-border:#dfe3ff;--status-color:#4154f1;--purchase-accent:#4154f1;';
                if (strpos($statusLower, 'order') !== false) {
                    $statusStyle = '--status-soft:#fff4e8;--status-border:#ffd9af;--status-color:#c65f00;--purchase-accent:#ff9f43;';
                } elseif (strpos($statusLower, 'receive') !== false || strpos($statusLower, 'stock') !== false) {
                    $statusStyle = '--status-soft:#e8f8ef;--status-border:#c7efd6;--status-color:#1f8f4d;--purchase-accent:#2eca6a;';
                } elseif (strpos($statusLower, 'invoice') !== false || strpos($statusLower, 'bill') !== false) {
                    $statusStyle = '--status-soft:#e8f4ff;--status-border:#bfdefb;--status-color:#0b6fbf;--purchase-accent:#0d8de3;';
                }
                $delivery = trim($row['delivery_address_line1'] . (!empty($row['delivery_address_suburb']) ? ', ' . $row['delivery_address_suburb'] : ''), ' ,');
                $reference = !empty($row['ven_inv_number']) ? 'Vendor Inv: ' . $row['ven_inv_number'] : (!empty($row['invoice_ref']) ? 'Invoice Ref: ' . $row['invoice_ref'] : '');

                $output .= '<tr onclick="redirectToPurchase(' . (int)$row['id'] . ')" style="' . $statusStyle . '">';
                $output .= '<td><span class="purchase-id-badge">#' . htmlspecialchars($row['id']) . '</span></td>';
                $output .= '<td><div class="purchase-main">' . htmlspecialchars($row['vendor_name']) . '</div><div class="purchase-sub">' . htmlspecialchars($row['vendor_phone']) . '</div></td>';
                $output .= '<td><div class="purchase-date-label">Created</div><div class="purchase-date-block">' . htmlspecialchars($orderDate) . '</div></td>';
                $output .= '<td><div class="purchase-date-label">Required</div><div class="purchase-date-block">' . htmlspecialchars($orderRequired) . '</div></td>';
                $output .= '<td><div class="purchase-main">' . htmlspecialchars($reference) . '</div><div class="purchase-sub">' . htmlspecialchars($delivery) . '</div></td>';
                $output .= '<td><span class="purchase-status-pill">' . htmlspecialchars($statusDescription) . '</span></td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div>';
        } else {
            $output .= '<div class="card mt-3"><div class="card-body"><p>No purchase orders found</p></div></div>';
        }

        $output .= '<div class="d-flex justify-content-end mt-2">
                        <button onclick="goToPreviousPage()" class="btn btn-outline-secondary" ' . ($currentPage <= 1 ? 'disabled' : '') . '><i class="bx bx-chevron-left"></i></button>
                        <button onclick="goToNextPage()" class="btn btn-outline-secondary"><i class="bx bx-chevron-right"></i></button>
                    </div>';

        echo $output;

    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit;
}
?>
