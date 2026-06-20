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
    protected $order_number;
    protected $order_delivery_date;
    protected $order_user;
    protected $header_displayed = false; // Property to track header display

    public function __construct($order_number, $order_delivery_date, $order_user) {
        parent::__construct();
        $this->order_number = $order_number;
        $this->order_delivery_date = $order_delivery_date;
        $this->order_user = $order_user;
    }

    public function Header() {
        if (!$this->header_displayed) { // Only display the header on the first page
            $logo = '../' . getTableColField('company_image_path', 'tblCompany', 'id', $_SESSION['session_company_id']);
            $image_file = $logo;
            $this->Image($image_file, 10, 10, 80, '', 'jpg', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 15, 'Production Card', 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', '', 12);
            $this->Ln(10);
            $this->Cell(0, 10, 'Order# ' . $_GET['order_id'], 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Customer Order# ' . $this->order_number, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Date: ' . date('d-m-Y'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Ship Date: ' . $this->order_delivery_date, 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
$pdf = new MYPDF($order_number, $order_delivery_date, $order_user, PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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
$pdf->SetMargins(PDF_MARGIN_LEFT, 40, PDF_MARGIN_RIGHT); // 40 is the top margin
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set font
$pdf->SetFont('times', '', 12);

// add a page
$pdf->AddPage('P');
$pdf->ln(2);
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
        <td style="width: 300px;">' . $site_suburb . '</td>
    </tr>
    <tr>
        <td style="width: 300px;">' . $customer_phone . '</td>
        <td style="width: 300px;">' . $order_delivery_note . '</td>
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
        <td style="border: 1px solid #737373; width: 120px;" color="#000" bgcolor="#ccc">ITEM NO</td>
        <td style="border: 1px solid #737373; width: 350px;" color="#000" bgcolor="#ccc">DESCRIPTION</td>
        <td style="border: 1px solid #737373; width: 80px;" color="#000" bgcolor="#ccc">Qty</td>
        <td style="border: 1px solid #737373; width: 70px;" color="#000" bgcolor="#ccc">UNIT</td>
    </tr>';

$database = new Database();
$conn = $database->connect();
$query = "SELECT * FROM tblOrderItems WHERE order_id = :order_id AND part_number = :part_number";
$stmt = $conn->prepare($query);
$stmt->bindValue(':order_id', $id, PDO::PARAM_INT);
$stmt->bindValue(':part_number', $_GET['part_number'], PDO::PARAM_STR);
$stmt->execute();


if ($stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row['unit_id']);
        $qty = $row['has_items'] ? getSumItemLength($_GET['order_id'], $_SESSION['session_company_id'], $row['part_number']) : number_format($row['qty'], 1);
        $tbl2 .= '
            <tr>
                <td style="border: 1px solid #737373;">' . $row['part_number'] . '</td>
                <td style="border: 1px solid #737373;">' . $row['description'] . '</td>
                <td style="border: 1px solid #737373;">' . $qty . '</td>
                <td style="border: 1px solid #737373;">' . $unit . '</td>
            </tr>';
        if ($row['has_items']) {
            $query2 = "SELECT * FROM tblOrderSubItems WHERE order_group_id = :order_group_id ORDER BY pack_id ASC";
            $stmt2 = $conn->prepare($query2);
            $stmt2->bindValue(':order_group_id', $row['id'], PDO::PARAM_INT);
            $stmt2->execute();

            while ($row2 = $stmt2->fetch(PDO::FETCH_ASSOC)) {
                $sub_row = $row2['qty'] * $row2['qty_unit'];

                $tbl2 .= '
                    <tr>
                        <td style="border: 1px solid #737373;">Pack: '.$row2['pack_id'].'</td>
                        <td colspan="2" style="border: 1px solid #737373;">' . $row2['mark'].' - '. $row2['qty'] . ' x ' . $row2['qty_unit'] . '</td>
                        <td style="border: 1px solid #737373;" align="right"></td>
                    </tr>';
            }
        }

        if ($pdf->getY() > 250) {
            $pdf->AddPage('P');
            $pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');
            $tbl2 = ''; // reset table content for the next page
        }
    }
} else {
    $tbl2 .= '
        <tr>
            <td colspan="6" style="border: 1px solid #737373; text-align: center;">No rows available</td>
        </tr>';
}

$pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');

// Add signature lines
$pdf->Ln(10);
$pdf->SetFont('times', '', 10);
$signature_lines = '
<table cellpadding="8" cellspacing="8" style="width: 638px;" >
    <tr>
        <td style="width: 350px;">Date: ____________________________</td>
        <td style="width: 280px;"></td>
    </tr>
    <tr>
        <td style="width: 350px;">Coil Number: __________________________</td>
        <td style="width: 280px;"></td>
    </tr>
    <tr>
        <td style="width: 350px;">Stock In: _____________________________</td>
        <td style="width: 280px;"></td>
    </tr>
    <tr>
        <td style="width: 350px;">Stock Out: ____________________________</td>
        <td style="width: 280px;">Packed Ready for Delivery</td>
    </tr>
    <tr>
        <td style="width: 350px;">Waste: _______________________________</td>
        <td style="width: 280px;">Signed: _______________________________</td>
    </tr>
    <tr>
        <td style="width: 350px;">Total Metres Rolled from Coil:</td>
        <td style="width: 280px;">Date: _________________________________</td>
    </tr>
    <tr>
        <td style="width: 350px;"> __________________________________</td>
        <td style="width: 280px;"</td>
    </tr>


</table>';
$pdf->writeHTML($signature_lines, true, false, false, false, '');

//Close and output PDF document
$pdf->Output($id . '.pdf', 'I');


?>
