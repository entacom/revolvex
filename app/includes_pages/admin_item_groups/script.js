var crud_url = 'includes_pages/admin_item_groups/crud.php';

function LoadHome() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_item_groups/content.php?item_groups",
        data: { item_groups: 'item_groups' },
        success: function(response) {
            $('#body_content').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function AddGroupModal() {
    $('#add_group_modal').modal('show');
}

function AddGroup() {
    var formData = {
        action: 'add_group',
        group_description: $("#item_groups").val(),
    };
    ajaxPostRequest(formData, crud_url);
    $("#item_groups").val("");
    $('#add_unit_modal').modal('hide');
    LoadHome();
}


function EditGroupModal(group_id) {
    var formData = {
        action: 'read_group',
        group_id: group_id,
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
                $("#edit_group_id").val(group_id);
                $("#edit_item_group").val(data.description);
                
                $('#edit_group_modal').modal('show');
            } else {
                alert('Unit not found');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error:", error);
        }
    });
}

function EditGroup() {
    var formData = {
        action: 'edit_group',
        id: $("#edit_group_id").val(),
        group_description: $("#edit_item_group").val(),
    };
    ajaxPostRequest(formData, crud_url);
    LoadHome();
    $("#item_groups").val("");
    $('#edit_group_modal').modal('hide');
    LoadHome();
}

function toggleActive(group_id) {
    var formData = {
        action: 'toggle_active',
        id: group_id
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
