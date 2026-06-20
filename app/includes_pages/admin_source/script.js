var crud_url = 'includes_pages/admin_source/crud.php';

function LoadHome() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_source/content.php?source",
        data: { source: 'source' },
        success: function(response) {
            $('#body_content').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function AddSourceModal() {
    $('#add_source_modal').modal('show');
}

function AddSource() {
    var formData = {
        action: 'add_source',
        source_description: $("#source").val(),
    };
    ajaxPostRequest(formData, crud_url);
    $("#source").val("");
    $('#add_unit_modal').modal('hide');
    LoadHome();
}


function EditSourceModal(source_id) {
    var formData = {
        action: 'read_source',
        source_id: source_id,
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
                $("#edit_source_id").val(source_id);
                $("#edit_source").val(data.description);
                $('#edit_source_modal').modal('show');
            } else {
                alert('source not found');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error:", error);
        }
    });
}

function EditSource() {
    var formData = {
        action: 'edit_source',
        id: $("#edit_source_id").val(),
        source_description: $("#edit_source").val(),
    };
    ajaxPostRequest(formData, crud_url);
    LoadHome();
    $("#item_source").val("");
    $('#edit_source_modal').modal('hide');
    LoadHome();
}

function toggleActive(source_id) {
    var formData = {
        action: 'toggle_active',
        id: source_id
    };
    var jsonData = JSON.stringify(formData);

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {

                LoadHome();
      
        },

    });
}

$(document).ready(function() {
    LoadHome();
});
