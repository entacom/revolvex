var pid = getUrlVars()["pid"];
var crud_url = 'includes_pages/admin_purchasing/crud.php';
var content_url = 'includes_pages/admin_purchasing/content.php';
function Loadtab(tab_id) {
    $.ajax({
        type: "POST",
        url: content_url,
        data: { tab_id: tab_id , pid: pid},
        dataType: "json", 
        success: function (response) {
            $('#jobs_tab_body').html(response.html);
            if (pid) {
                getPurchaseId();
               // toggleOrderFields('false')
                LoadSubtab('ordered_items')
                
                // Change button text to "Save" and set the event listener to saveContacts
                $('#saveContactsDiv button').text('Save');
                $('#saveContactsDiv').off('click').on('click', function(event) {
                    if (event.target.closest('button')) {
                        savePurchase();
                    }
                });
            } else {
                $('#saveContactsDiv button').text('Create Order');
                $('#saveContactsDiv').off('click').on('click', function(event) {
                    if (event.target.closest('button')) {
                        createNewPurchase();
                    }
                });
            }
        },
    });
}

function LoadSubtab(sub_tab_id) {

    $.ajax({
        type: "POST",
        url: content_url,
        data: { sub_tab_id: sub_tab_id , pid: pid},
        dataType: "json", 
        success: function (response) {
            $('#sub_items_body').html(response.html);
        },
    });
}
function copyOrderItemsModal() {
    $('#copyOrderItemsModal').modal('show');
    $('#copyOrderItemsModal').on('shown.bs.modal', function () {
    $('#add_item_part_number').focus();

    
        
    });
}
$(document).ready(function () {
    $('#copyOrderItemsModal').on('shown.bs.modal', function () {
        $("#this_order_id").val(pid);
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
                $("#vendor_name").val(ui.item.vendor_name);
                $("#ven_inv_number").val(ui.item.ven_inv_number);

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
// AMENDED BLOCK: replace your function with the following
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
            action: "copy_purchase_items",
            source_pid: fromOrderId,   // NOTE: must match PHP handler
            target_pid: toOrderId      // NOTE: must match PHP handler
        }),
        success: function (resp) {
            // Handle string or object responses
            let response = resp;
            if (typeof resp === "string") {
                try { response = JSON.parse(resp); } catch (e) { /* ignore */ }
            }

            if (response && response.message) {
                alert(response.message);
            } else {
                alert("Copy completed.");
            }

            // Bootstrap 5 modal hide (no jQuery plugin in Bootstrap 5)
            const modalEl = document.getElementById("copyOrderItemsModal");
            if (modalEl && window.bootstrap && bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
            }

            // Refresh items tab
            setTimeout(function () {
                Loadtab("order_items");
            }, 400);
        },
        error: function (xhr, status, error) {
            console.error("Copy failed:", status, error, xhr && xhr.responseText);
            alert("An error occurred while copying items.");
        }
    });
}

function createNewPurchase() {
    pid = null; // Ensure order_id is null to trigger the create action
    savePurchase();
}
function getPurchaseId() {
    var formData = {
        action: 'read_purchase',
        pid: pid,
    };
    var jsonData = JSON.stringify(formData);
    console.log("Request: " + jsonData);

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0]; // Assuming response[0] contains the main data
            console.log(data);

            // Setting form fields and handling placeholders for empty fields
            $("#vendor_uid").val(data.vendor_uid || '');
            $("#vendor_search").val(data.vendor_name || '');
            $("#vendor_address").val(data.vendor_address || '');
            $("#vendor_suburb").val(data.vendor_suburb || '');
            $("#vendor_state").val(data.vendor_state || '');
            $("#vendor_postcode").val(data.vendor_postcode || '');
            $("#vendor_phone").val(data.vendor_phone || '');
            $("#vendor_email").val(data.vendor_email || '');
            $("#order_date").val(data.order_date || '');
            $("#order_date_required").val(data.order_date_required || '');
            $(".order_date_required_picker").datepicker({ dateFormat: "dd-mm-yy" });
            $("#freight").val(data.freight || '');
            $("#order_number").val(data.order_number || '');
            $("#purchaser_user_id").val(data.purchaser_user_id || '');
            $("#ven_inv_number").val(data.ven_inv_number || '');
            $("#order_notes").val(data.order_notes || '');
            $("#additional_notes").val(data.additional_notes || '');
            $("#order_status_id").val(data.order_status_id || '');
            
            $("#order_receive_date").val(data.order_receive_date || '');
            $("#order_receive_ref").val(data.order_receive_ref || '');
            $("#order_receive_note").val(data.order_receive_note || '');
            
            $("#invoice_date").val(data.invoice_date || '');
            $("#invoice_ref").val(data.invoice_ref || '');
            $("#invoice_note").val(data.invoice_note || '');

            // Handling delivery address field and placeholder
            var delivery_address = '';
            if (data.delivery_address_line1 || data.delivery_address_suburb || data.delivery_postcode || data.delivery_state) {
                delivery_address = (data.delivery_address_line1 || '') + '\n' + 
                                  (data.delivery_address_suburb || '') + '\n' + 
                                  (data.delivery_postcode || '') + ' ' + (data.delivery_state || '');
            }

            $("#delivery_address").val(delivery_address || '');

            // If delivery_address is empty, placeholder will be visible
            if (!delivery_address.trim()) {
                $("#delivery_address").attr("placeholder", "Optional: Enter delivery address if different from business address.");
            }

            // Handle dropdowns
            populateDropdown('#order_status', data.order_status, 'get_purchase_status', 'id', 'description');
            $('#order_status').change(function() {
                var selectedStatusValue = $(this).val();
                $('#order_status_id').val(selectedStatusValue);
            });

            populateDropdown('#purchaser_user', data.purchaser_user, 'get_users_company', 'id', 'fullname');
            $('#purchaser_user').change(function() {
                var selectedSalesUserValue = $(this).val();
                $('#purchaser_user_id').val(selectedSalesUserValue);
            });






            // Other buttons for additional actions
            //$('#processInvoice').html('<button onclick="processInvButton(' + pid + ')" class="btn btn-outline-secondary btn-sm"><span id="buttonText">To Invoice</button>');
            //$('#convertToBill').html('<button type="button" class="btn btn-sm btn-outline-secondary" onclick="convertToBill()">To Bill</button>');
            //$('#convertStock').html('<button onclick="processStock(' + pid + ')" class="btn btn-outline-secondary btn-sm">To Stock</button>');
            //$('#convertA').html('<button onclick="processBill(' + pid + ')" class="btn btn-outline-secondary btn-sm">To Acc</button>');
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            console.error('AJAX status:', status);
            console.error('AJAX response:', xhr.responseText);
        }
    });
}

function delInvoiceModal(pid) {
     $('#process_pid').val(pid);
    $('#delete_invoice_modal').modal('show');

}
function deleteInvoice() {
    const pid = $("#process_pid").val();
    const delAcc = $("#delete_from_accounting").is(":checked") ? 1 : 0;

    $.ajax({
        url: crud_url,
        method: "POST",
        contentType: "application/json",
        dataType: "json",
        data: JSON.stringify({
            action: "delete_invoice",
            pid: pid,
            delete_from_accounting: delAcc
        }),
        success: function (resp) {
            if (resp.success) {
                $("#invoice_message_modal").html(`<div class="alert alert-success">${resp.message}</div>`);
                $("#delete_invoice_modal").modal("hide");
                setTimeout(function () {
                    LoadSubtab("order_bill");
                    getPurchaseId();
                }, 600);
            } else {
                $("#invoice_message_modal").html(`<div class="alert alert-danger">${resp.message}</div>`);
            }
        }
    });
}


function receiveInvoiceModal(pid) {
     $('#process_pid').val(pid);
    $('#receive_invoice_modal').modal('show');
     $("#invoice_date").datepicker({ dateFormat: "dd-mm-yy" });

}
function receiveInvoice() {
    var pid = $("#process_pid").val();
    var invoice_date = $("#invoice_date").val();
    var invoice_ref = $("#invoice_ref").val();
    var invoice_note = $("#invoice_note").val();
    var formData = {
        action: 'receive_invoice',
        invoice_date: invoice_date,
        invoice_ref: invoice_ref,
        invoice_note: invoice_note,
        pid: pid
    };

    console.log("XX"+formData)
    var errorMessage = '';
if (!formData.invoice_date) errorMessage += 'Missing Date ';
if (!formData.invoice_ref) errorMessage += 'Missing Invoice # ';

if (errorMessage) {
    $('#invoice_message_modal').html('<div class="alert alert-danger" style="display: none;">' + errorMessage + '</div>');
    
    // Fade in the error message
    $('#invoice_message_modal .alert').fadeIn('slow');
    
    // Fade out and remove the error message after 3 seconds
    setTimeout(function() {
        $('#invoice_message_modal .alert').fadeOut('slow', function() {
            $(this).remove(); // Remove the element after fading out
        });
    }, 3000);
    
    return; // Stop the function execution if any field is missing
}
    ajaxPostRequest(formData, crud_url);
    $('#receive_invoice_modal').modal('hide');
    setTimeout(function() {
        LoadSubtab('order_bill');
       // alert(pid)
        processBill(pid) // accounting functinn
    }, 1000);

}
function EmailPurchaseOrder(pid) {
    // Assuming vendor_email field exists and holds the email to autofill
    $('#email_pid').val(pid);
    let vendorEmail = $('#vendor_email').val();
    $('#email_address_po1').val(vendorEmail);

    // Show the modal
    $('#email_purchase_order').modal('show');
}
function SendEmailOrder() {
    const orderId = $('#email_pid').val();
    const emailTo1 = $('#email_address_po1').val();
    const emailTo2 = $('#email_address_po2').val();

    if (!emailTo1) {
        alert("Please enter at least one email address.");
        return;
    }

    // Step 1: Generate the PDF file
    $.ajax({
        url: `../pdf/purchase_order_v1.php?pid=${orderId}&s=1`,
        type: 'GET',
        success: function(response) {
            const pdfPath = `/files/purchase_order_${orderId}.pdf`; // Path where the PDF is saved
            
            // Add a delay to ensure PDF generation is complete
            setTimeout(function() {
                // Step 2: Send the email with the PDF attached
                const emailData = new FormData();
                emailData.append('action', 'send_purchase_order_email');
                emailData.append('order_id', orderId);
                emailData.append('pdf_path', pdfPath);
                emailData.append('email_to1', emailTo1);
                emailData.append('email_to2', emailTo2 || '');

                const attachmentInput = document.getElementById('purchase_email_attachments');
                if (attachmentInput && attachmentInput.files && attachmentInput.files.length) {
                    Array.from(attachmentInput.files).forEach(function(file) {
                        emailData.append('attachments[]', file);
                    });
                }

                $.ajax({
                    url: 'includes/common_mail.php',
                    type: 'POST',
                    data: emailData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        const res = JSON.parse(response);
                        if (res.success) {
                            // Display success message in #site_message
                            $('#site_message').html(`
                                <div class="alert alert-success" role="alert">
                                    Email sent successfully!
                                </div>
                            `).fadeIn('slow');
                            // Fade out and remove the message after 3 seconds
                            setTimeout(function() {
                                $('#site_message .alert').fadeOut('slow', function() {
                                    $(this).remove(); // Remove the alert after fading out
                                });
                            }, 3000);

                            $('#email_purchase_order').modal('hide'); // Close the modal
                            $('#purchase_email_attachments').val('');
                            if ($('#ProcessPurchaseModal').hasClass('show')) {
                                loadProcessPurchaseActivity();
                            }
                        } else {
                            alert("Error: " + res.message);
                        }
                    },
                    error: function() {
                        alert("Failed to send email. Please try again.");
                    }
                });
            }, 500); // 500ms delay to ensure PDF is saved
        },
        error: function() {
            alert("Failed to generate PDF. Please try again.");
        }
    });
}

function getProcessPurchaseId() {
    return $('#process_purchase_pid').val() || pid;
}

function purchaseProcessLabels() {
    return {
        purchase_delivery_docket_printed: 'Print',
        purchase_confirmation_requested: 'Confirmation required',
        purchase_order_printed: 'Print',
        purchase_order_emailed: 'Email'
    };
}

function OpenProcessPurchaseModal(processPid) {
    $('#process_purchase_pid').val(processPid || pid);
    updatePurchaseAttachmentSummary();
    loadProcessPurchaseActivity();
    $('#ProcessPurchaseModal').modal('show');
}

function renderProcessPurchaseActivity(history) {
    const labels = purchaseProcessLabels();
    const confirmationRequested = history
        && history.purchase_confirmation_requested
        && !String(history.purchase_confirmation_requested.description || '').toLowerCase().includes('cleared');

    $('#purchase_confirmation_requested_checkbox').prop('checked', !!confirmationRequested);

    $('[data-purchase-process-summary]').each(function() {
        const workflowType = $(this).data('purchase-process-summary');
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

function loadProcessPurchaseActivity() {
    const processPid = getProcessPurchaseId();

    if (!processPid) {
        renderProcessPurchaseActivity({});
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'get_purchase_process_activity',
            pid: processPid
        }),
        contentType: 'application/json',
        dataType: 'json',
        success: function(response) {
            if (response && response.success) {
                renderProcessPurchaseActivity(response.history || {});
            }
        }
    });
}

function recordProcessPurchaseActivity(workflowType, callback) {
    const processPid = getProcessPurchaseId();

    if (!processPid) {
        if (typeof callback === 'function') {
            callback();
        }
        return;
    }

    $.ajax({
        url: crud_url,
        type: 'POST',
        data: JSON.stringify({
            action: 'record_purchase_process_activity',
            pid: processPid,
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
                renderProcessPurchaseActivity(response.history || {});
                LoadSubtab('purchase_activity');
            }
        }
    });
}

function updatePurchaseAttachmentSummary() {
    const input = document.getElementById('purchase_email_attachments');
    const count = input && input.files ? input.files.length : 0;
    $('#purchase_process_attachment_summary').text(count ? count + ' file(s) selected for the PO email.' : 'No files selected.');
}

function ProcessPurchaseChooseAttachments() {
    $('#purchase_email_attachments').trigger('click');
}

$(document).on('change', '#purchase_email_attachments', updatePurchaseAttachmentSummary);

function ProcessPurchaseConfirmationChanged() {
    const workflowType = $('#purchase_confirmation_requested_checkbox').is(':checked')
        ? 'purchase_confirmation_requested'
        : 'purchase_confirmation_requested_cleared';
    recordProcessPurchaseActivity(workflowType, loadProcessPurchaseActivity);
}

function ProcessPurchasePrintDelivery() {
    recordProcessPurchaseActivity('purchase_delivery_docket_printed', function() {
        loadProcessPurchaseActivity();
    });
    PrintPurchaseDel(getProcessPurchaseId());
}

function ProcessPurchasePrintOrder() {
    recordProcessPurchaseActivity('purchase_order_printed', function() {
        loadProcessPurchaseActivity();
    });
    PrintPurchaseOrder(getProcessPurchaseId());
}

function ProcessPurchaseEmailOrder() {
    const processPid = getProcessPurchaseId();
    $('#email_pid').val(processPid);
    $('#email_address_po1').val($('#vendor_email').val() || '');
    updatePurchaseAttachmentSummary();
    $('#email_purchase_order').modal('show');
}




function toggleOrderFields(disable) {
    $('[id^="vendor_"]').prop('disabled', disable);
}

function savePurchase() {
    // Extracting and processing delivery address into individual components
    const delivery_address = $('#delivery_address').val();
    const deliveryAddressLines = delivery_address.split('\n');

    const delivery_address_line1 = deliveryAddressLines[0] ? deliveryAddressLines[0].trim() : '';
    const delivery_address_suburb = deliveryAddressLines[1] ? deliveryAddressLines[1].trim() : '';
    const delivery_postcode_state = deliveryAddressLines[2] ? deliveryAddressLines[2].trim() : '';

    let delivery_postcode = '';
    let delivery_state = '';

    // Split the third line (postcode and state) by comma
    if (delivery_postcode_state) {
        const postcodeStateArr = delivery_postcode_state.split(',');
        delivery_postcode = postcodeStateArr[0] ? postcodeStateArr[0].trim() : '';
        delivery_state = postcodeStateArr[1] ? postcodeStateArr[1].trim() : '';
    }
    
    const actionType = pid ? 'save_purchase' : 'create_purchase';
    
    const formData = {
        action: actionType,
        pid: pid,
        vendor_name: $('#vendor_search').val(),
        vendor_uid: $('#vendor_uid').val(),
        vendor_address: $('#vendor_address').val(),
        vendor_suburb: $('#vendor_suburb').val(),
        vendor_state: $('#vendor_state').val(),
        vendor_postcode: $('#vendor_postcode').val(),
        vendor_phone: $('#vendor_phone').val(),
        vendor_email: $('#vendor_email').val(),
        purchaser_user_id: $('#purchaser_user_id').val(),
        ven_inv_number: $('#ven_inv_number').val(),
        order_notes: $('#order_notes').val(),
        additional_notes: $('#additional_notes').val(),
        freight: $('#freight').val(),
        order_date_required: $('#order_date_required').val(),
        order_status_id: $('#order_status_id').val(),
        payment_terms_day: $('#payment_terms_day').val(),
        payment_terms_type: $('#payment_terms_type').val(),
        delivery_address_line1: delivery_address_line1,
        delivery_address_suburb: delivery_address_suburb,
        delivery_postcode: delivery_postcode,
        delivery_state: delivery_state,
    };

    // Send data via AJAX
    $.ajax({
        type: "POST",
        url: crud_url,
        data: JSON.stringify(formData),
        contentType: "application/json",
        success: function(response) {
            if (typeof response === 'string') {
                response = JSON.parse(response);
            }

            if (response.success) {
                if (actionType === 'create_purchase' && response.pid) {
                    const newPId = response.pid;
                    const redirectUrl = `${window.location.origin}${window.location.pathname}?p=admin_purchasing&pid=${newPId}`;
                    window.location.href = redirectUrl;
                } else {
                    handleSuccess(response.message);
                }
            } else if (response.error) {
                console.error('Error message:', response.message);
            } else {
                console.error('Unexpected response format:', response);
            }

            setTimeout(function() {
                LoadSubtab('ordered_items');
            }, 1000);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', error);
            console.error('AJAX status:', status);
            console.error('AJAX response:', xhr.responseText);
        }
    });
    getPurchaseId();
}


function getItems() {
    $.ajax({
        type: "POST",
        url: content_url,
        data: {tab_id: 'pid_ordered_items', pid: pid},
        dataType: "json", 
        success: function (response) {
            $('#items_body').html(response.html);

        },
    });
}

function addOrderItemsModal() {
    $('#add_item_order_modal').modal('show');
    $('#add_item_order_modal').on('shown.bs.modal', function () {
        $('#add_item_part_number').focus();

        // Remove old bindings first, then add fresh ones
        $('#add_item_units_qty, #add_item_mark').off('keydown').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault(); // prevent accidental form submit
                AddOrderItems();
            }
        });
    });
}

function AddOrderItems() {
    var formData = {
        action: 'add_order_items',
        pid: pid,
        part_number: $('#add_item_part_number').val(),
        description: $('#add_item_description').val(),
        rate: $('#add_item_rate').val(),
        
        // for item that have has_items =0
        qty_item: $('#add_item_qty').val(),
  
        // for item that have has_items =1
        has_items: $('#has_items_id_tag').val(),
        qty: $('#add_item_units').val(),
        qty_unit: $('#add_item_units_qty').val(),
        mark: $('#add_item_mark').val()
    };
    console.log(formData)
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
        LoadSubtab('ordered_items');
    }, 1000);
    $('#add_item_units').val("")
    $('#add_item_units_qty').val("")
    $('#add_item_mark').val("")
    $('#add_item_units').focus();
}



function editOrderItemModal(item_id) {
    $('#edit_item_modal').modal('show');
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
               
                $("#edit_part_number").val(data.part_number);
                $("#edit_description").val(data.description);
                $("#edit_rate").val(data.rate);
                $("#edit_qty").val(data.qty);
                $("#edit_unit_id").val(data.unit_id);
                populateDropdown('#edit_unit', data.unit, 'get_inventory_unit', 'id', 'description');
                $('#edit_unit').change(function() {
                    var selectedUnitValue = $(this).val();
                    $('#edit_unit_id').val(selectedUnitValue);
                });
                if(data.has_items){
                    $("#has_item_div").hide();
                    }
                    else{
                     $("#has_item_div").show();
                }

                
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function editOrderSubItemModal(sub_item_id) {
    $('#edit_sub_item_modal').modal('show');
    $("#edit_sub_item_id").val(sub_item_id);
    var formData = {
        action: 'read_order_sub_item',
        sub_item_id: sub_item_id,
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
                $("#edit_sub_qty").val(data.qty);
                $("#edit_sub_qty_unit").val(data.qty_unit);
                $("#edit_sub_mark").val(data.mark);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function SaveSubItem() {
    var item_id = $("#edit_sub_item_id").val();
     var formData = {
        action: 'save_sub_items',
        item_id: item_id,
        qty: $('#edit_sub_qty').val(), 
        qty_unit: $('#edit_sub_qty_unit').val(),
        mark: $('#edit_sub_mark').val(),
    };
    console.log(formData)
    ajaxPostRequest(formData, crud_url);
    $('#edit_sub_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('ordered_items')
    }, 1000);
}
function SaveItem() {
    var item_id = $("#edit_item_id").val();
     var formData = {
        action: 'save_items',
        item_id: item_id,
        part_number: $('#edit_part_number').val(), 
        description: $('#edit_description').val(),
        qty: $('#edit_qty').val(), 
        rate: $('#edit_rate').val(),

    };
    ajaxPostRequest(formData, crud_url);
    $('#edit_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('ordered_items')
    }, 1000);
}
function editBIllItemModal(bill_item_id) {
    $('#edit_bill_item_modal').modal('show');
    $("#edit_bill_item_id").val(bill_item_id);
    var formData = {
        action: 'read_bill_item',
        bill_item_id: bill_item_id,
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
                $("#edit_bill_part_number").val(data.part_number);
				$("#edit_bill_serial_number").val(data.serial_number);
                $("#edit_bill_description").val(data.description);
                $("#edit_bill_qty").val(data.qty);
                $("#edit_bill_rate").val(data.rate);
 
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function SaveBillItem() {
    var bill_item_id = $("#edit_bill_item_id").val();
     var formData = {
        action: 'save_bill_item',
        bill_item_id: bill_item_id,
        part_number: $('#edit_bill_part_number').val(), 
		serial_number: $('#edit_bill_serial_number').val(),
        description: $('#edit_bill_description').val(),
        qty: $('#edit_bill_qty').val(),
        rate: $('#edit_bill_rate').val(),

    };
    ajaxPostRequest(formData, crud_url);
    $('#edit_bill_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('order_bill')
    }, 1000);
}
function addReceivedItemsModal() {
    $('#add_bill_item_modal').modal('show');
    $('#add_bill_item_modal').on('shown.bs.modal', function () {
    $('#add_bill_part_number').focus();

 
    });
}
$(document).ready(function() {
    $('#add_bill_item_modal').on('shown.bs.modal', function () {
        $("#add_bill_part_number").autocomplete({
                source: function(request, response) {
                console.log("Request term: ", request.term);
                $.ajax({
                    url: crud_url,
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ action: 'get_part_number', term: request.term }),
                    dataType: "json",
                    success: function(data) {
                        console.log("Response data: ", data); // Ensure data is in correct format
                        response($.map(data, function(item) {
                            return {
                                label: item.part_number,
                                value: item.part_number,
                                description: item.description,
                                buy_rate: item.buy_rate,
                                has_sub_items: item.has_sub_items, // Add this to the map
								raw_material: item.raw_material // Add this to the map
                            };
                        }));
                    },
                    error: function(xhr, status, error) {
                        console.log("AJAX Error: ", status, error);
                        console.log("Response Text: ", xhr.responseText); // Log the response text for debugging
                    }
                });
            },
            minLength: 2,
            autoFocus: true,
            select: function(event, ui) {
                // Fill the input with the selected item and move to the next field
                $("#add_bill_part_number").val(ui.item.value);
                $("#add_bill_description").val(ui.item.description);
                $("#add_bill_rate").val(ui.item.buy_rate);
                $("#has_bill_id_tag").val(ui.item.has_sub_items);

                // Show the hidden fields
                $("#description_bill_field").show();
                $("#price_bill_field").show();

                // Hide or show the qty_field and units_field based on the has_sub_items value
                if (ui.item.has_sub_items) {
                    $("#qty_bill_field").hide();
                    $("#price_bill_field").hide();
                    $("#serial_field").hide();
                    $("#units_bill_field").show();
                } else {
                    $("#qty_bill_field").show();
                     $("#serial_field").show();
                    $("#units_bill_field").hide();
                    
                    
                }
				 if (ui.item.raw_material) {
						$("#units_bill_field").hide();
					 	$("#qty_bill_field").show();
					 	$("#price_bill_field").show();
				 }
				
                return false;
            }
        }).on('keydown', function(event) {
            if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
                event.preventDefault();
                // Close the autocomplete menu and select the highlighted item
                $(this).data('ui-autocomplete').menu.select();
            }
        });
    });
});
function AddBillItems() {
    // Prepare form data to be sent in the AJAX request
    var formData = {
        action: 'add_bill_items',
        pid: pid, // Assuming 'pid' is a global variable or defined elsewhere
        part_number: $('#add_bill_part_number').val(),
        serial_number: $('#add_bill_serial_number').val(),
        description: $('#add_bill_description').val(),
        rate: $('#add_bill_rate').val(),
        qty_item: $('#add_bill_qty').val(),
        mark: $('#add_add_mark').val(),
        rate_units: $('#add_add_bill_rate_units').val(),
        rate_units_qty: $('#add_add_bill_rate_units_qty').val(),
        has_items: $('#has_bill_id_tag').val(),
    };

    // Debugging: Log the formData to the console for verification
    console.log(formData);
var errorMessage = '';
if (!formData.part_number) errorMessage += 'Missing Part. ';
if (!formData.description) errorMessage += 'Missing Description. ';
//if (!formData.serial_number) errorMessage += 'Missing Serial Number. ';    
//if (!formData.qty_item) errorMessage += 'Missing Qty.';

if (errorMessage) {
    $('#site_message_modal').html('<div class="alert alert-danger" style="display: none;">' + errorMessage + '</div>');
    
    // Fade in the error message
    $('#site_message_modal .alert').fadeIn('slow');
    
    // Fade out and remove the error message after 3 seconds
    setTimeout(function() {
        $('#site_message_modal .alert').fadeOut('slow', function() {
            $(this).remove(); // Remove the element after fading out
        });
    }, 3000);
    
    return; // Stop the function execution if any field is missing
}
    // Send the AJAX POST request
    ajaxPostRequest(formData, crud_url, function(response) {
        // Optional: Handle the response here if needed
        console.log(response); // Log the response for debugging purposes
    });

    setTimeout(function() {
        LoadSubtab('order_bill');
    }, 1000);

    // Clear the form fields after submission
    $('#add_bill_units').val("");
    $('#add_bill_units_qty').val("");
    $('#add_bill_mark').val("");
    $('#add_bill_units').focus(); // Set focus back to the units input field
}

function editBillSubItemModal(sub_item_id) {
    $('#edit_bill_sub_item_modal').modal('show');
    $("#edit_bill_sub_item_id").val(sub_item_id);
    var formData = {
        action: 'read_bill_sub_item',
        sub_item_id: sub_item_id,
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
                $("#edit_bill_sub_qty").val(data.qty);
                $("#edit_bill_sub_qty_unit").val(data.qty_unit);
                $("#edit_bill_sub_mark").val(data.mark);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error: ", status, error);
            handleError(xhr, status, error);
        }
    });
}
function SaveBillSubItem() {
    var sub_item_id = $("#edit_bill_sub_item_id").val();
     var formData = {
        action: 'save_bill_sub_items',
        sub_item_id: sub_item_id,
        qty: $('#edit_bill_sub_qty').val(),
        qty_unit: $('#edit_bill_sub_qty_unit').val(),
        mark: $('#edit_bill_sub_mark').val(), 

    };
    ajaxPostRequest(formData, crud_url);
    $('#edit_bill_sub_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('order_bill')
    }, 1000);
}
function deleteOrderItem() {
     var del_item_id = $("#edit_item_id").val();
     console.log("Del" +del_item_id)

        var formData = {
            action: 'delete_order_item',
            del_item_id: del_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('ordered_items')
    }, 1000);
  
}
function deleteOrderSubItem() {
     var del_sub_item_id = $("#edit_sub_item_id").val();
     console.log("Del" +del_sub_item_id)

        var formData = {
            action: 'delete_order_sub_item',
            del_sub_item_id: del_sub_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_sub_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('ordered_items')
    }, 1000);
  
}
function DeleteBillItem() {
     var del_item_id = $("#edit_bill_item_id").val();
     console.log("Del" +del_item_id)

        var formData = {
            action: 'delete_bill_item',
            del_item_id: del_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_bill_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('order_bill')
    }, 1000);
  
}
function DeleteBillSubItem() {
     var del_item_id = $("#edit_bill_sub_item_id").val();
     console.log("Del" +del_item_id)

        var formData = {
            action: 'delete_bill_sub_item',
            del_item_id: del_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_bill_sub_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('order_bill')
    }, 1000);
  
}
$(document).ready(function() {
    $('#add_item_order_modal').on('shown.bs.modal', function () {
        $("#add_item_part_number").autocomplete({
                source: function(request, response) {
                console.log("Request term: ", request.term);
                $.ajax({
                    url: crud_url,
                    method: "POST",
                    contentType: "application/json",
                    data: JSON.stringify({ action: 'get_part_number', term: request.term }),
                    dataType: "json",
                    success: function(data) {
                        console.log("Response data: ", data); // Ensure data is in correct format
                        response($.map(data, function(item) {
                            return {
                                label: item.part_number,
                                value: item.part_number,
                                description: item.description,
                                buy_rate: item.buy_rate,
                                has_sub_items: item.has_sub_items, // Add this to the map
								raw_material: item.raw_material // Add this to the map
                            };
                        }));
                    },
                    error: function(xhr, status, error) {
                        console.log("AJAX Error: ", status, error);
                        console.log("Response Text: ", xhr.responseText); // Log the response text for debugging
                    }
                });
            },
            minLength: 2,
            autoFocus: true,
            select: function(event, ui) {
                // Fill the input with the selected item and move to the next field
                $("#add_item_part_number").val(ui.item.value);
                $("#add_item_description").val(ui.item.description);
                $("#add_item_rate").val(ui.item.buy_rate);
                $("#has_items_id_tag").val(ui.item.has_sub_items);

                // Show the hidden fields
                $("#description_field").show();
                $("#price_field").show();

                // Hide or show the qty_field and units_field based on the has_sub_items value
                if (ui.item.has_sub_items) {
                    $("#qty_field").hide();
                    $("#price_field").hide();
                    $("#units_field").show();
                } else {
                    $("#qty_field").show();
                    $("#units_field").hide();
                }
				 if (ui.item.raw_material) {
						$("#units_field").hide();
					 	$("#qty_field").show();
					 	$("#price_field").show();
				 }
				
                return false;
            }
        }).on('keydown', function(event) {
            if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
                event.preventDefault();
                // Close the autocomplete menu and select the highlighted item
                $(this).data('ui-autocomplete').menu.select();
            }
        });
    });
});
/*
function ReceivePurchase() {
     var formData = {
        action: 'receive_items',
        pid: pid,

    };
    ajaxPostRequest(formData, crud_url);

         setTimeout(function() {
         LoadSubtab('order_received')
    }, 1000);
}
*/


function deleteOrderItem() {
     var del_item_id = $("#edit_item_id").val();
     console.log("Del" +del_item_id)

        var formData = {
            action: 'delete_order_item',
            del_item_id: del_item_id
        };
       ajaxPostRequest(formData, crud_url);
         $('#edit_item_modal').modal('hide');
         setTimeout(function() {
         LoadSubtab('ordered_items')
    }, 1000);
  
}
function receiveItemsModal() {
    $('#receive_item_modal').modal('show');
     $("#order_receive_date").datepicker({ dateFormat: "dd-mm-yy" });

}
function receiveItems() {
    var order_receive_date = $("#order_receive_date").val();
    var order_receive_ref = $("#order_receive_ref").val();
    var order_receive_note = $("#order_receive_note").val();
    var formData = {
        action: 'receive_items',
        order_receive_date: order_receive_date,
        order_receive_ref: order_receive_ref,
        order_receive_note: order_receive_note,
        pid: pid
    };

    console.log("PP"+formData)
    ajaxPostRequest(formData, crud_url);
    $('#receive_item_modal').modal('hide');
    setTimeout(function() {
        LoadSubtab('order_bill');
        processStock(pid)
    }, 1000);

}
function reverseReceiveItems() {
    if (!pid) {
        alert("Missing purchase order ID.");
        return;
    }

    if (!confirm("Are you sure you want to reverse received items for this purchase order? This will remove the stock rows created from this receive.")) {
        return;
    }

    var formData = {
        action: "reverse_receive_items",
        pid: pid
    };

    $.ajax({
        url: crud_url,
        type: "POST",
        data: JSON.stringify(formData),
        contentType: "application/json",
        dataType: "json",
        success: function(response) {
            if (response.success) {
                alert(response.message);

                const modalEl = document.getElementById("receive_item_modal");
                if (modalEl && window.bootstrap && bootstrap.Modal) {
                    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modal.hide();
                }

                getPurchaseId();
                LoadSubtab("order_bill");
            } else {
                alert(response.message || "Reverse receive failed.");
            }
        },
        error: function(xhr, status, error) {
            console.error("AJAX error:", error);
            console.error("AJAX status:", status);
            console.error("AJAX response:", xhr.responseText);
            alert("An error occurred while reversing received items.");
        }
    });
}
function convertBill() {
    var formData = {
        action: 'convert_to_bill',
        pid: pid
    };

    console.log(formData)
    ajaxPostRequest(formData, crud_url);
    $('#receive_item_modal').modal('hide');
    setTimeout(function() {
        LoadSubtab('order_bill');
    }, 1000);

}
function processInvButton() {
    var formData = {
        action: 'convert_to_invoice',
        pid: pid
    };

    
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
        LoadSubtab('order_bill');
    }, 1000);
}
function processStock(pid) {
    var formData = {
        action: 'insert_stock_inv',
        pid: pid
    };
    console.log("ooo"+formData)
    ajaxPostRequest(formData, crud_url);

        setTimeout(function() {
        LoadSubtab('order_bill')
        getPurchaseId()     
    }, 1000);
}

function AddPurchaseActivityModal() {
    $('#modalAddPurchaseActivity').modal('show');
}

function AddPurchaseActivity() {
    var formData = {
        action: 'add_purchase_activity',
        pid: pid,
        description: $('#add_purchase_activity_description').val(),
        action_date: $('#add_purchase_date_activity').val(),
    };

    ajaxPostRequest(formData, crud_url);
    $('#modalAddPurchaseActivity').modal('hide');
    $('#add_purchase_activity_description').val('');
    $('#add_purchase_date_activity').val('');

    setTimeout(function() {
        LoadSubtab('purchase_activity');
    }, 1000);
}

function EditPurchaseActivity(activity_id) {
    $("#edit_purchase_activity_id").val(activity_id);
    var formData = {
        action: 'get_purchase_activity',
        activity_id: activity_id,
    };
    var jsonData = JSON.stringify(formData);
    $.ajax({
        url: crud_url,
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var parsedResponse = typeof response === 'string' ? JSON.parse(response) : response;
            if (parsedResponse && parsedResponse.length > 0) {
                var data = parsedResponse[0];
                $("#edit_purchase_activity_description").val(data.description);
                $("#edit_purchase_date_activity").val(data.action_date);
                $('#modalEditPurchaseActivity').modal('show');
            }
        }
    });
}

function SavePurchaseActivity() {
    var formData = {
        action: 'save_purchase_activity',
        activity_id: $('#edit_purchase_activity_id').val(),
        description: $('#edit_purchase_activity_description').val(),
        action_date: $('#edit_purchase_date_activity').val(),
    };

    ajaxPostRequest(formData, crud_url);
    $('#modalEditPurchaseActivity').modal('hide');

    setTimeout(function() {
        LoadSubtab('purchase_activity');
    }, 1000);
}

function delPurchaseActivity(id) {
    var formData = {
        action: 'delete_purchase_activity',
        id: id,
    };
    ajaxPostRequest(formData, crud_url);
    setTimeout(function() {
        LoadSubtab('purchase_activity');
    }, 1000);
}

$(document).ready(function() {
    if (pid) {
        Loadtab('home');
    } else {
        Loadtab('home');
    }
checkAccountingType()
});
