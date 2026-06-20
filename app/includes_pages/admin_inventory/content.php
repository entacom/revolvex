<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

include("../../includes/common.php");
requireLoggedInJson();

$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'inventory_list') {
    $filter_group_id = isset($_POST['filter_group_id']) ? $_POST['filter_group_id'] : null;

    try {
        // Modify the query to include the filter condition if filter_group_id is provided
        $query = "SELECT * FROM tblInventory WHERE company_id = :company_id";
        
        if ($filter_group_id) {
            $query .= " AND group_id = :filter_group_id";
        }

        $query .= " ORDER BY part_number";

        $statement = $conn->prepare($query);
        $statement->bindValue(':company_id', $_SESSION['session_company_id'], PDO::PARAM_INT);

        if ($filter_group_id) {
            $statement->bindValue(':filter_group_id', $filter_group_id, PDO::PARAM_INT);
        }

        $statement->execute();

        $rowCount = $statement->rowCount();
        if ($rowCount > 0) {
            $output = '<style>
                .inventory-panel {
                    border: 1px solid #dfe7f3;
                    border-radius: 8px;
                    background: #ffffff;
                    box-shadow: 0 16px 36px rgba(15, 42, 70, 0.09);
                    overflow: hidden;
                }
                .inventory-panel-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    gap: 12px;
                    padding: 15px 18px;
                    background: linear-gradient(135deg, #fbfcff 0%, #f3f7fd 100%);
                    border-bottom: 1px solid #dfe7f3;
                }
                .inventory-panel-title {
                    margin: 0;
                    color: #0b3158;
                    font-size: 1.05rem;
                    font-weight: 800;
                }
                .inventory-count-pill {
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                    padding: 5px 9px;
                    margin-top: 5px;
                    border-radius: 999px;
                    background: #eef2ff;
                    color: #315ccf;
                    font-size: 0.78rem;
                    font-weight: 800;
                }
                .inventory-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 7px;
                    justify-content: flex-end;
                }
                .inventory-actions .btn {
                    border-radius: 7px;
                    font-weight: 700;
                }
                .inventory-board {
                    padding: 12px;
                    background: #f5f8fc;
                }
                .inventory-board-head,
                .inventory-row {
                    display: grid;
                    grid-template-columns: minmax(110px, 0.9fr) minmax(260px, 2.4fr) minmax(95px, 0.8fr) minmax(100px, 0.8fr) minmax(150px, 1fr) minmax(92px, 0.7fr) minmax(110px, 0.8fr);
                    gap: 10px;
                    align-items: center;
                }
                .inventory-board-head {
                    padding: 0 12px 8px;
                    color: #6b7789;
                    font-size: 0.72rem;
                    font-weight: 800;
                    letter-spacing: 0;
                    text-transform: uppercase;
                }
                .inventory-row {
                    min-height: 58px;
                    padding: 10px 12px;
                    margin-bottom: 8px;
                    border: 1px solid #e0e7f0;
                    border-radius: 8px;
                    background: #ffffff;
                    box-shadow: 0 8px 20px rgba(15, 42, 70, 0.055);
                    transition: transform 0.12s ease, box-shadow 0.12s ease, border-color 0.12s ease;
                }
                .inventory-row:hover {
                    transform: translateY(-1px);
                    border-color: #c6d6ec;
                    box-shadow: 0 12px 26px rgba(15, 42, 70, 0.10);
                }
                .inventory-part {
                    color: #0b3158;
                    font-weight: 800;
                }
                .inventory-description {
                    color: #253346;
                    font-weight: 700;
                }
                .inventory-muted {
                    color: #637083;
                    font-size: 0.84rem;
                }
                .inventory-qty {
                    color: #102a43;
                    font-weight: 800;
                }
                .inventory-stock-pill {
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    min-width: 74px;
                    padding: 5px 8px;
                    border-radius: 999px;
                    background: #edf8f3;
                    color: #167452;
                    font-size: 0.78rem;
                    font-weight: 800;
                }
                .inventory-stock-pill.low {
                    background: #fff1f0;
                    color: #b42318;
                }
                .inventory-row-actions {
                    display: flex;
                    gap: 7px;
                    justify-content: flex-end;
                }
                .inventory-row-actions .btn,
                .inventory-subrow .btn {
                    width: 34px;
                    height: 34px;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 7px;
                    padding: 0;
                }
                .inventory-subwrap {
                    margin: -3px 0 10px 18px;
                    border-left: 3px solid #d7e4f5;
                    padding-left: 12px;
                }
                .inventory-subrow {
                    display: grid;
                    grid-template-columns: minmax(140px, 0.8fr) minmax(180px, 1fr) minmax(180px, 1fr) minmax(100px, 0.7fr) minmax(90px, 0.5fr);
                    gap: 10px;
                    align-items: center;
                    padding: 9px 12px;
                    margin-bottom: 6px;
                    border: 1px solid #e5ebf3;
                    border-radius: 8px;
                    background: #fbfdff;
                    color: #49566a;
                    font-size: 0.88rem;
                }
                .inventory-empty {
                    padding: 22px;
                    border: 1px dashed #cbd6e3;
                    border-radius: 8px;
                    background: #ffffff;
                    color: #667085;
                }
                @media (max-width: 1100px) {
                    .inventory-board-head {
                        display: none;
                    }
                    .inventory-row,
                    .inventory-subrow {
                        grid-template-columns: 1fr;
                        gap: 6px;
                    }
                    .inventory-row-actions {
                        justify-content: flex-start;
                    }
                }
            </style>
            <div class="inventory-panel">
                <div class="inventory-panel-header">
                    <div>
                        <h5 class="inventory-panel-title">Inventory Items</h5>
                        <span class="inventory-count-pill"><i class="bx bx-package"></i>' . (int)$rowCount . ' items</span>
                    </div>
                    <div class="inventory-actions">
                        <button type="button" class="btn btn-sm btn-primary" onclick="addInventoryModal()"><i class="bx bx-plus"></i> Add Item</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="redirectTo(\'p=admin_item_groups\')"><i class="bx bx-category"></i> Groups</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="redirectTo(\'p=admin_item_units\')"><i class="bx bx-ruler"></i> Units</button>
                    </div>
                </div>
                <div class="inventory-board">
                    <div class="inventory-board-head">
                        <div>Item#</div>
                        <div>Description</div>
                        <div>On Hand</div>
                        <div>Min</div>
                        <div>Group</div>
                        <div>Unit</div>
                        <div>Actions</div>
                    </div>';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    $stock_alarm = (getTableFieldSum('qty', 'tblInventoryItems', 'inventory_id', $row['id']) < $row['min_qty']) ? 'low' : '';
    
    // Determine the quantity
    if ($row['raw_material']) {
        $qty = getTableFieldSum('qty', 'tblInventoryItems', 'inventory_id', $row['id']);
    } elseif ($row['has_sub_items'] == 1) {
        $qty = getLengSumInve($row['id']);
    } else {
        $qty = $row['qty'];
    }

    // Check if there are sub-items
    $subQuery = "SELECT COUNT(*) FROM tblInventoryItems WHERE inventory_id = :inventory_id";
    $subStatement = $conn->prepare($subQuery);
    $subStatement->bindValue(':inventory_id', $row['id'], PDO::PARAM_INT);
    $subStatement->execute();
    $hasItems = $subStatement->fetchColumn() > 0;

    $button_items = $hasItems ? '<button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#collapse-' . htmlspecialchars($row['id']) .'" title="Show stock items"><i class="bx bx-chevron-down"></i></button>' : '';
    $stock_label = $stock_alarm === 'low' ? 'Low Stock' : 'Stock OK';
    $unit = getTableColField('description', 'tblItemUnits', 'id', $row['unit_id']);
    $group = getTableColField('description', 'tblInventoryGroup', 'id', $row['group_id']);

    $output .= '<div class="inventory-row">
                    <div class="inventory-part">' . htmlspecialchars($row['part_number']) . '</div>
                    <div>
                        <div class="inventory-description">' . htmlspecialchars($row['description']) . '</div>
                        <div class="inventory-muted">Buy $' . htmlspecialchars((string)$row['buy_rate']) . ' / Sell $' . htmlspecialchars((string)$row['rate']) . '</div>
                    </div>
                    <div><span class="inventory-qty">' . htmlspecialchars((string)$qty) . '</span></div>
                    <div><span class="inventory-stock-pill ' . $stock_alarm . '">' . htmlspecialchars($stock_label) . ' - Min ' . htmlspecialchars((string)$row['min_qty']) . '</span></div>
                    <div class="inventory-muted">' . htmlspecialchars((string)$group) . '</div>
                    <div class="inventory-muted">' . htmlspecialchars((string)$unit) . '</div>
                    <div class="inventory-row-actions">
                        <button class="btn btn-sm btn-outline-primary" onclick="editInventory(' . htmlspecialchars($row['id']) . ')" title="Edit item"><i class="bx bx-edit"></i></button>
                        ' . $button_items . '
                    </div>
                </div>
                <div id="collapse-' . htmlspecialchars($row['id']) . '" class="collapse inventory-subwrap">';

    $subQuery = "SELECT * FROM tblInventoryItems WHERE inventory_id = :inventory_id ORDER BY qty DESC";
    $subStatement = $conn->prepare($subQuery);
    $subStatement->bindValue(':inventory_id', $row['id'], PDO::PARAM_INT);
    $subStatement->execute();

    while ($subRow = $subStatement->fetch(PDO::FETCH_ASSOC)) {
       if (!$row['has_sub_items']) {
    $coilFinishedIcon = ((int)$subRow['coil_finished'] === 0)
        ? '<i class="bx bxs-check-circle text-success" title="Not Finished"></i>'
        : '<i class="bx bxs-x-circle text-danger" title="Finished"></i>';

    $output .= '<div class="inventory-subrow">
                <div class="inventory-muted">PID-' . htmlspecialchars($subRow['purchase_id']) . '</div>
                <div>SN-' . htmlspecialchars($subRow['serial_number']) . '</div>
                <div><strong>' . htmlspecialchars($subRow['qty']) . '</strong> on hand</div>
                <div class="text-center">' . $coilFinishedIcon . '</div>
                <div class="inventory-row-actions">
                    <button class="btn btn-sm btn-outline-secondary" onclick="editInventoryItem(' . htmlspecialchars($subRow['id']) . ')" title="Edit stock item"><i class="bx bx-edit"></i></button>
                </div>
            </div>';
              }
                else {
                            $output .= '<div class="inventory-subrow">
                                        <div class="inventory-muted">Length</div>
                                        <div>' . htmlspecialchars($subRow['serial_number']) . '</div>
                                        <div><strong>' . htmlspecialchars($subRow['qty'] . ' X ' . $subRow['qty_unit']) . '</strong></div>
                                        <div></div>
                                        <div class="inventory-row-actions">
                                            <button class="btn btn-sm btn-outline-secondary" onclick="editInventorySubItem(' . htmlspecialchars($subRow['id']) . ')" title="Edit length"><i class="bx bx-edit"></i></button>
                                        </div>
                                    </div>';
                        }
                    }
                    $output .= '</div>';
                }
            $output .= '    </div>
                       </div>';
            echo $output;
        } else {
            echo '<div style="padding:22px;border:1px dashed #cbd6e3;border-radius:8px;background:#ffffff;color:#667085;"><strong>No inventory items found.</strong><br>Try another group filter or add a new inventory item.</div>';
        }
    } catch (PDOException $e) {
        error_log("Error: " . $e->getMessage());
    }

    $conn = null;
    exit;
}
?>
