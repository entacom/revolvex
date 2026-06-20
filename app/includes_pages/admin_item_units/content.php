<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

include("../../includes/common.php");
$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['item_units'])) {
    $query = "SELECT * FROM tblItemUnits WHERE company_id = :company_id";
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
    $statement->execute();
    $rowCount = $statement->rowCount();
    if ($rowCount > 0) {
        $output = '<div class="card-body">
                        <div class="table-responsive">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary ml-auto" onclick="AddUnitModal()">Add Item</button>
                            </div>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Unit</th>
                                        <th scope="col">Divisible</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>';
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $description = htmlspecialchars($row['description']);
            if ($row['active'] == 0) {
                $description = '(<s>' . $description . '</s>)';
                }
                if($row['divisible']){
                $divisible= "Yes";
                }
                else{
                     $divisible= "No";
                }
            $output .= '<tr>
                            <td>' . $description . '</td>
                            <td>' . $divisible. '</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" onclick="EditUnitModal(' . $row['id'] . ')">
                                    <i class="bx bxs-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleActive(' . $row['id'] . ')">
                                    <i class="bx bxs-trash"></i>
                                </button>
                            </td>
                        </tr>';
        }
        $output .= '</tbody></table>
        If divisible is yes, then the unit can be Sold/Purchased in smaller units. For example, if a tonne is divisible, it can be divided into kilograms (1 tonne = 1,000 kilograms).
        </div></div>';
        echo $output;
    } else {
        echo '<div class="card"><div class="card-body"><p>No Units found</p></div></div>';
    }
    $conn = null;
}
