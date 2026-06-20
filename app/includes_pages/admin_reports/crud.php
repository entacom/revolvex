<?php
// /includes_pages/admin_reports/crud.php
// Streams CSV for Sales Report and Coil Report via AJAX (no redirects, no HTML).

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

require_once("../../includes/common.php");
requireLoggedInDownload();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        exit;
    }

    $database = new Database();
    $conn = $database->connect();

    // =======================
    // SALES REPORT CSV BLOCK
    // =======================
    if (isset($_POST['sales_report'])) {
        if (empty($_POST['report_date'])) {
            http_response_code(422);
            exit;
        }

        $selectedDate = $_POST['report_date']; // YYYY-MM-DD
        $toTs = strtotime($selectedDate . ' 23:59:59');
        if ($toTs === false) {
            http_response_code(422);
            exit;
        }
        $fromTs = strtotime('-12 months', $toTs) + 1;

        // CSV headers
        $filename = 'sales_report_' . date('Ymd', $toTs) . '_last12m.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Customer',
            'ID',
            'Order #',
            'Date',
            'Item',
            'Qty',
            'Price',
            'Total ex',
            'Status',
            'Invoice Date'
        ]);

        $sql = "
            SELECT
                o.customer_company                                                     AS customer,
                o.order_id                                                             AS id,
                o.order_number                                                         AS order_no,
                o.order_date                                                           AS order_date_raw,
                oi.part_number                                                         AS item,
                COALESCE(SUM(CAST(sub.qty AS DECIMAL(18,3)) * sub.qty_unit), oi.qty, 0) AS sum_qty,
                invPartAgg.inv_rate                                                    AS inv_rate,
                oi.rate                                                                AS oi_rate,
                COALESCE(s.description, '')                                            AS status_desc,
                invOrderAgg.has_invoice                                                AS has_invoice,
                invOrderAgg.trans_inv_date_raw                                         AS trans_inv_date_raw,
                invOrderAgg.inv_date_raw                                               AS inv_date_raw
            FROM tblOrders o
            INNER JOIN tblOrderItems oi
                ON oi.order_id = o.order_id
            LEFT JOIN tblOrderSubItems sub
                ON sub.order_group_id = oi.id
               AND sub.company_id = o.company_id
            LEFT JOIN (
                SELECT
                    i.order_id,
                    i.part_number,
                    MAX(i.rate) AS inv_rate
                FROM tblInvoice i
                WHERE i.company_id = :company_id
                GROUP BY i.order_id, i.part_number
            ) invPartAgg
                ON invPartAgg.order_id   = o.order_id
               AND invPartAgg.part_number = oi.part_number
            LEFT JOIN (
                SELECT
                    i.order_id,
                    COUNT(*) AS has_invoice,
                    MAX(NULLIF(i.transaction_invoice_date, '')) AS trans_inv_date_raw,
                    MAX(NULLIF(i.invoice_date, ''))             AS inv_date_raw
                FROM tblInvoice i
                WHERE i.company_id = :company_id
                GROUP BY i.order_id
            ) invOrderAgg
                ON invOrderAgg.order_id = o.order_id
            LEFT JOIN tblOrderStatus s
                ON s.id = o.order_status_id
            WHERE o.company_id = :company_id
              AND CAST(o.order_date AS UNSIGNED) BETWEEN :from_ts AND :to_ts
            GROUP BY
                o.customer_company, o.order_id, o.order_number, o.order_date,
                oi.part_number, invPartAgg.inv_rate, oi.rate, status_desc,
                invOrderAgg.has_invoice, invOrderAgg.trans_inv_date_raw, invOrderAgg.inv_date_raw
            ORDER BY CAST(o.order_date AS UNSIGNED) ASC, o.order_id ASC, oi.part_number ASC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
        $stmt->bindValue(':from_ts', $fromTs, PDO::PARAM_INT);
        $stmt->bindValue(':to_ts', $toTs, PDO::PARAM_INT);
        $stmt->execute();

        $safeUnix = function ($raw) {
            if ($raw === null) return null;
            $raw = trim((string)$raw);
            if ($raw === '' || $raw === '0') return null;
            if (ctype_digit($raw)) {
                $n = (int)$raw;
                return $n > 0 ? $n : null;
            }
            return null;
        };
        $safeDateFromAny = function ($raw) {
            if ($raw === null) return null;
            $raw = trim((string)$raw);
            if ($raw === '' || $raw === '0') return null;
            $t = strtotime($raw);
            return $t !== false ? $t : null;
        };

        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $orderTs = $safeUnix($r['order_date_raw']);
            $orderDate = $orderTs !== null ? date('d/m/Y', $orderTs) : '';

            $invoiceDate = '';
            if ((int)$r['has_invoice'] > 0) {
                $invTs = $safeUnix($r['trans_inv_date_raw']);
                if ($invTs === null) {
                    $invTs = $safeDateFromAny($r['inv_date_raw']);
                }
                $invoiceDate = ($invTs !== null) ? date('d/m/Y', $invTs) : 'NA';
            }

            $qtyFloat = (float)$r['sum_qty'];
            $qty = rtrim(rtrim(number_format($qtyFloat, 3, '.', ''), '0'), '.');
            if ($qty === '') { $qty = '0'; }

            $unitRate = ($r['inv_rate'] !== null) ? (float)$r['inv_rate'] : (float)$r['oi_rate'];
            $price = number_format($unitRate, 2, '.', '');

            $totalFloat = $qtyFloat * $unitRate;
            $total = number_format($totalFloat, 2, '.', '');

            $status = $r['status_desc'];

            fputcsv($out, [
                $r['customer'],
                (int)$r['id'],
                $r['order_no'],
                $orderDate,
                $r['item'],
                $qty,
                $price,
                $total,
                $status,
                $invoiceDate
            ]);
        }

        fclose($out);
        if (isset($conn)) { $conn = null; }
        exit;
    }

  // =======================
// COIL REPORT CSV BLOCK (FIRST WORKING VERSION using tblInventoryItems.qty)
// =======================
if (isset($_POST['coil_report'])) {
    if (empty($_POST['report_date'])) {
        http_response_code(422);
        exit;
    }

    $selectedDate = $_POST['report_date']; // YYYY-MM-DD
    $toTs = strtotime($selectedDate . ' 23:59:59');
    if ($toTs === false) {
        http_response_code(422);
        exit;
    }
    $fromTs = strtotime('-12 months', $toTs) + 1;

    $filename = 'coil_report_' . date('Ymd', $toTs) . '_last12m.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');

    // Columns (date formatted as d-m-Y)
    fputcsv($out, [
        'pid',
        'bill_date',
        'part_number',
        'serial_number',
        'description',
        'rate',
        'original_weight_kg',
        'qty_remaining'
    ]);

    $sql = "
        SELECT
            pi.pid,
            pi.bill_date,
            pi.part_number,
            pi.serial_number,
            pi.description,
            pi.rate,
            pi.qty AS original_weight_kg,
            COALESCE(ii.qty, 0) AS qty_remaining
        FROM tblPurchaseInvoice pi
        LEFT JOIN tblInventoryItems ii
          ON ii.serial_number = pi.serial_number
         AND ii.company_id   = pi.company_id
        WHERE pi.company_id = :company_id
          AND pi.part_number LIKE 'COIL%'
          AND CAST(pi.bill_date AS UNSIGNED) BETWEEN :from_ts AND :to_ts
        ORDER BY CAST(pi.bill_date AS UNSIGNED) ASC, pi.pid ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->bindValue(':from_ts', $fromTs, PDO::PARAM_INT);
    $stmt->bindValue(':to_ts', $toTs, PDO::PARAM_INT);
    $stmt->execute();

    // bill_date in tblPurchaseInvoice is varchar(20), usually epoch-string
    $fmtEpochSec = function ($raw) {
        $n = (int)$raw;
        return $n > 0 ? date('d-m-Y', $n) : '';
    };

    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $rate  = number_format((float)$r['rate'], 2, '.', '');

        // original_weight_kg stored as varchar(20) in tblPurchaseInvoice dump
        $origW = (string)$r['original_weight_kg'];
        $origWf = rtrim(rtrim(number_format((float)$origW, 3, '.', ''), '0'), '.');
        if ($origWf === '') { $origWf = '0'; }

        $qtyRem = rtrim(rtrim(number_format((float)$r['qty_remaining'], 3, '.', ''), '0'), '.');
        if ($qtyRem === '') { $qtyRem = '0'; }

        fputcsv($out, [
            $r['pid'],
            $fmtEpochSec($r['bill_date']),
            $r['part_number'],
            $r['serial_number'],
            $r['description'],
            $rate,
            $origWf,
            $qtyRem
        ]);
    }

    fclose($out);
    if (isset($conn)) { $conn = null; }
    exit;
}


    // If no recognized action
    http_response_code(400);
    exit;

} catch (Throwable $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    exit;
}
