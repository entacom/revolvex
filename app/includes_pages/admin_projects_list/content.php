<? 
session_start();
include("../../includes/common.php");
if (isset($_GET['recent_projects'])) {
  $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT * FROM tblJobs WHERE company_id = :company_id LIMIT 20"; // Change 'tblUsers' to your actual table name
        $result = $conn->prepare($query);
		 $result->bindParam(':company_id', $_SESSION['session_company_id']); 
        $result->execute();
        $rowCount = $result->rowCount();
        if ($rowCount > 0) {
            // Build the table HTML
            $output = '<div class="card">
                            <div class="card-body">
							<h5 class="card-title">PROJECTS</h5>
                                <table class="table table-hover">
                                    <thead>
                                    </thead>
                                    <tbody>';
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tr onclick="redirectToJob(' . $row['job_id'] . ')">';
                $output .= '<td>' . $row['site_address'] . ', ' . $row['site_suburb'] . '</td>'; // Adjust based on your actual columns
                $output .= '<td class=" d-none d-lg-block">' . $row['primary_contact'] . '</td>'; // Change 'email' to your actual column names
                // Add more columns if needed
                $output .= '</tr>';
            }

            $output .= '</tbody></table></div></div>';

            // Return the table HTML
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No users found</p></div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching users: ' . $e->getMessage() . '</p></div></div>';
    }
}