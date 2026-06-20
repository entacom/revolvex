var crud_url = 'includes_pages/admin_purchasing_list/crud.php';
var tab_id = 'order_list';
var currentPage = 1;
var selectedOrderStatusId = ''; // New variable to store selected order status
var currentSearchQuery = '';
var currentSortField = '';
var currentSortOrder = 'desc';
const purchaseUrlParams = new URLSearchParams(window.location.search);
const purchaseStatusParam = purchaseUrlParams.get('order_status_id') || '';

$(document).ready(function() {
    if (purchaseStatusParam) {
        selectedOrderStatusId = purchaseStatusParam;
    }
    Loadtab(tab_id);
});

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        searchVendor();
    }
}

function searchVendor() {
    currentSearchQuery = document.getElementById('vendorSearch').value.trim();
    currentPage = 1;
    Loadtab(tab_id);
}

function filterByStatus() {
    selectedOrderStatusId = document.getElementById('orderStatusFilter').value; // Store selected status
    currentPage = 1;
    Loadtab(tab_id);
}

function resetSearch() {
    var vendorSearch = document.getElementById('vendorSearch');
    if (vendorSearch) {
        vendorSearch.value = '';
    }
    var orderStatusFilter = document.getElementById('orderStatusFilter');
    if (orderStatusFilter) {
        orderStatusFilter.value = '';
    }
    selectedOrderStatusId = '';
    currentSearchQuery = '';
    currentSortField = '';
    currentSortOrder = 'desc';
    currentPage = 1;
    Loadtab(tab_id);
}

function sortPurchasesBy(field) {
    if (currentSortField === field) {
        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortField = field;
        currentSortOrder = 'desc';
    }
    currentPage = 1;
    Loadtab(tab_id);
}

function goToPreviousPage() {
    if (currentPage > 1) {
        currentPage--;
        Loadtab(tab_id);
    }
}

function goToNextPage() {
    currentPage++;
    Loadtab(tab_id);
}

// Loadtab function with fallback if no results
function Loadtab(tabId) {
    tab_id = tabId;

    var orderStatusId = selectedOrderStatusId || ''; // Use stored order status

    $.ajax({
        type: "POST",
        url: "includes_pages/admin_purchasing_list/content.php",
        data: { 
            tab_id: tab_id, 
            current_page: currentPage, 
            search: currentSearchQuery, 
            order_status_id: orderStatusId,
            sort_field: currentSortField,
            sort_order: currentSortOrder
        },
        success: function(response) {
            $('#tab_body').html(response);

            // Reapply the selected filter value if it's set
            if (selectedOrderStatusId) {
                $('#orderStatusFilter').val(selectedOrderStatusId);
            }
            if (currentSearchQuery) {
                $('#vendorSearch').val(currentSearchQuery);
            }
        },
        error: function(xhr, status, error) {
            console.error("Error receiving data:", error);
        }
    });
}
