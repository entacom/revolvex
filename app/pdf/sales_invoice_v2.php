<?php
session_start();
error_reporting(E_ALL & ~E_WARNING);
require_once('../assets/vendor/tcpdf/tcpdf.php');
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");

// ======================= AMENDED: Xero-style precision & rounding =======================
// Xero-style precision & rounding (per docs):
// - Qty & Unit price: 4 dp BEFORE multiplication
// - Line exclusive amount: 2 dp
// - Tax per line: round(line_ex * rate, 2) then SUM
// Ref: Xero Developer – Rounding in Xero
const XERO_QTY_DP   = 4;   // AMENDED
const XERO_UNIT_DP  = 4;   // AMENDED
const XERO_LINE_DP  = 2;   // AMENDED
const XERO_TAX_DP   = 2;   // AMENDED
const GST_RATE      = 0.10; // AMENDED AU GST 10%

// Toggle to mirror Xero tax code for Delivery (true by default if not provided) // AMENDED
$delivery_is_taxable = isset($order_delivery_is_taxable) ? (bool)$order_delivery_is_taxable : true; // AMENDED

// ---------- Display helpers (NEVER use formatted values in maths) ---------- // AMENDED
function fmt_money_2($v) { return '$' . number_format((float)$v, 2); } // AMENDED
function fmt_qty($v)    { return number_format((float)$v, 3); }       // AMENDED display only
function fmt_rate($v)   { // show up to 4 dp, trim zeros                // AMENDED
    $s = number_format((float)$v, 4);
    $s = rtrim(rtrim($s, '0'), '.');
    return '$' . $s;
}

// ---------- Safe rounding helpers (explicit HALF_UP) ---------- // AMENDED
function r4($v){ return round((float)$v, 4, PHP_ROUND_HALF_UP); } // AMENDED
function r2($v){ return round((float)$v, 2, PHP_ROUND_HALF_UP); } // AMENDED
// ========================================================================================

// ======================= AMENDED: ensure $id is set safely =======================
$id = isset($id) ? (int)$id : (int)($_GET['order_id'] ?? 0); // AMENDED

// Extend the TCPDF class to create custom Header and Footer
class MYPDF extends TCPDF {
    protected $order_number;
    protected $invoice_due_date;
    protected $invoice_date;
    protected $order_user;
    protected $header_displayed = false; // Property to track header display

    public function __construct($order_number, $invoice_due_date, $invoice_date, $order_user) {
        parent::__construct();
        $this->order_number = $order_number;
        $this->invoice_date = $invoice_date;
        $this->invoice_due_date = $invoice_due_date;
        $this->order_user = $order_user;
    }

    public function Header() {
        if (!$this->header_displayed) { // Only display the header on the first page
            $logo = '../' . getTableColField('company_image_path', 'tblCompany', 'id', $_SESSION['session_company_id']);
            $image_file = $logo;
            $this->Image($image_file, 10, 10, 80, '', 'jpg', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 10, 'TAX INVOICE', 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 25, 'Invoice# ' . ($_GET['order_id'] ?? ''), 0, false, 'R', 0, '', 0, false, 'T', 'M'); // AMENDED safe
            $this->Cell(0, 35, 'Customer Order# ' . $this->order_number, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 45, 'Invoice Date: ' . date('d-m-Y', $this->invoice_date), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 55, 'Due Date: ' . date('d-m-Y', $this->invoice_due_date), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Cell(0, 65, 'Contact: ' . $this->order_user, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->header_displayed = true; // Mark header as displayed
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// ======================= NOTE: keep constructor call arity same as class =======================
// (Do not pass extra TCPDF args; your custom __construct only takes 4) // AMENDED
$pdf = new MYPDF($order_number, $invoice_due_date, $invoice_date, $order_user); // AMENDED

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ACP');
$pdf->SetTitle('Print');
$pdf->SetSubject('TCPDF');
$pdf->SetKeywords('');

// set default header data
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP - 280, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font
$pdf->SetFont('times', '', 12);
$pdf->Ln();
// add a page
$pdf->AddPage('P');

$pdf->SetTopMargin(5);
$tbl_header = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer = '</table>';
$tbl = '';

$tbl .= '
    <tr>
        <td style="width: 300px;"><b>Billing Address</b></td>
        <td style="width: 300px;"><b>Delivery Address</b></td>
    </tr>';

$tbl .= '
    <tr>
        <td style="width: 300px;">' . $customer . '</td>
        <td style="width: 300px;">' . $customer . '</td>
    </tr>
    <tr>
        <td style="width: 300px;">' . $customer_address . '</td>
        <td style="width: 300px;">' . $site_address . '</td>
    </tr>
    <tr>
        <td style="width: 300px;">' . $customer_suburb . ' ' . $customer_postcode . ' ' . $customer_state . '</td>
        <td style="width: 300px;">' . $site_suburb .'</td>
    </tr>
    <tr>
        <td style="width: 300px;">' . $customer_phone . '</td>
        <td style="width: 300px;">' . $order_delivery_note . '</td>
    </tr>
';
$pdf->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false, false, '');

$pdf->writeHTML('Notes: ', 1, 0, 0, 0, "L");
$pdf->Ln(10);

$pdf->SetFont('times', '', 11);
// Print text using writeHTMLCell()
$tbl_header1 = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer1 = '</table>';

$tbl1 = '<tr>
        <td style="border: 1px solid #737373; width: 90px;" color="#000" bgcolor="#ccc">ITEM NO</td>
        <td style="border: 1px solid #737373; width: 220px;" color="#000" bgcolor="#ccc">DESCRIPTION</td>
        <td style="border: 1px solid #737373; width: 80px;" color="#000" bgcolor="#ccc">QTY</td>
        <td style="border: 1px solid #737373; width: 80px;" color="#000" bgcolor="#ccc">Unit Price (ex)</td> <!-- AMENDED label -->
        <td style="border: 1px solid #737373; width: 70px;" color="#000" bgcolor="#ccc">UNIT</td>
        <td style="border: 1px solid #737373; width: 100px;" color="#000" bgcolor="#ccc">EX AMOUNT</td>
    </tr>';

$database = new Database();
$conn = $database->connect();
$query = "SELECT * FROM tblOrderItems WHERE order_id = :order_id";
$stmt = $conn->prepare($query);
$stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
$stmt->execute();

// ======================= AMENDED: Xero-style maths accumulators =======================
$sub_total   = 0.00; // sum of per-line exclusive (rounded 2dp) // AMENDED
$gst_total   = 0.00; // sum of per-line GST (rounded 2dp)       // AMENDED
$delivery_ex = isset($order_delivery_rate) ? (float)$order_delivery_rate : 0.00; // AMENDED
$delivery_ex = r2($delivery_ex); // AMENDED cap delivery entry to 2dp

if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unit     = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
        $rate_raw = (float)$row['rate'];
        $rate4    = r4($rate_raw); // AMENDED: cap rate to 4 dp before multiplication


        if ((int)$row['has_items'] === 1) { // AMENDED: parent display line
            $aggQtyRaw = (float)getSumItemLength($id, $_SESSION['session_company_id'], $row['part_number']); // AMENDED
            $aggQty4   = r4($aggQtyRaw); // AMENDED

            $tbl1 .= '
                <tr>
                    <td style="border: 1px solid #737373;">' . $row['part_number'] . '</td>
                    <td style="border: 1px solid #737373;">' . $row['description'] . '</td>
                    <td style="border: 1px solid #737373;">' . fmt_qty($aggQty4) . '</td> <!-- AMENDED -->
                    <td style="border: 1px solid #737373;" align="right">' . fmt_rate($rate4) . '</td> <!-- AMENDED -->
                    <td style="border: 1px solid #737373;">' . $unit . '</td>
                    <td style="border: 1px solid #737373;" align="right"></td>
                </tr>';

            // AMENDED: sub-items are real money lines using Xero rounding
            $query2 = "SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
            $stmt2->execute();

            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $q_raw   = (float)$row2['qty'] * (float)$row2['qty_unit']; // raw
                $q4      = r4($q_raw);                                     // 4dp qty
                $line_ex = r2($q4 * $rate4);                               // 2dp exclusive
                $line_gst= r2($line_ex * GST_RATE);                        // 2dp gst per-line

                $sub_total += $line_ex; // AMENDED
                $gst_total += $line_gst; // AMENDED

                $tbl1 .= '
                    <tr>
                        <td style="border: 1px solid #737373;"></td>
                        <td colspan="3" style="border: 1px solid #737373;">' . $row2['mark'] . ' ' . $row2['qty'] . ' x ' . $row2['qty_unit'] . '</td>
                        <td style="border: 1px solid #737373;" align="right"></td>
                        <td style="border: 1px solid #737373;" align="right">' . fmt_money_2($line_ex) . '</td> <!-- AMENDED -->
                    </tr>';
            }
        } else {
            // AMENDED: simple line using Xero rounding
            $qty4    = r4((float)$row['qty']);           // 4dp
            $line_ex = r2($qty4 * $rate4);               // 2dp exclusive
            $line_gst= r2($line_ex * GST_RATE);          // 2dp gst per-line

            $sub_total += $line_ex; // AMENDED
            $gst_total += $line_gst; // AMENDED

            $tbl1 .= '
                <tr>
                    <td style="border: 1px solid #737373;">' . $row['part_number'] . '</td>
                    <td style="border: 1px solid #737373;">' . $row['description'] . '</td>
                    <td style="border: 1px solid #737373;">' . fmt_qty($qty4) . '</td> <!-- AMENDED -->
                    <td style="border: 1px solid #737373;" align="right">' . fmt_rate($rate4) . '</td> <!-- AMENDED -->
                    <td style="border: 1px solid #737373;">' . $unit . '</td>
                    <td style="border: 1px solid #737373;" align="right">' . fmt_money_2($line_ex) . '</td> <!-- AMENDED -->
                </tr>';
        }
    }

    // ======================= AMENDED: Delivery as a proper line (matches Xero) =======================
    if ($delivery_ex != 0.00) { // AMENDED
        $delivery_qty4  = 1.0000;                // AMENDED
        $delivery_rate4 = r4($delivery_ex);      // AMENDED treat as unit price for 1 unit
        $delivery_line  = r2($delivery_qty4 * $delivery_rate4); // AMENDED
        $delivery_gst   = $delivery_is_taxable ? r2($delivery_line * GST_RATE) : 0.00; // AMENDED

        $sub_total += $delivery_line; // AMENDED
        $gst_total += $delivery_gst;  // AMENDED

        $tbl1 .= '
            <tr>
                <td style="border: 1px solid #737373;">DELIVERY</td>
                <td style="border: 1px solid #737373;">Delivery</td>
                <td style="border: 1px solid #737373;">' . fmt_qty($delivery_qty4) . '</td> <!-- AMENDED -->
                <td style="border: 1px solid #737373;" align="right">' . fmt_rate($delivery_rate4) . '</td> <!-- AMENDED -->
                <td style="border: 1px solid #737373;">ea</td>
                <td style="border: 1px solid #737373;" align="right">' . fmt_money_2($delivery_line) . '</td> <!-- AMENDED -->
            </tr>';
    }

    // ======================= AMENDED: Totals (GST is sum of per-line, no extra delivery row) =======================
    $total_inc = r2($sub_total + $gst_total); // AMENDED

    $tbl1 .= '
        <tr>
            <td></td><td></td><td></td><td></td>
            <td style="border: 1px solid #000000;" align="right">Subtotal</td>
            <td style="border: 1px solid #000000;" align="right">' . fmt_money_2($sub_total) . '</td>
        </tr>
        <tr>
            <td></td><td></td><td></td><td></td>
            <td style="border: 1px solid #000000;" align="right">GST</td>
            <td style="border: 1px solid #000000;" align="right">' . fmt_money_2($gst_total) . '</td>
        </tr>
        <tr>
            <td></td><td></td><td></td><td></td>
            <td style="border: 1px solid #000000;" align="right">Total</td>
            <td style="border: 1px solid #000000;" align="right">' . fmt_money_2($total_inc) . '</td>
        </tr>';
} else {
    $tbl1 .= '
        <tr>
            <td colspan="6" style="border: 1px solid #737373; text-align: center;">No rows available</td>
        </tr>';
}

$tbl_header2 = '<table style="width: 638px; border: 1px solid #737373;" cellspacing="0" cellpadding="2">';
$tbl_footer2 = '</table>';
$tbl2 = '
<tr>
    <td style="width: 638px;" color="#000" bgcolor="#ccc">HOW TO PAY</td>
</tr>
<tr>
    <td style="width: 100px;"><b>EFT</b></td>
    <td style="width: 200px;"></td>
    <td style="width: 300px; border-left: 1px solid #737373;"><b>POST:</b> Cheques to PO BOX 14 Claremont 7011</td>
</tr>
<tr>
    <td style="width: 100px;">Account Name:</td>
    <td style="width: 200px;">' . $bank_account_name . '</td>
    <td style="width: 300px; border-left: 1px solid #737373;"><b>CREDIT CARD:</b> Phone us on: ' . formatPhoneNumber($company_phone, 'l') . '</td>
</tr>
<tr>
    <td style="width: 100px;">BSB#:</td>
    <td style="width: 200px;">' . $bank_bsb . '</td>
    <td style="width: 300px; border-left: 1px solid #737373;"><b>IN PERSON:</b> ' . $company_address . ' ' . $company_suburb . '</td>
</tr>
<tr>
    <td style="width: 100px;">ACCOUNT#:</td>
    <td style="width: 200px;">' . $bank_account . '</td>
    <td style="width: 300px; border-left: 1px solid #737373;"></td>
</tr>
<tr>
    <td style="width: 100px;">REFERENCE:</td>
    <td style="width: 200px;">' . ($_GET['order_id'] ?? '') . '</td> <!-- AMENDED safe -->
    <td style="width: 300px; border-left: 1px solid #737373;"></td>
</tr>';

$pdf->writeHTML($tbl_header1 . $tbl1 . $tbl_footer1, true, false, false, false, '');
$pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');

// Close and output PDF document
$files_dir = $_SERVER['DOCUMENT_ROOT'] . "/files";
if (!is_dir($files_dir)) {
    mkdir($files_dir, 0755, true);
}
$save_path = $_SERVER['DOCUMENT_ROOT'] . "/files/sales_invoice_v2{$id}.pdf";
$pdf->Output($save_path, 'F');

?>
