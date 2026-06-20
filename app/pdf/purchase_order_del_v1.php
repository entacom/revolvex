<?php
session_start();
error_reporting(E_ALL & ~E_WARNING);
// Include the main TCPDF library (search for installation path).
require_once('../assets/vendor/tcpdf/tcpdf.php');
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");

class MYPDF extends TCPDF {
    protected $purchase_user;
    protected $header_displayed = false; // Property to track header display

    public function __construct($purchaser_user) {
        parent::__construct();
        $this->purchaser_user = $purchaser_user;
    }

    public function Header() {
        if (!$this->header_displayed) { // Only display the header on the first page
            $logo = '../' . getTableColField('company_image_path', 'tblCompany', 'id', $_SESSION['session_company_id']);
            $image_file = $logo;
            $this->Image($image_file, 10, 10, 80, '', 'jpg', '', 'T', false, 300, '', false, false, 0, false, false, false);
            $this->SetFont('helvetica', 'B', 16);
            $this->Cell(0, 10, $this->order_status, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->SetFont('helvetica', 'B', 16);
            $this->Ln(10);
            $this->Cell(0, 10, 'DELIVERY DOCKET ', 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(8);
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 10, 'ORDER #' . $_GET['pid'], 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Date: ' . date('d-m-Y'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Contact: ' . $this->purchaser_user, 0, false, 'R', 0, '', 0, false, 'T', 'M');
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
$pdf = new MYPDF($purchaser_user, PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

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
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP-280, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
// set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
include("defaults.php");

// set font
$pdf->SetFont('times', '', 14);
$pdf->Ln();
// add a page
$pdf->AddPage('P');

$pdf->SetTopMargin(5);    
$tbl_header = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer = '</table>';
$tbl = '';

$tbl .= '
    <tr>
        <td style="width: 300px;"><b>Supplier To</b></td>
        <td style="width: 300px;"><b>Delivery To</b></td>
    </tr>';

$tbl .= '
    <tr>
        <td style="width: 300px;">' . $vendor . '</td>
        <td style="width: 300px;">' . $company . '</td>
    </tr>
    <tr>
        <td style="width: 300px;">' . $vendor_address . '</td>
        <td style="width: 300px;">' . $company_address . '</td>
    </tr>
    <tr>
        <td style="width: 300px;">' . $vendor_suburb . ', ' . $vendor_state . ' ' . $vendor_postcode . '</td>
        <td style="width: 300px;">' . $company_suburb . ', ' . $company_state . ' ' . $company_postcode . '</td>
    </tr>
    <tr>
        <td style="width: 300px;">Ph ' . $vendor_phone . '</td>
        <td style="width: 300px;">' . $company_phone . '</td>
    </tr>
';
$pdf->writeHTML($tbl_header . $tbl . $tbl_footer, true, false, false, false, '');
$pdf->SetFont('times', '', 12);
$pdf->writeHTML('Notes: '.$purchase_order_notes, 1, 0, 0, 0, "L");
$pdf->ln(10);

$pdf->SetFont('times', '', 11);
// Print text using writeHTMLCell()
$tbl_header2 = '<table style="width: 638px;" cellspacing="0">';
$tbl_footer2 = '</table>';

$tbl2 = '<tr>
        <td style="border: 1px solid #737373; width: 120px;" color="#000" bgcolor="#ccc">ITEM NO</td>
        <td style="border: 1px solid #737373; width: 350px;" color="#000" bgcolor="#ccc">DESCRIPTION</td>
        <td style="border: 1px solid #737373; width: 70px;" color="#000" bgcolor="#ccc">QTY</td>

        <td style="border: 1px solid #737373; width: 90px;" color="#000" bgcolor="#ccc">UNIT</td>

    </tr>';

$database = new Database();
$conn = $database->connect();
$query = "SELECT * FROM tblPurchaseItems WHERE pid = :pid";
$result = $conn->prepare($query);
$result->bindValue(':pid', $_GET['pid'], PDO::PARAM_INT);
$result->execute();

$sub_total = 0;

if ($result->rowCount() > 0) {
    while ($row2 = $result->fetch(PDO::FETCH_ASSOC)) {
        $unit = getFieldColumn('description', 'tblItemUnits', 'id', $row2['unit_id']);
        $qty = $row2['has_items'] ? getSumItemLengthPur($_GET['pid'], $_SESSION['session_company_id'], $row2['part_number']) : number_format($row2['qty'], 1);
        $line_total = $row2['rate'] * $qty;
        $sub_total += $line_total;

        $tbl2 .= '
            <tr>
                <td style="border: 1px solid #737373;">' . htmlspecialchars($row2['part_number']) . '</td>
                <td style="border: 1px solid #737373;">' . htmlspecialchars($row2['description']) . '</td>
                <td style="border: 1px solid #737373;">' . htmlspecialchars(number_format($qty, 3)) . '</td>
                <td style="border: 1px solid #737373;">' . htmlspecialchars($unit) . '</td>

            </tr>';

        if ($row2['has_items']) {
            $subQuery = "SELECT * FROM tblPurchaseSubItems WHERE order_group_id = :order_group_id ORDER BY qty_unit";
            $subStatement = $conn->prepare($subQuery);
            $subStatement->bindValue(':order_group_id', $row2['id'], PDO::PARAM_INT);
            $subStatement->execute();

            while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
                $tbl2 .= '
                    <tr>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;">' . htmlspecialchars($subRow['mark'].' - '.$subRow['qty'] . ' x ' . $subRow['qty_unit']) . '</td>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;" align="right"></td>
                    </tr>';
            }
        }
    }
} 

$pdf->writeHTML($tbl_header2 . $tbl2 . $tbl_footer2, true, false, false, false, '');

// Define the file path for saving if 's' is set in the GET parameter
if (isset($_GET['s'])) {
    $filePath = $_SERVER['DOCUMENT_ROOT'] . "/files/purchase_order_" . $_GET['pid'] . ".pdf";
    $pdf->Output($filePath, 'F'); // Save the PDF to the specified file path
    echo json_encode(['success' => true, 'message' => 'PDF generated and saved.', 'path' => $filePath]);
} else {
    $pdf->Output('invoice.pdf', 'I'); // Output to browser if 's' is not set
}
?>
