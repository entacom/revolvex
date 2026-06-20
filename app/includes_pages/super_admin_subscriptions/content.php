
<?php
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'subscribers') {
    $database = new Database();
    $conn = $database->connect();

    // Pagination settings
    $recordsPerPage = 20; // Number of records per page
    $currentPage = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Current page
    $offset = ($currentPage - 1) * $recordsPerPage; // Offset of the first record of the current page

    // Search query
    $searchQuery = isset($_POST['search']) ? $_POST['search'] : '';
    $searchSql = $searchQuery ? " AND (company_name LIKE :searchQuery OR company_email LIKE :searchQuery)" : '';

    try {
        // Count total number of records with search filter
        $countQuery = "SELECT COUNT(*) FROM tblCompany WHERE id != 1 $searchSql";
        $countStatement = $conn->prepare($countQuery);
        if ($searchQuery) {
            $searchTerm = "%$searchQuery%";
            $countStatement->bindParam(':searchQuery', $searchTerm, PDO::PARAM_STR);
        }
        $countStatement->execute();
        $totalRecords = $countStatement->fetchColumn();
        $totalPages = ceil($totalRecords / $recordsPerPage);

        // Query to fetch records for the current page with search filter
        $query = "SELECT * FROM tblCompany WHERE id != 1 $searchSql LIMIT :offset, :recordsPerPage";
        $statement = $conn->prepare($query);
        if ($searchQuery) {
            $statement->bindParam(':searchQuery', $searchTerm, PDO::PARAM_STR);
        }
        $statement->bindParam(':offset', $offset, PDO::PARAM_INT);
        $statement->bindParam(':recordsPerPage', $recordsPerPage, PDO::PARAM_INT);
        $statement->execute();

        // Output HTML for the table and records
        $output = '<div class="card-body">
                    <div class="row">
                        <div class="col">
                            <h5 class="card-title">Active Subscriptions</h5>
                        </div>
                        <div class="col-auto mt-2">
                            <button type="button" class="d-none d-md-block btn btn-sm btn-secondary" onclick="uniModal(\'AddSubscriberModal\')">Add Subscriber</button>
                        </div>
                    </div>
                    <div class="table-responsive">
                  <div class="mb-1">
						<div class="col-12 col-md-8 col-lg-6">
							<div class="input-group">
								<input type="text" id="searchInput" class="form-control" placeholder="Search for subscribers...">
								<button class="btn btn-outline-secondary" type="button" onclick="searchSubscribers()">
									<i class="bi bi-search"></i>
								</button>
							</div>
						</div>
					</div>
                   <table class="table table-hover">
                        <thead>
                            <tr >
                                <th >Name</th>
                                <th class="d-none d-md-table-cell">Email</th>
                                <th class="d-none d-md-table-cell">Renew</th>
                                <th >Edit</th>
                            </tr>
                        </thead>
                        <tbody>';

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $output .= '<tr>';
            $output .= '<td>' . htmlspecialchars($row['company_name']) . '</td>';
            $output .= '<td class="d-none d-md-table-cell">' . htmlspecialchars($row['company_email']) . '</td>'; 
            $output .= '<td class="d-none d-md-table-cell">' . date('d-m-Y', $row['subscription_renew']) . '</td>'; 
            $output .= '<td><button class="btn btn-sm btn-outline-secondary" onclick="EditSubscriber(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>'; 
            $output .= '</tr>';
        }

        $output .= '</tbody></table></div>';

        // Pagination controls
        $output .= '<div class="pagination">';
        if ($currentPage > 1) {
            $output .= '<button class="btn btn-sm btn-outline-secondary" onclick="Loadtab(\'subscribers\', ' . ($currentPage - 1) . ', \'' . $searchQuery . '\')">Previous</button>';
        }
        if ($currentPage < $totalPages) {
            $output .= '<button class="btn btn-sm btn-outline-secondary" onclick="Loadtab(\'subscribers\', ' . ($currentPage + 1) . ', \'' . $searchQuery . '\')">Next</button>';
        }
        $output .= '</div></div>';

        echo $output;
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching users: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit;
}




?>