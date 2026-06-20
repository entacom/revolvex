<?php
ob_start();
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

session_start();
require_once('../assets/vendor/tcpdf/tcpdf.php');
include("../includes/common.php");
requireLoggedInDownload();
require_once("defaults.php");

/* -------------------- INPUT -------------------- */
$order_id = (int)($_GET['order_id'] ?? 0);
$pack_no  = (int)($_GET['pack_id'] ?? 0);

/* -------------------- DB -------------------- */
$database = new Database();
$conn = $database->connect();

$stmt = $conn->prepare("
    SELECT mark, qty, qty_unit
    FROM tblOrderSubItems
    WHERE order_id = :order_id AND pack_id = :pack_id
    ORDER BY id ASC
");
$stmt->bindValue(':order_id', $order_id, PDO::PARAM_INT);
$stmt->bindValue(':pack_id',  $pack_no,  PDO::PARAM_INT);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* -------------------- PACK DATA -------------------- */
$max_pack_no      = getMaxPackOrder($order_id, $_SESSION['session_company_id']);
$pack_weight      = getWeightPackOrder($order_id, $_SESSION['session_company_id'], $pack_no);
$part_number      = gePartNumberPack($order_id, $_SESSION['session_company_id'], $pack_no);
$part_description = gePartDescriptionPack($order_id, $_SESSION['session_company_id'], $pack_no);

/* -------------------- SAFE DEFAULTS -------------------- */
$customer            = $customer ?? '';
$order_number        = $order_number ?? '';
$order_delivery_note = $order_delivery_note ?? '';
$order_delivery_date = $order_delivery_date ?? '';
$site_address_line1  = $site_address_line1 ?? '';
$site_phone          = $site_phone ?? '';
$pickup_label        = $site_suburb ?? '';

/* -------------------- TOTAL LENGTH (LM) -------------------- */
$total_len = 0.0;
foreach ($results as $r) {
    $q = is_numeric($r['qty']) ? (float)$r['qty'] : 0.0;
    $u = is_numeric($r['qty_unit']) ? (float)$r['qty_unit'] : 0.0;
    $total_len += ($q * $u);
}
$total_len_str = $total_len > 0 ? number_format($total_len, 3) . 'LM' : '';

/* -------------------- PDF -------------------- */
/* sticker size = 200mm wide x 76mm high */
$pdf = new TCPDF('L', 'mm', [200, 76], true, 'UTF-8', false);

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddPage();

/* -------------------- GEOMETRY -------------------- */
$pageW = $pdf->getPageWidth();
$pageH = $pdf->getPageHeight();

$left  = 3;
$right = $pageW - 3;
$midX  = 100;

$contentTop = 1.5;

/* sections */
$barcodeY = 39;
$boxH     = 11;
$boxW     = ($right - $left) / 4;

$bottomY  = 53;
$bottomH  = 14;

/* only keep centre divider - no top header, no top separator */
$pdf->Line($midX, $contentTop, $midX, $barcodeY);

/* -------------------- LEFT PANEL -------------------- */
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Text($left + 4, $contentTop + 3.5, $customer);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Text($left + 4, $contentTop + 11.5, 'REF:');

$pdf->SetFont('helvetica', '', 11);
$pdf->Text($left + 18, $contentTop + 11.5, $order_number);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Text($left + 4, $contentTop + 18.5, 'ADDR:');

$pdf->SetFont('helvetica', '', 11);
$pdf->Text($left + 24, $contentTop + 18.5, $pickup_label);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Text($left + 4, $contentTop + 25.5, 'NOTE:');

$pdf->SetFont('helvetica', '', 11);
$pdf->Text($left + 24, $contentTop + 25.5, $order_delivery_note);

$pdf->SetFont('helvetica', 'B', 11);
$pdf->Text($left + 4, $contentTop + 32.5, 'PACK:');

$pdf->SetFont('helvetica', '', 11);
$pdf->Text($left + 24, $contentTop + 32.5, $pack_no . ' of ' . $max_pack_no);

/* -------------------- RIGHT PANEL -------------------- */
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetXY($midX + 3, $contentTop + 3.5);
$pdf->Cell(
    $right - ($midX + 3),
    4,
    $part_description . ' (' . $part_number . ')',
    0,
    2,
    'C'
);

/* items grid */
$pdf->SetFont('helvetica', '', 8.5);

$colX   = [$midX + 6, $midX + 48];
$startY = $contentTop + 11.5;
$rowH   = 3.8;

$tokens = [];
foreach ($results as $r) {
    $q = trim((string)($r['qty'] ?? ''));
    $u = trim((string)($r['qty_unit'] ?? ''));
    $m = trim((string)($r['mark'] ?? ''));

    $line = '';
    if ($q !== '' && $u !== '') {
        $line = $q . ' @ ' . $u;
    } elseif ($q !== '') {
        $line = $q;
    } elseif ($u !== '') {
        $line = $u;
    }

    if ($m !== '') {
        $line .= ($line !== '' ? ' ' : '') . ':' . $m;
    }

    if ($line !== '') {
        $tokens[] = $line;
    }
}

$rows = (int)ceil(max(1, count($tokens)) / 2);

$idx = 0;
for ($r = 0; $r < $rows; $r++) {
    for ($c = 0; $c < 2; $c++) {
        if ($idx < count($tokens)) {
            $pdf->Text($colX[$c], $startY + ($r * $rowH), $tokens[$idx]);
        }
        $idx++;
    }
}

/* -------------------- BARCODE + SUMMARY ROW -------------------- */
$x0 = $left;

$pdf->Rect($x0,             $barcodeY, $boxW, $boxH);
$pdf->Rect($x0 + $boxW,     $barcodeY, $boxW, $boxH);
$pdf->Rect($x0 + $boxW * 2, $barcodeY, $boxW, $boxH);
$pdf->Rect($x0 + $boxW * 3, $barcodeY, $boxW, $boxH);

/* barcode */
$style = [
    'position'     => '',
    'align'        => 'C',
    'stretch'      => false,
    'fitwidth'     => true,
    'cellfitalign' => '',
    'border'       => false,
    'hpadding'     => 0,
    'vpadding'     => 0,
    'fgcolor'      => [0, 0, 0],
    'bgcolor'      => false,
    'text'         => false,
    'font'         => 'helvetica',
    'fontsize'     => 8,
    'stretchtext'  => 4
];

$pdf->write1DBarcode(
    (string)$order_id,
    'C128',
    $x0 + 4,
    $barcodeY + 1.5,
    $boxW - 8,
    $boxH - 3,
    0.4,
    $style,
    'N'
);

/* labels */
$pdf->SetFont('helvetica', '', 7.5);
$pdf->Text($x0 + $boxW + 2,      $barcodeY + 1.2, 'Delivery Date');
$pdf->Text($x0 + $boxW * 2 + 2,  $barcodeY + 1.2, 'Total Length');
$pdf->Text($x0 + $boxW * 3 + 2,  $barcodeY + 1.2, 'Weight');

/* values */
$pdf->SetFont('helvetica', 'B', 15);
$pdf->Text($x0 + $boxW + 2,      $barcodeY + 5.0, (string)$order_delivery_date);
$pdf->Text($x0 + $boxW * 2 + 2,  $barcodeY + 5.0, (string)$total_len_str);
$pdf->Text($x0 + $boxW * 3 + 2,  $barcodeY + 5.0, (string)round((float)$pack_weight) . ' KG');

/* -------------------- BOTTOM CUSTOMER BOX -------------------- */
$pdf->Rect($left, $bottomY, $right - $left, $bottomH);

$pdf->SetFont('helvetica', '', 7.5);
$pdf->Text($left + 2, $bottomY + 1.3, 'Customer Name');

$pdf->SetFont('helvetica', 'B', 15);
$pdf->Text($left + 2, $bottomY + 7.0, $customer);

$pdf->SetFont('helvetica', 'B', 23);
$pdf->SetXY($right - 52, $bottomY + 3.0);
$pdf->Cell(47, 7, '# ' . $order_id, 0, 0, 'R');

/* -------------------- OUTPUT -------------------- */
if (ob_get_length()) {
    ob_end_clean();
}
$pdf->Output('label_order' . $order_id . '-Pack' . $pack_no . '.pdf', 'I');
