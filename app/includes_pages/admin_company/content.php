<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
include("../../includes/common.php");



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'company') {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
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
                      <label for="profileImage" class="col-md-4 col-lg-3 col-form-label"></label>
                      <div class="col-md-8 col-lg-9">
                       <img id="company_image" src="" alt="Company" >
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'profile') {
    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
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
                        <input name="company_name" type="text" disabled class="form-control" id="company_name">
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
                        <input name="address" type="number" class="form-control" id="company_postcode">
                      </div>
                    </div>
					<div class="row mb-3">
                      <div class="row mb-3">
						<label for="timezone" class="col-md-4 col-lg-3 col-form-label">Country</label>
						<div class="col-md-8 col-lg-9">
						<select name="timezone" class="form-control" id="company_country">';

						$timezones = DateTimeZone::listIdentifiers();
						foreach ($timezones as $timezone) {
							$output .= '<option value="' . htmlspecialchars($timezone) . '">';
							$output .= htmlspecialchars($timezone);
							$output .= '</option>';
						}
						$output .= '</select>
						</div>
						</div>
					<div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">State</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="address" type="text" class="form-control" id="company_state">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">Date Format</label>
                      <div class="col-md-8 col-lg-9">
                        <select class="form-control"  name="company_date_format" id="company_date_format">
							<option value="d-m-Y">dd-mm-yyyy</option>
							<option value="m-d-Y">mm-dd-yyyy</option>
							<option value="Y-m-d">yyyy-mm-dd</option>
						</select>
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="phone" type="number" class="form-control" id="company_phone">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="domain_name" class="col-md-4 col-lg-3 col-form-label">Domain Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="domain_name"  type="text" class="form-control" disabled id="domain_name">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="company_contact" class="col-md-4 col-lg-3 col-form-label">Company Contact</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="company_contact" type="text" class="form-control" id="company_contact">
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'banking') {
    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
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
                  <h5 class="card-title">Banking Details</h5>
                    <div class="row mb-3">
                      <label for="bank_account_name" class="col-md-4 col-lg-3 col-form-label">Account Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="bank_account_name" type="text"  class="form-control" id="bank_account_name">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="bank_name" class="col-md-4 col-lg-3 col-form-label">Bank</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="bank_name" type="text" class="form-control" id="bank_name">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="bank_branch" class="col-md-4 col-lg-3 col-form-label">Branch</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="bank_branch" type="text" class="form-control" id="bank_branch">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="bank_bsb" class="col-md-4 col-lg-3 col-form-label">BSB No.</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="bank_bsb" type="text" class="form-control" id="bank_bsb">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="bank_account" class="col-md-4 col-lg-3 col-form-label">Account No.</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="bank_account" type="text"  class="form-control" id="bank_account">
                      </div>
                    </div>
                    <div class="text-center">
                      <button type="submit" class="btn btn-secondary" onclick="SaveBank()">Save Changes</button>
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'accounting') {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT * FROM tblAccounting WHERE company_id = :company_id"; // Assuming 'tblAccounting' is your table name and 'company_id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
    $statement->execute();
    $data = $statement->fetch(PDO::FETCH_ASSOC);
    if ($data) {
 
        if($data['xero_option']){
            $accounting_type="XERO";
            }
        else if($data['myob_option']){
        $accounting_type="MYOB";
        }

        $output = '<section class="section profile">
        <div class="row">
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-body pt-3">
                        <div class="tab-content pt-2">
                            <div class="tab-pane fade show active profile-overview" id="profile-overview">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">Accounting & Connection</h5>
                                    <div>
                                        <button type="submit" class="btn btn-secondary" onclick="redirectTo(\'p=admin_myob_connect\')">Setup MYOB</button>
                                        <button type="submit" class="btn btn-secondary" onclick="redirectTo(\'p=admin_xero_connect\')">Setup XERO</button>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label for="accounting_type" class="col-md-4 col-lg-3 col-form-label">Accounting Software </label>
                                    <div class="col-md-8 col-lg-9">
                                        <input name="accounting_type" type="text" class="form-control" disabled id="accounting_type" value="'.$accounting_type.'">
                                    </div>
                                </div>
                             <div class="row mb-3">
                                <label for="payable_account_name" class="col-md-4 col-lg-3 col-form-label">Payable Account Name</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="payable_account_name" type="text" class="form-control" id="payable_account_name">
                                    <input name="payable_account_code" type="hidden" class="form-control" id="payable_account_code">
                                </div>
                            </div>
                             <div class="row mb-3">
                                <label for="payable_account_tax" class="col-md-4 col-lg-3 col-form-label">Payable Account Tax</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="payable_account_tax" type="text" class="form-control" id="payable_account_tax">
                                    <input name="payable_account_code" type="hidden" class="form-control" id="payable_account_tax_code">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="receivable_account_name" class="col-md-4 col-lg-3 col-form-label">Receivable Account Name</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="receivable_account_name" class="form-control" id="receivable_account_name"/>
                                    <input name="receivable_account_code" type="hidden" class="form-control" id="receivable_account_code">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="receivable_account_name" class="col-md-4 col-lg-3 col-form-label">Receivable Account Tax</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="receivable_account_name" class="form-control" id="receivable_account_tax"/>
                                    <input name="receivable_account_code" type="hidden" class="form-control" id="receivable_account_tax_code">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label for="cash_sale_uid" class="col-md-4 col-lg-3 col-form-label">Default Cash Sale</label>
                                <div class="col-md-8 col-lg-9">
                                    <input name="customer_search" type="text" class="form-control" id="customer_search">
                                    <input name="customer_uid" type="hidden" class="form-control" id="customer_uid">
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-secondary" onclick="SaveAccounting()">Save Changes</button>
                            </div>
                        </div>
            </div>
        </div>
    </div>
</section>';

    }
    echo $output;
}
			
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'subscription') {
    $database = new Database();
    $conn = $database->connect();
    $query = "SELECT * FROM tblCompany WHERE id = :company_id"; // Assuming 'tblUsers' is your table name and 'id' is the column name
    $statement = $conn->prepare($query);
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
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
                  <h5 class="card-title">Subscription</h5>
                    <div class="row mb-3">
                      <label for="company" class="col-md-4 col-lg-3 col-form-label">Subscription </label>
                      <div class="col-md-8 col-lg-9">
                        <input name="company" type="text" disabled class="form-control" id="subscription_plan">
                      </div>
                    </div>


                    <div class="row mb-3">
					<label for="Country" class="col-md-4 col-lg-3 col-form-label">Amount(Monthly)</label>
					<div class="col-md-8 col-lg-9">
						<div class="input-group">
							<span class="input-group-text">$</span>
							<input name="country" type="text" disabled class="form-control" id="subscription_amount">
						</div>
					</div>
				</div>
                    <div class="row mb-3">
                      <label for="Address" class="col-md-4 col-lg-3 col-form-label">Renew</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="address" type="text" disabled class="form-control" id="subscription_renew">
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="subscription_commencement" class="col-md-4 col-lg-3 col-form-label">Commencement</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="subscription_commencement" type="text" disabled class="form-control" id="subscription_commencement">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="subscription_count" class="col-md-4 col-lg-3 col-form-label">Active Users</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="subscription_count" type="text" disabled class="form-control" id="subscription_count">
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
    $statement->bindParam(':company_id', $_SESSION['session_company_id']);
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
                          <input class="form-check-input" type="checkbox" id="changesMade" disabled >
                          <label class="form-check-label" for="changesMade">
                            Changes made to your account
                          </label>
                        </div>
                            <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="securityNotify"  disabled>
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'users_list') {
    $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT * FROM tblUsers WHERE company_id  = :company_id AND project_id = '0' AND group_id BETWEEN 11 AND 30";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();
        if ($rowCount > 0) {
            $output = '<div class="card-body">
						<div class="row">
							<div class="col">
								<h5 class="card-title">Company Active Users</h5>
							</div>
							<div class="col-auto mt-2">
									<button type="button" class="btn btn-sm btn-secondary" onclick="AddUserModal()">Add User</button>
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
				$user_locked = $row['active'] ? '' : '<button class="btn btn-sm btn-danger" title="User Login Disabled"><i class="bx bx-lock"></i></button>';

                $output .= '<tr>';
                $output .= '<td>' . $row['first_lastname']  . '</td>'; // Adjust based on your actual columns
                $output .= '<td>' . $row['email'] . '</td>'; // Change 'email' to your actual column names
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="editUser(' . $row['id'] . ')"><i class="bx bx-edit"></i></button> '.$user_locked.'</td>';
				
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

    $conn = null;
    exit; 
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'company_files') {
    $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT * FROM tblCompanyFiles WHERE company_id = :company_id";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();

        $output = '<div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">Company Files</h5>
            </div>
            <div class="row fw-bold border-bottom py-2 bg-light">
                <div class="col-6 col-md-4">Description</div>
                <div class="col-6 col-md-2">Edit</div>
                <div class="col-6 col-md-3">Download</div>
   
            </div>';

        while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $downloadUrl = generatePreSignedUrl($row['filename'], $row['description']);
            
            $output .= '<div class="row border-bottom py-2 hover-row">
                <div class="col-6 col-md-4">' . $row['description'] . '</div>
                <div class="col-6 col-md-2"><button class="btn btn-sm btn-secondary" onclick="editFile(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></div>
                <div class="col-6 col-md-3"><a href="' . htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') . '" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bx bxs-cloud-download"></i></a></div>

            </div>';
        }


        $output .= '</div>';
        echo $output;
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching files: ' . $e->getMessage() . '</p></div></div>';
    }
    $conn = null;
    exit;
}



if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'vendor_access') {
    $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT * FROM tblUsers WHERE company_id  = :company_id AND project_id = '0' AND group_id BETWEEN 31 AND 50 AND active = 1";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();
        if ($rowCount > 0) {
            $output = '<div class="card-body">
						<div class="row">
							<div class="col">
								<h5 class="card-title">Company Active Vendors</h5>
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
                $output .= '<td>' . $row['firstname'] . ' ' . $row['lastname'] . '</td>'; // Adjust based on your actual columns
                $output .= '<td>' . $row['email'] . '</td>'; // Change 'email' to your actual column names
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="editClientAccessModal(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>'; 
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

    $conn = null;
    exit; 
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'client_access') {
    $database = new Database();
    $conn = $database->connect();
    try {
        // Fetch users data from the database using PDO
        $query = "SELECT * FROM tblUsers WHERE company_id  = :company_id AND project_id > '0'"; // Change 'tblUsers' to your actual table name
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
                $output .= '<td>' . getJobField('site_address', 'tblProjects', $row['project_id'], $_SESSION['session_company_id']) . '</td>';
				$output .= '<td><button class="btn btn-sm btn-secondary" onclick="editClientAccessModal(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>';
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
 
