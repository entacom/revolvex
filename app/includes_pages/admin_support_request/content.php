<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['action'] == 'requests') {
    // If the condition is met, generate the data for 'content'
       $database = new Database();
    $conn = $database->connect();
    $data = '
    <div class="row dashboard">
        <div class="col-lg-12">
            <div class="card">
                <div class="filter">
                    <a class="icon" href="#" data-bs-toggle="dropdown"><i class="bi bi-three-dots"></i></a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                        <li class="dropdown-header text-start">
                            <h6>Filter</h6>
                        </li>
                        <li><a class="dropdown-item" href="#">Today</a></li>
                        <li><a class="dropdown-item" href="#">This Month</a></li>
                        <li><a class="dropdown-item" href="#">This Year</a></li>
                    </ul>
                </div>

                <div class="card-body">
                    <h5 class="card-title">Recent Support Requests <span>| This Month</span></h5>
                    <button onclick="NewSupportRequest(add_support_request)" class="btn btn-sm btn-outline-secondary mb-3">Add Request</button>
					';

                    // SQL Query for tblJobHistory
                    $query = "SELECT * FROM tblSupportRequest WHERE company_id= '".$_SESSION['session_company_id']."'";
                    $result = $conn->prepare($query);
                    $result->execute();


$data .= '<div class="list-group">'; 

while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
$date = date_c($row['action_date'],$_SESSION['date_company']) ;
$userID = getTableField('firstname', 'tblUsers', $row['user_id']) . ' ' . getTableField('lastname', 'tblUsers', $row['user_id']);
$description = $row['description'];
$status = $row['type_id'];

// Adjusting the status based on type_id value
if ($row['type_id'] == 1 || $row['type_id'] == 2 || $row['type_id'] == 3 || $row['type_id'] == 4) {
    $status = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Public</span>';
} elseif ($row['type_id'] == 10) {
    $status = '<span class="badge bg-danger"><i class="bi bi-exclamation-octagon me-1"></i> Private</span>';
}

// Create a list group item for each row with four columns
$data .= '<div class="list-group-item list-group-item-action">';
$data .= '<div class="row">';
$data .= '<div class="col-md-1">' . $status . '</div>'; // Column for status
$data .= '<div class="col-md-1 sm-text">' . $date . '</div>'; // Column for date with smaller text size
$data .= '<div class="col-md-2 sm-text">' . $userID . '</div>'; // Column for user ID with smaller text size
$data .= '<div class="col-md-7 sm-text">' . $description . '</div>'; // Column for description with smaller text size
$data .= '<div class="col-md-1 sm-text"><button class="btn btn-sm btn-outline-secondary" onclick="editUser(123)"><i class="bx bx-edit"></i></button><button class="btn btn-sm btn-outline-secondary" onclick="delActivity('.$row['id'].')"><i class="bx bxs-trash"></i></button></div>'; // Column for description with smaller text size
$data .= '</div>'; // Close the row div
$data .= '</div>'; // Close the list group item (div)

}

$data .= '</div>'; // Close the list group container

echo $data;




}