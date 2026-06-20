<?php
// ======= BEGIN AMENDED BLOCK: /path/to/your/pdf-generator-file.php =======
session_start();
error_reporting(E_ALL & ~E_WARNING);
// Include the main TCPDF library (search for installation path).
require_once('../assets/vendor/tcpdf/tcpdf.php');
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");

class MYPDF extends TCPDF {
    protected $purchaser_user;
    protected $order_date_required;    // NEW: will show under Contact
    protected $header_displayed = false; // Property to track header display

    // Accept existing TCPDF args without breaking current calls
    public function __construct($purchaser_user, ...$tcpdfArgs) {
        parent::__construct(...$tcpdfArgs);
        $this->purchaser_user = $purchaser_user;
        // Pull from defaults.php (already included)
        $this->order_date_required = isset($GLOBALS['order_date_required']) ? $GLOBALS['order_date_required'] : '';
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
            $this->Cell(0, 10, 'PURCHASE ORDER ', 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(8);
            $this->SetFont('helvetica', '', 12);
            $this->Cell(0, 10, 'ORDER #' . $_GET['pid'], 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Date: ' . date('d-m-Y'), 0, false, 'R', 0, '', 0, false, 'T', 'M');
            $this->Ln(5);
            $this->Cell(0, 10, 'Contact: ' . $this->purchaser_user, 0, false, 'R', 0, '', 0, false, 'T', 'M');

            // ===== Inserted BELOW your "Contact" line =====
            $this->Ln(5);
            $this->Cell(0, 10, 'Required: ' . $this->order_date_required, 0, false, 'R', 0, '', 0, false, 'T', 'M');
            // ===== End inserted =====

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
// NOTE: We keep your existing call signature intact.
$pdf = new MYPDF(
    $purchaser_user,
    PDF_PAGE_ORIENTATION,
    PDF_UNIT,
    PDF_PAGE_FORMAT,
    true,
    'UTF-8',
    false
);

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
        <td style="border: 1px solid #737373; width: 90px;" color="#000" bgcolor="#ccc">ITEM NO</td>
        <td style="border: 1px solid #737373; width: 250px;" color="#000" bgcolor="#ccc">DESCRIPTION</td>
        <td style="border: 1px solid #737373; width: 50px;" color="#000" bgcolor="#ccc">QTY</td>
        <td style="border: 1px solid #737373; width: 80px;" color="#000" bgcolor="#ccc">Ex Price</td>
        <td style="border: 1px solid #737373; width: 70px;" color="#000" bgcolor="#ccc">UNIT</td>
        <td style="border: 1px solid #737373; width: 100px;" color="#000" bgcolor="#ccc" align="right">EX AMOUNT</td>
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
                <td style="border: 1px solid #737373;">' . htmlspecialchars($row2['rate']) . '</td>
                <td style="border: 1px solid #737373;">' . htmlspecialchars($unit) . '</td>
                <td style="border: 1px solid #737373;" align="right">$' . number_format($line_total, 2) . '</td>
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
                        <td style="border: 1px solid #737373;">' . htmlspecialchars($subRow['mark'] .' - '.$subRow['qty'] . ' x ' . $subRow['qty_unit']) . '</td>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;"></td>
                        <td style="border: 1px solid #737373;" align="right"></td>
                    </tr>';
            }
        }
    }
    
    $gst = $sub_total * 0.1;
    $total_with_gst = $sub_total + $gst;
    $purchase_total = $total_with_gst + $freight;
    
    $tbl2 .= '
        <tr>
            <td colspan="4"></td>
            <td style="border: 1px solid #000000;" align="right">Subtotal</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($sub_total, 2) . '</td>
        </tr>
        <tr>
            <td colspan="4"></td>
            <td style="border: 1px solid #000000;" align="right">GST</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($gst, 2) . '</td>
        </tr>
        <tr>
            <td colspan="4"></td>
            <td style="border: 1px solid #000000;" align="right">Freight</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($freight, 2) . '</td>
        </tr>
        <tr>
            <td colspan="4"></td>
            <td style="border: 1px solid #000000;" align="right">Total</td>
            <td style="border: 1px solid #000000;" align="right">$' . number_format($purchase_total, 2) . '</td>
        </tr>';
} else {
    $tbl2 .= '
        <tr>
            <td colspan="6" style="border: 1px solid #737373; text-align: center;">No rows available</td>
        </tr>';
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
UpdatePurStatus($_GET['pid'],2);
?>
