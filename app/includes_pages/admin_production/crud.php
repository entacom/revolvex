<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 'Off');
    include("../../includes/common.php");
    requireLoggedInJson();
    $database = new Database();
    $conn = $database->connect();
    $data_raw = json_decode(file_get_contents("php://input"), true);
    
/*    
if (isset($data_raw['action']) && $data_raw['action'] == 'read_inventory_sim_stock') {
    $data = sanInputs($data_raw);
    $query = "SELECT qty, qty_unit FROM tblInventoryItems 
              WHERE order_id = :order_id 
              AND part_number = :part_number 
              AND company_id = :company_id";
    
    $result = $conn->prepare($query);
    $result->bindParam(':order_id', $data['order_id'], PDO::PARAM_INT); 
    $result->bindParam(':part_number', $data['part_number'], PDO::PARAM_STR);  
    $result->bindParam(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT); 
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'id' => $row['id'],
            'description' => $row['description'],
            'part_number' => $row['part_number'],

        );
        array_push($return_arr, $row_data);
    }
    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}
*/
if (isset($data_raw['action']) && $data_raw['action'] == 'read_inventory_total_stock') {
    $data = sanInputs($data_raw);
    $cumulative_total = 0;

    $query = "SELECT qty, qty_unit FROM tblInventoryItems 
              WHERE order_id = :order_id 
              AND part_number = :part_number 
              AND company_id = :company_id";
    
    $result = $conn->prepare($query);
    $result->bindParam(':order_id', $data['order_id'], PDO::PARAM_INT); 
    $result->bindParam(':part_number', $data['part_number'], PDO::PARAM_STR);  
    $result->bindParam(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT); 
    $result->execute();

    // Check if any rows were returned
    if ($result->rowCount() > 0) {
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            // Calculate the total inventory value for this row
            $total_inv = (float)$row['qty'] * (float)$row['qty_unit'];

            // Add to the cumulative total
            $cumulative_total += $total_inv;
        }

        // Prepare the final return data with the cumulative total
        $return_arr = array(
            array('cumulative_total' => number_format($cumulative_total, 3))
        );

    } else {
        // Handle the case where no records are found
        $return_arr = array(
            array('cumulative_total' => 0)
        );
    }

    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}




if (isset($data_raw['action']) && $data_raw['action'] == 'read_match') {
    $data = sanInputs($data_raw);
    $return_arr = array();
    $query = "SELECT * FROM tblOrderSubItems WHERE id = :id AND company_id = :company_id";
    $result = $conn->prepare($query);
    $result->bindParam(':id', $data['id']);   
    $result->bindParam(':company_id', $_SESSION['session_company_id']); 
    $result->execute();
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $row_data = array(
            'id' => $row['id'],
            'text_order_id' => $row['order_id'],
            'order_id' => $row['order_id'],
            'pack_id' => $row['pack_id'],
            'description' => $row['description'],
            'part_number' => $row['part_number'],
            'serial_number' => $row['serial_number'],
            'qty' => $row['qty'],
            'qty_unit' => $row['qty_unit'],
            'qty_total' => getLengSum($row['order_group_id']),
            'weight' => $row['weight'],
			'product_source_part' => getTabFieCol('product_source_id', 'tblInventory', 'part_number', $row['part_number'] ,$_SESSION['session_company_id']) 
			
	

        );
        array_push($return_arr, $row_data);
    }
    header('Content-Type: application/json');
    echo json_encode($return_arr);
    $conn = null;
}

if (isset($data_raw['action']) && $data_raw['action'] == 'update_purchased_item') {
    $data = sanInputs($data_raw);
    $order_item_id = isset($data['order_item_id']) ? (int)$data['order_item_id'] : 0;
    $purchased_item = !empty($data['purchased_item']) ? 1 : 0;

    if ($order_item_id <= 0) {
        echo json_encode(['error' => true, 'message' => 'Missing order item.']);
        exit();
    }

    $stmt = $conn->prepare("
        SELECT id, order_id, part_number, description, purchased_item
        FROM tblOrderItems
        WHERE id = :id
          AND company_id = :company_id
        LIMIT 1
    ");
    $stmt->bindValue(':id', $order_item_id, PDO::PARAM_INT);
    $stmt->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $stmt->execute();
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['error' => true, 'message' => 'Order item not found.']);
        exit();
    }

    $update = $conn->prepare("
        UPDATE tblOrderItems
        SET purchased_item = :purchased_item
        WHERE id = :id
          AND company_id = :company_id
    ");
    $update->bindValue(':purchased_item', $purchased_item, PDO::PARAM_INT);
    $update->bindValue(':id', $order_item_id, PDO::PARAM_INT);
    $update->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);
    $update->execute();

    $part_number = !empty($item['part_number']) ? $item['part_number'] : ('item #' . $order_item_id);
    $activity = $purchased_item
        ? 'Production item marked as purchased: ' . $part_number
        : 'Production item returned to production: ' . $part_number;
    addOrderActivity($item['order_id'], $_SESSION['session_company_id'], 5, $activity, $_SESSION['session_user_id'], 0);

    echo json_encode([
        'success' => true,
        'message' => $purchased_item ? 'Item marked as purchased.' : 'Item returned to production.'
    ]);
    $conn = null;
    exit();
}

if (isset($data_raw['action']) && $data_raw['action'] == 'split_group') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();
    
    // SELECT HERE
    $query = "SELECT * FROM tblOrderSubItems WHERE id = :id";
    $result = $conn->prepare($query);
    $result->bindParam(':id', $data['item_group_id']);   
    $result->execute();
    
    $row = $result->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        // INSERT HERE
        $insertQuery = "INSERT INTO tblOrderSubItems 
                            (company_id, order_id, order_group_id, part_number, description, mark, punch, qty, qty_unit) 
                        VALUES 
                            (:company_id, :order_id, :order_group_id, :part_number, :description, :mark, :punch, :qty, :qty_unit)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindParam(':company_id', $_SESSION['session_company_id']);
        $insertStmt->bindParam(':order_id', $row['order_id']);
        $insertStmt->bindParam(':order_group_id', $row['order_group_id']);
        $insertStmt->bindParam(':part_number', $row['part_number']);
        $insertStmt->bindParam(':description', $row['description']);
        $insertStmt->bindParam(':mark', $row['mark']);
        $insertStmt->bindParam(':punch', $row['punch']);
        $insertStmt->bindParam(':qty', $data['split_qty']); // new quantity from POST
        $insertStmt->bindParam(':qty_unit', $row['qty_unit']);
        
        $insertStmt->execute();
        
        // UPDATE HERE
        $newQty = $data['original_qty'] - $data['split_qty'];
        $updateQuery = "UPDATE tblOrderSubItems SET qty = :qty WHERE id = :item_group_id";
        $updateStmt = $conn->prepare($updateQuery);
        
        $updateStmt->bindParam(':qty', $newQty);
        $updateStmt->bindParam(':item_group_id', $data['item_group_id']);
        
        $rowCount = 0;
        if ($updateStmt->execute()) {
            $rowCount = $updateStmt->rowCount();
            if ($rowCount > 0) {
                echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
            } else {
                echo json_encode(['warning' => true, 'message' => 'No changes made.']);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Update failed.']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Original record not found.']);
    }
}
    
if (isset($data_raw['action']) && $data_raw['action'] == 'delete_stock_id') {	
	deleteId('tblInventoryItems', $data_raw['stock_id']);
} 
    
    
if (isset($data_raw['action']) && $data_raw['action'] == 'add_stock') {
    $data = sanInputs($data_raw);
     // Validate required fields

    
    $inventory_id=getTableColField('id', 'tblInventory', 'part_number', $data['part_number']);
    $serial_number=getTableColField('serial_number', 'tblInventoryItems', 'id', $data['inventory_id']);
    
    $database = new Database();
    $conn = $database->connect();
                $insertQuery = "INSERT INTO tblInventoryItems 
                                (company_id,  order_id, inventory_id, serial_number, part_number ,qty ,qty_unit) 
                                VALUES 
                                (:company_id, :order_id, :inventory_id, :serial_number, :part_number, :qty, :qty_unit)";

                $insertBindings = [
                    ':company_id' => $_SESSION['session_company_id'],
                    ':order_id' => $data['order_id'],
                    ':inventory_id' => $inventory_id,
                    ':serial_number' => $serial_number,
                    ':part_number' => $data['part_number'],
                    ':qty' => $data['add_stock_qty'], 
                    ':qty_unit' => $data['add_stock_qty_units'], 
                   
                ];

                executeDatabaseQuery($conn, $insertQuery, $insertBindings, $rowCount);
        if ($rowCount > 0) {
            echo json_encode(['success' => true, 'message' => 'Production added and inventory updated successfully.']);
        } else {
            echo json_encode(['warning' => true, 'message' => 'No changes made.']);
        }
    } 


  
if (isset($data_raw['action']) && $data_raw['action'] == 'add_production') {
    $data = sanInputs($data_raw);

    $serial_number = getTableColField('serial_number', 'tblInventoryItems', 'id', $data['inv_id']);
    $part_number = getTableColField('part_number', 'tblInventoryItems', 'id', $data['inv_id']);
    $production_date = strtotime($data['production_date']);
    $recorded_date = time();

    $database = new Database();
    $conn = $database->connect();

    $existing_qty = getTableColField('qty', 'tblInventoryItems', 'id', $data['inv_id']);

    if (!empty($data['order_items']) && is_array($data['order_items'])) {
        $query = "INSERT INTO tblProduction 
                    (company_id, order_id, order_item_id, recorded_date, production_date, part_number, serial_number, order_qty, stock_in_qty, waste_qty, stock_from_coil, stock_total_in_qty) 
                  VALUES 
                    (:company_id, :order_id, :order_item_id, :recorded_date, :production_date, :part_number, :serial_number, :order_qty, :stock_in_qty, :waste_qty, :stock_from_coil, :stock_total_in_qty)";

        $total_used = 0;

        foreach ($data['order_items'] as $item) {
            $order_item_id = $item['order_item_id'];
            $stock_in_qty = $item['stock_in_qty'];
            $waste_qty = $item['waste_qty'];
            $stock_from_coil = $item['stock_from_coil'];
            $stock_total_in_qty = $stock_in_qty + $waste_qty;

            $total_used += $stock_from_coil;

            $bindings = [
                ':company_id' => $_SESSION['session_company_id'],
                ':order_id' => $data['order_id'],
                ':order_item_id' => $order_item_id,
                ':recorded_date' => $recorded_date,
                ':production_date' => $production_date,
                ':part_number' => $part_number,
                ':serial_number' => $serial_number,
                ':order_qty' => $stock_from_coil,
                ':stock_in_qty' => $stock_in_qty,
                ':waste_qty' => $waste_qty,
                ':stock_from_coil' => $stock_from_coil,
                ':stock_total_in_qty' => $stock_total_in_qty
            ];

            $rowCount = 0;
            executeDatabaseQuery($conn, $query, $bindings, $rowCount);

            $query2 = "UPDATE tblOrderSubItems SET 
                       serial_number = :serial_number
                       WHERE id = :order_item_id";

            $bindings2 = [
                ':serial_number' => $serial_number,
                ':order_item_id' => $order_item_id
            ];

            $rowCount2 = 0;
            executeDatabaseQuery($conn, $query2, $bindings2, $rowCount2);
        }

        $new_qty = $existing_qty - $total_used;

        $query1 = "UPDATE tblInventoryItems SET 
                   qty = :qty,
                   coil_finished = :coil_finished
                   WHERE serial_number = :serial_number AND id = :id";

        $bindings1 = [
            ':qty' => $new_qty,
            ':coil_finished' => !empty($data['coil_finished']) ? 1 : 0,
            ':serial_number' => $serial_number,
            ':id' => $data['inv_id']
        ];

        $rowCount1 = 0;
        executeDatabaseQuery($conn, $query1, $bindings1, $rowCount1);

        echo json_encode(['success' => true, 'message' => 'Production added and records updated successfully.']);
    } else {
        echo json_encode(['error' => true, 'message' => 'No order items provided.']);
    }

    $conn = null;
    exit();
}

if (isset($data_raw['action']) && $data_raw['action'] == 'record_stock_used') {
    $data = sanInputs($data_raw);
    $database = new Database();
    $conn = $database->connect();

    $order_item_id = $data['order_item_id'];
    $stock_id = $data['stock_id'];
    $stock_used_qty = $data['stock_used_qty'];

    $serial_number = getTableColField('serial_number', 'tblInventoryItems', 'id', $stock_id);
    $original_qty = getTableColField('qty', 'tblInventoryItems', 'id', $stock_id);
    $new_qty = $original_qty - $stock_used_qty;

    $query1 = "UPDATE tblInventoryItems SET 
                qty = :new_qty
                WHERE id = :stock_id";
    $bindings1 = array(
        ':new_qty' => $new_qty,
        ':stock_id' => $stock_id,
    );

    $rowCount1 = 0;
    $rowCount2 = 0;

    if (executeDatabaseQuery($conn, $query1, $bindings1, $rowCount1)) {
        $query2 = "UPDATE tblOrderSubItems SET 
                    serial_number = :serial_number
                    WHERE id = :order_item_id";
        $bindings2 = [
            ':serial_number' => $serial_number,
            ':order_item_id' => $order_item_id,
        ];

        executeDatabaseQuery($conn, $query2, $bindings2, $rowCount2);

        if ($rowCount1 > 0 || $rowCount2 > 0) {
            echo json_encode(['success' => true, 'message' => 'Updated successfully.']);
        } else {
            echo json_encode(['success' => true, 'message' => 'No changes made.']);
        }
    } else {
        echo json_encode(['error' => true, 'message' => 'Failed to update the order.']);
    }
}





if (isset($data_raw['action']) && $data_raw['action'] == 'delete_production_id') {
    $data = sanInputs($data_raw);

    // Get qty from request (default to 1 if not valid)
    $qty = isset($data['qty']) && is_numeric($data['qty']) ? (int)$data['qty'] : 1;

    // Prepare DB connection
    $database = new Database();
    $conn = $database->connect();

    // Get the serial_number from tblProduction before deleting
    $serial_number = '';
    $stmt = $conn->prepare("SELECT serial_number FROM tblProduction WHERE id = :id");
    $stmt->execute([':id' => $data['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && !empty($result['serial_number'])) {
        $serial_number = $result['serial_number'];
    }

    // Delete from tblProduction
    deleteId('tblProduction', $data['id']);

    // Clear serial number from tblOrderSubItems
    if (!empty($data['order_item_id'])) {
        $query2 = "UPDATE tblOrderSubItems SET serial_number = '' WHERE id = :order_item_id";
        $bindings2 = [':order_item_id' => $data['order_item_id']];
        $rowCount2 = 0;
        executeDatabaseQuery($conn, $query2, $bindings2, $rowCount2);
    }

    // Increase qty in tblInventoryItems for matching serial number
    if (!empty($serial_number)) {
        $stmt = $conn->prepare("
            UPDATE tblInventoryItems
            SET qty = qty + :qty
            WHERE serial_number = :serial_number
        ");
        $stmt->execute([
            ':qty' => $qty,
            ':serial_number' => $serial_number
        ]);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Production deleted, serial cleared, and inventory quantity restored.'
    ]);

    $conn = null;
    exit();
}


}
?>
