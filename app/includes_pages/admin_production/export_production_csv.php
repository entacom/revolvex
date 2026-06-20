<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
requireLoggedInDownload();

$database = new Database();
$conn = $database->connect();

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=production_orders.csv');

$output = fopen('php://output', 'w');

fputcsv($output, ['Order ID', 'Pack ID', 'Part Number', 'Description', 'Qty', 'Qty Unit', 'Sub Total', 'Status']);

$query = "SELECT osi.order_id, osi.pack_id, osi.part_number, osi.description, osi.qty, osi.qty_unit, s.description AS status
          FROM tblOrderSubItems osi
          JOIN tblOrderItems oi ON oi.id = osi.order_group_id AND oi.company_id = osi.company_id
          JOIN tblOrders o ON osi.order_id = o.order_id
          LEFT JOIN tblOrderStatus s ON o.order_status_id = s.id
          WHERE osi.company_id = :company_id
          AND (osi.serial_number = '' OR osi.serial_number IS NULL)
          AND (oi.purchased_item IS NULL OR oi.purchased_item = '' OR oi.purchased_item = 0)
          ORDER BY osi.order_id, osi.part_number";

$statement = $conn->prepare($query);
$statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
$statement->execute();

while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, [
        $row['order_id'],
        $row['pack_id'],
        $row['part_number'],
        $row['description'],
        $row['qty'],
        $row['qty_unit'],
        $row['qty_unit']*$row['qty'],
        $row['status']
    ]);
}

fclose($output);
exit;
