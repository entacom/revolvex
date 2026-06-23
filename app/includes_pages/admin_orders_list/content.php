<?php
// ======= BEGIN AMENDED BLOCK: includes_pages/admin_orders_list/content.php =======
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
$database = new Database();
$conn = $database->connect();

function ordersListColumnExists($conn, $columnName) {
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

function ordersListCompletionSummary($conn, $order_id, $company_id, $delivery_date) {
    $summary = array('completed' => 0, 'total' => 0, 'ratio' => '0/0', 'warning' => false);
    if (!ordersListColumnExists($conn, 'item_completed')) {
        return $summary;
    }

    $syncFields = "oi.item_completed = 1";
    if (ordersListColumnExists($conn, 'item_completed_at')) {
        $syncFields .= ", oi.item_completed_at = COALESCE(NULLIF(oi.item_completed_at, 0), :completed_at)";
    }
    if (ordersListColumnExists($conn, 'item_completed_by')) {
        $syncFields .= ", oi.item_completed_by = COALESCE(NULLIF(oi.item_completed_by, 0), :completed_by)";
    }

    $syncStmt = $conn->prepare("
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
        SET {$syncFields}
        WHERE oi.company_id = :sync_company_id
          AND oi.order_id = :sync_order_id
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
    if (ordersListColumnExists($conn, 'item_completed_at')) {
        $syncStmt->bindValue(':completed_at', time(), PDO::PARAM_INT);
    }
    if (ordersListColumnExists($conn, 'item_completed_by')) {
        $syncStmt->bindValue(':completed_by', (int)$_SESSION['session_user_id'], PDO::PARAM_INT);
    }
    $syncStmt->bindValue(':sync_company_id', $company_id, PDO::PARAM_INT);
    $syncStmt->bindValue(':sync_order_id', $order_id, PDO::PARAM_INT);
    $syncStmt->execute();

    $stmt = $conn->prepare("
        SELECT
            COUNT(*) AS total_items,
            COALESCE(SUM(CASE WHEN item_completed = 1 THEN 1 ELSE 0 END), 0) AS completed_items
        FROM tblOrderItems
        WHERE company_id = :company_id
          AND order_id = :order_id
    ");
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    $summary['total'] = isset($row['total_items']) ? (int)$row['total_items'] : 0;
    $summary['completed'] = isset($row['completed_items']) ? (int)$row['completed_items'] : 0;
    $summary['ratio'] = $summary['completed'] . '/' . $summary['total'];
    $summary['warning'] = $summary['total'] > 0
        && $summary['completed'] < $summary['total']
        && (int)$delivery_date > 0
        && (int)$delivery_date <= strtotime('tomorrow 23:59:59');

    return $summary;
}

// Capture tab filter from GET param
$t_filter = isset($_GET['t']) ? (int)$_GET['t'] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'order_list') {
    $currentPage = isset($_POST['current_page']) ? (int)$_POST['current_page'] : 1;
    if ($currentPage < 1) { $currentPage = 1; }
    $recordsPerPage = 20;

    $searchQuery = isset($_POST['search']) ? trim($_POST['search']) : '';
    $orderStatusId = isset($_POST['order_status_id']) ? $_POST['order_status_id'] : '';
    $sortField = isset($_POST['sort_field']) ? $_POST['sort_field'] : '';
    $sortOrder = isset($_POST['sort_order']) ? strtolower($_POST['sort_order']) : 'desc';
    $offset = ($currentPage - 1) * $recordsPerPage;

    // Build search conditions
    $searchSQL = !empty($searchQuery)
        ? " AND (customer_company LIKE :searchQuery
                 OR id LIKE :searchQuery
                 OR order_id LIKE :searchQuery
                 OR customer_phone LIKE :searchQuery
                 OR customer_notes LIKE :searchQuery
                 OR site_address LIKE :searchQuery
                 OR order_number LIKE :searchQuery
                 OR site_email LIKE :searchQuery)"
        : '';

    // Status filter logic
    // When searching, do NOT filter by status at all.
    if (!empty($searchQuery)) {
        $statusSQL = "";
    } else {
        if (!empty($orderStatusId)) {
            $statusSQL = " AND order_status_id = :order_status_id";
        } else {
            if ($t_filter === 1) {
                $statusSQL = " AND order_status_id = 1 AND order_status_id NOT IN (16, 17)";
            } elseif ($t_filter === 2) {
                $statusSQL = " AND order_status_id != 1 AND order_status_id NOT IN (16, 17)";
            } else {
                $statusSQL = " AND order_status_id NOT IN (10, 16, 17)";
            }
        }
    }

    $sortColumns = [
        'created' => 'order_date',
        'delivery' => 'delivery_date'
    ];
    $orderBy = 'order_id DESC';
    if (isset($sortColumns[$sortField])) {
        $sortDirection = ($sortOrder === 'asc') ? 'ASC' : 'DESC';
        $orderBy = $sortColumns[$sortField] . ' ' . $sortDirection . ', order_id DESC';
    }

    try {
        $query = "SELECT * FROM tblOrders
                  WHERE company_id = :company_id
                  {$searchSQL} {$statusSQL}
                  ORDER BY {$orderBy}
                  LIMIT :offset, :recordsPerPage";

        $statement = $conn->prepare($query);
        $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);

        if (!empty($searchQuery)) {
            $likeSearchQuery = "%{$searchQuery}%";
            $statement->bindValue(':searchQuery', $likeSearchQuery, PDO::PARAM_STR);
        }
        // Only bind order_status_id when not searching and a status was provided
        if (empty($searchQuery) && !empty($orderStatusId)) {
            $statement->bindValue(':order_status_id', $orderStatusId, PDO::PARAM_INT);
        }

        $statement->bindValue(':offset', $offset, PDO::PARAM_INT);
        $statement->bindValue(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
        $statement->execute();

        $rowCount = $statement->rowCount();

        // Build only the table + pager. Controls are no longer included here.
        if ($rowCount > 0) {
            $output  = '<style>
                .orders-board {
                    border: 1px solid #e2e8f0;
                    border-radius: 8px;
                    overflow: hidden;
                    background: #fff;
                }
                .orders-board-table {
                    margin-bottom: 0;
                    border-collapse: separate;
                    border-spacing: 0 0.45rem;
                    background: #f6f8fb;
                }
                .orders-board-table thead th {
                    background: #f6f8fb;
                    border: 0;
                    color: #546070;
                    font-size: 0.74rem;
                    font-weight: 800;
                    letter-spacing: 0.02em;
                    padding: 0.65rem 0.8rem;
                    text-transform: uppercase;
                }
                .orders-board-table tbody tr {
                    cursor: pointer;
                    transition: transform 0.15s ease, box-shadow 0.15s ease;
                }
                .orders-board-table tbody tr:hover {
                    transform: translateY(-1px);
                    box-shadow: 0 8px 18px rgba(33, 37, 41, 0.08);
                }
                .orders-board-table tbody td {
                    background: #fff;
                    border-bottom: 1px solid #e7edf5;
                    border-top: 1px solid #e7edf5;
                    padding: 0.8rem;
                    vertical-align: middle;
                }
                .orders-board-table tbody td:first-child {
                    border-left: 4px solid var(--order-accent, #0b3158);
                    border-radius: 8px 0 0 8px;
                }
                .orders-board-table tbody td:last-child {
                    border-right: 1px solid #e7edf5;
                    border-radius: 0 8px 8px 0;
                }
                .order-id-badge {
                    background: #eef4ff;
                    border-radius: 999px;
                    color: #0b3158;
                    display: inline-block;
                    font-size: 0.76rem;
                    font-weight: 800;
                    padding: 0.25rem 0.55rem;
                }
                .order-main {
                    color: #1f2d3d;
                    font-size: 0.95rem;
                    font-weight: 800;
                    line-height: 1.25;
                }
                .order-sub {
                    color: #6c757d;
                    font-size: 0.76rem;
                    line-height: 1.35;
                    margin-top: 0.18rem;
                }
                .order-date-block {
                    color: #1f2d3d;
                    font-size: 0.9rem;
                    font-weight: 700;
                    white-space: nowrap;
                }
                .order-date-label {
                    color: #8a94a6;
                    font-size: 0.68rem;
                    font-weight: 800;
                    text-transform: uppercase;
                }
                .order-status-pill {
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
                .order-status-pill.order-risk,
                .order-date-block.order-risk {
                    color: #dc3545 !important;
                    font-weight: 900;
                }
                .order-status-pill.order-risk {
                    background: #fde8e8;
                    border-color: #f5b5b5;
                }
                .order-completion-pill {
                    background: #f1f5f9;
                    border: 1px solid #dbe3ef;
                    border-radius: 999px;
                    color: #44546a;
                    display: inline-block;
                    font-size: 0.72rem;
                    font-weight: 800;
                    margin-top: 0.35rem;
                    padding: 0.2rem 0.5rem;
                }
                .order-completion-pill.order-risk {
                    background: #fde8e8;
                    border-color: #f5b5b5;
                    color: #dc3545;
                }
                .orders-sort-button {
                    color: #546070 !important;
                    font-size: 0.74rem;
                    font-weight: 800 !important;
                    text-transform: uppercase;
                }
            </style>';
            $output .= '<div class="table-responsive orders-board">';
            $output .= '<table class="table orders-board-table">';
            $output .= '<thead><tr>';
            $output .= '<th scope="col">id#</th>';
            $output .= '<th scope="col">Customer</th>';
            $output .= '<th scope="col">Order#</th>';
            $output .= '<th scope="col"><button type="button" class="btn btn-link p-0 text-decoration-none orders-sort-button" onclick="sortOrdersByDate(\'created\')">Created <i class="bi bi-arrow-down-up"></i></button></th>';
            $output .= '<th scope="col">Delivery</th>';
            $output .= '<th scope="col"><button type="button" class="btn btn-link p-0 text-decoration-none orders-sort-button" onclick="sortOrdersByDate(\'delivery\')">Date <i class="bi bi-arrow-down-up"></i></button></th>';
            $output .= '<th scope="col">Status</th>';
            $output .= '</tr></thead><tbody>';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $customer_contact = $row['cash_sale'] ? "Cash Sale" : $row['customer_contact'];
                $customer_company = $row['cash_sale'] ? "Cash Sale" : $row['customer_company'];
                $statusDescription = getTabFieCol('description','tblOrderStatus', 'id', $row['order_status_id'], $_SESSION['session_company_id']);
                $statusLower = strtolower((string)$statusDescription);
                $statusStyle = '--status-soft:#eef0ff;--status-border:#dfe3ff;--status-color:#4154f1;--order-accent:#4154f1;';
                if (strpos($statusLower, 'quote') !== false) {
                    $statusStyle = '--status-soft:#fff4e8;--status-border:#ffd9af;--status-color:#c65f00;--order-accent:#ff9f43;';
                } elseif (strpos($statusLower, 'order') !== false) {
                    $statusStyle = '--status-soft:#e8f8ef;--status-border:#c7efd6;--status-color:#1f8f4d;--order-accent:#2eca6a;';
                } elseif (strpos($statusLower, 'invoice') !== false) {
                    $statusStyle = '--status-soft:#e8f4ff;--status-border:#bfdefb;--status-color:#0b6fbf;--order-accent:#0d8de3;';
                }
                $site = trim($row['site_address'] . (!empty($row['site_suburb']) ? ', ' . $row['site_suburb'] : ''), ' ,');
                $deliveryNote = !empty($row['delivery_note']) ? $row['delivery_note'] : '';
                $createdDate = !empty($row['order_date']) ? date_c($row['order_date']) : '-';
                $deliveryDate = !empty($row['delivery_date']) ? date_c($row['delivery_date']) : '-';
                $completion = ordersListCompletionSummary($conn, (int)$row['order_id'], (int)$_SESSION['session_company_id'], (int)$row['delivery_date']);
                $riskClass = $completion['warning'] ? ' order-risk' : '';

                $output .= '<tr onclick="redirectToJob(' . (int)$row['order_id'] . ')" style="' . $statusStyle . '">';
                $output .= '<td><span class="order-id-badge">#' . htmlspecialchars($row['order_id']) . '</span></td>';
                $output .= '<td><div class="order-main">' . htmlspecialchars($customer_company) . '</div><div class="order-sub">' . htmlspecialchars($customer_contact) . '</div></td>';
                $output .= '<td><div class="order-main">' . htmlspecialchars($row['order_number']) . '</div><div class="order-sub">' . htmlspecialchars($row['customer_phone']) . '</div></td>';
                $output .= '<td><div class="order-date-label">Created</div><div class="order-date-block">' . htmlspecialchars($createdDate) . '</div></td>';
                $output .= '<td><div class="order-main">' . htmlspecialchars($site) . '</div><div class="order-sub">' . htmlspecialchars($deliveryNote) . '</div></td>';
                $output .= '<td><div class="order-date-label">Delivery</div><div class="order-date-block' . $riskClass . '">' . htmlspecialchars($deliveryDate) . '</div></td>';
                $output .= '<td><span class="order-status-pill' . $riskClass . '">' . htmlspecialchars($statusDescription) . '</span><div><span class="order-completion-pill' . $riskClass . '">Items ' . htmlspecialchars($completion['ratio']) . '</span></div></td>';
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div>';
        } else {
            // No results - keep controls intact (they're on the main page), just show a friendly message.
            $output = '<div class="card"><div class="card-body"><p>No Orders found</p></div></div>';
        }

        // Pager (still appended as before)
        $output .= '<div class="d-flex justify-content-end mt-2">';
        $output .= '<button onclick="goToPreviousPage()" class="btn btn-outline-secondary" ' . ($currentPage <= 1 ? 'disabled' : '') . '><i class="bx bx-chevron-left"></i></button>';
        $output .= '<button onclick="goToNextPage()" class="btn btn-outline-secondary"><i class="bx bx-chevron-right"></i></button>';
        $output .= '</div>';

        echo $output;

    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit;
}
// ======= END AMENDED BLOCK: includes_pages/admin_orders_list/content.php =======
