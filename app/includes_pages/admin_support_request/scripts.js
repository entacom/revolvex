var crud_url ='includes_pages/admin_support_request/crud.php';
var content_url ='includes_pages/admin_support_request/content.php';
function LoadRequests() { 
    $.ajax({
        type: "POST",
        url: content_url,
        data: { action: 'requests'}, // Ensure 'tab_id' is sent correctly
        success: function (response) {
            $('#main_section').html(response);

			
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error) 
        }
	
    });
	

}
function NewSupportRequest() {
    $('#add_support_request').modal('show');
}
function AddClientActivity() {
    var formData = {
		'job_id': job_id, 
        'description': $('#add_client_activity').val(),

    };
    var jsonData = JSON.stringify(formData);
   $.ajax({
        type: "POST",
        url: 'includes_pages/admin_jobs/crud.php?add_client_activity',
        data: jsonData,
        contentType: "application/json",
        success: function (response) {
	
            $('#add_client_activity').val('');
				if (response) {
                handleSuccess(response);
                setTimeout(function() {
                    JobLoadtab('activity');
                }, 1000);
            }

        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
	$('#modalAddClientActivity').modal('hide');
}
$(document).ready(function() {
   LoadRequests()
});