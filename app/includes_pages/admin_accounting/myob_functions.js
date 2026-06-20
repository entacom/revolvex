
//   (sales)
function processInvoice(invoice_id,invoice_date) {
    $.post("includes_pages/admin_accounting/myob_functions.php?create_invoice", {
        invoice_id: invoice_id,
        invoice_date: invoice_date
    },
    function (data, status) {
        console.log("Response data:", data);
        try {
            data = JSON.parse(data);
            if (data.response) {
                var apiResponse = JSON.parse(data.response);
                if (apiResponse.UID) {
                    console.log("MYOB UID: " + apiResponse.UID);
                    console.log("Due Date: " + apiResponse.Terms.DueDate);
                    // $('.submyob').prop('disabled', false); //disable add scope line button
                } else {
                    console.log("Response without UID: ", apiResponse);
                    // readInvoicesWaitingtoProcess()
                }
            } else {
                console.log("Response without 'response' key: ", data);
            }
        } catch (error) {
            console.error("Error parsing JSON:", error);
            console.log("Raw response data:", data);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Request failed:", textStatus, errorThrown);
    });
}
//  (purchase)
function processBill(pid) {
    $.post("includes_pages/admin_accounting/myob_functions.php?create_bill", {
        pid: pid
    },
    function (data, status) {
        console.log("Response data:", data);
        try {
            data = JSON.parse(data);
            if (data.response) {
                var apiResponse = JSON.parse(data.response);
                if (apiResponse.UID) {
                    console.log("MYOB UID: " + apiResponse.UID);
                    console.log("Due Date: " + apiResponse.Terms.DueDate);
                    // $('.submyob').prop('disabled', false); //disable add scope line button
                } else {
                    console.log("Response without UID: ", apiResponse);
                    // readInvoicesWaitingtoProcess()
                }
            } else {
                console.log("Response without 'response' key: ", data);
            }
        } catch (error) {
            console.error("Error parsing JSON:", error);
            console.log("Raw response data:", data);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Request failed:", textStatus, errorThrown);
    });
}




$(document).ready(function() {
    $("#customer_search").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/myob_functions.php?myob_get_customers',
                method: "GET",
                data: { term: request.term },
                dataType: "json",
                success: function(data) {
                    console.log("AJAX success data: ", data);
                    if (data.length === 0) {
                        response([{ label: 'Customer not found', value: '' }]);
                    } else {
                        response($.map(data, function(item) {
                            let label = item.is_individual ? (item.firstname + ' ' + item.lastname) : item.company;
                            return {
                                label: label,
                                customer_uid: item.uid,
                                company: item.company,
                                lastname: item.lastname,
                                firstname: item.firstname,
                                address: item.address,
                                suburb: item.suburb,
                                state: item.state,
                                postcode: item.postcode,
                                email: item.email,
                                phone1: item.phone1,
                                is_individual: item.is_individual,
                                item_price_level: item.item_price_level
                            };
                        }));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            if (ui.item.customer_uid) {
                console.log("Selected item: ", ui.item);
                $("#customer_search").val(ui.item.label); // Set the label (name) in the input field
                $("#customer_uid").val(ui.item.customer_uid);

                if (ui.item.is_individual) {
                    // If it's an individual, set the individual's name
                    $("#customer_contact").val(ui.item.firstname + ' ' + ui.item.lastname);
                } else {
                    // If it's a company, set contact person name from the address array
                    $("#customer_contact").val(ui.item.firstname + ' ' + ui.item.lastname);
                }

                $("#customer_address").val(ui.item.address);
                $("#customer_suburb").val(ui.item.suburb);
                $("#customer_state").val(ui.item.state);
                $("#customer_postcode").val(ui.item.postcode);
                $("#customer_email").val(ui.item.email);
                $("#customer_phone").val(ui.item.phone1);
                $("#price_level").val(ui.item.item_price_level);
            } else {
                // Handle case when no customer is found
                $("#customer_search").val('');
                $("#customer_uid").val('');
                $("#customer_contact").val('');
                $("#customer_address").val('');
                $("#customer_suburb").val('');
                $("#customer_state").val('');
                $("#customer_postcode").val('');
                $("#customer_email").val('');
                $("#customer_phone").val('');
                alert('Customer not found');
            }
            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});




   $(document).ready(function() {
    $("#payable_account_name").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/myob_functions.php?get_all_accounts',
                method: "GET",
                data: { classification: 'CostOfSales', term: request.term },
                dataType: "json",
                success: function(data) {
                    console.log("AJAX CostOfSales data: ", data);
                    response($.map(data, function(item) {
                        return {
                            label: item.label,
                            uid: item.uid,
                            classification: item.classification,
                            display_id: item.display_id,
                            description: item.description
                        };
                    }));
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            console.log("Selected item: ", ui.item);
            $("#payable_account_name").val(ui.item.label); // Set the label (name) in the input field
            $("#payable_account_code").val(ui.item.uid);


            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});
   $(document).ready(function() {
    $("#receivable_account_name").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/myob_functions.php?get_all_accounts',
                method: "GET",
                data: { classification: 'Income', term: request.term },
                dataType: "json",
                success: function(data) {
                    console.log("AJAX Income data: ", data);
                    response($.map(data, function(item) {
                        return {
                            label: item.label,
                            uid: item.uid,
                            classification: item.classification,
                            display_id: item.display_id,
                            description: item.description
                        };
                    }));
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            console.log("Selected item: ", ui.item);
            $("#receivable_account_name").val(ui.item.label); // Set the label (name) in the input field
            $("#receivable_account_code").val(ui.item.uid);


            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});
  $(document).ready(function() {
    $("#payable_account_tax").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/myob_functions.php?get_all_tax',
                method: "GET",
                data: { term: request.term },
                dataType: "json",
                success: function(data) {
                    console.log("AJAX Income data: ", data);
                    if (data && data.Items) {
                        response($.map(data.Items, function(item) {
                            return {
                                label: item.Description,
                                uid: item.UID,
                                classification: item.Type,
                                display_id: item.Code,
                                description: item.Description
                            };
                        }));
                    } else {
                        console.error("Unexpected data format: ", data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            console.log("Selected item: ", ui.item);
            $("#payable_account_tax").val(ui.item.label); // Set the label (name) in the input field
            $("#payable_account_tax_code").val(ui.item.uid); // Ensure you have an input with this ID to store the UID
            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});
  $(document).ready(function() {
    $("#receivable_account_tax").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/myob_functions.php?get_all_tax',
                method: "GET",
                data: { term: request.term },
                dataType: "json",
                success: function(data) {
                    console.log("AJAX Income data: ", data);
                    if (data && data.Items) {
                        response($.map(data.Items, function(item) {
                            return {
                                label: item.Description,
                                uid: item.UID,
                                classification: item.Type,
                                display_id: item.Code,
                                description: item.Description
                            };
                        }));
                    } else {
                        console.error("Unexpected data format: ", data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            console.log("Selected item: ", ui.item);
            $("#receivable_account_tax").val(ui.item.label); // Set the label (name) in the input field
            $("#receivable_account_tax_code").val(ui.item.uid); // Ensure you have an input with this ID to store the UID
            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});
$(document).ready(function() {
    $("#vendor_search").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/myob_functions.php',
                method: "GET",
                data: { myob_get_suppliers: true, term: request.term },
                dataType: "json",
                success: function(data) {
                    console.log("AJAX Vendor data: ", data);
                    if (data.length === 0) {
                        response([{ label: 'Supplier not found ', value: '' }]);
                    } else {
                        response($.map(data, function(item) {
                            return {
                                label: item.company, // Assuming 'company' is the name to be displayed
                                uid: item.uid,
                                company: item.company,
                                address: item.address,
                                suburb: item.suburb,
                                state: item.state,
                                postcode: item.postcode,
                                phone: item.phone1,
                                email: item.email,
                            };
                        }));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error: ", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            console.log("Selected item: ", ui.item);
            $("#vendor_search").val(ui.item.company); 
            $("#vendor_uid").val(ui.item.uid); 
            $("#vendor_address").val(ui.item.address); 
            $("#vendor_suburb").val(ui.item.suburb); 
            $("#vendor_state").val(ui.item.state); 
            $("#vendor_postcode").val(ui.item.postcode);
            $("#vendor_phone").val(ui.item.phone); 
            $("#vendor_email").val(ui.item.email); 

            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});

