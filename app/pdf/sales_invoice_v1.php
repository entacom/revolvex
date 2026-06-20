<?php
session_start();
error_reporting(E_ALL & ~E_WARNING);

require_once('../assets/vendor/tcpdf/tcpdf.php');
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");

/**
 * Xero-style precision & rounding (per docs):
 * - Qty & Unit price: 4 dp BEFORE multiplication
 * - Line exclusive amount: 2 dp
 * - Tax per line: round(line_ex * rate, 2) then SUM
 * Ref: Xero Developer – Rounding in Xero
 */

const XERO_QTY_DP   = 4;
const XERO_UNIT_DP  = 4;
const XERO_LINE_DP  = 2;
const XERO_TAX_DP   = 2;
const GST_RATE      = 0.10; // AU GST 10%

// Toggle this to mirror your Xero tax code for Delivery:
$delivery_is_taxable = isset($order_delivery_is_taxable) ? (bool)$order_delivery_is_taxable : true;

// ---------- Display helpers (NEVER use formatted values in maths) ----------
function fmt_money_2($v) { return '$' . number_format((float)$v, 2); }
function fmt_qty($v)    { return number_format((float)$v, 3); } // display only
function fmt_rate($v)   { // show up to 4 dp, trim zeros
    $s = number_format((float)$v, 4);
    $s = rtrim(rtrim($s, '0'), '.');
    return '$' . $s;
}

// ---------- Safe rounding helpers (explicit HALF_UP) ----------
function r4($v){ return round((float)$v, 4, PHP_ROUND_HALF_UP); }
function r2($v){ return round((float)$v, 2, PHP_ROUND_HALF_UP); }

// ---------- TCPDF subclass ----------
class MYPDF extends TCPDF {
    protected $order_number;
    protected $invoice_due_date;
    protected $invoice_date;
    protected $order_user;
    protected $header_displayed = false;

    public function __construct($order_number, $invoice_due_date, $invoice_date, $order_user,
                                $orientation = PDF_PAGE_ORIENTATION, $unit = PDF_UNIT, $format = PDF_PAGE_FORMAT,
                                $unicode = true, $encoding = 'UTF-8', $diskcache = false) {
        parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache);
        $this->order_number     = $order_number;
        $this->invoice_date     = $invoice_date;
        $this->invoice_due_date = $invoice_due_date;
        $this->order_user       = $order_user;
    }

    public function Header() {
        if (!$this->header_displayed) {
            $logo = '../' . getTableColField('company_image_path', 'tblCompany', 'id', $_SESSION['session_company_id']);
            if (is_file($logo)) $this->Image($logo, 10, 10, 80, '', '', '', 'T', false, 300);

            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 10, 'TAX INVOICE', 0, 1, 'R');

            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 8, 'Invoice# ' . ($_GET['order_id'] ?? ''), 0, 1, 'R');
            $this->Cell(0, 8, 'Customer Order# ' . $this->order_number, 0, 1, 'R');
            $this->Cell(0, 8, 'Invoice Date: ' . date('d-m-Y', $this->invoice_date), 0, 1, 'R');
            $this->Cell(0, 8, 'Due Date: ' . date('d-m-Y', $this->invoice_due_date), 0, 1, 'R');
            $this->Cell(0, 8, 'Contact: ' . $this->order_user, 0, 1, 'R');

            $this->header_displayed = true;
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, 0, 'C');
    }
}

// ---------- Bootstrap PDF ----------
$id = isset($id) ? (int)$id : (int)($_GET['order_id'] ?? 0);

$pdf = new MYPDF(
    $order_number, $invoice_due_date, $invoice_date, $order_user,
    PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false
);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ACP');
$pdf->SetTitle('Print');
$pdf->SetSubject('TCPDF');

// sane margins (avoid header overlap)
$pdf->SetMargins(10, 48, 10);
$pdf->SetHeaderMargin(8);
$pdf->SetFooterMargin(14);
$pdf->SetAutoPageBreak(true, 22);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

$pdf->SetFont('times', '', 12);
$pdf->AddPage('P');

// ---------- Header columns ----------
$tbl_header = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer = '</table>';
$tbl = '
<tr>
  <td style="width: 320px;"><b>Customer</b></td>
  <td style="width: 318px;"><b></b></td>
</tr>
<tr>
  <td style="width: 320px;">'. $customer .'</td>
  <td style="width: 318px;">'. $company .'</td>
</tr>
<tr>
  <td style="width: 320px;">'. $customer_address .'</td>
  <td style="width: 318px;">'. $company_address .'</td>
</tr>
<tr>
  <td style="width: 320px;">'. $customer_suburb .', '. $customer_postcode .' '. $customer_state .'</td>
  <td style="width: 318px;">'. $company_suburb .', '. $company_state .' '. $company_postcode .'</td>
</tr>
<tr>
  <td style="width: 320px;">Ph '. formatPhoneNumber($customer_phone, 'm') .'</td>
  <td style="width: 318px;">'. formatPhoneNumber($company_phone, 'l') .'</td>
</tr>';
$pdf->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false, false, '');
$pdf->Ln(2);
$pdf->writeHTML('<b>Notes:</b>', true, false, false, false, '');
$pdf->Ln(2);

// ---------- Items table ----------
$pdf->SetFont('times', '', 11);

$tbl_header1 = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer1 = '</table>';

$tbl1 = '
<tr>
  <td style="border:1px solid #737373;width:90px;"  bgcolor="#ccc">ITEM NO</td>
  <td style="border:1px solid #737373;width:220px;" bgcolor="#ccc">DESCRIPTION</td>
  <td style="border:1px solid #737373;width:80px;"  bgcolor="#ccc">QTY</td>
  <td style="border:1px solid #737373;width:80px;"  bgcolor="#ccc">Unit Price (ex)</td>
  <td style="border:1px solid #737373;width:70px;"  bgcolor="#ccc">UNIT</td>
  <td style="border:1px solid #737373;width:100px;" bgcolor="#ccc">EX AMOUNT</td>
</tr>';

// ---------- Data + Xero-style maths ----------
$database = new Database();
$conn = $database->connect();

$stmt  = $conn->prepare("SELECT * FROM tblInvoice WHERE order_id = :order_id");
$stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
$stmt->execute();

$sub_total    = 0.00;  // sum of per-line exclusive (rounded 2dp)
$gst_total    = 0.00;  // sum of per-line GST (rounded 2dp)
$delivery_ex  = isset($order_delivery_rate) ? (float)$order_delivery_rate : 0.00;
$delivery_ex  = r2($delivery_ex);

if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unit     = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
        $rate_raw = (float)$row['rate'];
        $rate4    = r4($rate_raw); // Xero: cap to 4 dp before mult

        if ((int)$row['has_items'] === 1) {
            // Parent display line (amounts will come from sub-items)
            $aggQtyRaw = (float)getSumItemLength($id, $_SESSION['session_company_id'], $row['part_number']);
            $aggQty4   = r4($aggQtyRaw);
            $tbl1 .= '
            <tr>
              <td style="border:1px solid #737373;">'. $row['part_number'] .'</td>
              <td style="border:1px solid #737373;">'. $row['description'] .'</td>
              <td style="border:1px solid #737373;">'. fmt_qty($aggQty4) .'</td>
              <td style="border:1px solid #737373;" align="right">'. fmt_rate($rate4) .'</td>
              <td style="border:1px solid #737373;">'. $unit .'</td>
              <td style="border:1px solid #737373;" align="right"></td>
            </tr>';

            // Sub-items are real money lines
            $stmt2 = $conn->prepare("SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id");
            $stmt2->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
            $stmt2->execute();

            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $q = r4((float)$row2['qty'] * (float)$row2['qty_unit']); // 4dp
                $line_ex = r2($q * $rate4);                  // 2dp exclusive
                $line_gst = r2($line_ex * GST_RATE);         // per-line GST 2dp

                $sub_total += $line_ex;
                $gst_total += $line_gst;

                $tbl1 .= '
                <tr>
                  <td style="border:1px solid #737373;"></td>
                  <td colspan="3" style="border:1px solid #737373;">'. $row2['mark'] .' '. $row2['qty'] .' x '. $row2['qty_unit'] .'</td>
                  <td style="border:1px solid #737373;" align="right"></td>
                  <td style="border:1px solid #737373;" align="right">'. fmt_money_2($line_ex) .'</td>
                </tr>';
            }
        } else {
            // Simple line
            $qty4    = r4((float)$row['qty']);            // 4dp
            $line_ex = r2($qty4 * $rate4);                // 2dp
            $line_gst= r2($line_ex * GST_RATE);           // 2dp

            $sub_total += $line_ex;
            $gst_total += $line_gst;

            $tbl1 .= '
            <tr>
              <td style="border:1px solid #737373;">'. $row['part_number'] .'</td>
              <td style="border:1px solid #737373;">'. $row['description'] .'</td>
              <td style="border:1px solid #737373;">'. fmt_qty($qty4) .'</td>
              <td style="border:1px solid #737373;" align="right">'. fmt_rate($rate4) .'</td>
              <td style="border:1px solid #737373;">'. $unit .'</td>
              <td style="border:1px solid #737373;" align="right">'. fmt_money_2($line_ex) .'</td>
            </tr>';
        }
    }

    // Delivery as a proper line (so its tax behaviour matches Xero’s)
    if ($delivery_ex != 0.00) {
        $delivery_qty4 = 1.0000;
        $delivery_rate4= r4($delivery_ex);          // treat as unit price for 1 unit
        $delivery_line = r2($delivery_qty4 * $delivery_rate4);
        $delivery_gst  = $delivery_is_taxable ? r2($delivery_line * GST_RATE) : 0.00;

        $sub_total += $delivery_line;
        $gst_total += $delivery_gst;

        $tbl1 .= '
        <tr>
          <td style="border:1px solid #737373;">DELIVERY</td>
          <td style="border:1px solid #737373;">Delivery</td>
          <td style="border:1px solid #737373;">'. fmt_qty($delivery_qty4) .'</td>
          <td style="border:1px solid #737373;" align="right">'. fmt_rate($delivery_rate4) .'</td>
          <td style="border:1px solid #737373;">ea</td>
          <td style="border:1px solid #737373;" align="right">'. fmt_money_2($delivery_line) .'</td>
        </tr>';
    }

    $total_inc = r2($sub_total + $gst_total);

    // Totals
    $tbl1 .= '
    <tr>
      <td></td><td></td><td></td><td></td>
      <td style="border:1px solid #000;" align="right">Subtotal</td>
      <td style="border:1px solid #000;" align="right">'. fmt_money_2($sub_total) .'</td>
    </tr>
    <tr>
      <td></td><td></td><td></td><td></td>
      <td style="border:1px solid #000;" align="right">GST</td>
      <td style="border:1px solid #000;" align="right">'. fmt_money_2($gst_total) .'</td>
    </tr>
    <tr>
      <td></td><td></td><td></td><td></td>
      <td style="border:1px solid #000;" align="right">Total</td>
      <td style="border:1px solid #000;" align="right">'. fmt_money_2($total_inc) .'</td>
    </tr>';

} else {
    $tbl1 .= '
    <tr>
      <td colspan="6" style="border:1px solid #737373; text-align:center;">No rows available</td>
    </tr>';
}

// ---------- Payment block ----------
$tbl_header2 = '<table style="width: 638px; border: 1px solid #737373;" cellspacing="0" cellpadding="2">';
$tbl_footer2 = '</table>';

$tbl2 = '
<tr>
  <td style="width: 638px;" bgcolor="#ccc"><b>HOW TO PAY</b></td>
</tr>
<tr>
  <td style="width: 100px;"><b>EFT</b></td>
  <td style="width: 200px;"></td>
  <td style="width: 300px; border-left: 1px solid #737373;"><b>POST:</b> Cheques to PO BOX 14 Claremont 7011</td>
</tr>
<tr>
  <td style="width: 100px;">Account Name:</td>
  <td style="width: 200px;">'. $bank_account_name .'</td>
  <td style="width: 300px; border-left: 1px solid #737373;"><b>CREDIT CARD:</b> Phone us on: '. formatPhoneNumber($company_phone, 'l') .'</td>
</tr>
<tr>
  <td style="width: 100px;">BSB#:</td>
  <td style="width: 200px;">'. $bank_bsb .'</td>
  <td style="width: 300px; border-left: 1px solid #737373;"><b>IN PERSON:</b> '. $company_address .' '. $company_suburb .'</td>
</tr>
<tr>
  <td style="width: 100px;">ACCOUNT#:</td>
  <td style="width: 200px;">'. $bank_account .'</td>
  <td style="width: 300px; border-left: 1px solid #737373;"></td>
</tr>
<tr>
  <td style="width: 100px;">REFERENCE:</td>
  <td style="width: 200px;">'. ($_GET['order_id'] ?? '') .'</td>
  <td style="width: 300px; border-left: 1px solid #737373;"></td>
</tr>';

$pdf->writeHTML($tbl_header1 . $tbl1 . $tbl_footer1, true, false, false, false, '');
$pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');

$pdf->Output($id . '.pdf', 'I');
