<?php

function ensureDashboardInvoiceMonthlyTable($conn) {
    $conn->exec("
        CREATE TABLE IF NOT EXISTS tblDashboardInvoiceMonthly (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            company_id INT NOT NULL,
            invoice_month CHAR(7) NOT NULL,
            invoice_count INT NOT NULL DEFAULT 0,
            invoice_value DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            updated_at INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY uq_company_invoice_month (company_id, invoice_month),
            KEY idx_company_month (company_id, invoice_month)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function calculateDashboardInvoiceMonth($conn, $company_id, $monthKey) {
    $monthStart = strtotime($monthKey . '-01');
    $nextMonthStart = strtotime('+1 month', $monthStart);

    $query = "
        SELECT
            COUNT(*) AS invoice_count,
            COALESCE(SUM((invoice_orders.line_total + invoice_orders.delivery_rate) * 1.1), 0) AS invoice_value
        FROM (
            SELECT
                inv.order_id,
                MAX(inv.invoice_date) AS invoice_date,
                SUM(COALESCE(inv.qty, 0) * COALESCE(inv.rate, 0)) AS line_total,
                COALESCE(MAX(o.delivery_rate), 0) AS delivery_rate
            FROM tblInvoice inv
            LEFT JOIN tblOrders o
                ON o.order_id = inv.order_id
                AND o.company_id = inv.company_id
            WHERE inv.company_id = :company_id
                AND inv.invoice_date >= :month_start
                AND inv.invoice_date < :next_month_start
                AND inv.invoice_date > 0
            GROUP BY inv.order_id, inv.company_id
        ) invoice_orders
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':month_start', $monthStart, PDO::PARAM_INT);
    $stmt->bindValue(':next_month_start', $nextMonthStart, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return array(
        'invoice_count' => isset($row['invoice_count']) ? (int)$row['invoice_count'] : 0,
        'invoice_value' => isset($row['invoice_value']) ? round((float)$row['invoice_value'], 2) : 0.0
    );
}

function refreshDashboardInvoiceMonth($conn, $company_id, $monthKey) {
    $stats = calculateDashboardInvoiceMonth($conn, $company_id, $monthKey);

    $query = "
        INSERT INTO tblDashboardInvoiceMonthly
            (company_id, invoice_month, invoice_count, invoice_value, updated_at)
        VALUES
            (:company_id, :invoice_month, :invoice_count, :invoice_value, :updated_at)
        ON DUPLICATE KEY UPDATE
            invoice_count = VALUES(invoice_count),
            invoice_value = VALUES(invoice_value),
            updated_at = VALUES(updated_at)
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    $stmt->bindValue(':invoice_month', $monthKey, PDO::PARAM_STR);
    $stmt->bindValue(':invoice_count', $stats['invoice_count'], PDO::PARAM_INT);
    $stmt->bindValue(':invoice_value', $stats['invoice_value']);
    $stmt->bindValue(':updated_at', time(), PDO::PARAM_INT);
    $stmt->execute();

    return $stats;
}

function getCachedDashboardInvoiceMonths($conn, $company_id, $months) {
    if (empty($months)) {
        return array();
    }

    $placeholders = array();
    foreach ($months as $index => $monthKey) {
        $placeholders[] = ':month_' . $index;
    }

    $query = "
        SELECT invoice_month, invoice_count, invoice_value
        FROM tblDashboardInvoiceMonthly
        WHERE company_id = :company_id
            AND invoice_month IN (" . implode(',', $placeholders) . ")
    ";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':company_id', $company_id, PDO::PARAM_INT);
    foreach ($months as $index => $monthKey) {
        $stmt->bindValue(':month_' . $index, $monthKey, PDO::PARAM_STR);
    }
    $stmt->execute();

    $cached = array();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cached[$row['invoice_month']] = array(
            'invoice_count' => (int)$row['invoice_count'],
            'invoice_value' => round((float)$row['invoice_value'], 2)
        );
    }

    return $cached;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    ini_set('log_errors', 'On');
    ini_set('error_log', '/var/www/html/php_errors.log'); // Ensure this path is correct and writable

    include("../../includes/common.php");

    $data_raw = json_decode(file_get_contents("php://input"), true);

    // Log raw input data for debugging
    error_log("Raw input data: " . print_r($data_raw, true));

    if (isset($data_raw['action']) && $data_raw['action'] == 'read_orders_status') {
        try {
            
            $data = sanInputs($data_raw);
            $database = new Database();
            $conn = $database->connect();


            // Get the current date and date 12 months ago in epoch time
            $current_date = time();
            $date_12_months_ago = strtotime('-12 months', $current_date);

            $query = "SELECT order_date, order_status_id FROM tblOrders WHERE company_id = :company_id AND order_date >= :date_12_months_ago";
            $result = $conn->prepare($query);

            $result->bindParam(':company_id', $_SESSION['session_company_id']);
            $result->bindParam(':date_12_months_ago', $date_12_months_ago);
            $result->execute();

            // Log query execution
            error_log("Query executed");

            // Initialize an array to store the monthly counts
            $monthly_counts = [];
            $status_names = [];

            // Fetch all rows and process them
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $order_date = $row['order_date'];
                error_log("Original order date (epoch): " . $order_date);

                // Convert epoch time to Y-m format
                $month = date("Y-m", $order_date);
                error_log("Converted month: " . $month);

                if (!isset($monthly_counts[$month])) {
                    $monthly_counts[$month] = [];
                }

                $order_status_id = $row['order_status_id'];
                if (!isset($status_names[$order_status_id])) {
                    $status_names[$order_status_id] = getTabFieCol('description', 'tblOrderStatus', 'id', $order_status_id, $_SESSION['session_company_id']);
                }
                $order_status_name = $status_names[$order_status_id];

                if (isset($monthly_counts[$month][$order_status_name])) {
                    $monthly_counts[$month][$order_status_name]++;
                } else {
                    $monthly_counts[$month][$order_status_name] = 1;
                }
            }

            // Prepare data for JSON output
            $categories = array_keys($monthly_counts);
            $series_data = [];

            // Initialize series data with status names
            foreach ($status_names as $status_name) {
                $series_data[$status_name] = [];
            }

            foreach ($monthly_counts as $month => $counts) {
                foreach ($status_names as $status_name) {
                    $series_data[$status_name][] = $counts[$status_name] ?? 0;
                }
            }


            $response = [
                'categories' => $categories,
                'series' => $series_data,
                'status_names' => array_values($status_names)
            ];

            header('Content-Type: application/json');
            echo json_encode($response);
            error_log("Response sent: " . json_encode($response));
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    } 
    if (isset($data_raw['action']) && $data_raw['action'] == 'read_invoice_performance') {
        try {
            $database = new Database();
            $conn = $database->connect();
            $companyId = (int)$_SESSION['session_company_id'];

            ensureDashboardInvoiceMonthlyTable($conn);

            $currentMonthStart = strtotime(date('Y-m-01'));
            $date12MonthsAgo = strtotime('-11 months', $currentMonthStart);
            $currentMonthKey = date('Y-m');

            $months = [];
            $closedMonths = [];
            $invoiceCounts = [];
            $invoiceValues = [];
            for ($i = 0; $i < 12; $i++) {
                $monthKey = date('Y-m', strtotime('+' . $i . ' months', $date12MonthsAgo));
                $months[] = $monthKey;
                $invoiceCounts[$monthKey] = 0;
                $invoiceValues[$monthKey] = 0.0;
                if ($monthKey !== $currentMonthKey) {
                    $closedMonths[] = $monthKey;
                }
            }

            $cachedMonths = getCachedDashboardInvoiceMonths($conn, $companyId, $closedMonths);

            foreach ($closedMonths as $monthKey) {
                if (!isset($cachedMonths[$monthKey])) {
                    $cachedMonths[$monthKey] = refreshDashboardInvoiceMonth($conn, $companyId, $monthKey);
                }

                if (isset($cachedMonths[$monthKey])) {
                    $invoiceCounts[$monthKey] = (int)$cachedMonths[$monthKey]['invoice_count'];
                    $invoiceValues[$monthKey] = round((float)$cachedMonths[$monthKey]['invoice_value'], 2);
                }
            }

            $currentMonthStats = calculateDashboardInvoiceMonth($conn, $companyId, $currentMonthKey);
            if (isset($invoiceCounts[$currentMonthKey])) {
                $invoiceCounts[$currentMonthKey] = $currentMonthStats['invoice_count'];
                $invoiceValues[$currentMonthKey] = $currentMonthStats['invoice_value'];
            }

            $totalInvoices = array_sum($invoiceCounts);
            $totalValue = array_sum($invoiceValues);
            $currentMonthValue = isset($invoiceValues[$currentMonthKey]) ? $invoiceValues[$currentMonthKey] : 0;
            $currentMonthCount = isset($invoiceCounts[$currentMonthKey]) ? $invoiceCounts[$currentMonthKey] : 0;
            $averageInvoiceValue = $totalInvoices > 0 ? round($totalValue / $totalInvoices, 2) : 0;

            header('Content-Type: application/json');
            echo json_encode([
                'categories' => array_map(function ($monthKey) {
                    return date('M Y', strtotime($monthKey . '-01'));
                }, $months),
                'invoice_values' => array_values($invoiceValues),
                'invoice_counts' => array_values($invoiceCounts),
                'summary' => [
                    'total_invoices' => $totalInvoices,
                    'total_value' => round($totalValue, 2),
                    'current_month_value' => round($currentMonthValue, 2),
                    'current_month_count' => $currentMonthCount,
                    'average_invoice_value' => $averageInvoiceValue
                ]
            ]);
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
    if (isset($data_raw['action']) && $data_raw['action'] == 'read_orders_source') {
        try {
            $data = sanInputs($data_raw);
            $database = new Database();
            $conn = $database->connect();

          
            // Get the current date and date 12 months ago in epoch time
            $current_date = time();
            $date_12_months_ago = strtotime('-12 months', $current_date);

            $query = "SELECT client_source_id, COUNT(*) as source_count FROM tblOrders WHERE company_id = :company_id AND order_date >= :date_12_months_ago GROUP BY client_source_id";
            $result = $conn->prepare($query);

            $result->bindParam(':company_id', $_SESSION['session_company_id']);
            $result->bindParam(':date_12_months_ago', $date_12_months_ago);
            $result->execute();

            error_log("Query executed");
            $source_counts = [];
            $source_names = [];

            // Fetch all rows and process them
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $client_source_id = $row['client_source_id'];
                $source_count = $row['source_count'];
                error_log("Client source ID: " . $client_source_id . ", Count: " . $source_count);

                if (!isset($source_names[$client_source_id])) {
                    $source_names[$client_source_id] = getTabFieCol('description', 'tblClientSource', 'id', $client_source_id, $_SESSION['session_company_id']);
                }
                $client_source_name = $source_names[$client_source_id];
                error_log("Client source name: " . $client_source_name);

                $source_counts[] = [
                    'name' => $client_source_name,
                    'value' => $source_count
                ];
            }


            $response = [
                'source_counts' => $source_counts
            ];

            header('Content-Type: application/json');
            echo json_encode($response);
            error_log("Response sent: " . json_encode($response));
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}

?>
