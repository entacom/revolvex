<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");

if (isset($_GET['user'])) {
    $user_id = $_SESSION['session_user_id'];
    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT username, first_lastname, email, mobile, job_position, timezone ,group_id FROM tblUsers WHERE id = :user_id"; 
    $statement = $conn->prepare($query);
    $statement->bindParam(':user_id', $user_id);
    $statement->execute();

    // Fetch the user data
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    // Display the fetched user data
    if ($user) {
        $output = '
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
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 label">Username (Login)</div>
                                        <div class="col-lg-9 col-md-8" id="username_text">'.$user['username'].'</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 label">Phone</div>
                                        <div class="col-lg-9 col-md-8" id="mobile_text">'.$user['mobile'].'</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 label">Email</div>
                                        <div class="col-lg-9 col-md-8" id="email_text">'.$user['email'].'</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 label">Position</div>
                                        <div class="col-lg-9 col-md-8" id="email_text">'.$user['job_position'].'</div>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-3 col-md-4 label">Security Level</div>
                                        <div class="col-lg-9 col-md-8" id="email_text">'.getTableColField('user_group', 'tblUsersGroups', 'group_id' ,$user['group_id']) .'</div>
                                    </div>
                                </div>
                                <div class="tab-pane fade profile-edit pt-3" id="profile-edit">
                                    <!-- Profile Edit Form -->
                                    <div class="row mb-3">
                                        <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Full Name</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="fullName" type="text" id="first_lastname" class="form-control" disabled  value="'.$user['first_lastname'].'">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="Job" class="col-md-4 col-lg-3 col-form-label">Job</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="job" type="text" class="form-control" id="job_position" value="'.$user['job_position'].'">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="phone" type="text" class="form-control" id="mobile" value="'.$user['mobile'].'">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="Email" class="col-md-4 col-lg-3 col-form-label">Email</label>
                                        <div class="col-md-8 col-lg-9">
                                            <input name="email" type="email" class="form-control" id="email" value="'.$user['email'].'">
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <label for="timezone" class="col-md-4 col-lg-3 col-form-label">Timezone</label>
                                        <div class="col-md-8 col-lg-9">
                                            <select name="timezone" class="form-control" id="timezone">';
                                            $timezones = DateTimeZone::listIdentifiers();
                                            foreach ($timezones as $timezone) {
                                                $selected = ($timezone == $user['timezone']) ? 'selected' : '';
                                                $output .= '<option value="'.htmlspecialchars($timezone).'" '.$selected.'>'.htmlspecialchars($timezone).'</option>';
                                            }
                                            $output .= '</select>
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
                                    <div class="d-flex justify-content-end">
                                       
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
            </div>
        </section>';
    }
    echo $output;
}
?>
