
<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'subscribers') {
    $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT * FROM tblCompany ";
        $statement = $conn->prepare($query);
        //$statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();
        if ($rowCount > 0) {
            $output = '<div class="card-body">
						<div class="row">
							<div class="col">
								<h5 class="card-title">Active Subscribers</h5>
							</div>
							<div class="col-auto mt-2">
									<button type="button" class="btn btn-sm btn-secondary" onclick="uniModal(\'AddUserModal\')">Add User</button>
							</div>
						</div>
						<div class="table-responsive">
							<table class="table table-hover">
								<thead>
									<tr>
										<th scope="col">Name</th>
										<th scope="col">Email</th>
										<th scope="col">Edit</th>
									</tr>
								</thead>
								<tbody>';
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tr>';
                $output .= '<td>' . $row['company_name'] . '</td>'; // Adjust based on your actual columns
                $output .= '<td>' . $row['email'] . '</td>'; // Change 'email' to your actual column names
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="editUser(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>'; 
                $output .= '</tr>';
            }
            $output .= '</tbody></table></div></div></div>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>Not found</p></div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit; 
}



?>