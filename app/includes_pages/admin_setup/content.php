<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'tab_budget') {
    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT * FROM tblCompany WHERE id = :company_id";
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
    $statement->execute();
    $data = $statement->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        $output = '<section class="section profile">
          <div class="row">
            <div class="col-xl-12">
              <div class="card shadow-sm">
                <div class="card-body pt-3">
                 Reserved Area
                </div>
              </div>
            </div>
          </div>
        </section>';

        $query = "SELECT * FROM tblBudgetBuild WHERE company_id  = :company_id";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            $output .= '<section class="section profile mt-4">
              <div class="row">
                <div class="col-xl-12">
                  <div class="card shadow-sm">
                    <div class="card-body">
                      <div class="row">
                        <div class="col">
                          <h5 class="card-title">Default Budget Menu Items</h5>
						  
                        </div>
						<div class="col-auto mt-2">
							<button type="button" class="btn btn-sm btn-secondary" onclick="AddVendorModal()">Add Menu Item</button>
						</div>
                      </div>
                      <div class="table-responsive">
                        <table class="table table-hover sortable">';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tbody class="parent-group" data-group-id="' . $row['id'] . '">';
                $output .= '<tr>';
                $output .= '<td><b>' . $row['description'] . '</b> <button class="btn btn-sm btn-outline-secondary" onclick="editClientAccessModal(' . $row['id'] . ')"><i class="bx bx-plus"></i></button></td>';
                $output .= '<td><button class="btn btn-sm btn-outline-secondary" onclick="editClientAccessModal(' . $row['id'] . ')"><i class="bx bx-edit"></i></button> <button class="btn btn-sm btn-outline-secondary" onclick="editClientAccessModal(' . $row['id'] . ')"><i class="bx bx-trash"></i></button></td>'; 
 
                $output .= '</tr>';

                $query_sub = "SELECT * FROM tblBudgetBuildSub WHERE budget_build_id = :budget_build_id ORDER BY ordering";
                $statement_sub = $conn->prepare($query_sub);
                $statement_sub->bindParam(':budget_build_id', $row['id']);
                $statement_sub->execute();

                while ($row_sub = $statement_sub->fetch(PDO::FETCH_ASSOC)) {
                    $output .= '<tr class="sortable-sub" data-id="' . $row_sub['id'] . '">';
                    $output .= '<td class="pl-4">- ' . $row_sub['description'] . '</td>';
                    $output .= '<td colspan="2"><button class="btn btn-sm btn-outline-secondary" onclick="editClientAccessModal(' . $row_sub['id'] . ')"><i class="bx bx-edit-alt"></i></button> <button class="btn btn-sm btn-outline-secondary" onclick="editClientAccessModal(' . $row_sub['id'] . ')"><i class="bx bx-trash"></i></button></td>'; 
                    $output .= '</tr>';
                }
                $output .= '</tbody>'; // Close the group
            }

            $output .= '</table></div></div></div></div></section>';
        } else {
            $output .= '<div class="alert alert-warning mt-4">No budget items found.</div>';
        }
    }
    echo $output;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'tab_trades') {
    $database = new Database();
    $conn = $database->connect();

    try {
        $query = "SELECT * FROM tblTrades WHERE company_id = :company_id ORDER BY trade_name";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();

        if ($rowCount > 0) {
            $output = '<section class="section profile mt-4">
              <div class="row">
                <div class="col-xl-12">
                  <div class="card shadow-sm">
                    <div class="card-body">
                      <div class="row">
                        <div class="col">
                          <h5 class="card-title">Company Trades</h5>
                        </div>
                        <div class="col-auto mt-2">
                          <button type="button" class="btn btn-sm btn-secondary" onclick="AddTradeModal()">Add Trade</button>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              <th scope="col">Name</th>
                              <th scope="col">Rate</th>    
                              <th scope="col">Edit</th>
                            </tr>
                          </thead>
                          <tbody>';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tr>';
                $output .= '<td>' . htmlspecialchars($row['trade_name']) . '</td>';
                $output .= '<td>' . number_format($row['rate'], 2) . '</td>';
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="editTrade(' . htmlspecialchars($row['id']) . ')"><i class="bx bx-edit"></i></button></td>'; 
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div></div></div></div></section>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No trades found</p></div>

                  </div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit; 
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'tab_budget_cat') {
    $database = new Database();
    $conn = $database->connect();

    try {
        $query = "SELECT * FROM tblBuildStages WHERE company_id = :company_id ORDER BY description";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();

        if ($rowCount > 0) {
            $output = '<section class="section profile mt-4">
              <div class="row">
                <div class="col-xl-12">
                  <div class="card shadow-sm">
                    <div class="card-body">
                      <div class="row">
                        <div class="col">
                          <h5 class="card-title">Company XXXX</h5>
                        </div>
                        <div class="col-auto mt-2">
                          <button type="button" class="btn btn-sm btn-secondary" onclick="AddTradeModal()">Add Trade</button>
                        </div>
                      </div>
                      <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              <th scope="col">Name</th> 
                              <th scope="col">Edit</th>
                            </tr>
                          </thead>
                          <tbody>';

            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tr>';
                $output .= '<td>' . htmlspecialchars($row['description']) . '</td>';
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="editTrade(' . htmlspecialchars($row['id']) . ')"><i class="bx bx-edit"></i></button></td>'; 
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div></div></div></div></section>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No trades found</p></div>

                  </div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit; 
}