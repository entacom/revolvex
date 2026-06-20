<style>

</style>
<?php
// Assuming you have a valid PDO connection object named $conn
$database = new Database();
$conn = $database->connect();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['query'])) {
    $search_input = trim($_POST['query']);
    $search_input = htmlspecialchars($search_input);

    if (!empty($search_input)) {
        $query = "SELECT job_id,site_address, site_suburb, primary_contact FROM tblJobs WHERE site_address LIKE :search OR site_suburb LIKE :search OR primary_contact LIKE :search";
        $result = $conn->prepare($query);

        $search_term = "%$search_input%";
        $result->bindParam(':search', $search_term, PDO::PARAM_STR);

        if ($result) {
            $result->execute();

            // Display search results in a table within the main tag
            echo '<main id="main" class="main">
                    <div class="abh-table-responsive">
                        <table class="abh-table">
                            <thead>
                                <tr >
                                    <th>Site Address</th>
                                    <th>Site Suburb</th>
                                    <th>Primary Contact</th>
                                </tr>
                            </thead>
                            <tbody>';

            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr onclick="redirectTo(\'p=admin_jobs&job_id='.$row['job_id'].'\')">
                        <td>' . htmlspecialchars($row['site_address']) . '</td>
                        <td>' . htmlspecialchars($row['site_suburb']) . '</td>
                        <td>' . htmlspecialchars($row['primary_contact']) . '</td>
                      </tr>';
            }

            echo '</tbody></table>
                  </div>
                </main>';
			// JavaScript function to redirect to admin_jobs
           
        } else {
            echo 'Error preparing the query: ' . $conn->errorInfo()[2];
        }
    } else {
        echo 'Please enter a search query.';
    }
}
?>
