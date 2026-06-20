<?php
session_start();
error_reporting(E_ALL & ~E_WARNING);

// Ensure the common file is correctly included
include_once("../../includes/common.php");
requireLoggedInDownload();

// Function to format the punch data
function formatPunchData($punch) {
    $patterns = [
        'B' => 'Web',
        'C' => 'Flange',
        'D' => 'WebFlange',
        'E' => 'End'
    ];

    $formatted_punch = [];
    $parts = explode(' ', $punch);

    foreach ($parts as $index => $part) {
        if ($index === 0 && substr($part, -1) === 'L') {
            // Skip the initial 'L'
            continue;
        }
        $length = substr($part, 0, -1);
        $type = substr($part, -1);
        if (array_key_exists($type, $patterns)) {
            $formatted_punch[] = $patterns[$type];
            $formatted_punch[] = $length;
        }
    }

    // Join the parts with commas
    return implode(',', $formatted_punch);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['part_number']) && isset($_POST['order_id'])) {
    try {
        $part_number = $_POST['part_number'];
        $order_id = $_POST['order_id'];
        $company_id = $_SESSION['session_company_id'];
        

        // Check if the Database class is available
        if (!class_exists('Database')) {
            throw new Exception("Database class not found. Please check the inclusion of the required files.");
        }

        $database = new Database();
        $conn = $database->connect();

        // Retrieve order_number, customer, and delivery_date
        $order_number = getFieldColumn('order_number', 'tblOrders', 'order_id', $order_id);
        $customer = getFieldColumn('customer_company', 'tblOrders', 'order_id', $order_id);
        $order_delivery_date_x = getFieldColumn('delivery_date', 'tblOrders', 'order_id', $order_id);
        $deliver_note = getFieldColumn('deliver_note', 'tblOrders', 'order_id', $order_id);

        $order_delivery_date = date('d-m-Y', $order_delivery_date_x);
        $order_delivery_short = date('D', $order_delivery_date_x);

        // Retrieve the number of packs in the order
        $num_packs = getMaxPackOrder($order_id, $company_id);
        $gauge = getFieldColumn('gauge', 'tblInventory', 'part_number', $part_number);
        $manufacture_code = getFieldColumn('manufacture_code', 'tblInventory', 'part_number', $part_number);

        // Main query to get all order items, join with tblOrderSubItems to get pack_id, and order by pack_id
        $query = "
            SELECT oi.*, osi.pack_id, osi.qty, osi.qty_unit, osi.mark, osi.punch 
            FROM tblOrderItems oi 
            JOIN tblOrderSubItems osi ON oi.id = osi.order_group_id 
            WHERE oi.part_number = :part_number AND oi.order_id = :order_id 
            ORDER BY osi.pack_id
        ";

        $result = $conn->prepare($query);
        $result->bindParam(':part_number', $part_number, PDO::PARAM_STR);
        $result->bindParam(':order_id', $order_id, PDO::PARAM_INT);

        $result->execute();

        $csv_data = [];

        // Check if we have items to add PROFILE, DELIVERY, FRAMESET, and COMPONENT data
        $packs_processed = [];

        if ($result->rowCount() > 0) {
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                // Check if this pack has already been processed
                if (!in_array($row['pack_id'], $packs_processed)) {
                    // Retrieve weight of the pack
                    $pack_weight = getWeightPackOrder($order_id, $company_id, $row['pack_id']);

                    // Add PROFILE data
                    $csv_data[] = ['PROFILE', $gauge, $manufacture_code, $part_number];

                    // Add DELIVERY data
                    $csv_data[] = ['DELIVERY', $deliver_note .',' . $order_delivery_date];

                    // Add FRAMESET data
                    $csv_data[] = [
                        'FRAMESET', 
                        'PACK ' . $row['pack_id'], 
                        '', 
                        $order_id, 
                        '', 
                        '', 
                        $order_number, 
                        '', 
                        $row['pack_id'], 
                        $num_packs, 
                        $pack_weight, 
                        $customer
                    ];

                    $packs_processed[] = $row['pack_id'];
                }
                // Convert qty_unit from meters to millimeters
                $qty_unit_mm = $row['qty_unit'] * 1000;

                // Format punch data
                $formatted_punch = formatPunchData($row['punch']);

                // Add COMPONENT data
                $csv_data[] = [
                    'COMPONENT',
                    $row['mark'],
                    '|',
                    $row['qty'],
                    $qty_unit_mm,
                    $formatted_punch
                ];
            }
        }

        // Fallback if no COMPONENT data is found
        if (count($csv_data) == 0) { // No data was added
            $csv_data[] = ['PROFILE', $gauge, $manufacture_code, $part_number];
            $csv_data[] = ['DELIVERY', $deliver_note .','  . $order_delivery_date];
            $csv_data[] = [
                'FRAMESET', 
                'PACK TBA', 
                '', 
                'TBA', 
                '', 
                '', 
                'TBA', 
                '', 
                'TBA', 
                'TBA', 
                'TBA', 
                'TBA'
            ];
            $csv_data[] = ['COMPONENT', '', '|', 'TBA', 'TBA', 'TBA'];
        }

       // Generate CSV file content
        $output = fopen('php://memory', 'w');
        foreach ($csv_data as $line) {
            // Clean, quote-free CSV output
            fwrite($output, implode(',', $line) . "\n");
        }
        fseek($output, 0);

// Set headers to download the file instead of displaying it
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="part_number_' . $part_number . '.csv"');
fpassthru($output);
exit();

    } catch (Exception $e) {
        // Handle errors appropriately
        echo '<b>Error:</b> ' . $e->getMessage();
    }
}
?>
