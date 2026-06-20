//   (sales)
function processInvoice(invoice_id, invoice_date) {
    $.post("includes_pages/admin_accounting/xero_functions.php?create_invoice", {
        invoice_id: invoice_id,
        invoice_date: invoice_date
    },
    function (data, status) {
        console.log("Xero response:", data);
        try {
            data = JSON.parse(data);
            if (data.InvoiceID) {
                console.log("Xero InvoiceID: " + data.InvoiceID);
                // update UI etc.
            } else {
                console.warn("No InvoiceID returned:", data);
            }
        } catch (error) {
            console.error("JSON parse error:", error);
            console.log("Raw:", data);
        }
    }).fail(function (jqXHR, textStatus, errorThrown) {
        console.error("Xero request failed:", textStatus, errorThrown);
    });
}

//  (purchase)
// (purchase)
function processBill(pid) {
    console.log("PID"+pid)
    $.post("includes_pages/admin_accounting/xero_functions.php?create_xero_bill", {
        
        pid: pid
    }, function(data, status) {
        console.log("Xero Bill Raw Response:", data);

        try {
            let bill = JSON.parse(data);

            if (!bill || typeof bill !== 'object') {
                console.error("Xero response is null or not an object:", bill);
                return;
            }

            if (bill.error) {
                console.error("Xero API Error:", bill.error);
                return;
            }

            if (bill.InvoiceID) {
                console.log("Xero Invoice ID: " + bill.InvoiceID);
            } else {
                console.warn("Unexpected Xero response structure:", bill);
            }

        } catch (err) {
            console.error("JSON parse error:", err, data);
        }

    }).fail(function(jqXHR, textStatus, errorThrown) {
        console.error("Xero Bill Request failed:", textStatus, errorThrown);
    });
}



$(document).ready(function() {
    $("#payable_account_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '/includes_pages/admin_accounting/xero_functions.php?get_all_accounts',
                method: "GET",
                data: {
                    classification: 'CURRENT', // Or 'COSTOFSALES' depending on how you map it
                    term: request.term
                },
                dataType: "json",
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.label,                // Display name (with code)
                            value: item.label,                // What goes in the textbox
                            uid: item.uid,                    // Xero AccountID
                            display_id: item.display_id,      // Account code (4-1000)
                            description: item.description,    // Optional
                            classification: item.classification
                        };
                    }));
                },
                error: function(xhr, status, error) {
                    console.error("Autocomplete error:", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            $("#payable_account_name").val(ui.item.label);
            $("#payable_account_code").val(ui.item.uid); // Save UID (Xero AccountID)
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
    $("#customer_search").autocomplete({
        source: function(request, response) {
            console.log("Autocomplete request: ", request.term);
            $.ajax({
                url: '/includes_pages/admin_accounting/xero_functions.php?xero_get_customers',
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
                                discount: item.discount,
                                payment_terms: item.payment_terms 
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
                // Map discount to item_price_level label
                const discountToPriceLevel = {
                    1: 'Level A',
                    2: 'Level B',
                    3: 'Level C',
                    4: 'Level D',
                    5: 'Level E',
                    6: 'Level F',
                    7: 'Level G',
                    8: 'Level H',
                    9: 'Level I',
                    10: 'Level J',
                };

                const priceLevelLabel = discountToPriceLevel[parseInt(ui.item.discount)] || '';

                $("#customer_search").val(ui.item.label);
                $("#customer_uid").val(ui.item.customer_uid);
                $("#customer_contact").val(ui.item.firstname + ' ' + ui.item.lastname);
                $("#customer_address").val(ui.item.address);
                $("#customer_suburb").val(ui.item.suburb);
                $("#customer_state").val(ui.item.state);
                $("#customer_postcode").val(ui.item.postcode);
                $("#customer_email").val(ui.item.email);
                $("#customer_phone").val(ui.item.phone1);
                $("#price_level").val(priceLevelLabel); //  mapped label
                $("#customer_discount").val(ui.item.discount); // raw numeric
                $("#payment_terms").val(JSON.stringify(ui.item.payment_terms));
                $("#payment_terms_text").val(JSON.stringify(ui.item.payment_terms));
                        // Step 1: Fallback if null, empty, or not an object
                        let paymentTerms = ui.item.payment_terms;
                        if (!paymentTerms || typeof paymentTerms !== 'object' || Array.isArray(paymentTerms)) {
                            paymentTerms = { Day: 0, Type: 'DAYSAFTERBILLDATE' };
                        }

                        // Step 2: Safe type/value checks
                        let day = Number(paymentTerms.Day);
                        let type = paymentTerms.Type || 'DAYSAFTERBILLDATE';

                        let termsText = '';
                        if (type === 'DAYSAFTERBILLDATE') {
                            termsText = day === 0
                                ? 'Due on invoice date'
                                : `Due ${day} day(s) after invoice date`;
                        } else if (type === 'OFFOLLOWINGMONTH') {
                            termsText = `Due end of following month (Day ${day || '??'})`;
                        } else if (type === 'OFCURRENTMONTH') {
                            termsText = `Due end of current month (Day ${day || '??'})`;
                        } else {
                            termsText = `Due based on: ${type} (Day ${day || '??'})`;
                        }

                        // Step 3: Set values
                        $("#payment_terms").val(JSON.stringify(paymentTerms)); // Hidden
                        $("#payment_terms_text").val(termsText);               // Shown nicely



                console.log("Discount value: ", ui.item.discount);
                console.log("Mapped to: ", priceLevelLabel);
            } else {
                $("#customer_search").val('');
                $("#customer_uid").val('');
                $("#customer_contact").val('');
                $("#customer_address").val('');
                $("#customer_suburb").val('');
                $("#customer_state").val('');
                $("#customer_postcode").val('');
                $("#customer_email").val('');
                $("#customer_phone").val('');
                $("#price_level").val('');
                $("#customer_discount").val('');
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
    $("#receivable_account_name").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: '/includes_pages/admin_accounting/xero_functions.php?get_all_accounts',
                method: "GET",
                data: {
                    classification: 'SALES',
                    term: request.term
                },
                dataType: "json",
                success: function(data) {
                    response($.map(data, function(item) {
                        return {
                            label: item.label,
                            value: item.label,
                            code: item.display_id, // <-- use display_id (code)
                            description: item.description
                        };
                    }));
                },
                error: function(xhr, status, error) {
                    console.error("Autocomplete error:", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            $("#receivable_account_name").val(ui.item.label);
            $("#receivable_account_code").val(ui.item.code); // <-- store account "Code" string
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
            $.ajax({
                url: '/includes_pages/admin_accounting/xero_functions.php?get_all_tax',
                method: "GET",
                dataType: "json",
                success: function(data) {
                    if (data && data.TaxRates) {
                        response($.map(data.TaxRates, function(item) {
                            return {
                                label: item.Name + ' (' + item.TaxType + ')',
                                value: item.Name,
                                uid: item.TaxType,
                                display_id: item.Name,
                                description: item.TaxType
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
            $("#payable_account_tax").val(ui.item.label);
            $("#payable_account_tax_code").val(ui.item.uid);
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
            $.ajax({
                url: '/includes_pages/admin_accounting/xero_functions.php?get_all_tax',
                method: "GET",
                dataType: "json",
                success: function(data) {
                    if (data && data.TaxRates) {
                        response($.map(data.TaxRates, function(item) {
                            return {
                                label: item.Name + ' (' + item.TaxType + ')',
                                value: item.Name,
                                uid: item.TaxType,
                                classification: item.TaxType,
                                display_id: item.Name,
                                description: item.Name
                            };
                        }));
                    } else {
                        console.error("Unexpected data format: ", data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            $("#receivable_account_tax").val(ui.item.label); // Display tax name
            $("#receivable_account_tax_code").val(ui.item.uid); // Store TaxType code
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
            $.ajax({
                url: '/includes_pages/admin_accounting/xero_functions.php',
                method: "GET",
                data: {
                    xero_get_suppliers: true,
                    term: request.term
                },
                dataType: "json",
                success: function(data) {
                    if (data.length === 0) {
                        response([{ label: 'Supplier not found', value: '' }]);
                    } else {
                        console.log(data)
                        response($.map(data, function(item) {
                            return {
                                label: item.company,
                                uid: item.uid,
                                company: item.company,
                                address: item.address,
                                suburb: item.suburb,
                                state: item.state,
                                postcode: item.postcode,
                                phone: item.phone1,
                                email: item.email,
                                payment_terms_day: item.payment_terms_day,
                                payment_terms_type: item.payment_terms_type
                            };
                        }));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Xero Supplier AJAX Error:", status, error);
                }
            });
        },
        minLength: 2,
        autoFocus: true,
        select: function(event, ui) {
            $("#vendor_search").val(ui.item.company);
            $("#vendor_uid").val(ui.item.uid);
            $("#vendor_address").val(ui.item.address);
            $("#vendor_suburb").val(ui.item.suburb);
            $("#vendor_state").val(ui.item.state);
            $("#vendor_postcode").val(ui.item.postcode);
            $("#vendor_phone").val(ui.item.phone);
            $("#vendor_email").val(ui.item.email);
            $("#payment_terms_day").val(ui.item.payment_terms_day);
            $("#payment_terms_type").val(ui.item.payment_terms_type);
            return false;
        }
    }).on('keydown', function(event) {
        if (event.keyCode === $.ui.keyCode.TAB && $(this).data('ui-autocomplete').menu.active) {
            event.preventDefault();
            $(this).data('ui-autocomplete').menu.select();
        }
    });
});


