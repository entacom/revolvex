var crud_url ='includes_pages/super_admin_plans/crud.php';
function Loadtab(tab_id) {
    $.ajax({
        type: "POST",
        url: "includes_pages/super_admin_plans/content.php",
        data: { tab_id: tab_id }, // Ensure 'tab_id' is sent correctly
        success: function (response) {
           console.log("LoadTab:" +tab_id) // Display the returned data in the '#jobs_tab_body' element
            $('#tab_body').html(response);
				

            $('#success_message').text("Data received successfully");
            $('#site_message').removeClass('alert alert-danger');
			
			
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



$(document).ready(function() {
   //GetCompanyId();
	Loadtab('plans')
	
});