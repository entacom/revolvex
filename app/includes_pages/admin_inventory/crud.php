<?php
ob_start();
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    include("../../includes/common.php");
    requireLoggedInJson();
    $database = new Database();
    $conn = $database->connect();
    $data_raw = json_decode(file_get_contents("php://input"), true);


// PHP Code
if (isset($data_raw['action']) && $data_raw['action'] == 'read_inventory') {
    $data = sanInputs($data_raw);
    $return_arr = array();
    $query = "SELECT * FROM tblInventory WHERE id = :inventory_id AND company_id = :company_id";
    $result = $conn->prepare($query);
    $result->bindParam(':inventory_id', $data['inventory_id']);   
    $result->bindParam(':company_id', $_SESSION['session_company_id']); 
    $result->execute();
  
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'group_id' => $row['group_id'],
            'raw_material' => $row['raw_material'],
            'product_source_id' => $row['product_source_id'],
            'product_source' => getTableColField('part_number', 'tblInventory', 'id', $row['product_source_id']),
            'group' => getTableColField('description', 'tblInventoryGroup', 'id', $row['group_id']),
            'has_sub_items' => $row['has_sub_items'],
            'part_number' => $row['part_number'],
            'description' => $row['description'],
            'unit_id' => $row['unit_id'],
            'unit' => getTableColField('description', 'tblItemUnits', 'id', $row['unit_id']),
            'metre_unit' => $row['metre_unit'],
            'weight_unit' => $row['weight_unit'],
            'min_qty' => $row['min_qty'],
            'qty' => $row['qty'],
            'rate' => $row['rate'],
            'buy_rate' => $row['buy_rate'],

            'rate_level_a' => $row['rateLevelA'],
            'rate_level_b' => $row['rateLevelB'],
            'rate_level_c' => $row['rateLevelC'],
            'rate_level_d' => $row['rateLevelD'],
            'rate_level_e' => $row['rateLevelE'],
            'rate_level_f' => $row['rateLevelF'],
            'rate_level_g' => $row['rateLevelG'],
            'rate_level_h' => $row['rateLevelH'],
            'rate_level_i' => $row['rateLevelI'],
            'rate_level_j' => $row['rateLevelJ'],

            'gauge' => $row['gauge'],
            'manufacture_code' => $row['manufacture_code'],
        );
        array_push($return_arr, $row_data);
    }

    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}

 if (isset($data_raw['action']) && $data_raw['action'] == 'read_inventory_item') {
    $data = sanInputs($data_raw);
    $return_arr = array();
    $query = "SELECT * FROM tblInventoryItems WHERE id = :inventory_item_id ";
    $result = $conn->prepare($query);
    $result->bindParam(':inventory_item_id', $data['inventory_item_id']);   

    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'serial_number' => $row['serial_number'],
            'part_number' => $row['part_number'],
            'qty' => $row['qty'],
            'qty_unit' => $row['qty_unit'],
            'coil_finished' => $row['coil_finished'],
        );
        array_push($return_arr, $row_data);
    }
    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}  
    
if (isset($data_raw['action']) && $data_raw['action'] == 'save_inventory_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    
    $query = "UPDATE tblInventoryItems SET 
                serial_number = :serial_number,
                qty = :qty,
                coil_finished = :coil_finished
                WHERE id = :id AND company_id = :company_id";
                
    $bindings = array(
        ':id' => $data['inventory_item_id'],
        ':company_id' => $_SESSION['session_company_id'],
        ':serial_number' => $data['serial_number'],
        ':qty' => $data['qty'],
        ':coil_finished' => $data['coil_finished'],
    );
    
    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    }
    $conn = null;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'save_inventory_sub_item') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    
    $query = "UPDATE tblInventoryItems SET 
                qty = :qty,
                qty_unit = :qty_unit
                WHERE id = :id AND company_id = :company_id";
                
    $bindings = array(
        ':id' => $data['inventory_sub_item_id'],
        ':company_id' => $_SESSION['session_company_id'],
        ':qty' => $data['qty'],
        ':qty_unit' => $data['qty_unit'],
    );
    
    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    }
    $conn = null;
}
if (isset($data_raw['action']) && $data_raw['action'] == 'save_inventory') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    // Check if part number is unique
    $checkQuery = "SELECT COUNT(*) FROM tblInventory WHERE part_number = :part_number AND company_id = :company_id AND id != :id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':part_number', $data['part_number']);
    $checkStmt->bindParam(':company_id', $_SESSION['session_company_id']);
    $checkStmt->bindParam(':id', $data['inventory_id']);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['error' => true, 'message' => 'Item number already exists.']);
        $conn = null;
        exit;
    }
    
    $unit = getTableColField('description', 'tblItemUnits', 'id', $data['unit_id']);
    $divisible_unit = getTableColField('divisible', 'tblItemUnits', 'id', $data['unit_id']);
    $has_sub_items = $divisible_unit ? 1 : 0;

    $data['part_number'] = trim(str_replace(' ', '', $data['part_number']));

    // Update query with price level columns A-J
    $query = "UPDATE tblInventory SET 
                group_id = :group_id,
                raw_material = :raw_material,
                product_source_id = :product_source_id,
                has_sub_items = :has_sub_items,
                part_number = :part_number,
                description = :description, 
                unit = :unit,
                unit_id = :unit_id,
                metre_unit = :metre_unit,
                weight_unit = :weight_unit,
                min_qty = :min_qty,
                qty = :qty,
                rate = :rate,
                buy_rate = :buy_rate,
                rateLevelA = :rateLevelA,
                rateLevelB = :rateLevelB,
                rateLevelC = :rateLevelC,
                rateLevelD = :rateLevelD,
                rateLevelE = :rateLevelE,
                rateLevelF = :rateLevelF,
                rateLevelG = :rateLevelG,
                rateLevelH = :rateLevelH,
                rateLevelI = :rateLevelI,
                rateLevelJ = :rateLevelJ,
                gauge = :gauge,
                manufacture_code = :manufacture_code
              WHERE id = :id AND company_id = :company_id";

    $bindings = array(
        ':id' => $data['inventory_id'],
        ':company_id' => $_SESSION['session_company_id'],
        ':group_id' => $data['group_id'],
        ':raw_material' => $data['raw_material'],
        ':product_source_id' => $data['product_source_id'],
        ':has_sub_items' => $has_sub_items,
        ':part_number' => $data['part_number'],
        ':description' => $data['description'],
        ':unit' => $unit,
        ':unit_id' => $data['unit_id'],
        ':metre_unit' => $data['metre_unit'],
        ':weight_unit' => $data['weight_unit'],
        ':min_qty' => $data['min_qty'],
        ':qty' => $data['qty'],
        ':rate' => $data['rate'],
        ':buy_rate' => $data['buy_rate'],

        ':rateLevelA' => $data['rate_level_a'],
        ':rateLevelB' => $data['rate_level_b'],
        ':rateLevelC' => $data['rate_level_c'],
        ':rateLevelD' => $data['rate_level_d'],
        ':rateLevelE' => $data['rate_level_e'],
        ':rateLevelF' => $data['rate_level_f'],
        ':rateLevelG' => $data['rate_level_g'],
        ':rateLevelH' => $data['rate_level_h'],
        ':rateLevelI' => $data['rate_level_i'],
        ':rateLevelJ' => $data['rate_level_j'],
        ':gauge' => $data['gauge'],
        ':manufacture_code' => $data['manufacture_code'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    }
    $conn = null;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'add_inventory') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    // Check if part_number already exists
    $checkQuery = "SELECT COUNT(*) FROM tblInventory WHERE part_number = :part_number AND company_id = :company_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':part_number', $data['part_number']);
    $checkStmt->bindParam(':company_id', $_SESSION['session_company_id']);
    $checkStmt->execute();
    $count = $checkStmt->fetchColumn();

    if ($count > 0) {
        echo json_encode(['success' => true, 'message' => 'Item number already exists.']);
        $conn = null;
        exit;
    }

    // Determine if item has sub-items
    $divisible_unit = getTableColField('divisible', 'tblItemUnits', 'id', $data['unit_id']);
    $has_sub_items = $divisible_unit ? 1 : 0;

    // Insert query including price levels A-J
    $query = "INSERT INTO tblInventory 
                (company_id, group_id, raw_material, has_sub_items, part_number, description, unit_id, metre_unit, weight_unit, min_qty, qty, rate, buy_rate,
                 rateLevelA, rateLevelB, rateLevelC, rateLevelD, rateLevelE, rateLevelF, rateLevelG, rateLevelH, rateLevelI, rateLevelJ, gauge, manufacture_code)
              VALUES 
                (:company_id, :group_id, :raw_material, :has_sub_items, :part_number, :description, :unit_id, :metre_unit, :weight_unit, :min_qty, :qty, :rate, :buy_rate,
                 :rateLevelA, :rateLevelB, :rateLevelC, :rateLevelD, :rateLevelE, :rateLevelF, :rateLevelG, :rateLevelH, :rateLevelI, :rateLevelJ, :gauge, :manufacture_code)";

    $bindings = array(
        ':company_id' => $_SESSION['session_company_id'],
        ':group_id' => $data['group_id'],
        ':raw_material' => $data['raw_material'],
        ':has_sub_items' => $has_sub_items,
        ':part_number' => $data['part_number'],
        ':description' => $data['description'],
        ':unit_id' => $data['unit_id'],
        ':metre_unit' => $data['metre_unit'],
        ':weight_unit' => $data['weight_unit'],
        ':min_qty' => $data['min_qty'],
        ':qty' => $data['qty'],
        ':rate' => $data['rate'],
        ':buy_rate' => $data['buy_rate'],

        ':rateLevelA' => $data['rate_level_a'],
        ':rateLevelB' => $data['rate_level_b'],
        ':rateLevelC' => $data['rate_level_c'],
        ':rateLevelD' => $data['rate_level_d'],
        ':rateLevelE' => $data['rate_level_e'],
        ':rateLevelF' => $data['rate_level_f'],
        ':rateLevelG' => $data['rate_level_g'],

        ':rateLevelH' => $data['rate_level_h'],
        ':rateLevelI' => $data['rate_level_i'],
        ':rateLevelJ' => $data['rate_level_j'],
        ':gauge' => $data['gauge'],
        ':manufacture_code' => $data['manufacture_code'],
    );

    $rowCount = 0;
    if (executeDatabaseQuery($conn, $query, $bindings, $rowCount)) {
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Item added successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'An error occurred while adding the item.']);
    }

    $conn = null;
}


}
ob_end_flush();
?>
