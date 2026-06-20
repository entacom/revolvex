function Loadtab(tab_id) {
    $.ajax({
        type: "POST",
        url: "includes_pages/super_admin_profile/content.php",
        data: { tab_id: tab_id }, // Ensure 'tab_id' is sent correctly
        success: function (response) {
            // Display the returned data in the '#jobs_tab_body' element
            $('#tab_body').html(response);
				

            $('#success_message').text("Data received successfully");
            $('#site_message').removeClass('alert alert-danger');
			GetCompanyId()

        },
        error: function (xhr, status, error) {
            // Handle error response
            console.error("Error receiving data:", error);
            var errorMessage = xhr.responseText;
            $('#site_message').text(errorMessage);
            $('#site_message').addClass('alert alert-danger');
        }
	
    });

}
function GetCompanyId() {
    $.ajax({
        url: 'includes_pages/super_admin_profile/crud.php?read_company_profile',
        type: 'POST',
        data: { },
        dataType: 'json',
        success: function(data, status) {
            $.each(data, function(index, data) {
                $("#company_name").val(data.company_name);
				$("#company_address").val(data.company_address);
				$("#company_suburb").val(data.company_suburb);
				$("#company_state").val(data.company_state);
				$("#company_postode").val(data.company_postode);
				$("#company_country").val(data.company_country);
				$("#company_phone").val(data.company_phone);
				$("#company_email").val(data.company_email);
				$("#company_contact").val(data.company_contact);
				$("#company_accounts_email").val(data.company_accounts_email);
				$("#company_url").val(data.company_url);
				$("#domain_name").val(data.domain_name);
				$("#company_abn").val(data.company_abn);
				$("#company_acn").val(data.company_acn);
				$("#bank_account_name").val(data.bank_account_name);
				$("#bank_name").val(data.bank_name);
				$("#bank_branch").val(data.bank_branch);
				$("#bank_bsb").val(data.bank_bsb);
				$("#bank_account").val(data.bank_account);
				$("#subscription_plan").val(data.subscription_plan);
				$("#subscription_amount").val(data.subscription_amount+ ' Excluding Tax');
				$("#subscription_renew").val(data.subscription_renew);
				$("#subscription_commencement").val(data.subscription_commencement);
				$("#subscription_count").val(data.subscription_count);
                // Other operations with the received data
				$("#company_name_text").text(data.company_name);
                $("#company_address_text").text(data.company_address+', '+data.company_suburb+', '+data.company_state+', '+data.company_postode);
				$("#company_phone_text").text(data.company_phone);
				$("#company_email_text").text(data.company_email);
				$("#company_image").attr("src", data.company_image_path)
				
				$("#domain_name_user").val(data.domain_name);

            });
			console.log('Data received successfully.');
        },
        error: function(xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}
function SaveProfile() {
    var url = 'includes_pages/super_admin_profile/crud.php?update_profile'; 
    var company_name = $("#company_name").val();
	var company_address = $("#company_address").val();
    var company_suburb = $("#company_suburb").val();
    var company_state = $("#company_state").val();
    var company_postode = $("#company_postode").val();
    var company_country = $("#company_country").val();
    var company_phone = $("#company_phone").val();
    var company_email = $("#company_email").val();
    var company_contact = $("#company_contact").val();
    var company_accounts_email = $("#company_accounts_email").val();
	var company_url = $("#company_url").val();
    var domain_name = $("#domain_name").val();
    var company_abn = $("#company_abn").val();
    var company_acn = $("#company_acn").val();
    var bank_account_name = $("#bank_account_name").val();
    var bank_name = $("#bank_name").val();
    var bank_branch = $("#bank_branch").val();
    var bank_bsb = $("#bank_bsb").val();
    var bank_account = $("#bank_account").val();

    var postData = {

		company_name: company_name,
        company_address: company_address,
        company_suburb: company_suburb,
        company_state: company_state,
        company_postode: company_postode,
        company_country: company_country,
        company_phone: company_phone,
        company_email: company_email,
        company_contact: company_contact,
        company_accounts_email: company_accounts_email,
        company_url: company_url,
		domain_name: domain_name,
        company_abn: company_abn,
        company_acn: company_acn,
        bank_account_name: bank_account_name,
        bank_name: bank_name,
        bank_branch: bank_branch,
        bank_bsb: bank_bsb,
        bank_account: bank_account
    };

    $.ajax({
        url: url,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(data) {
            // Handle success response
            console.log('Profile successfully saved:', data);
            // You can add any additional actions upon successful save/update
        },
        error: function(xhr, status, error) {
            // Handle error if any
            console.error('Error occurred while saving profile:', error);
        }
    });
}

function GetSubscriber() {
    var csrf_token = $('#csrf_token').val(); // Retrieve CSRF token from the input field
    var url = 'includes_pages/super_admin_profile/crud.php?read_company_profile';
    var postData = {
		csrf_token: csrf_token
    };
    $.ajax({
        url: url,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(data) {
            $.each(data, function(index, data) {
                $("#company_name").val(data.company_name);
				$("#company_address").val(data.company_address);
				$("#company_suburb").val(data.company_suburb);
				$("#company_state").val(data.company_state);
				$("#company_postode").val(data.company_postode);
				$("#company_country").val(data.company_country);
				$("#company_phone").val(data.company_phone);
				$("#company_email").val(data.company_email);
				$("#company_contact").val(data.company_contact);
				$("#company_accounts_email").val(data.company_accounts_email);
				$("#company_url").val(data.company_url);
				$("#domain_name").val(data.domain_name);
				$("#company_abn").val(data.company_abn);
				$("#company_acn").val(data.company_acn);
				$("#bank_account_name").val(data.bank_account_name);
				$("#bank_name").val(data.bank_name);
				$("#bank_branch").val(data.bank_branch);
				$("#bank_bsb").val(data.bank_bsb);
				$("#bank_account").val(data.bank_account);
				
				$("#subscription_plan").val(data.subscription_plan);
				$("#subscription_amount").val(data.subscription_amount+ ' Excluding Tax');
				$("#subscription_renew").val(data.subscription_renew);
				$("#subscription_commencement").val(data.subscription_commencement);
				$("#subscription_count").val(data.subscription_count);
                // Other operations with the received data
				$("#company_name_text").text(data.company_name);
                $("#company_address_text").text(data.company_address+', '+data.company_suburb+', '+data.company_state+', '+data.company_postode);
				$("#company_phone_text").text(data.company_phone);
				$("#company_email_text").text(data.company_email);
				$("#company_image").attr("src", data.company_image_path)
				
				$("#domain_name_user").val(data.domain_name);

            });
			console.log(data)
        },
        error: function(xhr, status, error) {
            // Handle error if any
            console.error(error);
        }
    });
}

function editSubscriber() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_company/content.php?company", // URL for editing a user
        data: {  }, // Send the user ID to the server
        success: function(data, status) {
            $("#company_data").html(data);
		   	GetCompanyId()
        },
        error: function(xhr, status, error) {
            // Handle errors if any
            console.error("Error editing company:", error);
        }
    });
}

function editUser(user_id) {
	$("#edit_user_id").val(user_id);
    var csrf_token = $('#csrf_token').val(); // Retrieve CSRF token from the input field
    var url = 'includes_pages/admin_company/crud.php?read_user_profile';
    var postData = {
		user_id: user_id,
		csrf_token: csrf_token
    };
    $.ajax({
        url: url,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(data) {
            $.each(data, function(index, data) {
                $("#firstname").val(data.firstname);
				$("#lastname").val(data.lastname);
				$("#username").val(data.username);
				$("#password").val(data.password);
				$("#company_id").val(data.company_id);
				$("#email").val(data.email);
				$("#mobile").val(data.mobile);
				$("#job_position").val(data.job_position);
				$("#timezone").val(data.timezone);
				
				$("#username_text").text(data.username);
				$("#email_text").text(data.email);
				$("#mobile_text").text(data.mobile);
				$("#fullname_text").text(data.firstname+' '+data.lastname);
				$("#job_position_text").text(data.job_position);
				
				$("#user_image").attr("src", data.user_image_path)
				$('#ExtralargeModal').modal('show')
            });
			
			
        },
        error: function(xhr, status, error) {
            // Handle error if any
            console.error(error);
        }
    });
}


function AddUser() {
    var csrf_token = $('#csrf_token').val();
    var url = 'includes_pages/admin_company/crud.php?add_user';
    var firstname = $("#new_firstname").val();
    var lastname = $("#new_lastname").val();
    var username = $("#new_username").val();
    var domain_name_user = $("#domain_name_user").val();
    var email = $("#new_email").val();
    var mobile = $("#new_mobile").val();
    var job_position = $("#new_job_position").val();
    var timezone = $("#new_timezone").val();
	
	if (
        firstname === '' ||
        lastname === '' ||
        username === '' ||
        domain_name_user === '' ||
        email === '' ||
        mobile === '' ||
        job_position === '' ||
        timezone === ''
		) {
			$("#fieldsError").removeClass('d-none');
			return; // Prevent further execution of the function
		} else {
			$("#fieldsError").addClass('d-none');
		}
	
    var postData = {
        csrf_token: csrf_token,
        firstname: firstname,
        lastname: lastname,
        username: username,
        domain_name_user: domain_name_user,
        email: email,
        mobile: mobile,
        job_position: job_position,
        timezone: timezone
    };
    
    $.ajax({
        url: url,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(data) {
            console.log('Profile successfully saved:', data);
            if (data.error && data.error === 'Username already exists') {
                $("#usernameError").text(data.error).removeClass('d-none');
                return; // Exit function without closing modal for this specific error
            }
            // If success or other errors, close the modal
            $("#AddUserModal").modal("hide");
            Loadtab('users_list'); // Load content after successful insertion
        },
        error: function(xhr, status, error) {
            console.error('Error occurred while saving profile:', error);
            console.log('XHR Response:', xhr.responseText); // Log the response text for further analysis
            
            // Handle other error scenarios here
            // You can decide whether to close the modal or not
            // For example, you might want to close the modal for all other errors
            $("#AddUserModal").modal("hide");
        }
    });
}

function SaveUser() {
    var csrf_token = $('#csrf_token').val();
    var user_id = $('#edit_user_id').val();
    var url = 'includes_pages/admin_company/crud.php?update_user';
    var firstname = $("#firstname").val();
    var lastname = $("#lastname").val();
    var email = $("#email").val();
    var mobile = $("#mobile").val();
	var job_position = $("#job_position").val();
    var timezone = $("#timezone").val();

    var postData = {
        csrf_token: csrf_token,
        user_id: user_id,
        firstname: firstname,
        lastname: lastname,
        email: email,
        mobile: mobile,
		job_position: job_position,
        timezone: timezone
    };
    $.ajax({
        url: url,
        type: 'POST',
        data: postData,
        dataType: 'json',
        success: function(data) {
            console.log('Profile successfully saved:', data);
            Loadtab('users_list')
        },
        error: function(xhr, status, error) {
            console.error('Error occurred while saving profile:', error);
            // Handle error messages or notify the user
        }
    });
}

function changePassword() {
	var user_id = $('#edit_user_id').val();
    var newPassword1 = document.getElementById("newpassword_1").value;
    var newPassword2 = document.getElementById("newpassword_2").value;
    var passwordMessage = document.getElementById("passwordMessage");

    // Regular expression to validate password with your defined criteria
    var passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=!])(?=.*[^\w\d\s])[a-zA-Z0-9@#$%^&+=!]{8,}$/;

    if (!passwordRegex.test(newPassword1)) {
        passwordMessage.textContent = "Password must meet the specified complexity requirements.";
    } else if (newPassword1 !== newPassword2) {
        passwordMessage.textContent = "Passwords do not match.";
    } else {
        passwordMessage.textContent = ""; // Clear previous messages

        // Perform AJAX call to update the password
        var url = 'includes_pages/admin_company/crud.php?update_user_password';
        $.ajax({
            url: url,
            type: 'POST',
            data: { user_id:user_id, newPassword1: newPassword1}, // Send the new password
            dataType: 'json',
            success: function(data) {
                console.log('Password successfully saved:', data);
                // Handle success message or perform additional actions
            },
           error: function(xhr, status, error) {
				console.error('Error occurred while saving profile:', error);
				console.log('XHR Response:', xhr.responseText); // Log the response text for further analysis
				// Handle error messages or notify the user based on the server response
			}

        });
    }
}

$(document).ready(function() {
 
	Loadtab('company')
	
});



	
