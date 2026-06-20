<?php
session_start();
error_reporting(E_ALL & ~E_WARNING);
// Include the main TCPDF library (search for installation path).
require_once('../assets/vendor/tcpdf/tcpdf.php');
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");
// Extend the TCPDF class to create custom Header and Footer

class MYPDF extends TCPDF {
    protected $order_status;
    protected $order_delivery_date;
    protected $order_user;
    protected $header_displayed = false; // Property to track header display

    public function __construct($order_status, $order_delivery_date, $order_user) {
        parent::__construct();
        $this->order_status = $order_status;
        $this->order_delivery_date = $order_delivery_date;
        $this->order_user = $order_user;
    }

    public function Header() {
        if (!$this->header_displayed) { // Only display the header on the first page
            $logo = '../' . getTableColField('company_image_path', 'tblCompany', 'id', $_SESSION['session_company_id']);
            $image_file = $logo;
            $this->Image($image_file, 10, 10, 80, '', 'jpg', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 10, 'ORDER CONFIRMATION', 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 12);
            $this->Ln(10);
            $this->Cell(0, 10, 'Order# ' . $_GET['order_id'], 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Date: ' . date('d-m-Y'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Date Required: ' . $this->order_delivery_date, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Contact: ' . $this->order_user, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(15); // Add space after header
            $this->header_displayed = true; // Mark header as displayed
        }
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

// create new PDF document
$pdf = new MYPDF($order_status, $order_delivery_date, $order_user, PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('ACP');
$pdf->SetTitle('Order Confirmation');
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
$pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT); // 40 is the top margin
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
        <td style="width: 320px;"><b>Billing Address</b></td>
        <td style="width: 300px;"><b>Delivery Address</b></td>
    </tr>';

$tbl .= '
    <tr>
        <td style="width: 320px;">' . $customer . '</td>
        <td style="width: 300px;"><b>Address:</b> ' . $site_address . '</td>
    </tr>
    <tr>
        <td style="width: 320px;">' . $customer_address . '</td>
        <td style="width: 300px;"><b>Suburb:</b> ' . $site_suburb . '</td>
    </tr>
    <tr>
        <td style="width: 320px;">' . $customer_suburb . ' ' . $customer_postcode . ' ' . $customer_state . '</td>
        <td style="width: 300px;"><b>Note:</b> ' . $order_delivery_note . '</td>
    </tr>
    <tr>
        <td style="width: 320px;">' . $customer_phone . '</td>
        <td style="width: 300px;"></td>
    </tr>
';
$pdf->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false, false, '');

$pdf->writeHTML('Notes: ', 1, 0, 0, 0, "L");
$pdf->Ln(10);

$pdf->SetFont('times', '', 12);
// Print text using writeHTMLCell()
$tbl_header2 = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer2 = '</table>';

$tbl2 = '<tr>
        <td style="border: 1px solid #737373; width: 90px;" color="#000" bgcolor="#ccc">ITEM NO</td>
        <td style="border: 1px solid #737373; width: 250px;" color="#000" bgcolor="#ccc">DESCRIPTION</td>
        <td style="border: 1px solid #737373; width: 70px;" color="#000" bgcolor="#ccc">QTY</td>
        <td style="border: 1px solid #737373; width: 80px;" color="#000" bgcolor="#ccc">Ex Price</td>
        <td style="border: 1px solid #737373; width: 70px;" color="#000" bgcolor="#ccc">UNIT</td>
        <td style="border: 1px solid #737373; width: 100px;" color="#000" bgcolor="#ccc">EX AMOUNT</td>
    </tr>';

$database = new Database();
$conn = $database->connect();
$query = "SELECT * FROM tblOrderItems WHERE order_id = :order_id";
$stmt = $conn->prepare($query);
$stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
$stmt->execute();

$sub_total = 0;

if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
        $qty = $row['has_items'] ? getSumItemLength($_GET['order_id'], $_SESSION['session_company_id'], $row['part_number']) : number_format($row['qty'], 1);
        $ex_amount = $row['has_items'] ? '' : number_format($row['rate'] * $row['qty'], 2);
        $sub_total += $row['has_items'] ? 0 : $row['rate'] * $row['qty'];

        $rate_display = $row['rate'] ? '$' . $row['rate'] : '';
        $ex_amount_display = $ex_amount ? '$' . $ex_amount : '';

        $tbl2 .= '
            <tr>
                <td style="border: 1px solid #737373;">' . $row['part_number'] . '</td>
                <td style="border: 1px solid #737373;">' . $row['description'] . '</td>
                <td style="border: 1px solid #737373;">' . number_format($qty,3) . '</td>
                <td style="border: 1px solid #737373;" align="right">' . $rate_display . '</td>
                <td style="border: 1px solid #737373;">' . $unit . '</td>
                <td style="border: 1px solid #737373;" align="right">' . $ex_amount_display . '</td>
            </tr>';
        if ($row['has_items']) {
            $query2 = "SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
            $stmt2->execute();

            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $sub_row = $row2['qty'] * $row2['qty_unit'];
                $row_value = $sub_row * $row['rate'];
                $sub_total += $row_value;
                $tbl2 .= '
                    <tr>
                        <td style="border: 1px solid #737373;"></td>
                        <td colspan="3" style="border: 1px solid #737373;">' . $row2['mark'] . ' ' . $row2['qty'] . ' x ' . $row2['qty_unit'] . '</td>
                        <td style="border: 1px solid #737373;" align="right"></td>
                        <td style="border: 1px solid #737373;" align="right">$' . number_format($row_value, 2) . '</td>
                    </tr>';
            }
        }

        if ($pdf->getY() > 250) {
            $pdf->AddPage('P');
            $pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');
            $tbl2 = ''; // reset table content for the next page
        }
    }

    $gst = ($sub_total + $order_delivery_rate )* 0.1; // GST calculation on the subtotal
    //$order_delivery_rate = 50; // Assuming this value is fetched from the database or calculated elsewhere
    $purchase_total = $sub_total + $order_delivery_rate + $gst ; // Total including GST and Delivery

    $tbl2 .= '
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="border: 1px solid #000000;" align="right">Subtotal</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($sub_total, 2) . '</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="border: 1px solid #000000;" align="right">Delivery</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($order_delivery_rate, 2) . '</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="border: 1px solid #000000;" align="right">GST</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($gst, 2) . '</td>
        </tr>
        
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td style="border: 1px solid #000000;" align="right">Total</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($purchase_total, 2) . '</td>
        </tr>';
} else {
    $tbl2 .= '
        <tr>
            <td colspan="6" style="border: 1px solid #737373; text-align: center;">No rows available</td>
        </tr>';
}
$tbl_header3 = '<table style="width: 638px; border: 1px solid #737373;" cellspacing="0" cellpadding="2">';
$tbl_footer3 = '</table>';
$tbl3 = '
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
    <td style="width: 200px;">' . $_GET['order_id'] . '</td>
    <td style="width: 300px; border-left: 1px solid #737373;"></td>
</tr>';
$pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');
$pdf->writeHTML($tbl_header3 . $tbl3 . $tbl_footer3, true, false, false, false, '');
//Close and output PDF document
if (isset($_GET['s']) && $_GET['s'] == '1') {
    $save_path = $_SERVER['DOCUMENT_ROOT'] . "/files/sales_order_v1{$id}.pdf";
    $pdf->Output($save_path, 'F');
} else {
    $pdf->Output($id . '.pdf', 'I');
}

?>
