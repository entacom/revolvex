var crud_url ='includes_pages/super_admin_subscriptions/crud.php';
function Loadtab(tab_id, page = 1 , searchQuery = '') { // 'page' parameter with default value 1
    $.ajax({
        type: "POST",
        url: "includes_pages/super_admin_subscriptions/content.php",
        data: { 
            tab_id: tab_id,
            page: page,
			search: searchQuery
        },
        success: function (response) {
            console.log("LoadTab:" + tab_id);
            $('#tab_body').html(response);
            $('#success_message').text("Data received successfully");
            $('#site_message').removeClass('alert alert-danger');
        },
        error: function (xhr, status, error) {
            console.error("Error receiving data:", error);
            var errorMessage = xhr.responseText;
            $('#site_message').text(errorMessage);
            $('#site_message').addClass('alert alert-danger');
        }
    });
}

function EditSubscriber(company_id) {
    var formData = {
        action: 'read_company_profile',
		company_id: company_id
    };
    var jsonData = JSON.stringify(formData);
	console.log(jsonData)
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
           console.log("response:", response);
			 $("#edit_company_id").val(company_id);
                var data = response[0];
			
                $("#edit_company_name").val(data.company_name);
				$("#edit_company_address").val(data.company_address);
				$("#edit_company_suburb").val(data.company_suburb);
				$("#edit_company_state").val(data.company_state);
				$("#edit_company_postode").val(data.company_postode);
				$("#edit_company_country").val(data.company_country);
				$("#edit_company_date_format").val(data.company_date_format);
				$("#edit_company_phone").val(data.company_phone);
				$("#edit_company_email").val(data.company_email);
				$("#edit_company_contact").val(data.company_contact);
				$("#edit_company_accounts_email").val(data.company_accounts_email);
				$("#edit_company_url").val(data.company_url);
				$("#edit_domain_name").val(data.domain_name);
				$("#edit_company_abn").val(data.company_abn);
				$("#edit_company_acn").val(data.company_acn);
				$("#edit_bank_account_name").val(data.bank_account_name);
				$("#edit_bank_name").val(data.bank_name);
				$("#edit_bank_branch").val(data.bank_branch);
				$("#edit_bank_bsb").val(data.bank_bsb);
				$("#edit_bank_account").val(data.bank_account);
				$("#edit_subscription_plan").val(data.subscription_plan);
				$("#edit_subscription_monthly_rate").val(data.subscription_monthly_rate);
				$("#edit_subscription_amount").val(data.subscription_amount);
				$("#edit_subscription_renew").val(data.subscription_renew);
				$("#edit_subscription_commencement").val(data.subscription_commencement);
				$("#edit_subscription_count").val(data.subscription_count);
                // Other operations with the received data
				$("#edit_company_name_text").text(data.company_name);
                $("#edit_company_address_text").text(data.company_address+', '+data.company_suburb+', '+data.company_state+', '+data.company_postode);
				$("#edit_company_phone_text").text(data.company_phone);
				$("#edit_company_email_text").text(data.company_email);
				$("#edit_company_image").attr("src", data.company_image_path)
			
				$("#edit_domain_name_user").val(data.domain_name);
			
				
				$('#modalEditCompany').modal('show');
				
				},
				error: function(xhr, status, error) {
				   handleError(xhr, status, error)
				}
    });
}
function SaveCompany() {
    var formData = {
		action: 'update_company', 
		company_id: $("#edit_company_id").val(),
        company_name: $("#edit_company_name").val(),
        company_address: $("#edit_company_address").val(),
        company_suburb: $("#edit_company_suburb").val(),
        company_state: $("#edit_company_state").val(),
        company_postode: $("#edit_company_postode").val(),
        company_phone: $("#edit_company_phone").val(),
        company_email: $("#edit_company_email").val(),
        company_contact: $("#edit_company_contact").val(),
        company_accounts_email: $("#edit_company_accounts_email").val(),
		domain_name: $("#edit_domain_name").val(),
		subscription_plan: $("#edit_subscription_plan").val(),
		subscription_renew: $("#edit_subscription_renew").val(),
		subscription_monthly_rate: $("#edit_subscription_monthly_rate").val(),
    };
 //   console.log(formData);
    ajaxPostRequest(formData,crud_url)
	Loadtab('subscribers')
	$('#modalEditCompany').modal('hide');
}
function searchSubscribers() {
    var searchQuery = document.getElementById('searchInput').value;
    Loadtab('subscribers', 1, searchQuery); // Always start from the first page for a new search
}
$(document).ready(function() {
   //GetCompanyId();
	Loadtab('subscribers')
	
});