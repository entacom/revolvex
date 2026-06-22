var order_id = getUrlVars()["order_id"] || null;
var crud_url = 'includes_pages/admin_orders/crud.php';
var content_url = 'includes_pages/admin_orders/content.php';
const urlParams = new URLSearchParams(window.location.search);
const tParam = urlParams.get('t'); // "1" or "2"

function currentOrderId() {
    const params = new URLSearchParams(window.location.search);
    return params.get('order_id')
        || $('#this_order_id_pid').val()
        || $('#process_workflow_order_id').val()
        || order_id
        || null;
}

function Loadtab(tab_id) {
    order_id = currentOrderId();
    $.ajax({
        type: "POST",
        url: content_url,
        data: { tab_id: tab_id, order_id: order_id },
        dataType: "json",
        success: function (response) {
            $('#jobs_tab_body').html(response.html);

            $('#order_status').change(function () {
                var selectedStatusValue = $(this).val();
                $('#order_status_id').val(selectedStatusValue);
    
            });

            $('#order_source').change(function () {
                var selectedSourcetValue = $(this).val();
                $('#order_source_id').val(selectedSourcetValue);
            });

            $('#cash_sale_checkbox').change(function () {
                togglePrimaryFields(this.checked);
            });

            // Editing existing order
            if (order_id) {
                getOrderId();

                $('#saveContactsDiv button').text('Save');
                $('#saveContactsDiv').off('click').on('click', function (event) {
                    if (event.target.closest('button')) {
                        saveContacts();
                    }
                });

                // Wait for DOM to render and dropdown to populate before checking status
                setTimeout(function () {
                    const currentStatus = $('#order_status_id').val();
                  
                }, 200);
            } 

            // Creating new order or quote
            else {
                $('#saveContactsDiv button').text('Save');
                $('#saveContactsDiv').off('click').on('click', function (event) {
                    if (event.target.closest('button')) {
                        createNewContacts();
                    }
                });

                populateDropdown('#order_source', '', 'get_source', 'id', 'description');

                let defaultStatusId = '';
                let defaultStatusLabel = '';
                if (tParam === '1') {
                    defaultStatusId = '1';
                    defaultStatusLabel = 'Quote';
                } else if (tParam === '2') {
                    defaultStatusId = '5';
                    defaultStatusLabel = 'Order';
                }

                if (defaultStatusId) {
                    $('#order_status').html(`<option value="${defaultStatusId}">${defaultStatusLabel}</option>`);
                    $('#order_status').val(defaultStatusId).trigger('change');
                    $('#order_status_id').val(defaultStatusId);
                 
                }
            }

            if (tab_id === 'attachments') {
                initializeDropzone();
            }

            if (tab_id === 'pack_tab') {
                initializeDragAndDrop();
            }
        }
    });
}

function toggleTag(id) {
    console.log("toggleTag called with ID:", id);

    $.post(crud_url, {
        action: 'toggle_order_item_tag',
        item_id: id
    }, function (res) {
        console.log("Response:", res);
        const btn = document.getElementById(`tag-btn-${id}`);
        if (!btn) return;

        if (res.success) {
            // Toggle to solid green
            if (btn.classList.contains('btn-success')) {
                btn.classList.remove('btn-success');
                btn.classList.add('btn-outline-secondary');
            } else {
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
            }
        } else {
            alert("Toggle failed: " + (res.message || 'Unknown error'));
        }
    }, 'json').fail(function (xhr) {
        alert("AJAX error: " + xhr.responseText);
    });
}






function deleteInvoiceModal(del_in_id) {
     console.log("MD" + order_id);
    $('#DelInvoiceModal').modal('show');
    $("#del_in_id").val(del_in_id);

}
function deleteInvoice() {
    var del_in_id = $("#del_in_id").val(); // this IS the order_id
    console.log(del_in_id);

    var formData = {
        action: 'delete_invoice',
        del_in_id: del_in_id
    };

    ajaxPostRequest(formData, crud_url);
    $('#DelInvoiceModal').modal('hide');
    Loadtab('invoice');
}


// AMENDED BLOCK: replaces function processInvButtonModal(order_id)
// AMENDED BLOCK: replaces function processInvButtonModal(order_id)
function processInvButtonModal(order_id) {
    $('#ProcessInvoiceModal').modal('show');
    $('#process_order_id').val(order_id);

    var $inp = $('.edit_date_invoice');

    if (!$inp.data('datepicker')) {
        $inp.datepicker({ dateFormat: 'dd-mm-yy' });
    }
    $inp.val('');

    var formData = {
        action: 'read_invoice',
        invoice_id: order_id
    };
    var jsonData = JSON.stringify(formData);

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: 'application/json',
        success: function(response) {
            console.log("[read_invoice] raw response:", response);

            var res = (typeof response === 'string') ? JSON.parse(response) : response;
            console.log("[read_invoice] parsed:", res);

            if (res && res.length > 0) {
                var dt = res[0].invoice_date; // dd-mm-YYYY (formatted by PHP below)
                console.log("[read_invoice] invoice_date:", dt);
                if (dt) {
                    $inp.datepicker('setDate', dt);
                }
            } else {
                console.log("[read_invoice] no invoice found for order_id:", order_id);
            }
        },
        error: function(xhr, status, err) {
            console.warn('read_invoice failed:', status, err);
            if (xhr && xhr.responseText) {
                console.warn('server response:', xhr.responseText);
            }
        }
    });
}




function processInvButton() {
    var invoice_date = $('.edit_date_invoice').val();
    var order_id = $('#process_order_id').val();
    console.log("OID" + order_id);
    processInvoice(order_id, invoice_date);
    setTimeout(function() {
        Loadtab('invoice');
    }, 1000);
}

function editInventoryItem(item_id) {
    $("#edit_item_id").val(item_id);
    var formData = {
        action: 'read_inventory_item',
        item_id: item_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            console.log("Response: ", response);
            if(response && response.length > 0) {
                var data = response[0];
                $("#edit_item_numberx").val(data.item_number);
                $("#edit_item_description").val(data.item_description);
                $("#edit_qty").val(data.qty);
                
                
                $('#inventoryItemEditModal').modal('show');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}

function saveContacts() {
    const orderStatusId = $('#order_status_id').val();
    if (!orderStatusId) {
        handleError('Status Dropdown Required', 1)
        $('#order_status').focus();
        return;
    }
    const actionType = order_id ? 'save_contacts' : 'create_order';
    const formData = {
        action: actionType,
        order_id: order_id,
        order_date: $('#order_date').val(),
        order_number: $('#order_number').val(),
        order_status_id: $('#order_status_id').val(),
        order_user_id: $('#order_user_id').val(),
        cash_sale: $('#cash_sale_checkbox').prop('checked'),
        customer_uid: $('#customer_uid').val(),
        customer_company: $('#customer_search').val(),
        customer_contact: $('#customer_search').val(),
        customer_address: $('#customer_address').val(),
        customer_suburb: $('#customer_suburb').val(),
        customer_state: $('#customer_state').val(),
        customer_postcode: $('#customer_postcode').val(),
        customer_phone: $('#customer_phone').val(),
        customer_email: $('#customer_email').val(),
        client_source_id: $('#order_source_id').val(),
        site_contact: $('#site_contact').val(),
        site_address: $('#site_address').val(),
        site_suburb: $('#site_suburb').val(),
        deliver_note: $('#deliver_note').val(),
        deliver_instructions: $('#deliver_instructions').val(),
        site_phone: $('#site_phone').val(),
        customer_notes: $('#customer_notes').val(),
        delivery_date: $('#order_delivery_date').val(),
        delivery_rate: $('#delivery_rate').val(),
        pickup_checkbox: $('#pickup_checkbox').prop('checked'),
        price_level: $('#price_level').val(),
        payment_terms: $('#payment_terms').val(),

    };
    $.ajax({
        type: "POST",
        url: crud_url,
        data: JSON.stringify(formData),
        contentType: "application/json",
        success: function(response) {
           
            // Ensure response is parsed as an object
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            if (response.success) {
                if (actionType === 'create_order' && response.order_id) {
                    const newOrderId = response.order_id;
                    const redirectUrl = `${window.location.origin}${window.location.pathname}?p=admin_orders&order_id=${newOrderId}`;
                    window.location.href = redirectUrl;
                } else {
                    handleSuccess(response.message)
                }
            } else if (response.error) {
                console.error('Error message:', response.message);
            } else {
                console.error('Unexpected response format:', response);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            console.error('AJAX status:', status);
            console.error('AJAX response:', xhr.responseText);
        }
    });
}

function createNewContacts() {
    order_id = null; // Ensure order_id is null to trigger the create action
   
    saveContacts();
}

var pdfDropzoneOptions = {
    url: "../../api/crud.php?upload_order_file&order_id="+order_id,
    method: 'POST',
    maxFilesize: 10,
    maxFiles: 1,
    acceptedFiles: ".pdf,.msg,.jpg,.jpeg",
    createImageThumbnails: false,
    headers: {
        "X-Requested-With": "XMLHttpRequest"
    },
    dictDefaultMessage: "Drag & Drop PDF, JPG/JPEG, or Outlook MSG files here or click to upload",
    success: function(file, response) {
        try {
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }
        } catch (e) {
            this.emit("error", file, "Upload failed: server returned an invalid response.");
            return;
        }
        if (!response.success) {
            this.emit("error", file, response.error || "Upload failed");
            return;
        }
        Loadtab('attachments');
    },
    error: function(file, response) {
        var message = response;
        if (response && response.error) {
            message = response.error;
        }
        alert(message || "Upload failed");
    }
};

// Function to initialize Dropzone
function initializeDropzone() {
    if (typeof Dropzone === 'undefined') {
        alert("File upload could not be loaded. Please refresh and try again.");
        return;
    }
    if (Dropzone.instances.length > 0) {
        Dropzone.instances.forEach(dz => dz.destroy()); // Destroy existing instances
    }
    var dropzoneElement = document.getElementById('dropzone_orders');
    if (!dropzoneElement) {
        return;
    }
    new Dropzone(dropzoneElement, pdfDropzoneOptions);
}

$(document).off('click', '.delete-file').on('click', '.delete-file', function () {
    var $item = $(this).closest('[data-file-id]');
    var fileId = parseInt($item.data('file-id'), 10);
    var itemOrderId = parseInt($item.data('order-id'), 10);

    if (!fileId || !itemOrderId) {
        alert('Missing file details.');
        return;
    }

    if (!confirm('Delete this attachment from the order?')) {
        return;
    }

    $.ajax({
        url: '../../api/crud.php',
        type: 'POST',
        data: JSON.stringify({
            action: 'delete_order_file',
            file_id: fileId,
            order_id: itemOrderId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function (response) {
            if (response && response.success) {
                Loadtab('attachments');
                return;
            }
            alert((response && response.error) ? response.error : 'Delete failed.');
        },
        error: function (xhr) {
            var message = 'Delete failed.';
            if (xhr.responseJSON && xhr.responseJSON.error) {
                message = xhr.responseJSON.error;
            } else if (xhr.responseText) {
                message = xhr.responseText;
            }
            alert(message);
        }
    });
});


function getOrderId() {
    order_id = currentOrderId();
    var formData = {
        action: 'read_order',
        order_id: order_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            console.log(response); // Debugging line
            var data = response[0];
            if (data) {
                // Check the cash_sale value and set the checkbox accordingly
                const isCashSaleChecked = data.cash_sale == 1;
                $("#cash_sale_checkbox").prop('checked', isCashSaleChecked);
                
                
               if (data.cash_sale == 1) {
                        $("#infobar_address_full").text('Cash Sale');
                    } else {
                        let contactInfo = data.site_contact ? data.site_contact : (data.customer_company ? data.customer_company : data.customer_contact);
                        $("#infobar_address_full").text(contactInfo || 'Customer');
                    }

                
                
                $("#infobar_order_id").text('ID #' + order_id + (data.site_address_full ? ' - ' + data.site_address_full : ''));
                $("#infobar_delivery_date")
                    .text(data.delivery_date || '-')
                    .toggleClass('text-danger fw-bold', !!(data.completion && data.completion.warning));
                $("#infobar_note").text(data.deliver_note || '-');
                $("#infobar_status").empty().append(
                    $('<span>', { class: 'order-hero-status' + ((data.completion && data.completion.warning) ? ' text-danger fw-bold border-danger' : '') })
                        .append($('<i>', { class: 'bx bx-info-circle' }))
                        .append(document.createTextNode(data.order_status || '-'))
                );
                if (data.completion && data.completion.total > 0) {
                    $("#infobar_order_status").html(
                        '<span class="badge ' + (data.completion.warning ? 'bg-danger' : 'bg-primary') + '">Items ' + data.completion.ratio + '</span>'
                    );
                } else {
                    $("#infobar_order_status").html('');
                }
                $("#infobar_order_number").text('Order # ' + (data.order_number || '-'));
                
                $("#order_user_id").val(data.order_user_id);
                populateDropdown('#order_user', data.order_user, 'get_users_company', 'id', 'fullname');
                
                
                
                $("#order_source_id").val(data.client_source_id);
                populateDropdown('#order_source', data.client_source, 'get_source', 'id', 'description');
                
                 $("#order_status_id").val(data.order_status_id);
                populateDropdown('#order_status', data.order_status, 'get_order_status', 'id', 'description');
                
                $("#order_date").val(data.order_date);
                $("#order_number").val(data.order_number);
                $(".order_date_picker").datepicker({dateFormat: "dd-mm-yy" });
                $("#site_contact").val(data.site_contact);
                $("#site_address").val(data.site_address);
                $("#site_suburb").val(data.site_suburb);
                $("#deliver_note").val(data.deliver_note);
                $("#deliver_instructions").val(data.deliver_instructions);
                $("#site_phone").val(data.site_phone);
                $("#customer_notes").val(data.customer_notes);
                
                $(".delivery_date").datepicker({dateFormat: "dd-mm-yy" });
                $("#order_delivery_date").val(data.delivery_date);
                $("#delivery_rate").val(data.delivery_rate);
                 const isPickupChecked = data.pickup_checkbox == 1;
                $("#pickup_checkbox").prop('checked', isPickupChecked);
                
                
               // $("#pickup_checkbox").val(data.pickup_checkbox);
                $("#client_notes").val(data.client_notes);
                $("#customer_search").val(data.customer_company);
                $("#customer_uid").val(data.customer_uid);
                $("#customer_contact").val(data.customer_contact);
                $("#customer_address").val(data.customer_address);
                $("#customer_suburb").val(data.customer_suburb);
                $("#customer_state").val(data.customer_state);
                $("#customer_postcode").val(data.customer_postcode);
                $("#customer_phone").val(data.customer_phone);
                $("#customer_email").val(data.customer_email);
                
                $("#order_invoiced_date").val(data.order_invoiced_date);
                
                $("#price_level").val(data.price_level);

                // Disable primary fields if cash_sale_checkbox is checked
                togglePrimaryFields(isCashSaleChecked);

                // Attach change event handlers after populating dropdowns
                $('#order_source').change(function() {
                    var selectedSourcetValue = $(this).val();
                    $('#order_source_id').val(selectedSourcetValue);
                });
                $('#order_user').change(function() {
                    var selectedSalesUserValue = $(this).val();
                    $('#order_user_id').val(selectedSalesUserValue);
                });
                
                $('#order_status').change(function() {
                    var selectedStatusValue = $(this).val();
                    $('#order_status_id').val(selectedStatusValue);
                });

                // Attach event listener to the checkbox
                $('#cash_sale_checkbox').change(function() {
                    togglePrimaryFields(this.checked);
                });
				        $('#pickup_checkbox').change(function() {
                    if (this.checked) {
                        $('#site_address').val('Pickup');
                        $('#site_suburb').val('Pickup');
   
                        //$('#deliver_note').val('');
                        $('#deliver_instructions').val('');
                        $('#site_contact').val('');
                        $('#site_phone').val('');
                    } else {
                        $('#site_address').val('');
                        $('#site_suburb').val('');
                        //$('#deliver_note').val('');
                        $('#deliver_instructions').val('');
                        $('#site_contact').val('');
                        $('#site_phone').val('');
                    }
                });
const orderStatusText = String(data.order_status || '').toLowerCase();
const isQuoteWorkflow = orderStatusText === 'quote' || orderStatusText === 'quoted';
const primaryProcessButton = isQuoteWorkflow
    ? '<button class="btn btn-info btn-sm" type="button" onclick="OpenProcessQuoteModal(' + order_id + ')"><i class="bx bx-file"></i> Process Quote</button>'
    : '<button class="btn btn-success btn-sm" type="button" onclick="OpenProcessOrderModal(' + order_id + ')"><i class="bx bxs-factory"></i> Process Order</button>';

// AMENDED BLOCK: Print and email dropdowns
$('#printOrder').html(
    '<div class="d-flex align-items-center gap-2">' +
        primaryProcessButton +
        '<div class="dropdown">' +
            '<button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="printDropdown" data-bs-toggle="dropdown" aria-expanded="false">' +
                '<i class="bx bx-printer"></i> Print' +
            '</button>' +
            '<ul class="dropdown-menu dropdown-menu-end" style="z-index:3000;" aria-labelledby="printDropdown">' +
                '<li><button type="button" class="dropdown-item" onclick="PrintSalesQuote(' + order_id + ')"><i class="bx bx-file"></i> Quote</button></li>' +
                '<li><button type="button" class="dropdown-item" onclick="PrintSalesInvoice(' + order_id + ')"><i class="bx bx-receipt"></i> Invoice</button></li>' +
                '<li><button type="button" class="dropdown-item" onclick="PrintPicking(' + order_id + ')"><i class="bx bx-list-check"></i> Picking</button></li>' +
            '</ul>' +
        '</div>' +
        '<div class="dropdown">' +
            '<button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="emailDropdown" data-bs-toggle="dropdown" aria-expanded="false">' +
                '<i class="bx bx-envelope"></i> Email' +
            '</button>' +
            '<ul class="dropdown-menu dropdown-menu-end" style="z-index:3000;" aria-labelledby="emailDropdown">' +
                '<li><button type="button" class="dropdown-item" onclick="EmailSalesOrder(' + order_id + ', null, null, null, \'quote\')"><i class="bx bx-envelope"></i> Quote</button></li>' +
                '<li><button type="button" class="dropdown-item" onclick="EmailSalesOrder(' + order_id + ')"><i class="bx bx-envelope"></i> Invoice</button></li>' +
            '</ul>' +
        '</div>' +
    '</div>'
);
                
                 $('#deleteInvoiceModal').html('<button onclick="deleteInvoiceModal(' + order_id + ')"  class="btn btn-danger btn-sm"><i class="bx bx-trash"></i> <span id="buttonText">Delete</span></button>');
                
                if(data.order_invoiced){
                     $('#processInvoice').html('<button class="btn btn-success btn-sm"><i class="bx bx-printer"></i> <span id="buttonText">Invoiced</span></button>');
                    }
                    else{
                        $('#processInvoice').html('<button onclick="processInvButtonModal(' + order_id + ')" class="btn btn-outline-secondary btn-sm"><i class="bx bx-printer"></i> <span id="buttonText">Process Invoice</span></button>');
                    }
                


            } else {
                console.error("No data received");
            }
        },
        error: function(xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}

function safeCsvFileText(value) {
    return String(value || '')
        .replace(/[^A-Za-z0-9._-]+/g, '_')
        .replace(/^_+|_+$/g, '') || 'file';
}

function downloadCsvForPart(part_number, customer_contact, csvOrderId) {
    var targetOrderId = csvOrderId || order_id;
    return $.ajax({
        url: 'includes_pages/admin_orders/generate_csv.php', // The URL to your PHP script
        type: 'POST',
        data: { part_number: part_number, order_id: targetOrderId },
        success: function(response) {
            // Create a link element, set its href to the blob URL, and trigger a click event to download the file
            var blob = new Blob([response], { type: 'text/csv' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = safeCsvFileText(targetOrderId) + '_' + safeCsvFileText(part_number) + '_' + safeCsvFileText(customer_contact) + '.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        },
        error: function(xhr, status, error) {
            console.error('Error generating CSV:', error);
        }
    });
}

function toggleOrderItemCompleted(id, checked) {
    $.post(crud_url, {
        action: 'toggle_order_item_completed',
        item_id: id,
        completed: checked ? 1 : 0
    }, function (res) {
        if (res && res.success) {
            if (res.summary && res.summary.ratio) {
                $('#order_items_completion_badge').text(res.summary.ratio + ' complete');
            }
            getOrderId();
            return;
        }

        alert((res && res.message) ? res.message : 'Could not update item completion.');
        Loadtab('order_items');
    }, 'json').fail(function (xhr) {
        alert("AJAX error: " + xhr.responseText);
        Loadtab('order_items');
    });
}

function generateCSV(part_number, customer_contact) {
    downloadCsvForPart(part_number, customer_contact, order_id);
}
function copyContactSite() {
    var primary_contactValue = $('#customer_contact').val();
    var primary_addressValue = $('#customer_address').val();
    var primary_suburbValue = $('#customer_suburb').val();
    var primary_stateValue = $('#customer_state').val();
    var primary_postcodeValue = $('#customer_postcode').val();
    var primary_emailValue = $('#customer_email').val();
    var primary_phoneValue = $('#customer_phone').val();
    $('#site_contact').val(primary_contactValue);
    $('#site_address').val(primary_addressValue);
    $('#site_suburb').val(primary_suburbValue);
    $('#site_state').val(primary_stateValue);
    $('#site_postcode').val(primary_postcodeValue);
    $('#site_email').val(primary_emailValue);
    $('#site_phone').val(primary_phoneValue);
}



// AMENDED BLOCK: replace existing addOrderItemsModal function
function addOrderItemsModal() {
    $("#add_item_order_modal").modal("show");
}
function copyOrderItemsModal() {
    $('#copyOrderItemsModal').modal('show');
    $('#copyOrderItemsModal').on('shown.bs.modal', function () {
    $('#add_item_part_number').focus();
        
    });
}
function copyOrderPurModal() {
    $('#copyOrdePurModal').modal('show');

}
// AMENDED BLOCK: replace existing add_item_order_modal document ready block
$(document).ready(function() {
    $("#add_item_order_modal").on("shown.bs.modal", function() {
        $("#add_item_part_number").focus();

        $("#add_item_units_qty").off("keypress").on("keypress", function(e) {
            if (e.which == 13) {
                AddOrderItems();
            }
        });

        $("#add_item_mark").off("keypress").on("keypress", function(e) {
            if (e.which == 13) {
                AddOrderItems();
            }
        });

        $("#add_item_punch").off("keypress").on("keypress", function(e) {
            if (e.which == 13) {
                AddOrderItems();
            }
        });

        $("#add_item_punch").off("input").on("input", function() {
            var value = $(this).val();
            var formattedValue = value.replace(/([A-Za-z])([^ ])/g, function(match, p1, p2) {
                return p1.toUpperCase() + " " + p2;
            });
            $(this).val(formattedValue.toUpperCase());
        });

        if (!$("#add_item_part_number").data("ui-autocomplete")) {
            $("#add_item_part_number").autocomplete({
                source: function(request, response) {
                    console.log("Request term: ", request.term);
                    $.ajax({
                        url: crud_url,
                        method: "POST",
                        contentType: "application/json",
                        data: JSON.stringify({
                            action: "get_part_number",
                            term: request.term,
                            order_id: $("#infobar_order_id").text().trim()
                        }),
                        dataType: "json",
                        success: function(data) {
                            console.log("Response data: ", data);
                            response($.map(data, function(item) {
                                return {
                                    label: item.part_number,
                                    value: item.part_number,
                                    description: item.description,
                                    rate: item.rate,
                                    has_sub_items: item.has_sub_items
                                };
                            }));
                        },
                        error: function(xhr, status, error) {
                            console.log("AJAX Error: ", status, error);
                            console.log("Response Text: ", xhr.responseText);
                        }
                    });
                },
                minLength: 2,
                autoFocus: true,
                select: function(event, ui) {
                    $("#add_item_part_number").val(ui.item.value);
                    $("#add_item_description").val(ui.item.description);
                    $("#add_item_rate").val(ui.item.rate);
                    $("#has_items_id_tag").val(ui.item.has_sub_items);

                    $("#description_field").show();
                    $("#price_field").show();

                    if (ui.item.has_sub_items) {
                        $("#qty_field").hide();
                        $("#price_field").hide();
                        $("#units_field").show();
                    } else {
                        $("#qty_field").show();
                        $("#units_field").hide();
                    }

                    return false;
                }
            });
        }
            $("#add_item_part_number").off("keydown.additemtab").on("keydown.additemtab", function(event) {
                if (event.keyCode === $.ui.keyCode.TAB && $(this).data("ui-autocomplete").menu.active) {
                    event.preventDefault();
                    $(this).data("ui-autocomplete").menu.select();
                }
            });
    });
});

$(document).ready(function () {
    $('#copyOrdePurModal').on('shown.bs.modal', function () {
        $("#this_order_id_pid").val(order_id);
        $("#search_pi_copy").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: crud_url,
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        action: 'get_pi_copy',
                        term: request.term
                    }),
                    dataType: "json",
                    success: function (data) {
                        response(data);
                    },
                    error: function (xhr, status, error) {
                        console.error("AJAX Error:", status, error);
                        console.error("Response:", xhr.responseText);
                    }
                });
            },
            minLength: 2,
            autoFocus: true,
            select: function (event, ui) {
                // Fill order info fields
                $("#search_pi_copy").val(ui.item.label);
                $("#copy_order_id").val(ui.item.value);
                $("#vendor_name").val(ui.item.vendor_name);

                return false;
            }
        });
    });
});
$(document).ready(function () {
    $('#copyOrdePurModal').on('shown.bs.modal', function () {
        // Clear existing options except placeholder
        console.log("ssss")
        $("#search_vendor").find("option:not(:first)").remove();
        $.ajax({
            url: crud_url,
            method: "POST",
            contentType: "application/json",
            data: JSON.stringify({
                action: "get_vendors_grouped"
            }),
            dataType: "json",
            success: function (data) {
                // Expect: [{ id: "123", vendor_name: "Acme" }, ...]
                if (Array.isArray(data) && data.length > 0) {
                    $.each(data, function (i, vendor) {
                        $("#search_vendor").append(
                            $("<option>", {
                                value: vendor.id,               // this is just the id
                                text: vendor.vendor_name
                            })
                        );
                    });
                }

                // always keep hidden #po_id in sync
                $("#search_vendor").off("change.poid").on("change.poid", function () {
                    $("#po_id").val($(this).val());
                });
            },


            error: function (xhr, status, error) {
                console.error("Vendor dropdown error:", status, error);
                console.error("Response:", xhr.responseText);
            }
        });

    });
});
// NEW: Create Purchase Order from tagged items of the current order
function copyOrderPurchaseButton() {
    var source_order_id = $("#this_order_id_pid").val();     // current order id (disabled input in modal)
    var vendor_source_po_id = $("#search_vendor").val();      // an existing PO id for the chosen vendor (used to grab vendor_name)
    if (!source_order_id) {
        alert("Missing source order id.");
        return;
    }
    if (!vendor_source_po_id) {
        alert("Please select a vendor.");
        return;
    }

    $.ajax({
        url: crud_url,
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "create_po_from_tagged",
            order_id: parseInt(source_order_id, 10),
            vendor_source_po_id: parseInt(vendor_source_po_id, 10)
        }),
        success: function (res) {
            if (res && res.success) {
                // Close modal and optionally navigate or notify
                $('#copyOrdePurModal').modal('hide');
                alert("Created PO #" + res.pid + " and copied " + (res.copied_items || 0) + " tagged item(s).");
                Loadtab('order_items');
            } else {
                alert((res && res.message) ? res.message : "Failed to create purchase order.");
            }
        },
        error: function (xhr, status, error) {
            console.error("Create PO error:", status, error, xhr.responseText);
            alert("Server error creating purchase order.");
        }
    });
}


$(document).ready(function () {
    $('#copyOrderItemsModal').on('shown.bs.modal', function () {
        $("#this_order_id").val(order_id);
        $("#search_order_copy").autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: crud_url,
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        action: 'get_order_copy',
                        term: request.term
                    }),
                    dataType: "json",
                    success: function (data) {
                        response(data);
                    },
                    error: function (xhr, status, error) {
                        console.log("AJAX Error:", status, error);
                        console.log("Response:", xhr.responseText);
                    }
                });
            },
            minLength: 2,
            autoFocus: true,
            select: function (event, ui) {
                // Fill order info fields
                $("#search_order_copy").val(ui.item.label);
                $("#copy_order_id").val(ui.item.value);
                $("#order_customer").val(ui.item.customer_company);
                $("#customer_order").val(ui.item.order_number);

                // Fetch order items
                $.ajax({
                    url: crud_url,
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({
                        action: 'get_order_items',
                        copy_order_id: ui.item.value
                    }),
                    dataType: "json",
                    success: function (items) {
                        let rows = '';
                        if (items.length > 0) {
                            $.each(items, function (i, item) {
                                rows += `
                                    <tr>
                                        <td>${item.part_number}</td>
                                        <td>${item.description}</td>
                                    </tr>`;
                            });
                        } else {
                            rows = '<tr><td colspan="5" class="text-center text-muted">No items found for this order.</td></tr>';
                        }
                        $("#order_items_body").html(rows);
                    },
                    error: function (xhr, status, error) {
                        console.error("Error loading order items:", error);
                        $("#order_items_body").html('<tr><td colspan="5" class="text-danger text-center">Error loading items</td></tr>');
                    }
                });

                return false;
            }
        });
    });
});

function copyOrderItemsButton() {
    const fromOrderId = $("#copy_order_id").val();
    const toOrderId = $("#this_order_id").val();

    if (!fromOrderId || !toOrderId) {
        alert("Both source and destination order IDs are required.");
        return;
    }

    if (fromOrderId === toOrderId) {
        alert("Cannot copy items to the same order.");
        return;
    }

    if (!confirm("Are you sure you want to copy all items and sub-items to this order?")) {
        return;
    }

    $.ajax({
        url: crud_url,
        method: "POST",
        contentType: "application/json",
        data: JSON.stringify({
            action: "copy_order_items",
            copy_order_id: fromOrderId,
            this_order_id: toOrderId
        }),
        success: function (response) {
                $('#copyOrderItemsModal').modal('hide');

                // Optional short delay before loading tab
                setTimeout(function () {
                    Loadtab('order_items');
                }, 1000); // Adjust delay as needed (ms)
            },

        error: function (xhr, status, error) {
            console.error("Copy failed:", status, error);
            alert("An error occurred while copying items.");
        }
    });
    
}


// AMENDED BLOCK: replace existing AddOrderItems function
function AddOrderItems() {
    var formData = {
        action: "add_order_items",
        order_id: order_id,
        has_items: $("#has_items_id_tag").val(),
        part_number: $("#add_item_part_number").val(),
        mark: $("#add_item_mark").val(),
        punch: $("#add_item_punch").val(),
        qty_item: $("#add_item_qty").val(),
        qty: $("#add_item_units").val(),
        qty_unit: $("#add_item_units_qty").val()
    };

    ajaxPostRequest(formData, crud_url);

    setTimeout(function() {
        Loadtab("order_items");
    }, 1000);

    $("#add_item_units").val("");
    $("#add_item_units_qty").val("");
    $("#add_item_mark").val("");
    $("#add_item_punch").val("");
    $("#add_item_units").focus();
}
function editOrderItem(item_id) {
    $("#edit_item_id").val(item_id);
    var formData = {
        action: 'read_order_item',
        item_id: item_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var parsedResponse = JSON.parse(response);
            console.log("Parsed Response: ", parsedResponse);
            if(parsedResponse && parsedResponse.length > 0) {
                var data = parsedResponse[0];
                if (data.has_items) {
                         $("#edit_has_item").hide();
                    } else {
                         $("#edit_has_item").show();
                    }

                $("#edit_part_number").val(data.part_number);
                $("#edit_item_description").val(data.description);
                $("#edit_item_qty").val(data.qty);
                $("#edit_item_rate").val(data.rate);

                $('#edit_order_item_modal').modal('show');
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function SaveOrderItems() {
    var item_id = $("#edit_item_id").val();
     var formData = {
        action: 'save_order_items',
        item_id: item_id,
        description: $('#edit_item_description').val(), 
        qty: $('#edit_item_qty').val(),
        rate: $('#edit_item_rate').val(),

    };
    ajaxPostRequest(formData, crud_url);
    $('#edit_order_item_modal').modal('hide');
     setTimeout(function() {
        Loadtab('order_items');
    }, 1000);
}
// AMENDED BLOCK: replace your existing editOrderSubItem function with this
function editOrderSubItem(sub_item_id) {
    $("#edit_sub_item_id").val(sub_item_id);

    var formData = {
        action: "read_order_sub_item",
        sub_item_id: sub_item_id
    };

    var jsonData = JSON.stringify(formData);

    $.ajax({
        url: crud_url,
        type: "POST",
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var parsedResponse = JSON.parse(response);

            if (parsedResponse && parsedResponse.length > 0) {
                var data = parsedResponse[0];

                $("#edit_item_mark").val(data.mark);
                $("#edit_item_punch").val(data.punch);
                $("#edit_item_units").val(data.qty);
                $("#edit_item_units_qty").val(data.qty_unit);

                $("#edit_order_sub_item_modal").off("shown.bs.modal").on("shown.bs.modal", function() {
                    $("#edit_item_mark").focus();
                });

                $("#edit_item_punch").off("input").on("input", function() {
                    var value = $(this).val();
                    var formattedValue = value.replace(/([A-Za-z])([^ ])/g, function(match, p1, p2) {
                        return p1.toUpperCase() + " " + p2;
                    });
                    $(this).val(formattedValue.toUpperCase());
                });

                $("#edit_order_sub_item_modal").modal("show");
            }
        }
    });
}

function SaveOrderSubItems() {
    var sub_item_id = $("#edit_sub_item_id").val();
     var formData = {
        action: 'save_order_sub_items',
        sub_item_id: sub_item_id,
        mark: $('#edit_item_mark').val(), 
        punch: $('#edit_item_punch').val(),
        qty: $('#edit_item_units').val(),
        qty_unit: $('#edit_item_units_qty').val(),
    };
    ajaxPostRequest(formData, crud_url);
    $('#edit_order_sub_item_modal').modal('hide');
     setTimeout(function() {
        Loadtab('order_items');
    }, 1000);
}
function deleteOrderItem() {
     var del_item_id = $("#edit_item_id").val();
    console.log(del_item_id)

        var formData = {
            action: 'delete_order_item',
            del_item_id: del_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_order_item_modal').modal('hide');
         setTimeout(function() {
        Loadtab('order_items');
    }, 1000);
  
}
function deleteOrderSubItem() {
     var del_sub_item_id = $("#edit_sub_item_id").val();
    console.log(del_sub_item_id)

        var formData = {
            action: 'delete_order_sub_item',
            del_sub_item_id: del_sub_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_order_sub_item_modal').modal('hide');
         Loadtab('items');
  
}


function AddOrderActivityModal() {
    $('#modalAddOrderActivity').modal('show');

}

function AddOrderActivity() {
    var formData = {
        action: 'add_activity',
        order_id: order_id,
        description: $('#add_activity_description').val(),
        action_date: $('#add_date_activity').val(),
    };

    ajaxPostRequest(formData, crud_url);
    $('#modalAddOrderActivity').modal('hide');
    
    setTimeout(function() {
        Loadtab('activity');
    }, 1000);
    
}

function EditOrderActivity(activity_id) {
    $("#edit_activity_id").val(activity_id);
    var formData = {
        action: 'get_activity',
        activity_id: activity_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var parsedResponse = JSON.parse(response);
            if(parsedResponse && parsedResponse.length > 0) {
                var data = parsedResponse[0];
                console.log(data.action_date)
                $("#edit_activity_description").val(data.description);
                
                
                $("#edit_date_activity").val(data.action_date);
                $('#modalEditOrderActivity').modal('show');
            }
        }
    });
}
function SaveOrderActivity() {
    var formData = {
        action: 'save_activity',
        activity_id: $('#edit_activity_id').val(),
        description: $('#edit_activity_description').val(),
        action_date: $('#edit_date_activity').val(),
    };

    ajaxPostRequest(formData, crud_url);
    $('#modalEditOrderActivity').modal('hide');
    
    setTimeout(function() {
        Loadtab('activity');
    }, 1000);
    
}
function delOrderActivity(id) {
    if (!confirm('Delete this activity record?')) {
        return;
    }

    var formData = {
        action: 'delete_order_activity',
        id: id,
    };
    var jsonData = JSON.stringify(formData);
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
        Loadtab('activity');
    }, 1000);
}

function togglePrimaryFields(disable) {
    // Disable or enable fields
    $('[id^="customer_"]').prop('disabled', disable);

    // If disabling the fields, clear their values as well
    if (disable) {
        $('[id^="customer_"]').val('');
    }
}
function toggleOrderFields(disable) {
    $('[id^="order_"]').prop('disabled', disable);
}
function importOrder() {
    var formData = {
        action: 'import_to_invoice',
        order_id: order_id
    };

    console.log(formData)
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
        Loadtab('invoice');
    }, 1000);

}
function editInvoiceItem(invoice_item_id) {
    $("#edit_invoice_item_id").val(invoice_item_id);
    var formData = {
        action: 'read_invoice_item',
        invoice_item_id: invoice_item_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var parsedResponse = JSON.parse(response);
            if(parsedResponse && parsedResponse.length > 0) {
                var data = parsedResponse[0];
                $("#edit_invoice_description").val(data.description);
                $("#edit_invoice_qty").val(data.qty);
                $("#edit_invoice_rate").val(data.rate);
                 $('#edit_invoice_item_modal').modal('show');
                $('#edit_invoice_item_modal').on('shown.bs.modal', function () {
                    $('#edit_invoice_description').focus();
                });

            }
        },
    });
}
function saveInvoiceItem() {
    var formData = {
        action: 'save_invoice_item',
        description: $('#edit_invoice_description').val(),
        qty: $('#edit_invoice_qty').val(),
        rate: $('#edit_invoice_rate').val(),
        invoice_item_id: $('#edit_invoice_item_id').val(),
    };
    ajaxPostRequest(formData, crud_url);
    $('#edit_invoice_item_modal').modal('hide');
    setTimeout(function() {
        Loadtab('invoice');
    }, 1000);
    
}
function delInvoiceId() {
    var id = $('#edit_invoice_item_id').val();
    var formData = {
        action: 'delete_invoice_item',
        id: id,
    };
    
    ajaxPostRequest(formData, crud_url); // Assuming ajaxPostRequest takes JSON string
    $('#edit_invoice_item_modal').modal('hide');
    setTimeout(function() {
        Loadtab('invoice');
    }, 1000);
}
function addNewPack() {
     var formData = {
        action: 'add_pack',
        order_id: order_id,
    };
    ajaxPostRequest(formData, crud_url);
     setTimeout(function() {
        Loadtab('pack_tab');
    }, 1000);
}

function initializeDragAndDrop() {
    $(".draggable").draggable({
        helper: "clone",
        cursor: "move",
        start: function(event, ui) {
            $(ui.helper).css("z-index", 1000);
        }
    });

    $(".droppable").droppable({
        accept: ".draggable",
        drop: function(event, ui) {
            var pack_id = $(this).data("pack-id");
            var item_id = $(ui.helper).data("id");

            // Update the pack_id in the database
            $.ajax({
                url: crud_url, // Replace with the path to your CRUD file
                method: "POST",
                data: JSON.stringify({
                    action: 'update_pack_item',
                    id: item_id,
                    pack_id: pack_id
                }),
                contentType: "application/json",
                success: function(response) {
                    try {
                        var result = JSON.parse(response);
                        if (result.success) {
                              setTimeout(function() {
                        Loadtab('pack_tab');
                    }, 1000);
                           // alert("Item added to pack successfully.");
                        } else if (result.warning) {
                           // alert("No changes made.");
                        } else {
                           // alert("Failed to add item to pack.");
                        }
                    } catch (e) {
                        console.error("Error parsing JSON response: ", e);
                        console.log("Raw response: ", response);
                        alert("An error occurred while processing the response.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                    alert("An error occurred while updating the pack.");
                }
            });
        }
    });
}
function delPack(pack_id,pack_number) {
    var formData = {
        action: 'delete_pack',
        order_id: order_id,
        pack_id: pack_id,
        pack_number: pack_number,
    };
    console.log(formData)
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
        Loadtab('pack_tab');
    }, 1000);
}

function getProcessOrderId() {
    return currentOrderId();
}

function OpenProcessOrderModal(processOrderId) {
    const resolvedOrderId = processOrderId || currentOrderId();
    order_id = resolvedOrderId;
    $('#process_workflow_order_id').val(resolvedOrderId);
    $('#process_order_button').prop('disabled', false).html('<i class="bx bxs-factory"></i> Process');
    renderProcessOrderSummary({});
    loadProcessOrderSummary();
    loadProcessOrderActivity();
    $('#ProcessOrderModal').modal('show');
}

function processOrderActivityLabels() {
    return {
        upload_original_opened: 'Open attachments',
        order_confirmation_emailed: 'Email',
        order_confirmation_printed: 'Print',
        quote_emailed: 'Email',
        quote_printed: 'Print',
        quote_payment_required: 'Payment required',
        quote_payment_received: 'Payment received',
        quote_converted_to_order: 'Convert',
        production_cards_printed: 'Print',
        production_csv_saved: 'Save',
        labels_dymo_printed: 'Dymo',
        labels_zebra_printed: 'Zebra',
        delivery_docket_printed: 'Print',
        order_processed: 'Process'
    };
}

function renderProcessOrderActivity(history) {
    const labels = processOrderActivityLabels();

    $('[data-workflow-summary]').each(function() {
        const workflowType = $(this).data('workflow-summary');
        const item = history && history[workflowType] ? history[workflowType] : null;
        const label = labels[workflowType] || 'Action';

        if (!item) {
            $(this)
                .removeClass('text-success')
                .addClass('text-muted')
                .html('<i class="bx bx-time-five"></i> ' + label + ': No activity yet');
            return;
        }

        $(this)
            .removeClass('text-muted')
            .addClass('text-success')
            .html('<i class="bx bx-check-circle"></i> ' + label + ': ' + item.date + ' by ' + item.user);
    });
}

let processOrderCurrentSummary = {};

function getVisibleProcessOrderSummaryFallback() {
    const packs = new Set();
    let weight = 0;
    const parts = new Set();

    $('#jobs_tab_body .droppable[data-pack-id]').each(function() {
        const packId = String($(this).data('pack-id') || '').trim();
        if (packId && packId !== '0') {
            packs.add(packId);
        }

        const weightText = $(this).find('.col-3').first().text().replace(/[^0-9.-]/g, '');
        const packWeight = parseFloat(weightText);
        if (!Number.isNaN(packWeight)) {
            weight += packWeight;
        }
    });

    $('#jobs_tab_body .droppable[data-pack-id] .col-3, #jobs_tab_body .hover-row .col-1, #jobs_tab_body .hover-row .col-2').each(function() {
        const text = $.trim($(this).text());
        if (/^[A-Z]{1,4}\d{3,}/i.test(text)) {
            parts.add(text);
        }
    });

    return {
        packs: packs.size,
        manufactured_items: parts.size,
        total_items: parts.size,
        weight_kg: Math.round(weight * 10) / 10
    };
}

function renderProcessOrderSummary(summary) {
    let data = summary || {};
    if (!Number(data.packs || 0) && !Number(data.total_items || 0) && !Number(data.weight_kg || 0)) {
        const fallback = getVisibleProcessOrderSummaryFallback();
        if (fallback.packs || fallback.total_items || fallback.weight_kg) {
            data = fallback;
        }
    }
    processOrderCurrentSummary = data;
    const values = [
        data.packs || 0,
        data.manufactured_items || 0,
        data.total_items || 0,
        (data.weight_kg || 0) + 'kg'
    ];

    $('#process_order_summary .summary-value').each(function(index) {
        $(this).text(values[index]);
    });
}

function getProcessQuoteId() {
    return $('#process_quote_order_id').val() || order_id;
}

function OpenProcessQuoteModal(processOrderId) {
    $('#process_quote_order_id').val(processOrderId || order_id);
    $('#convert_quote_button').prop('disabled', false).html('Convert');
    loadProcessQuoteActivity();
    $('#ProcessQuoteModal').modal('show');
}

function renderProcessQuoteActivity(history) {
    const labels = processOrderActivityLabels();
    const paymentRequired = history
        && history.quote_payment_required
        && !String(history.quote_payment_required.description || '').toLowerCase().includes('cleared');
    const paymentReceived = history
        && history.quote_payment_received
        && !String(history.quote_payment_received.description || '').toLowerCase().includes('cleared');

    $('#quote_payment_required_checkbox').prop('checked', !!paymentRequired);
    $('#quote_payment_received_checkbox').prop('checked', !!paymentReceived).prop('disabled', !paymentRequired);

    $('[data-quote-workflow-summary]').each(function() {
        const workflowType = $(this).data('quote-workflow-summary');
        const item = history && history[workflowType] ? history[workflowType] : null;
        const label = labels[workflowType] || 'Action';

        if (!item) {
            $(this)
                .removeClass('text-success')
                .addClass('text-muted')
                .html('<i class="bx bx-time-five"></i> ' + label + ': No activity yet');
            return;
        }

        $(this)
            .removeClass('text-muted')
            .addClass('text-success')
            .html('<i class="bx bx-check-circle"></i> ' + label + ': ' + item.date + ' by ' + item.user);
    });
}

function loadProcessQuoteActivity() {
    const processOrderId = getProcessQuoteId();

    if (!processOrderId) {
        renderProcessQuoteActivity({});
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'get_process_order_activity',
            order_id: processOrderId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderProcessQuoteActivity(response.history || {});
            }
        }
    });
}

function recordProcessQuoteActivity(workflowType, callback) {
    const processOrderId = getProcessQuoteId();

    if (!processOrderId) {
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'record_process_order_activity',
            order_id: processOrderId,
            workflow_type: workflowType
        }),
        contentType: 'application/json',
        dataType: 'json',
        complete: function() {
            if (typeof callback === 'function') {
                callback();
            }
        },
        success: function(response) {
            if (response && response.success) {
                renderProcessQuoteActivity(response.history || {});
            }
        }
    });
}

function ProcessQuotePaymentRequiredChanged() {
    const workflowType = $('#quote_payment_required_checkbox').is(':checked')
        ? 'quote_payment_required'
        : 'quote_payment_required_cleared';
    recordProcessQuoteActivity(workflowType, loadProcessQuoteActivity);
}

function ProcessQuotePaymentReceivedChanged() {
    const workflowType = $('#quote_payment_received_checkbox').is(':checked')
        ? 'quote_payment_received'
        : 'quote_payment_received_cleared';
    recordProcessQuoteActivity(workflowType, loadProcessQuoteActivity);
}

function ProcessQuoteEmail() {
    var processOrderId = getProcessQuoteId();
    $('#ProcessQuoteModal').modal('hide');
    EmailSalesOrder(processOrderId, null, null, null, 'quote');
}

function ProcessQuotePrint() {
    recordProcessQuoteActivity('quote_printed', function() {
        loadProcessQuoteActivity();
    });
    PrintSalesQuote(getProcessQuoteId());
}

function ProcessQuoteConvertToOrder() {
    var processOrderId = getProcessQuoteId();

    if (!processOrderId) {
        alert('Missing order id.');
        return;
    }

    if ($('#quote_payment_required_checkbox').is(':checked') && !$('#quote_payment_received_checkbox').is(':checked')) {
        alert('Payment is required before this quote can be converted.');
        return;
    }

    if (!confirm('Convert this quote to an order?')) {
        return;
    }

    $('#convert_quote_button').prop('disabled', true).text('Converting...');

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'convert_quote_to_order',
            order_id: processOrderId,
            payment_required: $('#quote_payment_required_checkbox').is(':checked') ? 1 : 0,
            payment_received: $('#quote_payment_received_checkbox').is(':checked') ? 1 : 0
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                handleSuccess(response.message || 'Quote converted to order.');
                if (response.history) {
                    renderProcessQuoteActivity(response.history);
                }
                $('#ProcessQuoteModal').modal('hide');
                getOrderId();
                return;
            }

            $('#convert_quote_button').prop('disabled', false).text('Convert');
            alert((response && response.message) ? response.message : 'Convert failed.');
        },
        error: function(xhr) {
            $('#convert_quote_button').prop('disabled', false).text('Convert');
            alert(xhr.responseText || 'Convert failed.');
        }
    });
}

function currentProcessOrderUserName() {
    return $.trim($('.nav-profile .dropdown-toggle').first().text())
        || $.trim($('.profile h6').first().text())
        || 'Current user';
}

function markProcessOrderActivityPending(workflowType) {
    const labels = processOrderActivityLabels();
    const label = labels[workflowType] || 'Action';
    const now = new Date();
    const dateText = now.toLocaleDateString('en-AU') + ' ' + now.toLocaleTimeString('en-AU', {
        hour: 'numeric',
        minute: '2-digit'
    });

    $('[data-workflow-summary="' + workflowType + '"]')
        .removeClass('text-muted')
        .addClass('text-success')
        .html('<i class="bx bx-check-circle"></i> ' + label + ': ' + dateText + ' by ' + currentProcessOrderUserName());
}

function loadProcessOrderActivity() {
    const processOrderId = getProcessOrderId();

    if (!processOrderId) {
        renderProcessOrderActivity({});
        renderProcessOrderSummary({});
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'get_process_order_activity',
            order_id: processOrderId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderProcessOrderActivity(response.history || {});
                renderProcessOrderSummary(response.summary || {});
            }
        }
    });
}

function recordProcessOrderActivity(workflowType, callback) {
    const processOrderId = getProcessOrderId();
    markProcessOrderActivityPending(workflowType);

    if (!processOrderId) {
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'record_process_order_activity',
            order_id: processOrderId,
            workflow_type: workflowType
        }),
        contentType: 'application/json',
        dataType: 'json',
        complete: function() {
            if (typeof callback === 'function') {
                callback();
            }
        },
        success: function(response) {
            if (response && response.success) {
                renderProcessOrderActivity(response.history || {});
            }
        }
    });
}

function ProcessOrderOpenAttachments() {
    recordProcessOrderActivity('upload_original_opened');
    $('#ProcessOrderModal').modal('hide');
    if ($('#attachments-tab').length && typeof bootstrap !== 'undefined') {
        bootstrap.Tab.getOrCreateInstance(document.querySelector('#attachments-tab')).show();
    }
    Loadtab('attachments');
}

function ProcessOrderEmailConfirmation() {
    var processOrderId = getProcessOrderId();
    $('#ProcessOrderModal').modal('hide');
    EmailSalesOrder(processOrderId, null, null, null, 'order_confirmation');
}

function ProcessOrderPrintConfirmation() {
    recordProcessOrderActivity('order_confirmation_printed', function() {
        loadProcessOrderActivity();
    });
    PrintSalesOrder(getProcessOrderId());
}

function ProcessOrderPrintProductionCards() {
    if (!processOrderCurrentSummary || Number(processOrderCurrentSummary.manufactured_items || 0) <= 0) {
        alert('No manufactured items found for this order.');
        return;
    }
    recordProcessOrderActivity('production_cards_printed', function() {
        loadProcessOrderActivity();
    });
    PrintProdCardAll(getProcessOrderId());
}

function ProcessOrderSaveProductionCsvs() {
    var processOrderId = getProcessOrderId();

    if (!processOrderId) {
        alert('Missing order id.');
        return;
    }

    $('#process_csv_button').prop('disabled', true).text('Saving...');

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'get_process_order_csv_parts',
            order_id: processOrderId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (!response || !response.success) {
                alert((response && response.message) ? response.message : 'Could not load production CSV items.');
                return;
            }

            var parts = response.parts || [];
            if (!parts.length) {
                alert('No manufactured production items found for CSV export.');
                return;
            }

            var customerContact = response.customer_contact || $('#customer_contact').val() || $('#customer_search').val() || 'customer';
            var chain = $.Deferred().resolve().promise();

            parts.forEach(function(partNumber) {
                chain = chain.then(function() {
                    return downloadCsvForPart(partNumber, customerContact, processOrderId);
                });
            });

            chain.done(function() {
                recordProcessOrderActivity('production_csv_saved', function() {
                    loadProcessOrderActivity();
                });
            }).fail(function() {
                alert('One or more production CSV files failed to save.');
            }).always(function() {
                $('#process_csv_button').prop('disabled', false).text('Save');
            });
        },
        error: function(xhr) {
            $('#process_csv_button').prop('disabled', false).text('Save');
            alert(xhr.responseText || 'Could not load production CSV items.');
        }
    });
}

function loadProcessOrderSummary() {
    const processOrderId = getProcessOrderId();

    if (!processOrderId) {
        renderProcessOrderSummary({});
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'get_process_order_summary',
            order_id: processOrderId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderProcessOrderSummary(response.summary || {});
            }
        }
    });
}

function ProcessOrderPrintLabels(labelType) {
    var processOrderId = getProcessOrderId();
    if (!processOrderCurrentSummary || Number(processOrderCurrentSummary.packs || 0) <= 0) {
        alert('No packs found for this order. Create packs before printing labels.');
        return;
    }
    if (labelType === 'zebra') {
        recordProcessOrderActivity('labels_zebra_printed', function() {
            loadProcessOrderActivity();
        });
        PrintPackAllZeb(processOrderId);
        return;
    }
    recordProcessOrderActivity('labels_dymo_printed', function() {
        loadProcessOrderActivity();
    });
    PrintPackAll(processOrderId);
}

function ProcessOrderPrintDelivery() {
    recordProcessOrderActivity('delivery_docket_printed', function() {
        loadProcessOrderActivity();
    });
    PrintSalesDelivery(getProcessOrderId());
}

function ProcessOrderButton() {
    var processOrderId = getProcessOrderId();

    if (!processOrderId) {
        alert('Missing order id.');
        return;
    }

    if (!confirm('Process this order and move it to In Production?')) {
        return;
    }

    $('#process_order_button').prop('disabled', true).text('Processing...');

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'process_order_to_production',
            order_id: processOrderId
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                handleSuccess(response.message || 'Order processed.');
                if (response.history) {
                    renderProcessOrderActivity(response.history);
                }
                $('#ProcessOrderModal').modal('hide');
                getOrderId();
                return;
            }

            $('#process_order_button').prop('disabled', false).html('<i class="bx bxs-factory"></i> Process');
            alert((response && response.message) ? response.message : 'Process failed.');
        },
        error: function(xhr) {
            $('#process_order_button').prop('disabled', false).html('<i class="bx bxs-factory"></i> Process');
            alert(xhr.responseText || 'Process failed.');
        }
    });
}

function EmailSalesOrder(order_id, defaultEmail, defaultSubject, defaultBody, emailType) {
    ensureEmailOrderModalFields();

    const orderNumber = $('#order_number').val() || '';
    const emailMode = emailType || 'invoice';
    const rawOrderDate = $('#order_date').val() || '';
    const orderDate = rawOrderDate.replaceAll('-', '/');
    const fallbackSubject = emailMode === 'order_confirmation'
        ? 'Featherstones ORDER CONFIRMATION #' + order_id
        : 'Featherstones Document #' + (orderNumber || order_id);
    const fallbackBody = emailMode === 'quote'
        ? 'Please find attached your quote #' + order_id + (orderNumber ? ' for your Purchase Order #' + orderNumber : '') + '.'
        : emailMode === 'order_confirmation'
        ? (
            'Please find attached your ORDER CONFIRMATION #' + order_id + (orderNumber ? ' for your Purchase Order #' + orderNumber : '') + (orderDate ? ' of ' + orderDate : '') + '.\n\n' +
            'Please confirm quantities and advise discrepancies.'
        )
        : 'Please find attached your document #' + (orderNumber || order_id) + '.';
    const bodyText = defaultBody || fallbackBody;

    $('#email_order_id').val(order_id);
    $('#email_document_type').val(emailMode);
    $('#email_address_po1').val(defaultEmail || $('#customer_email').val() || '');
    $('#email_subject').val(defaultSubject || fallbackSubject);
    $('#email_body').val(bodyText);
    $('#email_sales_order').modal('show');
}

function ensureEmailOrderModalFields() {
    const $modalBody = $('#email_sales_order .modal-body');

    if (!$modalBody.length) {
        return;
    }

    if (!$('#email_document_type').length) {
        $('#email_order_id').after('<input name="email_document_type" type="hidden" id="email_document_type" class="form-control">');
    }

    if (!$('#email_subject').length) {
        $modalBody.append(
            '<div class="row mt-3 email-subject-row">' +
                '<div class="col-12">' +
                    '<label for="email_subject" class="form-label">Subject</label>' +
                    '<input name="email_subject" type="text" id="email_subject" class="form-control">' +
                '</div>' +
            '</div>'
        );
    }

    if (!$('#email_body').length) {
        $modalBody.append(
            '<div class="row mt-3 email-body-row">' +
                '<div class="col-12">' +
                    '<label for="email_body" class="form-label">Body</label>' +
                    '<textarea name="email_body" id="email_body" class="form-control" rows="7"></textarea>' +
                '</div>' +
            '</div>'
        );
    }
}


function SendEmailOrder() {
    const orderId = $('#email_order_id').val();
    const documentType = $('#email_document_type').val() || 'invoice';
    const emailTo1 = $('#email_address_po1').val();
    const emailTo2 = $('#email_address_po2').val();
    const emailSubject = $('#email_subject').val();
    const emailBody = $('#email_body').val();

    if (!emailTo1) {
        alert("Please enter at least one email address.");
        return;
    }

    if (!emailSubject || !emailBody) {
        alert("Please enter an email subject and body.");
        return;
    }

    const pdfUrl = documentType === 'quote'
        ? `../pdf/sales_quote_v1.php?order_id=${orderId}&s=1`
        : documentType === 'order_confirmation'
            ? `../pdf/sales_order_v1.php?order_id=${orderId}&s=1`
            : `../pdf/sales_invoice_v2.php?order_id=${orderId}&s=1`;
    const pdfPath = documentType === 'quote'
        ? `/files/sales_quote_v1${orderId}.pdf`
        : documentType === 'order_confirmation'
            ? `/files/sales_order_v1${orderId}.pdf`
            : `/files/sales_invoice_v2${orderId}.pdf`;

    $.ajax({
        url: pdfUrl,
        type: 'GET',
        success: function() {
            setTimeout(function() {
                $.ajax({
                    url: 'includes/common_mail.php',
                    type: 'POST',
                    data: {
                        action: 'send_sales_order_email',
                        order_id: orderId,
                        pdf_path: pdfPath,
                        email_to1: emailTo1,
                        email_to2: emailTo2,
                        email_subject: emailSubject,
                        email_body: emailBody,
                        document_type: documentType
                    },
                    success: function(response) {
                        let res;
                        try {
                            res = typeof response === 'object' ? response : JSON.parse(response);
                        } catch (e) {
                            alert("Server error or invalid response.");
                            return;
                        }

                        if (res.success) {
                            $('#site_message').html(`
                                <div class="alert alert-success" role="alert">
                                    Email sent successfully!
                                </div>
                            `).fadeIn('slow');
                            setTimeout(function() {
                                $('#site_message .alert').fadeOut('slow', function() {
                                    $(this).remove();
                                });
                            }, 3000);
                            $('#email_sales_order').modal('hide');
                            if (documentType === 'order_confirmation') {
                                $('#process_workflow_order_id').val(orderId);
                                loadProcessOrderActivity();
                                $('#ProcessOrderModal').modal('show');
                            } else if (documentType === 'quote') {
                                $('#process_quote_order_id').val(orderId);
                                loadProcessQuoteActivity();
                                $('#ProcessQuoteModal').modal('show');
                            }
                        } else {
                            alert("Error: " + res.message);
                        }
                    },
                    error: function() {
                        alert("Failed to send email. Please try again.");
                    }
                });
            }, 500);
        },
        error: function() {
            alert("Failed to generate PDF. Please try again.");
        }
    });
}



$(document).ready(function() {
    if (order_id) {
        Loadtab('home');
    } else {
        Loadtab('home');
        
    }

    checkAccountingType();
});

