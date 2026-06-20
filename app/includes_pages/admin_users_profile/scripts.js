
var crud_url ='includes_pages/admin_users_profile/crud.php';
function editUser() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_users_profile/content.php?user", // URL for editing a user
        data: {}, // Send the user ID to the server
       success: function(data, status) {
            $("#users_table").html(data);
        },
        error: function(xhr, status, error) {
            console.error("Error editing user:", error);
        }
    });
}

function SaveUser() {
    var formData = {
		'action': 'save_user', 
		'email': $('#email').val(),
		'mobile': $('#mobile').val(),
		'job_position': $('#job_position').val(),
		'timezone': $('#timezone').val(),
    };
    ajaxPostRequest(formData, crud_url);

}
$(document).ready(function() {
    editUser();
});

	