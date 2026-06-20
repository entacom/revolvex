var crud_url = 'includes_pages/admin_inventory/crud.php';
var tab_id = 'inventory_list'; // Set default tab_id
var currentPage = 1; // Keep track of the current page

function Loadtab(tabId, search = '', filter_group_id = '') {
    tab_id = tabId;
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_inventory/content.php",
        data: { tab_id: tab_id, filter_group_id: filter_group_id },
        success: function(response) {
            $('#tab_body').html(response);

            // Clear the dropdown before repopulating to prevent duplication
            $('#filter_group').empty();
            populateDropdown('#filter_group', 'Select Group', 'get_inventory_group', 'id', 'description');

            // Set the filter if it's provided
            if (filter_group_id) {
                $('#filter_group').val(filter_group_id);
            }

            // Bind the change event, making sure to handle filter changes correctly
            $('#filter_group').off('change').on('change', function() {
                var selectedFilGroupValue = $(this).val();
                $('#filter_group_id').val(selectedFilGroupValue);

                // Reload the data with the selected filter
                loadFilteredInventory(selectedFilGroupValue);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function loadFilteredInventory(filter_group_id) {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_inventory/content.php",
        data: { tab_id: 'inventory_list', filter_group_id: filter_group_id },
        success: function(response) {
            $('#tab_body').html(response);

            // Clear the dropdown before repopulating to prevent duplication
            $('#filter_group').empty();
            populateDropdown('#filter_group', 'Select Group', 'get_inventory_group', 'id', 'description');

            // Set the filter if it's provided
            if (filter_group_id) {
                $('#filter_group').val(filter_group_id);
            }

            // Bind the change event, making sure to handle filter changes correctly
            $('#filter_group').off('change').on('change', function() {
                var selectedFilGroupValue = $(this).val();
                $('#filter_group_id').val(selectedFilGroupValue);

                // Reload the data with the selected filter
                loadFilteredInventory(selectedFilGroupValue);
            });
        },
        error: function(xhr, status, error) {
            console.error("Error loading filtered data:", error);
        }
    });
}


function editInventory(inventory_id) {
    $("#edit_inventory_id").val(inventory_id);

    var formData = {
        action: 'read_inventory',
        inventory_id: inventory_id,
    };

    var jsonData = JSON.stringify(formData);

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            if (response && response.length > 0) {
                var data = response[0];

                // Core fields
                $("#edit_part_number").val(data.part_number);
                $("#edit_description").val(data.description);
                $("#edit_rate").val(data.rate);
                $("#edit_buy_rate").val(data.buy_rate);
                $("#edit_min_qty").val(data.min_qty);
                $("#edit_qty").val(data.qty);
                $("#edit_product_source_id").val(data.product_source_id);
                $("#edit_unit_id").val(data.unit_id);
                $("#edit_metre_unit").val(data.metre_unit);
                $("#edit_weight_unit").val(data.weight_unit);
                $("#edit_group_id").val(data.group_id);

                const isProductionItemChecked = data.raw_material == 1;
                $("#edit_raw_material_checkbox").prop('checked', isProductionItemChecked);

                // Price Level Fields A-F
                $("#edit_rate_level_a").val(data.rate_level_a);
                $("#edit_rate_level_b").val(data.rate_level_b);
                $("#edit_rate_level_c").val(data.rate_level_c);
                $("#edit_rate_level_d").val(data.rate_level_d);
                $("#edit_rate_level_e").val(data.rate_level_e);
                $("#edit_rate_level_f").val(data.rate_level_f);
                // New: G-J
                $("#edit_rate_level_g").val(data.rate_level_g);
                $("#edit_rate_level_h").val(data.rate_level_h);
                $("#edit_rate_level_i").val(data.rate_level_i);
                $("#edit_rate_level_j").val(data.rate_level_j);

                $("#edit_gauge").val(data.gauge);
                $("#edit_manufacture_code").val(data.manufacture_code);

                // Dropdowns
                populateDropdown('#edit_product_source', data.product_source, 'get_product_source', 'id', 'part_number');
                $('#edit_product_source').change(function () {
                    var selectedPCValue = $(this).val();
                    $('#edit_product_source_id').val(selectedPCValue);
                });

                populateDropdown('#edit_group', data.group, 'get_inventory_group', 'id', 'description');
                $('#edit_group').change(function () {
                    var selectedGroupValue = $(this).val();
                    $('#edit_group_id').val(selectedGroupValue);
                });

                populateDropdown('#edit_unit', data.unit, 'get_inventory_unit', 'id', 'description');
                $('#edit_unit').change(function () {
                    var selectedUnitValue = $(this).val();
                    $('#edit_unit_id').val(selectedUnitValue);
                });

                $('#inventoryEditModal').modal('show');
            }
        },
        error: function (xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}

function editInventoryItem(inventory_item_id) {
    $("#edit_inventory_item_id").val(inventory_item_id);
    var formData = {
        action: 'read_inventory_item',
        inventory_item_id: inventory_item_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            if(response && response.length > 0) {
                var data = response[0];
                $("#edit_item_serial_number").val(data.serial_number);
                $("#edit_part_number").val(data.part_number);
                $("#edit_item_qty").val(data.qty);
                $("#edit_coil_finished").prop('checked', data.coil_finished === 1);
                $('#inventoryItemEditModal').modal('show');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}

function saveInventoryItem() {
    var formData = {
        'action': 'save_inventory_item', 
        'inventory_item_id': $('#edit_inventory_item_id').val(),
        'serial_number': $('#edit_item_serial_number').val(),
        'qty': $('#edit_item_qty').val(),
        'coil_finished': $('#edit_coil_finished').is(':checked') ? 1 : 0
    };
    var currentFilterGroupId = $('#filter_group_id').val();
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
         Loadtab('inventory_list', '', currentFilterGroupId);
    }, 1000);
    $('#inventoryItemEditModal').modal('hide');
}

function SaveInventory() {
    var formData = {

        'action': 'save_inventory',
        'inventory_id': $('#edit_inventory_id').val(),
        'group_id': $('#edit_group_id').val(),
        'product_source_id': $('#edit_product_source_id').val(),
        'part_number': $('#edit_part_number').val(),
        'description': $('#edit_description').val(),
        'unit_id': $('#edit_unit_id').val(),
        'min_qty': $('#edit_min_qty').val(),
        'qty': $('#edit_qty').val(),
        'rate': $('#edit_rate').val(),
        'buy_rate': $('#edit_buy_rate').val(),
        'metre_unit': $('#edit_metre_unit').val(),
        'weight_unit': $('#edit_weight_unit').val(),
        'raw_material': $('#edit_raw_material_checkbox').prop('checked'),

        // Price Levels A-J
        'rate_level_a': $('#edit_rate_level_a').val(),
        'rate_level_b': $('#edit_rate_level_b').val(),
        'rate_level_c': $('#edit_rate_level_c').val(),
        'rate_level_d': $('#edit_rate_level_d').val(),
        'rate_level_e': $('#edit_rate_level_e').val(),
        'rate_level_f': $('#edit_rate_level_f').val(),
        'rate_level_g': $('#edit_rate_level_g').val(),
        'rate_level_h': $('#edit_rate_level_h').val(),
        'rate_level_i': $('#edit_rate_level_i').val(),
        'rate_level_j': $('#edit_rate_level_j').val(),

        'gauge': $('#edit_gauge').val(),
        'manufacture_code': $('#edit_manufacture_code').val()
    };

    var currentFilterGroupId = $('#filter_group_id').val();

    ajaxPostRequest(formData, crud_url);

    setTimeout(function() {
        Loadtab('inventory_list', '', currentFilterGroupId);
    }, 1000);

    $('#inventoryEditModal').modal('hide');
}

function addInventoryModal() {
    $('#inventoryAddModal').modal('show');
    // Populate the group and unit dropdowns
    populateDropdown('#add_group', 'Select Group', 'get_inventory_group', 'id', 'description');
    populateDropdown('#add_unit', 'Select Unit', 'get_inventory_unit', 'id', 'description');
    populateDropdown('#product_source', 'Select Unit', 'get_product_source', 'id', 'part_number');

    // Handle change event to update hidden ids
    $('#add_group').change(function() {
        var selectedGroupValue = $(this).val();
        $('#add_group_id').val(selectedGroupValue);
    });
    $('#add_unit').change(function() {
        var selectedUnitValue = $(this).val();
        $('#add_unit_id').val(selectedUnitValue);
    });
    $('#product_source').change(function() {
        var selectedPvValue = $(this).val();
        $('#product_source_id').val(selectedPvValue);
    });
}

function addInventory() {
    var formData = {
        'action': 'add_inventory',
        'group_id': $('#add_group_id').val(),
        'product_source_id': $('#product_source_id').val(),
        'raw_material': $('#raw_material_checkbox').prop('checked'),
        'part_number': $('#part_number').val(),
        'description': $('#description').val(),
        'unit_id': $('#add_unit_id').val(),
        'metre_unit': $('#metre_unit').val(),
        'weight_unit': $('#weight_unit').val(),
        'min_qty': $('#min_qty').val(),
        'qty': $('#qty').val(),
        'rate': $('#rate').val(),
        'buy_rate': $('#buy_rate').val(),

        // Price levels A-J
        'rate_level_a': $('#add_rate_level_a').val(),
        'rate_level_b': $('#add_rate_level_b').val(),
        'rate_level_c': $('#add_rate_level_c').val(),
        'rate_level_d': $('#add_rate_level_d').val(),
        'rate_level_e': $('#add_rate_level_e').val(),
        'rate_level_f': $('#add_rate_level_f').val(),
        'rate_level_g': $('#add_rate_level_g').val(),
        'rate_level_h': $('#add_rate_level_h').val(),
        'rate_level_i': $('#add_rate_level_i').val(),
        'rate_level_j': $('#add_rate_level_j').val(),

        'gauge': $('#add_gauge').val(),
        'manufacture_code': $('#add_manufacture_code').val()
    };

    // Validation check for required fields
    var errorMessage = '';
    if (!formData.group_id) errorMessage += 'Missing Group.<br>';
    if (!formData.part_number) errorMessage += 'Missing Part#.<br>';
    if (!formData.rate) errorMessage += 'Missing Rate.<br>';
    if (!formData.description) errorMessage += 'Missing Description.<br>';
    if (!formData.unit_id) errorMessage += 'Missing Unit.<br>';

    if (errorMessage) {
        $('#site_message_modal').html('<div class="alert alert-danger">' + errorMessage + '</div>');
        return;
    }

    $('#site_message_modal').html('');

    var currentFilterGroupId = $('#filter_group_id').val();

    ajaxPostRequest(formData, crud_url);

    $('#inventoryAddModal').modal('hide');

    setTimeout(function () {
        Loadtab('inventory_list', '', currentFilterGroupId);
    }, 1000);

    // Reset fields
    $('#part_number, #description, #add_unit_id, #min_qty, #qty, #rate, #buy_rate').val("");
    $('#add_rate_level_a, #add_rate_level_b, #add_rate_level_c, #add_rate_level_d, #add_rate_level_e, #add_rate_level_f, #add_rate_level_g, #add_rate_level_h, #add_rate_level_i, #add_rate_level_j').val("");
}


function editInventorySubItem(inventory_item_id) {
    $("#edit_inventory_sub_item_id").val(inventory_item_id);
    var formData = {
        action: 'read_inventory_item',
        inventory_item_id: inventory_item_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            if(response && response.length > 0) {
                var data = response[0];
                $("#edit_item_serial_number").val(data.serial_number);
                $("#edit_part_number").val(data.part_number);
                $("#edit_sub_item_qty").val(data.qty);
                $("#edit_sub_item_qty_unit").val(data.qty_unit);
                $('#inventorySubItemEditModal').modal('show');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function saveInventorySubItem() {
    var formData = {
        'action': 'save_inventory_sub_item', 
        'inventory_sub_item_id': $('#edit_inventory_sub_item_id').val(),
        'qty_unit': $('#edit_sub_item_qty_unit').val(),
        'qty': $('#edit_sub_item_qty').val()
    };
    var currentFilterGroupId = $('#filter_group_id').val();
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
         Loadtab('inventory_list', '', currentFilterGroupId);
    }, 1000);
    $('#inventoryItemEditModal').modal('hide');
}

$(document).ready(function() {
   //GetCompanyId();
    Loadtab('inventory_list')
    
});
