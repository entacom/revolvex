var crud_url ='includes_pages/admin_company/crud.php';
function Loadtab(tab_id) {
	
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_company/content.php",
        data: { tab_id: tab_id }, // Ensure 'tab_id' is sent correctly
        success: function (response) {
           console.log("LoadTab:" +tab_id) // Display the returned data in the '#jobs_tab_body' element
            $('#tab_body').html(response);
				

            $('#success_message').text("Data received successfully");
            $('#site_message').removeClass('alert alert-danger');
			if(tab_id=='company' ||tab_id=='profile' ){
                GetCompanyId()
			}
            if(tab_id=='accounting'){
               GetAccounting()
			}
            if(tab_id=='banking'){
               GetBanking()
			}
  
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

function DisableCompanyUser() {
	 var formData = {
		action: 'disable_user',
		user_id: $('#edit_user_id').val()
	    };
	    ajaxPostRequest(formData, crud_url);
        Loadtab('users_list')
        $('#userProfileModal').modal('hide')
}
function EnableCompanyUser() {
	 var formData = {
		action: 'enable_user',
		user_id: $('#edit_user_id').val()
	    };
	    ajaxPostRequest(formData, crud_url);
        Loadtab('users_list')
        $('#userProfileModal').modal('hide')
}
function RevokeUser() {
	 var formData = {
		action: 'revoke_user',
		user_id: $('#edit_client_id').val()
	    };
	var jsonData = JSON.stringify(formData);
    console.log(jsonData);
	$('#client_access_modal').modal('hide')
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function (response) {
             handleSuccess(response);
			 Loadtab('company')
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}
function SaveProfile() {
    var formData = {
		action: 'update_profile', 
        company_address: $("#company_address").val(),
        company_suburb: $("#company_suburb").val(),
        company_state: $("#company_state").val(),
        company_postcode: $("#company_postcode").val(),
        company_country: $("#company_country").val(),
		company_date_format: $("#company_date_format").val(),
        company_phone: $("#company_phone").val(),
        company_email: $("#company_email").val(),
        company_contact: $("#company_contact").val(),
        company_accounts_email: $("#company_accounts_email").val(),
    };
	    ajaxPostRequest(formData, crud_url);
}
function SaveBank() {
    var formData = {
		action: 'update_bank', 
        bank_account_name: $("#bank_account_name").val(),
        bank_name: $("#bank_name").val(),
        bank_branch: $("#bank_branch").val(),
        bank_bsb: $("#bank_bsb").val(),
        bank_account: $("#bank_account").val(),
        };
	    ajaxPostRequest(formData, crud_url);
}
function SaveAccounting() {
    var formData = {
		action: 'update_accounting', 
        payable_account_name: $("#payable_account_name").val(),
         payable_account_tax: $("#payable_account_tax").val(),
         payable_account_tax_code: $("#payable_account_tax_code").val(),
        receivable_account_name: $("#receivable_account_name").val(),
        payable_account_code: $("#payable_account_code").val(),
        receivable_account_code: $("#receivable_account_code").val(),
        
        receivable_account_tax: $("#receivable_account_tax").val(),
        receivable_account_tax_code: $("#receivable_account_tax_code").val(),
        
        cash_sale_customer: $("#customer_search").val(),
        cash_sale_uid: $("#customer_uid").val(),
    };
	    ajaxPostRequest(formData, crud_url); 
}
function GetCompanyId() {
    var formData = {
        action: 'read_company_profile',
    };
    var jsonData = JSON.stringify(formData);
    console.log(jsonData);
    
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0];
            $("#company_name").val(data.company_name);
            $("#company_address").val(data.company_address);
            $("#company_suburb").val(data.company_suburb);
            $("#company_state").val(data.company_state);
            $("#company_postcode").val(data.company_postcode);
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
            $("#subscription_amount").val(data.subscription_amount + ' Excluding Tax');
            $("#subscription_renew").val(data.subscription_renew);
            $("#subscription_commencement").val(data.subscription_commencement);
            $("#subscription_count").val(data.subscription_count);

            // Other operations with the received data
            $("#company_name_text").text(data.company_name);
            $("#company_address_text").text(data.company_address + ', ' + data.company_suburb + ', ' + data.company_state + ', ' + data.company_postode);
            $("#company_phone_text").text(data.company_phone);
            $("#company_email_text").text(data.company_email);
            $("#company_image").attr("src", data.company_image_path);
            $("#domain_name_user").val(data.domain_name);
            

        },
        error: function(xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}
function GetAccounting() {
    var formData = {
        action: 'read_company_profile',
    };
    var jsonData = JSON.stringify(formData);
    console.log(jsonData);
    
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0];
            // Accounting script loading
            if (data.accounting_script === 'myob') {
                loadScript('/includes_pages/admin_accounting/myob_functions.js');
            } else if (data.accounting_script === 'xero') {
                loadScript('/includes_pages/admin_accounting/xero_functions.js');
            }
            $("#payable_account_name").val(data.payable_account_name);
            $("#payable_account_code").val(data.payable_account_code);
            
            $("#payable_account_tax").val(data.payable_account_tax);
            $("#payable_account_tax_code").val(data.payable_account_tax_code);
            
            $("#receivable_account_name").val(data.receivable_account_name);
            $("#receivable_account_code").val(data.receivable_account_code); // Set the UID in the dropdown
            
            $("#receivable_account_tax").val(data.receivable_account_tax);
            $("#receivable_account_tax_code").val(data.receivable_account_tax_code); // Set the UID in the dropdown

            $("#customer_search").val(data.cash_sale_customer);
            $("#customer_uid").val(data.cash_sale_uid);
        },
        error: function(xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}

function GetBanking() {
    var formData = {
        action: 'read_company_profile',
    };
    var jsonData = JSON.stringify(formData);
    console.log(jsonData);
    
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0];
            $("#bank_account_name").val(data.bank_account_name);
            $("#bank_name").val(data.bank_name);
            $("#bank_branch").val(data.bank_branch);
            $("#bank_bsb").val(data.bank_bsb);
            $("#bank_account").val(data.bank_account);

        },
        error: function(xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}
function editUser(user_id) {
    // Prepare the data to be sent
    var formData = {
        action: 'read_user_profile',
        user_id: user_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            //console.log("Received response:", response);
                var data = response[0];
                //console.log("User Data:", userData);
				$("#edit_user_id").val(user_id);
                // Populate the form fields with the received data
                $("#first_lastname").val(data.first_lastname);
                $("#username").val(data.username);
                $("#company_id").val(data.company_id);
                $("#email").val(data.email);
                $("#mobile").val(data.mobile);
                $("#job_position").val(data.job_position);
                $("#timezone").val(data.timezone);
            
                $("#security_group_id").val(data.security_group_id);
                populateDropdown('#security_group', data.security_group, 'get_access_levels', 'id', 'user_group');
                 $('#security_group').change(function() {
                    var selectedSecGroupValue = $(this).val();
                    $('#security_group_id').val(selectedSecGroupValue);
                });
            
            
                
                // Update text fields
                $("#username_text").text(data.username);
                $("#email_text").text(data.email);
                $("#mobile_text").text(data.mobile);
                $("#fullname_text").text(data.first_lastname);
                $("#job_position_text").text(data.job_position);
                $("#user_image").attr("src", data.user_image_path);
                $('#userProfileModal').modal('show');

				},
				error: function(xhr, status, error) {
				   handleError(xhr, status, error)
				}
    });
}




function AddUserModal() {
	$('#AddUserModal').modal('show');
	populateDropdown('#user_access_level', '', 'get_access_levels', 'id', 'user_group');
	$('#user_access_level').change(function() {
		var selectedConsultantValue = $(this).val();
		$('#user_access_level_id').val(selectedConsultantValue);
		});

}
function editClientAccessModal(id) {
	$("#edit_client_id").val(id);
	$('#client_access_modal').modal('show');
}


function AddUser() {
    var formData = {
        'action': 'add_user', 
        firstname: $("#new_firstname").val(),
        lastname: $("#new_lastname").val(),
        username: $("#new_username").val(),
        domain_name_user: $("#domain_name_user").val(),
        email: $("#new_email").val(),
        mobile: $("#new_mobile").val(),
        job_position:  $("#new_job_position").val(),
		user_access_level_id:  $("#user_access_level_id").val(),
        timezone: $("#new_timezone").val()
    };
    var jsonData = JSON.stringify(formData);
    console.log(jsonData);

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function (response) {
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.success) {
                handleSuccess(parsedResponse.message);
                $('#AddUserModal').modal('hide');
                Loadtab('users_list');
                // Call EmailNotifyUserAdded with the new user ID
                EmailNotifyUserAdded(parsedResponse.newUserId,parsedResponse.tempPassword);
				
            } else {
                // Handle the error case
                handleError(null, null, parsedResponse.message);
            }
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}

function EmailNotifyUserAdded(userId,TempPass) {
    $.ajax({
        type: "POST",
        url: 'includes/common_mail.php?notify_user_added',
        data: { userId: userId,TempPass: TempPass }, // Send the new user ID
        success: function (response) {
            handleSuccess(response);
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}


function SaveUser() {
    var formData = {
		'action': 'update_user', 
		'user_id': $('#edit_user_id').val(),
		'first_lastname': $('#first_lastname').val(),
		'email': $('#email').val(),
		'mobile': $('#mobile').val(),
		'job_position': $('#job_position').val(),
        'security_group_id': $('#security_group_id').val(),
		'timezone': $('#timezone').val(),
    };
    ajaxPostRequest(formData, crud_url);
    Loadtab('users_list')
    $('#userProfileModal').modal('hide')
}
function changePassword() {
    var newPassword1 = document.getElementById("newpassword_1").value;
    var newPassword2 = document.getElementById("newpassword_2").value;
    var passwordMessage = document.getElementById("passwordMessage");
    var passwordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[@#$%^&+=!])(?=.*[^\w\d\s])[a-zA-Z0-9@#$%^&+=!]{8,}$/;
    if (!passwordRegex.test(newPassword1)) {
        passwordMessage.textContent = "Password must meet the specified complexity requirements.";
    } else if (newPassword1 !== newPassword2) {
        passwordMessage.textContent = "Passwords do not match.";
    } else {
        passwordMessage.textContent = ""; // Clear previous messages

	 var formData = {
		'action': 'update_user_password', 
		'user_id': $('#edit_user_id').val(),
		'newPassword1':newPassword1,

    };
    var jsonData = JSON.stringify(formData);
	console.log(jsonData)
        $.ajax({
            url: crud_url,
            type: 'POST',
            data: jsonData,
        	contentType: "application/json",
            success: function (response) {
			$('#userProfileModal').modal('hide')	 
             handleSuccess(response);
				},
			error: function (xhr, status, error) {
				handleError(xhr, status, error)
				}

        });
    }
}


// Function to generate and set the username based on first and last names
document.addEventListener("DOMContentLoaded", function() {
function generateUsername() {
    var firstName = document.getElementById("new_firstname").value.trim().toLowerCase();
    var lastName = document.getElementById("new_lastname").value.trim().toLowerCase();
	var domain_name_user= document.getElementById("domain_name_user").value.trim().toLowerCase();
    var generatedUsername = firstName + '.' + lastName;
    document.getElementById("new_username").value = generatedUsername;
	//document.getElementById("new_email").value = generatedUsername+'@'+domain_name_user;
}

	document.getElementById("new_firstname").addEventListener("keyup", generateUsername);
	document.getElementById("new_lastname").addEventListener("keyup", generateUsername);

});


$(document).ready(function() {
   //GetCompanyId();
	Loadtab('company')

});



	