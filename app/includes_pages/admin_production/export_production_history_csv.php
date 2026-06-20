<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
requireLoggedInDownload();

$database = new Database();
$conn = $database->connect();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=production_history.csv');

$output = fopen('php://output', 'w');

// Column headers matching your history table
fputcsv($output, ['Date', 'Order ID', 'Part Number', 'Serial Number', 'Order Qty', 'Coil Used', 'Stock In', 'Waste', 'Total Stock In', 'Value']);

$query = "SELECT *
          FROM tblProduction 
          WHERE company_id = :company_id
          ORDER BY production_date DESC";

$statement = $conn->prepare($query);
$statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
$statement->execute();

while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $metre_unit = getTableFieldSum('metre_unit', 'tblInventory', 'part_number', $row['part_number']); // mtr per 1000kg
    $purchased_price = getTableFieldSum('rate', 'tblBillItems', 'serial_number', $row['serial_number']); // price per 1000kg

    $stock_from_coil = (float)$row['stock_from_coil'];
    $value_total = ($purchased_price / $metre_unit ) * $stock_from_coil;

    fputcsv($output, [
        date('d-m-Y', $row['production_date']),
        $row['order_id'],
        $row['part_number'],
        $row['serial_number'],
        $row['order_qty'],
        $stock_from_coil,
        $row['stock_in_qty'],
        $row['waste_qty'],
        $row['stock_total_in_qty'],
        number_format($value_total, 2), // ← Total cost in AUD or currency
    ]);
}


fclose($output);
exit;
