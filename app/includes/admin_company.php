 <? if (in_array($_SESSION['session_group_id'], [11, 12, 13])) {  ?>
<main id="main" class="main">
	   <div class="card border-0">
        <div class="card-body">
            <h4 class="card-title" id="company_name_text"></h4>

            <!-- Styled Tabs with Boxicons and Text -->
            <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="company-tab" data-bs-toggle="tab" onclick="Loadtab('company')" role="tab" aria-controls="company" aria-selected="true">
                        <i class='bx bxs-home'></i> Home
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="profile-tab" data-bs-toggle="tab" onclick="Loadtab('profile')" role="tab" aria-controls="profile" aria-selected="false">
                        <i class='bx bxs-task'></i> Company
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="settings-tab" data-bs-toggle="tab" onclick="Loadtab('settings')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> Settings
                    </a>
                </li>
				<li class="nav-item" role="presentation">
                    <a class="nav-link" id="banking-tab" data-bs-toggle="tab" onclick="Loadtab('banking')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> Banking
                    </a>
                </li>
				<li class="nav-item" role="presentation">
                    <a class="nav-link" id="accounting-tab" data-bs-toggle="tab" onclick="Loadtab('accounting')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> Accounting
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="users_list-tab" data-bs-toggle="tab" onclick="Loadtab('users_list')" role="tab" aria-controls="activity" aria-selected="false">
                        <i class='bx bx-history'></i> Users
                    </a>
                </li>

            </ul>
            <div id="tab_body"></div>
        </div>
    </div>
	<div id="company_data"></div>
</main>
  
<div class="modal fade" id="userProfileModal" tabindex="-1">
     <div class="modal-dialog modal-lg">
      <div class="modal-content">

		<div class="modal-body">
       <h1>Profile</h1>
	<input type="hidden" id="edit_user_id" >
    <section class="section profile">
      <div class="row">
        <div class="col-xl-12">
          <div class="card">
            <div class="card-body pt-3">
              <!-- Bordered Tabs -->
              <ul class="nav nav-tabs nav-tabs-bordered">

                <li class="nav-item">
                  <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#profile-about">About</button>
                </li>

                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-edit">Edit Profile</button>
                </li>

                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-settings">Settings</button>
                </li>

                <li class="nav-item">
                  <button class="nav-link" data-bs-toggle="tab" data-bs-target="#profile-change-password">Change Password</button>
                </li>

              </ul>
              <div class="tab-content pt-2">

                <div class="tab-pane fade show active profile-about" id="profile-about">
                  <h5 class="card-title">About</h5>
                  <div class="card">
					<div class="card-body profile-card pt-4 d-flex flex-row align-items-center">
						<div>
							<!-- Name and Job Position -->
							<h2 id="fullname_text"></h2>
							<h3 id="job_position_text"></h3>
						</div>
						<div class="ms-auto">
							<!-- Image -->
							<img id="user_image" alt="Profile" style="max-width: 40%;">
						</div>
					</div>
				</div>
				<div class="row">
                    <div class="col-lg-3 col-md-4 label">Username (Login)</div>
                    <div class="col-lg-9 col-md-8" id="username_text"></div>
                  </div>	
                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Phone</div>
                    <div class="col-lg-9 col-md-8" id="mobile_text"></div>
                  </div>

                  <div class="row">
                    <div class="col-lg-3 col-md-4 label">Email</div>
                    <div class="col-lg-9 col-md-8" id="email_text"></div>
                  </div>
					 <div class="d-flex justify-content-end">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
				</div>
                </div>

                <div class="tab-pane fade profile-edit pt-3" id="profile-edit">

                    <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="fullName" type="text" id="first_lastname" class="form-control" >
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="Job" class="col-md-4 col-lg-3 col-form-label">Job</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="job" type="text" class="form-control" id="job_position">
                      </div>
                    </div>
                    <div class="row mb-3">
                      <label for="Job" class="col-md-4 col-lg-3 col-form-label">Security Group</label>
                      <div class="col-md-8 col-lg-9">
                        <select id="security_group" class="form-control form-control"></select>
                        <input name="security_group_id" type="hidden" class="form-control" id="security_group_id" >
                      </div>
                    </div>



                    <div class="row mb-3">
                      <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="phone" type="text" class="form-control" id="mobile" >
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="email" type="email" class="form-control" id="email" >
                      </div>
                    </div>
					<div class="row mb-3">
					<label for="timezone" class="col-md-4 col-lg-3 col-form-label">Timezone</label>
					<div class="col-md-8 col-lg-9">
						<select name="timezone" class="form-control" id="timezone">
							<?php 
							$timezones = DateTimeZone::listIdentifiers();
							foreach ($timezones as $timezone): ?>
								<option value="<?php echo htmlspecialchars($timezone); ?>">
									<?php echo htmlspecialchars($timezone); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>



				<div class="d-flex justify-content-end">
					<button type="submit" class="btn btn-outline-secondary me-2" onclick="SaveUser()">Save</button>
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
				</div>

          
                </div>

                <div class="tab-pane fade pt-3" id="profile-settings">
                    <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Email Notifications</label>
                      <div class="col-md-8 col-lg-9">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="changesMade"  disabled>
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
						  <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="securityNotify"  disabled>
                          <label class="form-check-label" for="securityNotify">
                            Security alerts
                          </label>
						  </div><br>
							<br>
						<div class="me-0">
							<button type="submit" class="btn btn-sm btn-outline-danger me-2" onclick="DisableCompanyUser()">Disable User Subscription</button>
						  </div>
						<div class="me-0">
							<button type="submit" class="btn btn-sm btn-outline-success me-2" onclick="EnableCompanyUser()">Enable User Subscription</button>
						  </div>  
                      </div>
                    </div>

					<div class="d-flex justify-content-end">
				
					<button type="submit" class="btn btn-outline-secondary me-2" onclick="SaveUserX()">Save</button>
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
				</div>
                </div>    


				<div class="tab-pane fade pt-3" id="profile-change-password">
					<div class="row mb-3">
						<label for="newPassword" class="col-md-4 col-lg-3 col-form-label">New Password</label>
						<div class="col-md-8 col-lg-9">
							<input name="newpassword_1" type="password" class="form-control" id="newpassword_1">
						</div>
					</div>

					<div class="row mb-3">
						<label for="renewPassword" class="col-md-4 col-lg-3 col-form-label">Re-enter</label>
						<div class="col-md-8 col-lg-9">
							<input name="newpassword_2" type="password" class="form-control" id="newpassword_2">
							<div id="passwordMessage" class="text-danger"></div> <!-- Div for password messages -->
						</div>
					</div>

					<!-- Password requirements section -->
					<div class="row mb-3">
						<div class="col-md-12">
							<p>Password requirements:</p>
							<ul>
								<li>Must be at least 8 characters long.</li>
								<li>Must contain at least one digit (0-9).</li>
								<li>Must include at least one lowercase letter (a-z).</li>
								<li>Must include at least one uppercase letter (A-Z).</li>
								<li>Must contain at least one special character from the following set: @#$%^&+=!</li>
								<li>Should not contain spaces or non-ASCII characters.</li>
							</ul>
						</div>
					</div>
					<!-- End of Password requirements section -->
				

					<div class="d-flex justify-content-end">
					<button type="submit" class="btn btn-outline-secondary me-2" onclick="changePassword()">Change</button>
					<button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
				</div>
				
              </div><!-- End Bordered Tabs -->
            </div>
          </div>
        </div>
      </div>
    </section>
     </div>
   </div>
  </div>
</div><!-- End Extra Large Modal-->

<div class="modal fade" id="AddUserModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Add User</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                     <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">First Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="fullName" type="text" id="new_firstname" class="form-control" >
                      </div>
                    </div>
					 <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Last Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="fullName" type="text" id="new_lastname" class="form-control" >
                      </div>
                    </div>	
	
	
					<div class="input-group mb-3">
						 <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Username</label>
                      <input type="text" class="form-control"   disabled id="new_username" aria-label="new_username">
                      <span class="input-group-text">@</span>
                      <input type="text" class="form-control" disabled id="domain_name_user" aria-label="domain_name_user">
                    </div>
						
                    <div class="row mb-3">
                      <label for="Job" class="col-md-4 col-lg-3 col-form-label">Job Description</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="job" type="text" class="form-control" id="new_job_position">
                      </div>
                    </div>
					<div class="row mb-3">
                      <label for="Job" class="col-md-4 col-lg-3 col-form-label">Access Level</label>
                      <div class="col-md-8 col-lg-9">
                        <select name="project_consultant" id="user_access_level" class="form-control">             
                        </select>
						<input type="hidden" id="user_access_level_id">
                      </div>
                    </div>	


					
                    <div class="row mb-3">
                      <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Timezone</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="timezone" disabled type="text" class="form-control" id="new_timezone" value="Australia/Hobart" >
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="phone" type="text" class="form-control" id="new_mobile" >
                      </div>
                    </div>

                    <div class="row mb-3">
                      <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="email" type="email" class="form-control" id="new_email" >
                      </div>
                    </div>
                    </div>
					  <!-- Inside your modal -->
					<div class="alert alert-danger d-none" role="alert" id="fieldsError">
						Please fill in all fields.
					</div>

					  <!-- Inside your modal -->
						<div id="modal_message"></div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-outline-secondary" onclick="AddUser() ">Add User</button>
                    </div>
                  </div>
                </div>
</div><!-- End Large Modal-->

<div class="modal fade" id="client_access_modal" tabindex="-1">
                <div class="modal-dialog modal-sm">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Edit Access</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
					  <input type="hidden"  id="edit_client_id" >
                    <div class="modal-body">
						 <button type="button" class="btn btn-outline-secondary" onclick="RevokeUser() ">Revoke Access</button>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>

					 
                    </div>
                  </div>
                </div>
</div><!-- End Large Modal-->
<script type="text/javascript" src="includes_pages/admin_company/script.js?n=<? echo date('h:i');?>"></script> 
<?
    }
?>