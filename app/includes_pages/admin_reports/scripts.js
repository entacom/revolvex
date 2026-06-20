// /includes_pages/admin_reports/scripts.js
// Note: Bootstrap 5 already included globally. jQuery is available.

var content_url = 'includes_pages/admin_reports/content.php';
var crud_url = 'includes_pages/admin_reports/crud.php';

function getCurrentMonthDates() {
    var date = new Date();
    var firstDay = new Date(date.getFullYear(), date.getMonth(), 1);
    var lastDay = new Date(date.getFullYear(), date.getMonth() + 1, 0);
    return {
        firstDay: formatDate(firstDay),
        lastDay: formatDate(lastDay)
    };
}

function formatDate(date) {
    var day = ("0" + date.getDate()).slice(-2);
    var month = ("0" + (date.getMonth() + 1)).slice(-2);
    var year = date.getFullYear();
    return day + "-" + month + "-" + year;
}

function Loadtab(tab) {
    var data = {};
    if (tab === 'item_report') {
        data.sales_invoice_report = true;
        var dates = getCurrentMonthDates();
        data.from_date = dates.firstDay;
        data.to_date = dates.lastDay;
        console.log("Loading data from:", dates.firstDay, "to:", dates.lastDay);
    } else if (tab === 'customer_report') {
        data.customer_report = true;
    }

    $.ajax({
        type: "POST",
        url: content_url,
        data: data,
        success: function(response) {
            $('#body_content').html(response);

            // Initialize datepickers for item report only
            $(".datepicker").datepicker({ dateFormat: "dd-mm-yy" });

            // Populate dropdown after content is loaded
            if (tab === 'item_report') {
                populateDropdown('#select_part_number', 'Select a part number', 'get_invoiced_part_numbers', 'part_number', 'part_number');
                $('#from_date').val(dates.firstDay);
                $('#to_date').val(dates.lastDay);

                // Date range filter
                $('#filter_date_range').off('click').on('click', function() {
                    var fromDate = $('#from_date').val();
                    var toDate = $('#to_date').val();
                    var partNumber = $('#select_part_number').val();
                    filterReportByDateAndPartNumber(fromDate, toDate, partNumber);
                });
            }
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function filterReportByDateAndPartNumber(fromDate, toDate, partNumber) {
    var data = {
        sales_invoice_report: true,
        from_date: fromDate,
        to_date: toDate,
        part_number: partNumber
    };

    console.log(data);

    $.ajax({
        type: "POST",
        url: content_url,
        data: data,
        success: function(response) {
            $('#body_content').html(response);

            // Reinitialize datepickers after content is loaded
            $(".datepicker").datepicker({ dateFormat: "dd-mm-yy" });

            // Repopulate dropdown
            populateDropdown('#select_part_number', 'Select a part number', 'get_invoiced_part_numbers', 'part_number', 'part_number');
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function StockReport(finishedFilter) {
    var data = {
        stock_report: true,
        finished_filter: finishedFilter || $('#stock_finished_filter').val() || 'all'
    };
    $.ajax({
        type: "POST",
        url: content_url,
        data: data,
        success: function(response) {
            $('#body_content').html(response);
            $('#stock_finished_filter').val(data.finished_filter);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function ClosedCoilReport() {
    var data = { closed_coil_report: true };
    $.ajax({
        type: "POST",
        url: content_url,
        data: data,
        success: function(response) {
            $('#body_content').html(response);
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

// =======================
// SALES REPORT (CSV ONLY)
// =======================
function SalesReport() {
    var data = { sales_report: true };
    $.ajax({
        type: "POST",
        url: content_url,
        data: data,
        success: function(response) {
            $('#body_content').html(response);
            bindSalesReportUI(); // attach click handler for CSV generation
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function bindSalesReportUI() {
    // Use delegated handler in case content is reloaded
    $(document).off('click', '#btn-generate-csv').on('click', '#btn-generate-csv', function (e) {
        e.preventDefault();
        var d = $('#report_date').val(); // expected YYYY-MM-DD from <input type="date">
        if (!d) {
            alert('Please choose a date.');
            return;
        }
        generateSalesCSV(d);
    });
}

function generateSalesCSV(reportDate) {
    $.ajax({
        type: "POST",
        url: crud_url,
        data: { sales_report: true, report_date: reportDate },
        xhrFields: { responseType: 'blob' },
        success: function (data, status, xhr) {
            var filename = 'sales_report.csv';
            var disp = xhr.getResponseHeader('Content-Disposition');
            if (disp && disp.indexOf('filename=') !== -1) {
                var match = disp.match(/filename="?([^"]+)"?/);
                if (match && match[1]) { filename = match[1]; }
            }
            var blob = new Blob([data], { type: 'text/csv;charset=utf-8' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        },
        error: function (xhr, status, error) {
            console.error('CSV download failed:', error);
            alert('Failed to generate CSV.');
        }
    });
}

// =======================
// COIL REPORT (HTML + CSV)
// =======================
function CoilReport() {
    var data = { coil_report: true };
    $.ajax({
        type: "POST",
        url: content_url,
        data: data,
        success: function(response) {
            $('#body_content').html(response);
            bindCoilReportUI(); // attach click handler for CSV generation
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}

function bindCoilReportUI() {
    $(document).off('click', '#btn-coil-csv').on('click', '#btn-coil-csv', function (e) {
        e.preventDefault();
        var d = $('#coil_report_date').val(); // expected YYYY-MM-DD
        if (!d) {
            alert('Please choose a date.');
            return;
        }
        generateCoilCSV(d);
    });
}

function generateCoilCSV(reportDate) {
    $.ajax({
        type: "POST",
        url: crud_url,
        data: { coil_report: true, report_date: reportDate },
        xhrFields: { responseType: 'blob' },
        success: function (data, status, xhr) {
            var filename = 'coil_report.csv';
            var disp = xhr.getResponseHeader('Content-Disposition');
            if (disp && disp.indexOf('filename=') !== -1) {
                var match = disp.match(/filename="?([^"]+)"?/);
                if (match && match[1]) { filename = match[1]; }
            }
            var blob = new Blob([data], { type: 'text/csv;charset=utf-8' });
            var url = window.URL.createObjectURL(blob);
            var a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
        },
        error: function (xhr, status, error) {
            console.error('CSV download failed:', error);
            alert('Failed to generate CSV.');
        }
    });
}

$(document).ready(function() {
    Loadtab('item_report');
});
