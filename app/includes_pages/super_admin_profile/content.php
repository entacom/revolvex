<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");

$company_id=1;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'company') {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $company_id);
    $statement->execute();
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    if ($data) {
  	$output = '

  <section class="section profile">
      <div class="row">
        <div class="col-xl-8">
          <div class="card">
            <div class="card-body pt-3">
              <div class="tab-content pt-2">
                <div class="tab-pane fade show active profile-overview" id="profile-overview">
                  <h5 class="card-title">Company Details</h5>
				  <div class="row mb-3">
                      <label for="profileImage" class="col-md-4 col-lg-3 col-form-label">Logo</label>
                      <div class="col-md-8 col-lg-9">
                       <img id="company_image" src="" alt="Company" class="rounded-circle">
                      </div>
                    </div>
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Address</div>
                    <div class="col-lg-9 col-md-8" id="company_address_text"></div>
                  </div>

                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Phone</div>
                    <div class="col-lg-9 col-md-8" id="company_phone_text"></div>
                  </div>

                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Email</div>
                    <div class="col-lg-9 col-md-8" id="company_email_text"></div>
                  </div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</section>';
				}
	echo $output;
}	
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'edit_profile') {
    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $company_id);
    $statement->execute();
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    if ($data) {
  	$output = '  <section class="section profile">
      <div class="row">
        <div class="col-xl-8">
          <div class="card">
            <div class="card-body pt-3">
              <div class="tab-content pt-2">
                <div class="tab-pane fade show active profile-overview" id="profile-overview">
                  <h5 class="card-title">Company Details</h5>
                    <div class="row mb-3">
                      <label for="company" class="col-md-4 col-lg-3 col-form-label">Company</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="company_name" type="text"  class="form-control" id="company_name">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">Address</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="address" type="text" class="form-control" id="company_address">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">Suburb</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="address" type="text" class="form-control" id="company_suburb">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">Postcode</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="address" type="text" class="form-control" id="company_postode">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Country" class="col-md-4 col-lg-3 col-form-label">Country</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="country" type="text"  class="form-control" id="company_country">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">State</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="address" type="text" class="form-control" id="company_state">
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="phone" type="text" class="form-control" id="company_phone">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="domain_name" class="col-md-4 col-lg-3 col-form-label">Domain Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="domain_name"  type="text" class="form-control" id="domain_name">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="Email" class="col-md-4 col-lg-3 col-form-label">Primary Email</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="email" type="email"  class="form-control" id="company_email">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Email" class="col-md-4 col-lg-3 col-form-label">Accounts Email</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="email" type="email_accounts" class="form-control" id="company_accounts_email">
                      </div>
                    </div>


                    <div class="text-center">
                      <button type="submit" class="btn btn-secondary" onclick="SaveProfile()">Save Changes</button>
                    </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</section>';
	}
echo $output;
}			

				
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'settings') {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $company_id);
    $statement->execute();
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    if ($data) {			
 	$output = '<section class="section profile">
      <div class="row">
        <div class="col-xl-8">
          <div class="card">
            <div class="card-body pt-3">
              <div class="tab-content pt-2">
                <div class="tab-pane fade show active profile-overview" id="profile-overview">
                  <h5 class="card-title">Settings</h5>
                    <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Email Notifications</label>
                      <div class="col-md-8 col-lg-9">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="changesMade" checked>
                          <label class="form-check-label" for="changesMade">
                            Changes made to your account
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="newProducts" checked>
                          <label class="form-check-label" for="newProducts">
                            Information on new products and services
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="proOffers">
                          <label class="form-check-label" for="proOffers">
                            Marketing and promo offers
                          </label>
                        </div>
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="securityNotify" checked disabled>
                          <label class="form-check-label" for="securityNotify">
                            Security alerts
                          </label>
                        </div>
                      </div>
                    </div>
                    <div class="text-center">
                      <button type="submit" class="btn btn-secondary" onclick="SaveSettings()">Save Changes</button>
                    </div>
                </div>
                <div class="tab-pane fade pt-3" id="profile-change-password">
                </div>
              </div><!-- End Bordered Tabs -->
            </div>
          </div>
        </div>
      </div>
    </section>
  ';
	}
	echo $output;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'subscribers') {
    $database = new Database();
    $conn = $database->connect();
	$subscription_total=0;
    try {
        $query = "SELECT * FROM tblCompany WHERE id != 1";
        $statement = $conn->prepare($query);
        $statement->execute();
        $rowCount = $statement->rowCount();
        if ($rowCount > 0) {
            $output = '<div class="card-body">
						<div class="row">
							<div class="col">
								<h5 class="card-title">Subscribers</h5>
							</div>
							<div class="col-auto mt-2">
									<button type="button" class="btn btn-sm btn-secondary" onclick="uniModal(\'AddUserModal\')">Add Subscriber</button>
							</div>
						</div>
						<div class="table-responsive">
							<table class="table table-hover">
								<thead>
									<tr>
										<th scope="col">Name</th>
										<th scope="col">Plan</th>
										<th scope="col">Due</th>
										<th scope="col">Renew</th>
										<th scope="col">Users</th>
										<th scope="col">Total</th>
										<th scope="col">Edit</th>
									</tr>
								</thead>
								<tbody>';
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
				//$subscription_count=countTableFieldX2('id','tblUsers','company_id',$row['id'],'active', 1);
				 $subscription_count= rand(3, 50);
				$subscription_total+= ($subscription_count*$row['subscription_amount']);
                $output .= '<tr>';
                $output .= '<td>' . $row['company_name'] . '</td>'; 
				$output .= '<td>' . $row['subscription_plan'] . '</td>'; 
				$output .= '<td>' . date_correct_dmy($row['subscription_renew']) . '</td>'; 
                $output .= '<td>$' . $row['subscription_amount'] . '</td>';
				$output .= '<td>' . $subscription_count . '</td>'; 
				$output .= '<td>$' . number_format($subscription_count * $row['subscription_amount'],2) . '</td>'; 
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="editUser(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>'; 
                $output .= '</tr>';
            }
			 $output .= '<tr>
			 <td></td>
			 <td></td>
			 <td></td>
			 <td></td>
			 <td></td>
			 <td colspan="6">$' . number_format($subscription_total,2) . '</td> 
			</tr>
             </tbody></table></div></div></div>';
            echo $output;
			
        } else {
            echo '<div class="card"><div class="card-body"><p>No users found</p></div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching users: ' . $e->getMessage() . '</p></div></div>';
    }

    $conn = null;
    exit; 
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'client_access') {
    $database = new Database();
    $conn = $database->connect();
    try {
        // Fetch users data from the database using PDO
        $query = "SELECT * FROM tblUsers WHERE company_id  = :company_id AND client_job_id > '0'"; // Change 'tblUsers' to your actual table name
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();

        if ($rowCount > 0) {
            $output = '<div class="card-body">
						<div class="row">
							<div class="col">
								<h5 class="card-title">Client Access List</h5>
							</div>
						<div class="col-auto mt-2">
							<i>Client access to projects are granted on the clients project page.</i>
						</div>	
						</div>
						<div class="table-responsive">
						<div class="col-auto mt-2">
							<i>Client Access will be automatically removed after project completion.</i>
						</div>
							<table class="table table-hover">
								<thead>
									<tr>
										<th scope="col">Name</th>
										<th scope="col">Email</th>
										<th scope="col">Project</th>
										<th scope="col">Options</th>
									</tr>
								</thead>
								<tbody>';
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tr>';
                $output .= '<td>' . $row['first_lastname'] . '</td>'; // Adjust based on your actual columns
                $output .= '<td>' . $row['email'] . '</td>'; // Change 'email' to your actual column names
                $output .= '<td>' . getJobField('site_address', 'tblJobs', $row['client_job_id'], $_SESSION['session_company_id']) . '</td>';
				$output .= '<td><button class="btn btn-sm btn-secondary" onclick="editClient(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>';
                $output .= '</tr>';
            }
            $output .= '</tbody></table></div></div></div>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No users found</p></div></div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching users: ' . $e->getMessage() . '</p></div></div>';
    }
    exit; 
}
?>
