var crud_url = 'includes_pages/admin_purchase_status/crud.php';
var content_url = 'includes_pages/admin_purchase_status/content.php';
function LoadHome() {
    $.ajax({
        type: "POST",
        url: content_url+"?order_status",
        data: { order_status: 'order_status' },
        success: function(response) {
            $('#body_content').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function AddStatusModal() {
    $('#add_status_modal').modal('show');
}

function AddStatus() {
    var formData = {
        action: 'add_status',
        description: $("#description").val(),
    };
    console.log(formData)
    ajaxPostRequest(formData, crud_url);
    $("#description").val("");
    $('#add_status_modal').modal('hide');
    LoadHome();
}

function EditStatusModal(status_id) {
    var formData = {
        action: 'read_status',
        status_id: status_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            if (response.length > 0) {
                var data = response[0];
                $("#edit_status_id").val(status_id);
                $("#edit_description").val(data.description);
                $('#edit_status_modal').modal('show');
            } else {
                alert('status not found');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error:", error);
        }
    });
}

function EditStatus() {
    var formData = {
        action: 'edit_status',
        id: $("#edit_status_id").val(),
        description: $("#edit_description").val(),
    };
    ajaxPostRequest(formData, crud_url);
    LoadHome();
    $("#status").val("");
    $('#edit_status_modal').modal('hide');
    LoadHome();
}

function toggleActive(status_id) {
    var formData = {
        action: 'toggle_active',
        id: status_id
    };
   ajaxPostRequest(formData, crud_url);
   LoadHome();

}

$(document).ready(function() {
    LoadHome();
});
