var crud_url = 'includes_pages/admin_item_units/crud.php';

function LoadHome() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_item_units/content.php?item_units",
        data: { item_units: 'item_units' },
        success: function(response) {
            $('#body_content').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function AddUnitModal() {
    $('#add_unit_modal').modal('show');
}

function AddUnit() {
    var formData = {
        action: 'add_unit',
        unit_description: $("#item_units").val(),
        divisible: $('#divisible').prop('checked'),
    };
    ajaxPostRequest(formData, crud_url);
    $("#item_units").val("");
    $('#add_unit_modal').modal('hide');
    LoadHome();
}


function EditUnitModal(unit_id) {
    var formData = {
        action: 'read_unit',
        unit_id: unit_id,
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
                $("#edit_unit_id").val(unit_id);
                $("#edit_item_units").val(data.description);
                
                const isDivisibleChecked = data.divisible == 1;
                $("#edit_divisible").prop('checked', isDivisibleChecked);
                
                
                $('#edit_unit_modal').modal('show');
            } else {
                alert('Unit not found');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error:", error);
        }
    });
}

function EditUnit() {
    var formData = {
        action: 'edit_unit',
        id: $("#edit_unit_id").val(),
        unit_description: $("#edit_item_units").val(),
        divisible: $('#edit_divisible').prop('checked'),
    };
    ajaxPostRequest(formData, crud_url);
    LoadHome();
    $("#item_units").val("");
    $('#edit_unit_modal').modal('hide');
    LoadHome();
}

function toggleActive(unit_id) {
    var formData = {
        action: 'toggle_active',
        id: unit_id
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
