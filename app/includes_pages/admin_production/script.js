var crud_url = 'includes_pages/admin_production/crud.php';
var content_url = 'includes_pages/admin_production/content.php';

function Loadtab(tab_id, serial_number = '') {
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { tab_id: tab_id, serial_number: serial_number },
        success: function(response) {
            $('#tab_body').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

$(document).on('change', '.production-purchased-checkbox', function() {
    var checkbox = $(this);
    var checked = checkbox.is(':checked');
    var orderItemId = checkbox.data('order-item-id');
    var row = checkbox.closest('.production-row');

    if (checked && !confirm('Mark this item as purchased and remove it from the production queue?')) {
        checkbox.prop('checked', false);
        return;
    }

    checkbox.prop('disabled', true);

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'update_purchased_item',
            order_item_id: orderItemId,
            purchased_item: checked ? 1 : 0
        }),
        contentType: 'application/json',
        success: function(response) {
            var parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;

            if (parsedResponse.success) {
                handleSuccess(parsedResponse.message);
                if (checked) {
                    row.fadeOut(180, function() {
                        $(this).remove();
                    });
                } else {
                    checkbox.prop('disabled', false);
                }
                return;
            }

            checkbox.prop('checked', !checked).prop('disabled', false);
            handleError(parsedResponse.message || 'Purchased item update failed.', 'Unknown');
        },
        error: function(xhr) {
            checkbox.prop('checked', !checked).prop('disabled', false);
            handleError(xhr.responseText || 'Purchased item update failed.', xhr.status);
        }
    });
});

function LoadSubTab(sub_tab_id) {
	var part_number = $('#part_number_x').val();
	var source_id = $('#product_source_id_x').val();
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { sub_tab_id: sub_tab_id, part_number: part_number, source_id: source_id },
        success: function(response) {
            $('#coil').html(response);
           // console.log(response)
        	},
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}
function LoadCoilStock() {
	var part_number = $('#part_number_x').val();
	var source_id = $('#product_source_id_x').val();
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { sub_tab_id: 'select_from_coil', part_number: part_number, source_id: source_id },
        success: function(response) {
            $('#coil_stock_list').html(response);
           // console.log(response)
        	},
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}
function LoadProductionStock() {
	var part_number = $('#part_number_x').val();
    var order_id = $('#order_id_x').val();
    console.log("PN "+part_number)
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { sub_tab_id: 'select_production_stock', part_number: part_number, order_id: order_id },
        success: function(response) {
            $('#production_stock_in').html(response);
            //console.log(response)
        	},
        error: function(xhr, status, error) {
           // console.error("Error receiving data:", error);
        }
    });
}
// Function to handle the search
function searchProductionHistory() {
    const serialNumber = document.getElementById('serial_number_search').value;
    Loadtab('production_history', serialNumber);
}



function ReadStockData() {
   var part_number = $('#part_number_x').val();
   var order_id = $('#order_id_x').val();
   var formData = {
        action: 'read_inventory_total_stock',
        order_id: order_id,
        part_number: part_number,
    };
    console.log(formData)
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
              var data = response[0];
              $("#stock_in_qty").val(data.cumulative_total);
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function SplitProdModal(id) {
    $("#item_group_id").val(id);
    var formData = {
        action: 'read_match',
        id: id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
              var data = response[0];
			$("#original_qty").val(data.qty);
            $("#original_qty_units").val(data.qty_unit);
            $('#split_product_modal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function SplitItemGroup() {
    var formData = {
        action: 'split_group',
        original_qty: $('#original_qty').val(),
        split_qty: $('#split_qty').val(),
        item_group_id: $('#item_group_id').val(),
    };
    ajaxPostRequest(formData, crud_url);
    $('#split_product_modal').modal('hide');
    
    var part_number = $('#part_number_x').val();
    var order_id = $('#order_id_x').val();
    
    LoadItemsOrder(order_id,part_number)
    setTimeout(function() {
        Loadtab('invoice');
    }, 1000);
    
} 
function addStockModal() {
      $('#add_production_modal').modal('show');
     $('#add_production_modal').on('shown.bs.modal', function () {
      $('#add_stock_qty').focus();
     });
}
function addStock() {
    var formData = {
        action: 'add_stock',
        add_stock_qty: $('#add_stock_qty').val(),
        add_stock_qty_units: $('#add_stock_qty_units').val(),
        order_id: $('#order_id_x').val(),
        part_number: $('#part_number_x').val(),
        inventory_id: $('input[name="selected_coil_item"]:checked').val()
    };

    // Validation check
    var errorMessage = '';
    if (!formData.inventory_id) errorMessage += 'Select Coil.';
    if (!formData.add_stock_qty) errorMessage += 'Missing Qty.';
    if (!formData.add_stock_qty_units) errorMessage += 'Missing Length.';
    
    if (errorMessage) {
        $('#site_message_modal').html('<div class="alert alert-danger">' + errorMessage + '</div>');
        return; // Stop the function execution if any field is missing
    }

    console.log(formData);
   
    $('#site_message_modal').html('');
    ajaxPostRequest(formData, crud_url);
    
    $('#add_production_modal').modal('hide');

    setTimeout(function() {
       LoadProductionStock();
       ReadStockData() 
    }, 1000);
}

function deleteStockItem(stock_id) {
    console.log(stock_id)

        var formData = {
            action: 'delete_stock_id',
            stock_id: stock_id
        };
       ajaxPostRequest(formData, crud_url);
       setTimeout(function() {
       LoadProductionStock();
       ReadStockData()   
           
    }, 1000);
  
}
function LoadItemsOrder(order_id,part_number) {
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { sub_tab_id: 'select_order_items', order_id: order_id, part_number: part_number },
        success: function(response) {
            $('#order_body').html(response);
            $('#order_stock_body').html(response);
           // console.log(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}
$(document).on('change', '#select-all', function() {
    var isChecked = $(this).is(':checked');
    $('.calculate-length:not(:disabled)').prop('checked', isChecked).trigger('change');
});


function recordProduction() {
    var selectedOrderItems = [];

    $('input.form-check-input.calculate-length:checked').each(function () {
        var row = $(this).closest('.row');
        selectedOrderItems.push({
            order_item_id: $(this).data('order-item-id'),
            stock_from_coil: $(this).data('length'),
            stock_in_qty: row.find('.stock-in-qty-input').val() || 0,
            waste_qty: row.find('.waste-qty-input').val() || 0
        });
    });

    var selectedCoilValue = $('input[name="selected_coil_item"]:checked').val();
    $('#selected_coil_value').val(selectedCoilValue);

    var part_number_x = $('#part_number_x').val();
    var production_date = $('#date_used').val();
    var order_id = $('#order_id_x').val();
    var coil_finished = $('#coil_finished').is(':checked');

    var formData = {
        action: 'add_production',
        inv_id: selectedCoilValue,
        part_number: part_number_x,
        production_date: production_date,
        order_id: order_id,
        coil_finished: coil_finished,
        order_items: selectedOrderItems
    };

    var errorMessage = '';
    if (!formData.inv_id) errorMessage += 'Missing Coil Selection. ';
    if (formData.order_items.length === 0) errorMessage += 'Missing Item Selection. ';

    if (errorMessage) {
        $('#site_message_modal').html('<div class="alert alert-danger" style="display: none;">' + errorMessage + '</div>');
        $('#site_message_modal .alert').fadeIn('slow');
        setTimeout(function () {
            $('#site_message_modal .alert').fadeOut('slow', function () {
                $(this).remove();
            });
        }, 3000);
        return;
    }

    $('#site_message_modal').html('');
    $('#stock_in_qty').val('');

    // Temporarily override handleSuccess
    const originalHandleSuccess = window.handleSuccess;
    window.handleSuccess = function (msg) {
        originalHandleSuccess(msg); // still show any success message
        LoadItemsOrder(order_id, part_number_x); // ✅ reload the HTML
        window.handleSuccess = originalHandleSuccess; // restore original
    };

    ajaxPostRequest(formData, crud_url);
}



$(document).on('change', '.calculate-length', function() {
    var totalLength = 0;
    $('.calculate-length:checked').each(function() {
        totalLength += parseFloat($(this).data('length'));
    });

    // Format the total length to 3 decimal places
    totalLength = totalLength.toFixed(3);

    console.log('Total Length:', totalLength);

    // Update the total length in the appropriate field
    $('#total_length').text('Total Length: ' + totalLength);

    // Set the order_qty input to the total length
    $('#selected_qty').val(totalLength);

    // Call calculateQuantities to update the other fields
    calculateQuantities();
});


function calculateQuantities() {
    var orderQty = parseFloat(document.getElementById('selected_qty').value) || 0;
    var stockInQty = parseFloat(document.getElementById('stock_in_qty').value) || 0;
    var wasteQty = parseFloat(document.getElementById('waste_qty').value) || 0;
    var stockSubQty = (orderQty + stockInQty) + wasteQty;
    var stockQty = orderQty + stockInQty;
    document.getElementById('stock_from_coil').value = stockSubQty.toFixed(3);
    document.getElementById('stock_total_in_qty').value = stockQty.toFixed(3);
}

function delProductionId(id, order_item_id, qty) {
    if (!confirm('Are you sure you want to delete this production item (Qty: ' + qty + ')?')) {
        return;
    }

    var formData = {
        action: 'delete_production_id',
        id: id,
        order_item_id: order_item_id,
        qty: qty
    };

    ajaxPostRequest(formData, crud_url);

    setTimeout(function () {
        Loadtab('production_history');
    }, 1000);
}


function fromCoilModal(id) {
    id = id || $("#match_id").val();
    if (!id) {
        console.error("No match_id provided or found in the hidden input.");
        return;
    }

    $('#match_stock_modal').modal('hide'); // Hide the current modal
    $("#match_id").val(id); // Ensure match_id is set in the hidden input

    var formData = {
        action: 'read_match',
        id: id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0];

            // Populate the fields in the product modal
            $("#match_order_id").html(data.text_order_id);
            $("#match_pack_id").html(data.pack_id);
            $("#match_description").html(data.description);
            $("#match_part_number").html(data.part_number);
            $("#order_qty").val(parseFloat(data.qty_total).toFixed(3));
            $("#match_qty_unit").html(data.qty_unit);
            $("#match_weight").html(data.weight);
            $("#order_id_x").val(data.order_id);
            $("#part_number_x").val(data.part_number);
            $("#product_source_id_x").val(data.product_source_part);

            $('#match_product_modal').modal('show'); // Show the product modal
             $(".date_used").datepicker({ dateFormat: "dd-mm-yy" });

            // Load additional data
            LoadCoilStock();
            LoadProductionStock();
            LoadItemsOrder(data.order_id, data.part_number);
            ReadStockData();
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}

// stock stuff here
function fromStockModal() {
    $('#match_product_modal').modal('hide'); // Hide the current modal
    var id = $('#match_id').val(); // Get the match_id from the hidden input
    
  

    if (!id) {
        console.error("No match_id found in the hidden input.");
        return;
    }

    var formData = {
        action: 'read_stock',
        id: id,
    };

    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0];

            $('#match_stock_modal').modal('show'); // Show the stock modal
            var part_number = $('#part_number_x').val();
            var order_id = $('#order_id_x').val();
            LoadStock()
            LoadStockOrderItems(order_id,part_number) 
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function LoadStock() {
	var part_number = $('#part_number_x').val();
    var order_id = $('#order_id_x').val();
    console.log("PN "+order_id)
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { sub_tab_id: 'select_stock', part_number: part_number, order_id: order_id },
        success: function(response) {
            $('#stock_available').html(response);
           
        	},
        error: function(xhr, status, error) {
           // console.error("Error receiving data:", error);
        }
    });
}
function LoadStockOrderItems() {
      var part_number = $('#part_number_x').val();
      var order_id = $('#order_id_x').val();
    $.ajax({
        type: "POST",
        url: 'includes_pages/admin_production/content.php',
        data: { sub_tab_id: 'stock_order_select_items', order_id: order_id, part_number: part_number },
        success: function(response) {
            $('#order_stock_body').html(response);
           // console.log(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}
// Initialize variables to store the total selected length
let or_totalSelectedLength = 0;

// Function to calculate the final total from stock based on stock_selected_qty, stock_order_selected_qty, and stock_waste_qty
function calculateStockFinalTotal() {
    // Get the values of stock_selected_qty, stock_order_selected_qty, and stock_waste_qty
    var stockSelectedQty = parseFloat($('#stock_selected_qty').val()) || 0;
    var stockOrderSelectedQty = parseFloat($('#stock_order_selected_qty').val()) || 0;
    var stockWasteQty = parseFloat($('#stock_waste_qty').val()) || 0;

    // Calculate the total from stock: stock_selected_qty - stock_order_selected_qty - stock_waste_qty
    var totalFromStock = stockSelectedQty - stockOrderSelectedQty - stockWasteQty;

    // Ensure the total is formatted to 3 decimal places
    totalFromStock = totalFromStock.toFixed(3);

    // Update the total_from_stock field with the calculated total
    $('#total_from_stock').val(totalFromStock);
}

// When the stock_selected_qty changes (triggered by stock selection)
$(document).on('change', '.calculate-stock-length', function() {
    var totalLength = 0;

    // Calculate the total length from selected radio buttons
    $('.calculate-stock-length:checked').each(function() {
        totalLength += parseFloat($(this).data('length')); // Use the data-length attribute
    });

    // Format the total length to 3 decimal places
    totalLength = totalLength.toFixed(3);

    console.log('Total Stock Length:', totalLength);

    // Set the stock_selected_qty input to the total length
    $('#stock_selected_qty').val(totalLength);

    // Recalculate the total from stock
    calculateStockFinalTotal();
});

// When the stock_waste_qty input changes, recalculate the total from stock
$(document).on('change', '#stock_waste_qty', function() {
    calculateStockFinalTotal();
});

// Ensure stock_order_selected_qty resets when modal closes
$('#match_stock_modal').on('hidden.bs.modal', function () {
    or_totalSelectedLength = 0;
    $('#stock_order_selected_qty').val('0.000');
    $('#stock_selected_qty').val('0.000');
    $('#stock_waste_qty').val('0');
    $('#total_from_stock').val('0.000');
});

// When the stock_order_selected_qty changes (triggered by selecting an order item)
$(document).on('change', 'input[name="order_item"]', function() {
    // Reset total selected length since only one item can be selected with a radio button
    or_totalSelectedLength = 0;

    if ($(this).is(':checked')) {
        // Get the length based on the selected item's stock-data-order-item-id attribute
        let or_length = parseFloat($(this).closest('.row').find('.col-4').eq(1).text().split(' X ')[0].trim());
        let or_qtyUnit = parseFloat($(this).closest('.row').find('.col-4').eq(1).text().split(' X ')[1].trim());
        or_totalSelectedLength = or_length * or_qtyUnit; // Calculate the total length

        // Update the stock_order_selected_qty input with the total length
        $('#stock_order_selected_qty').val(or_totalSelectedLength.toFixed(3));

        // Recalculate the total from stock
        calculateStockFinalTotal();
    }
});


function recordStock() {
    // Get the selected radio button for the stock item
    var selectedStockItem = $('input[name="selected_stock_item"]:checked');
    var selectedOrderItem = $('input[name="order_item"]:checked'); // Get the selected order item radio button

    // Retrieve the stock item ID from the selected radio button's data attribute
    var selectedStockValue = selectedStockItem.data('stock-item-id');
    
    // Retrieve the order item ID from the selected radio button's stock-data-order-item-id attribute
    var selectedOrderValue = selectedOrderItem.attr('stock-data-order-item-id'); // Using attr since it's a custom attribute
    
    var stock_used_qty = $('#total_from_stock').val();

    // Set the hidden input field value (if needed)
    $('#selected_stock_value').val(selectedStockValue);

    // Create the form data object
    var formData = {
        action: 'record_stock_used',
        stock_id: selectedStockValue,
        order_item_id: selectedOrderValue, // Add the order item ID to the form data
        stock_used_qty: stock_used_qty
    };

    console.log(formData);

    // Initialize the error message string
    var errorMessage = '';
    
    // Check if the stock_id (selectedStockValue) is empty or undefined
    if (!formData.stock_id) errorMessage += 'Missing stock selection. ';
    
    // Check if the order_item_id (selectedOrderValue) is empty or undefined
    if (!formData.order_item_id) errorMessage += 'Missing order item selection. ';
    
    // Check if stock_used_qty is less than 0
    if (formData.stock_used_qty === '' || parseFloat(formData.stock_used_qty) < 0) {
        errorMessage += 'Used stock quantity cannot be less than 0. ';
    }

    // If there's an error message, display it
    if (errorMessage) {
        $('#site_message_modal_stock').html('<div class="alert alert-danger" style="display: none;">' + errorMessage + '</div>');
        
        // Fade in the error message
        $('#site_message_modal_stock .alert').fadeIn('slow');
        
        // Fade out and remove the error message after 3 seconds
        setTimeout(function() {
            $('#site_message_modal_stock .alert').fadeOut('slow', function() {
                $(this).remove(); // Remove the element after fading out
            });
        }, 3000);
        
        // Stop the function execution if any field is missing
        return;
    }

    // Example AJAX request (uncomment when necessary)
     ajaxPostRequest(formData, crud_url);

    // Call LoadStock if necessary (uncomment if you need to reload stock data)
     LoadStock();
    LoadStockOrderItems()
}




$(document).ready(function() {
    Loadtab('production');
});
