var content_url = 'includes_pages/admin_setup/content.php';
var crud_url = 'includes_pages/admin_setup/crud.php';

function Loadtab(tab_id) {
    $.ajax({
        type: "POST",
        url: content_url,
        data: { tab_id: tab_id },
        success: function(response) {
            $('#main_content').html(response);

            // Initialize sortable for each group separately
            $('.parent-group').each(function() {
                $(this).sortable({
                    items: 'tr.sortable-sub', 
                    update: function(event, ui) {
                        var newOrder = $(this).sortable('toArray', { attribute: 'data-id' });
                        updateOrder(newOrder);
                    }
                }).disableSelection();
            });
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function updateOrder(newOrder) {
   // console.log("Sending update order:", newOrder); // Log the data being sent
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({ action: 'budget_sub_ordering', order: newOrder }),
        contentType: "application/json",
        success: function(response) {
            console.log("Update response:", response);
        },
        error: function(xhr, status, error) {
            console.error("Error sending data:", error);
        }
    });
}


function AddTradeModal() {
	$('#addTradeModal').modal('show');
	
}
function AddTrade() {
    var formData = {
        'action': 'add_trade', 
        'trade_name': $("#trade_name").val(),
        'rate': $("#trade_rate").val(),
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function (response) {
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.success) {
                handleSuccess(parsedResponse.message);
                $('#addTradeModal').modal('hide');
              	Loadtab('tab_trades')
            } else {
                // Handle the error case
                handleError(null, null, parsedResponse.message);
            }
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}
function editTrade(trade_id) {
	$("#edit_trade_id").val(trade_id);
	edit_trade_id
    var formData = {
        action: 'get_trade',
        trade_id: trade_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
                var data = response[0];
                $("#edit_trade_name").val(data.trade_name);
                $("#edit_rate").val(data.rate);
                $('#editTradeModal').modal('show');
				},
				error: function(xhr, status, error) {
				   handleError(xhr, status, error)
				}
    });
}
function SaveTrade() {
    var formData = {
		'action': 'update_trade', 
		'trade_id': $('#edit_trade_id').val(),
		'trade_name': $('#edit_trade_name').val(),
		'rate': $('#edit_rate').val(),
    };
    var jsonData = JSON.stringify(formData);
	console.log(jsonData)
   $.ajax({
        type: "POST",
        url: crud_url,
        data: jsonData,
        contentType: "application/json",
        contentType: "application/json",
        success: function (response) {
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.success) {
                handleSuccess(parsedResponse.message);
                $('#editTradeModal').modal('hide');
              	Loadtab('tab_trades')
            } else {
                // Handle the error case
                handleError(null, null, parsedResponse.message);
            }
        },
        error: function (xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}
$(document).ready(function() {
    Loadtab('tab_budget');
});
