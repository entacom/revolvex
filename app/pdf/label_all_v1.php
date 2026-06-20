<?php
session_start();
// Include the main TCPDF library (search for installation path).
require_once('../assets/vendor/tcpdf/tcpdf.php');

// Include database connection
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");

$database = new Database();
$conn = $database->connect();

// Function to fetch data from the database for a given pack_id
function fetchData($conn, $order_id, $pack_id) {
    $query = "
        SELECT * FROM tblOrderSubItems WHERE order_id = :order_id AND pack_id = :pack_id ORDER BY pack_id
    ";
    $statement = $conn->prepare($query);
    $statement->bindValue(':order_id', $order_id, PDO::PARAM_INT);
    $statement->bindValue(':pack_id', $pack_id, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

$order_id = $_GET['order_id'];
$max_pack_no = getMaxPackOrder($order_id, $_SESSION['session_company_id']);

// Define custom page size in millimeters (190mm x 59mm)
$customPageWidth = 190;
$customPageHeight = 59;
$customPageSize = array($customPageWidth, $customPageHeight);

// Create new PDF document with custom page size in landscape orientation
$pdf = new TCPDF('L', PDF_UNIT, $customPageSize, true, 'UTF-8', false);
$logo = '../assets/img/featherstone_v.jpg';

// Set document information
$pdf->SetCreator('l');
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Custom Size PDF');
$pdf->SetSubject('TCPDF Tutorial');

// Disable header and footer
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set all margins to 5 units
$pdf->SetMargins(5, 5, 5);
$pdf->SetHeaderMargin(0);
$pdf->SetFooterMargin(5);

// Set auto page breaks
$pdf->SetAutoPageBreak(FALSE, 5);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

for ($pack_no = 1; $pack_no <= $max_pack_no; $pack_no++) {
    $results = fetchData($conn, $order_id, $pack_no);
    $pack_weight = getWeightPackOrder($order_id, $_SESSION['session_company_id'], $pack_no);
    $part_number = gePartNumberPack($order_id, $_SESSION['session_company_id'], $pack_no);
    $part_description = gePartDescriptionPack($order_id, $_SESSION['session_company_id'], $pack_no);

    // Add a page
    $pdf->AddPage();

    // Add the logo
    $pdf->Image($logo, 5, 5, 20, 50);

    // Add text content
    $pdf->SetFont('helvetica', 'B', 18);
    $pdf->Text(30, 5, $customer);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Text(30, 17, 'REF: ' . $order_number);
    $pdf->Text(30, 22, 'ADDR: ' . $site_suburb);
    $pdf->Text(30, 27, 'Del. ' . $order_delivery_short . ' ' . $order_delivery_date);
    $pdf->Text(30, 32, $part_description. ' (' . $part_number.')'  );
    $pdf->Text(30, 37, 'PACK ' . $pack_no . ' of ' . $max_pack_no);

    // Prepare the table HTML
    $tableHtml = '
    <table border="0" cellpadding="1" cellspacing="0">
        <thead>
            <tr>
                <th style="border-top: 1px solid #737373; border-bottom: 1px solid #737373; border-left: 1px solid #737373; width: 5px;"></th>
                <th style="border-top: 1px solid #737373; border-bottom: 1px solid #737373;" >Item</th>
                <th style="border-top: 1px solid #737373; border-bottom: 1px solid #737373;" >Quantity</th>
                <th style="border-top: 1px solid #737373; border-bottom: 1px solid #737373; border-right: 1px solid #737373;" >Length</th>
            </tr>
        </thead>
        <tbody>';

    foreach ($results as $row) {
        $tableHtml .= '
            <tr>
                <td style="border-left: 1px solid #737373; width: 5px"></td>
                <td >' . htmlspecialchars($row['mark']) . '</td>
                <td>' . htmlspecialchars($row['qty']) . '</td>
                <td style="border-right: 1px solid #737373;">' . htmlspecialchars($row['qty_unit']) . '</td>
            </tr>';
    }
    $tableHtml .= '
            <tr>
                <td style="border-left: 1px solid #737373; border-bottom: 1px solid #737373; width: 5px"></td>
                <td style="border-bottom: 1px solid #737373; border-right: 1px solid #737373; " colspan="3">Total Pack Weight ' . $pack_weight . ' kg</td>
            </tr>';

    $tableHtml .= '
        </tbody>
    </table>';

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->writeHTMLCell(75, 0, 100, 5, '#'.$order_id, 0, 1, 0, true, 'R', true);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->writeHTMLCell(75, 0, 120, 12, $tableHtml, 0, 1, 0, true, '', true);
}

// Close and output PDF document
$pdf->Output('label_order'.$order_id.'.pdf', 'I');
?>
