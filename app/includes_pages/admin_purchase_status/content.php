<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');

include("../../includes/common.php");
$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_status'])) {
    $query = "SELECT * FROM tblPurchaseStatus WHERE company_id = :company_id";
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
    $statement->execute();
    $rowCount = $statement->rowCount();
    if ($rowCount > 0) {
        $output = '<div class="card-body">
                        <div class="table-responsive">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary ml-auto" onclick="AddStatusModal()">Add Status</button>
                            </div>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Status</th>
                                        <th scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>';
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $description = htmlspecialchars($row['description']);
            if ($row['active'] == 0) {
                $description = '(<s>' . $description . '</s>)';
            }
            $output .= '<tr>
                            <td>' . $description . '</td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" onclick="EditStatusModal(' . $row['id'] . ')">
                                    <i class="bx bxs-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="toggleActive(' . $row['id'] . ')">
                                    <i class="bx bxs-trash"></i>
                                </button>
                            </td>
                        </tr>';
        }
        $output .= '</tbody></table></div></div>';
        echo $output;
    } else {
        echo '<div class="card"><div class="card-body"><p>Not found</p></div></div>';
    }
    $conn = null;
}
