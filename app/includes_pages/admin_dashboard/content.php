<? 
session_start();
include("../../includes/common.php");
if (isset($_GET['recent_orders'])) {
    error_reporting(E_ALL);
 ini_set('display_errors', 'Off');
  $database = new Database();
    $conn = $database->connect();
    try {
        $query = "
            SELECT
                o.*,
                os.description AS status_description,
                COALESCE(order_totals.item_count, 0) AS item_count,
                COALESCE(order_totals.sub_total, 0) AS sub_total
            FROM tblOrders o
            LEFT JOIN tblOrderStatus os
                ON os.id = o.order_status_id
                AND os.company_id = o.company_id
            LEFT JOIN (
                SELECT
                    oi.order_id,
                    oi.company_id,
                    COUNT(oi.id) AS item_count,
                    SUM(
                        CASE
                            WHEN oi.has_items = 1 THEN COALESCE(sub_items.sub_qty_total, 0) * COALESCE(oi.rate, 0)
                            ELSE COALESCE(oi.qty, 0) * COALESCE(oi.rate, 0)
                        END
                    ) AS sub_total
                FROM tblOrderItems oi
                LEFT JOIN (
                    SELECT order_group_id, SUM(qty * qty_unit) AS sub_qty_total
                    FROM tblOrderSubItems
                    GROUP BY order_group_id
                ) sub_items
                    ON sub_items.order_group_id = oi.id
                GROUP BY oi.order_id, oi.company_id
            ) order_totals
                ON order_totals.order_id = o.order_id
                AND order_totals.company_id = o.company_id
            WHERE o.company_id = :company_id
            ORDER BY o.order_date DESC, o.order_id DESC
            LIMIT 3
        ";
        $result = $conn->prepare($query);
		$result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        $rowCount = $result->rowCount();
        if ($rowCount > 0) {
            $output = '<style>
                .recent-orders-panel {
                    border: 0;
                    border-radius: 8px;
                    box-shadow: 0 8px 20px rgba(33, 37, 41, 0.08);
                    overflow: hidden;
                }
                .recent-orders-table {
                    margin-bottom: 0;
                }
                .recent-orders-table tr {
                    cursor: pointer;
                    border-left: 4px solid transparent;
                    transition: background-color 0.15s ease, border-color 0.15s ease;
                }
                .recent-orders-table tr:hover {
                    background-color: #f6f8ff;
                    border-left-color: #4154f1;
                }
                .recent-orders-table td {
                    padding: 0.7rem 0.75rem;
                    vertical-align: middle;
                }
                .recent-order-main {
                    color: #263238;
                    font-size: 0.95rem;
                    font-weight: 700;
                }
                .recent-order-meta {
                    color: #6c757d;
                    font-size: 0.76rem;
                    line-height: 1.35;
                    margin-top: 0.18rem;
                }
                .recent-order-label {
                    color: #8a94a6;
                    font-size: 0.68rem;
                    font-weight: 700;
                    letter-spacing: 0.02em;
                    text-transform: uppercase;
                }
                .recent-order-value {
                    color: #263238;
                    font-size: 0.92rem;
                    font-weight: 700;
                    white-space: nowrap;
                }
                .recent-order-status {
                    background: #eef0ff;
                    color: #4154f1;
                    border-radius: 999px;
                    display: inline-block;
                    font-size: 0.72rem;
                    font-weight: 700;
                    padding: 0.18rem 0.55rem;
                    white-space: nowrap;
                }
            </style>
            <div class="card recent-orders-panel">
                <div class="card-body p-0">
                    <table class="table table-hover recent-orders-table">
                        <thead>
                            <tr>
                                <th scope="col">Order</th>
                                <th scope="col">Site</th>
                                <th scope="col">Dates</th>
                                <th scope="col" class="text-end">Value</th>
                            </tr>
                        </thead>
                        <tbody>';
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                if($row['cash_sale']){
                    $customer_company="Cash Sale";
                    }
                    else{
                        $customer_company=$row['customer_company'];
                    }
                $deliveryRate = isset($row['delivery_rate']) ? (float)$row['delivery_rate'] : 0;
                $subTotal = isset($row['sub_total']) ? (float)$row['sub_total'] : 0;
                $orderTotal = ($subTotal + $deliveryRate) * 1.1;
                $orderNumber = !empty($row['order_number']) ? $row['order_number'] : 'Order ' . $row['order_id'];
                $statusDescription = !empty($row['status_description']) ? $row['status_description'] : 'Status ' . $row['order_status_id'];
                $site = trim($row['site_address'] . ', ' . $row['site_suburb'], ' ,');
                $deliveryDate = !empty($row['delivery_date']) ? dashboardOrderDate($row['delivery_date']) : '-';
                $createdDate = !empty($row['order_date']) ? dashboardOrderDate($row['order_date']) : '-';
                $itemCount = (int)$row['item_count'];

                $output .= '<tr onclick="redirectToJob(' . (int)$row['order_id'] . ')">';
                $output .= '<td>
                                <div class="recent-order-main">' . htmlspecialchars($customer_company) . '</div>
                                <div class="recent-order-meta">#' . htmlspecialchars($orderNumber) . ' &middot; ' . htmlspecialchars($itemCount) . ' item' . ($itemCount === 1 ? '' : 's') . '</div>
                                <div class="recent-order-meta"><span class="recent-order-status">' . htmlspecialchars($statusDescription) . '</span></div>
                            </td>';
                $output .= '<td>
                                <div class="recent-order-main">' . htmlspecialchars($site) . '</div>
                                <div class="recent-order-meta">' . htmlspecialchars(isset($row['customer_contact']) ? $row['customer_contact'] : '') . '</div>
                                <div class="recent-order-meta">' . htmlspecialchars(isset($row['customer_phone']) ? $row['customer_phone'] : '') . '</div>
                            </td>';
                $output .= '<td>
                                <div class="recent-order-label">Created</div>
                                <div class="recent-order-meta">' . htmlspecialchars($createdDate) . '</div>
                                <div class="recent-order-label mt-1">Delivery</div>
                                <div class="recent-order-meta">' . htmlspecialchars($deliveryDate) . '</div>
                            </td>';
                $output .= '<td class="text-end">
                                <div class="recent-order-label">Est. total</div>
                                <div class="recent-order-value">' . htmlspecialchars(dashboardOrderMoney($orderTotal)) . '</div>
                                <div class="recent-order-meta">Inc GST</div>
                            </td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table></div></div>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No Orders found</p></div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching users: ' . $e->getMessage() . '</p></div></div>';
    }

}

if (isset($_GET['recent_activity'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT
                    a.id,
                    a.order_id,
                    a.action_date,
                    a.description,
                    a.user_id,
                    o.order_number,
                    o.customer_company,
                    o.customer_contact,
                    o.site_suburb,
                    o.delivery_date,
                    os.description AS order_status
                FROM tblOrderActivity a
                LEFT JOIN tblOrders o
                    ON o.order_id = a.order_id
                    AND o.company_id = a.company_id
                LEFT JOIN tblOrderStatus os
                    ON os.id = o.order_status_id
                    AND os.company_id = a.company_id
                WHERE a.company_id = :company_id
                ORDER BY a.action_date DESC, a.id DESC
                LIMIT 5";
        $result = $conn->prepare($query);
        $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        $activityRows = $result->fetchAll(PDO::FETCH_ASSOC);
        if (!empty($activityRows)) {
            $output = '<div class="card dashboard-activity-card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="card-title mb-0">Recent Activity <span>| Orders</span></h5>
                                    <span class="activity-count-badge">' . count($activityRows) . '</span>
                                </div>
                                <div class="dashboard-activity-list">';
            foreach ($activityRows as $row) {
                $epochTime = (int)$row['action_date'];
                $timeAgo = timeAgo($epochTime);
                $description = (string)$row['description'];
                $activityClass = 'activity-general';
                $activityIcon = 'bi-clock-history';

                if (stripos($description, 'Status changed:') === 0) {
                    $activityClass = 'activity-status';
                    $activityIcon = 'bi-arrow-repeat';
                } elseif (stripos($description, 'Delivery changed:') === 0) {
                    $activityClass = 'activity-delivery';
                    $activityIcon = 'bi-truck';
                } elseif (stripos($description, 'Invoice processed:') === 0) {
                    $activityClass = 'activity-invoice';
                    $activityIcon = 'bi-receipt';
                } elseif (stripos($description, 'Customer/details updated:') === 0) {
                    $activityClass = 'activity-customer';
                    $activityIcon = 'bi-person-lines-fill';
                }

                $customerName = !empty($row['customer_company']) ? $row['customer_company'] : $row['customer_contact'];
                if (empty($customerName)) {
                    $customerName = 'Order ' . $row['order_id'];
                }

                $orderNumber = !empty($row['order_number']) ? $row['order_number'] : '#' . $row['order_id'];
                $siteText = !empty($row['site_suburb']) ? $row['site_suburb'] : 'No site suburb';
                $statusText = !empty($row['order_status']) ? $row['order_status'] : 'No status';
                $deliveryText = !empty($row['delivery_date']) ? dashboardOrderDate($row['delivery_date']) : '-';
                $userName = getUserFullName($row['user_id']);
                $orderUrl = '?p=admin_orders&order_id=' . urlencode($row['order_id']);

                $output .= '<a class="dashboard-activity-item ' . $activityClass . '" href="' . htmlspecialchars($orderUrl) . '">
                                <div class="activity-icon"><i class="bi ' . $activityIcon . '"></i></div>
                                <div class="activity-main">
                                    <div class="activity-topline">
                                        <span class="activity-time">' . htmlspecialchars($timeAgo) . '</span>
                                        <span class="activity-status-pill">' . htmlspecialchars($statusText) . '</span>
                                    </div>
                                    <div class="activity-description">' . htmlspecialchars($description) . '</div>
                                    <div class="activity-order">' . htmlspecialchars($customerName) . ' <span>' . htmlspecialchars($orderNumber) . '</span></div>
                                    <div class="activity-meta">
                                        <span><i class="bi bi-geo-alt"></i> ' . htmlspecialchars($siteText) . '</span>
                                        <span><i class="bi bi-calendar-event"></i> ' . htmlspecialchars($deliveryText) . '</span>
                                        <span><i class="bi bi-person"></i> ' . htmlspecialchars($userName) . '</span>
                                    </div>
                                </div>
                            </a>';
            }
            $output .= '</div></div></div>';
            echo $output;
        } else {
            echo '<div class="card dashboard-activity-card"><div class="card-body"><h5 class="card-title">Recent Activity</h5><p>No recent activities found</p></div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching activities: ' . $e->getMessage() . '</p></div></div>';
    }
}

// Helper functions
function dashboardOrderDate($epochTime) {
    if (empty($epochTime)) {
        return '-';
    }

    return date('d/m/Y', (int)$epochTime);
}

function dashboardOrderMoney($value) {
    return '$' . number_format((float)$value, 2);
}

function timeAgo($epochTime) {
    $timeDiff = time() - $epochTime;
    if ($timeDiff < 1) { return 'just now'; }
    $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    );
    foreach ($tokens as $unit => $text) {
        if ($timeDiff < $unit) continue;
        $numberOfUnits = floor($timeDiff / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
}

function getStatusColor($status) {
    switch ($status) {
        case 'success':
            return 'success';
        case 'danger':
            return 'danger';
        case 'primary':
            return 'primary';
        case 'info':
            return 'info';
        case 'warning':
            return 'warning';
        case 'muted':
            return 'muted';
        default:
            return 'secondary';
    }
}


