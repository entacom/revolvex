$(document).ajaxSend(function(event, request, settings) {
  $('#loading-indicator').show();
});

$(document).ajaxComplete(function(event, request, settings) {
  $('#loading-indicator').hide();
});


function loadScript(src) {
    var script = document.createElement('script');
    script.src = src + '?v=' + new Date().getTime(); // Append timestamp to URL
    script.type = 'text/javascript';
    script.onload = function() {
        //console.log(src + ' loaded successfully.');
    };
    script.onerror = function() {
        console.error('site.js..Error loading script: ' + src);
    };
    document.head.appendChild(script);
}

function checkAccountingType() {
    console.log('check_accounting');
    var formData = {
        action: 'check_accounting',
    };
    var jsonData = JSON.stringify(formData);

    $.ajax({
        url: 'api/crud.php',
        type: 'POST',
        data: jsonData,
        contentType: "application/json",
        success: function(response) {
            var data = response[0];
            if (data.accounting_script === 'xero') {
                 console.log('Xero functions loaded.');
                loadScript('/includes_pages/admin_accounting/xero_functions.js');
            }
        },
        error: function(xhr, status, error) {
            handleError(xhr, status, error);
        }
    });
}


function getUrlVars() {
  var vars = {};
  var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(match, key, value) {
    vars[key] = decodeURIComponent(value);
  });
  return vars;
}
function redirectTo(page_url) {
    window.location.href = '?'+ page_url;
     }
function redirectToJob(order_id) {
    window.open('?p=admin_orders&order_id=' + order_id, '_blank');
}

function redirectToPurchase(order_id) {
    window.open('?p=admin_purchasing&pid=' + order_id, '_blank');
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        console.log('Text successfully copied to clipboard');
    }).catch(function(err) {
        console.error('Could not copy text to clipboard: ', err);
    });
}

document.addEventListener('click', function(event) {
    const tabLink = event.target.closest('.custom-tabs .nav-link');
    if (!tabLink) {
        return;
    }

    const tabList = tabLink.closest('.custom-tabs');
    if (!tabList) {
        return;
    }

    tabList.querySelectorAll('.nav-link').forEach(function(link) {
        link.classList.remove('active');
        link.setAttribute('aria-selected', 'false');
    });
    tabLink.classList.add('active');
    tabLink.setAttribute('aria-selected', 'true');
});

function PrintPurchaseOrder(order_id) {
    window.open('pdf/purchase_order_v1.php?pid=' + order_id, '_blank');
}
function PrintPurchaseDel(order_id) {
    window.open('pdf/purchase_order_del_v1.php?pid=' + order_id, '_blank');
}
function PrintSalesOrder(order_id) {
    window.open('pdf/sales_order_v1.php?order_id=' + order_id , '_blank');
}
function PrintSalesQuote(order_id) {
    window.open('pdf/sales_quote_v1.php?order_id=' + order_id , '_blank');
}
function PrintSalesInvoice(order_id) {
    window.open('pdf/sales_invoice_v1.php?order_id=' + order_id , '_blank');
}
function PrintSalesDelivery(order_id) {
    window.open('pdf/sales_delivery_v1.php?order_id=' + order_id , '_blank');
}
function PrintPack(order_id,pack_id) {
    window.open('pdf/label_v1.php?order_id=' + order_id +'&pack_id='+pack_id , '_blank');
}
function PrintPackZeb(order_id,pack_id) {
    window.open('pdf/label_v5.php?order_id=' + order_id +'&pack_id='+pack_id , '_blank');
}
function PrintPack_dl(order_id,pack_id) {
    window.open('pdf/label_v2.php?order_id=' + order_id +'&pack_id='+pack_id , '_blank');
}
function PrintPackAll(order_id) {
    window.open('pdf/label_all_v1.php?order_id=' + order_id  , '_blank');
}
function PrintPackAllZeb(order_id) {
    window.open('pdf/label_all_v5.php?order_id=' + order_id  , '_blank');
}
function PrintPicking(order_id,pack_id) {
    window.open('pdf/sales_picking_v1.php?order_id=' + order_id , '_blank');
}
function PrintProdCard(order_id,part_number) {
    window.open('pdf/sales_production_v1.php?order_id=' + order_id +'&part_number='+part_number, '_blank');
}
function PrintProdCardAll(order_id) {
    window.open('pdf/sales_production_v2.php?order_id=' + order_id , '_blank');
}
// open full screen if viewing jobs
/*
document.addEventListener('DOMContentLoaded', function() {
    // Check if the current URL contains '/?p=project_id'
    if (window.location.href.indexOf('/?p=project_id') > -1) {
        // Simulate a click on the toggle sidebar button
        var toggleButton = document.querySelector('.toggle-sidebar-btn');

        // Trigger a click event on the button to toggle the sidebar
        if (toggleButton) {
            toggleButton.click();
        }
    }
});
*/
function uniModal(modalName) {
        $('#' + modalName).modal('show');
    }
if (typeof Dropzone !== 'undefined') {
    Dropzone.autoDiscover = false;
}

// General function to populate dropdowns
function populateDropdown(selector, initialData, action, valueField, textField) {
    $(selector).html('<option>' + initialData + '</option>');
    $(selector).append('<option>----Select----</option>');
	  var formData = {
        action: action,
    };
	
	var jsonData = JSON.stringify(formData);
    $.ajax({
         url: '../../api/crud.php',
        type: 'POST',
        contentType: "application/json",
        data: jsonData,
        success: function(responseData) {
            $.each(responseData, function(key, item) {
                $(selector).append('<option value="' + item[valueField] + '">' + item[textField] + '</option>');
            });
        },
        error: function(jqXHR, textStatus, errorThrown) {
            console.log('Error occurred: ' + textStatus + ' - ' + errorThrown);
        }
    });
}
function ajaxPostRequest(formData, crud_url) {
    var jsonData = JSON.stringify(formData);
    $.ajax({
        type: "POST",
        url: crud_url,
        data: jsonData,
        contentType: "application/json",
        success: function (response) {
            var parsedResponse = JSON.parse(response);
            if (parsedResponse.success) {
                handleSuccess(parsedResponse.message);
            } else if (parsedResponse.warning) {
                handleNoChange(parsedResponse.message);
            } else {
                handleError('Server returned an error.', 'Unknown');
            }
        },
        error: function (xhr, status, error) {
            if (xhr.status === 500) {
                handleError('Internal Server Error', xhr.status);
            } else {
                handleError(xhr.responseText, xhr.status);
            }
        }
    });
}
function handleSuccess(response) {
    $('#site_message').text(response);
    $('#site_message').css({
        'background-color': '#d4edda', // Success background color (greenish)
        'color': '#155724', // Success text color
        'border': '1px solid #c3e6cb', // Success border color
        'padding': '10px',
        'margin-bottom': '10px'
    });

    $('#site_message').delay(3500).fadeOut('slow', function() {
        $(this).text('');
        $(this).css({
            'background-color': '',
            'color': '',
            'border': '',
            'padding': '',
            'margin-bottom': ''
        });
        $(this).show();
    });
}

function handleNoChange(response) {
    $('#site_message').text(response);
    $('#site_message').css({
        'background-color': '#fff3cd', // Warning background color (yellowish)
        'color': '#856404', // Warning text color
        'border': '1px solid #ffeeba', // Warning border color
        'padding': '10px',
        'margin-bottom': '10px'
    });

    $('#site_message').delay(3500).fadeOut('slow', function() {
        $(this).text('');
        $(this).css({
            'background-color': '',
            'color': '',
            'border': '',
            'padding': '',
            'margin-bottom': ''
        });
        $(this).show();
    });
}

function handleError(errorMessage, statusCode) {
    $('#site_message').text('Error: ' + errorMessage + ' (Code: ' + statusCode + ')');
    $('#site_message').css({
        'background-color': '#f8d7da', // Error background color (reddish)
        'color': '#721c24', // Error text color
        'border': '1px solid #f5c6cb', // Error border color
        'padding': '10px',
        'margin-bottom': '10px'
    });

    $('#site_message').delay(3500).fadeOut('slow', function() {
        $(this).text('');
        $(this).css({
            'background-color': '',
            'color': '',
            'border': '',
            'padding': '',
            'margin-bottom': ''
        });
        $(this).show();
    });
}

function goToNextPage() {
    currentPage++;
    Loadtab(tab_id);
}

function goToPreviousPage() {
    if (currentPage > 1) {
        currentPage--;
        Loadtab(tab_id);
    }
}






