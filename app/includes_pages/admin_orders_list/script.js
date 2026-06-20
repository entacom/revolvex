// ======= BEGIN AMENDED BLOCK: includes_pages/admin_orders_list/script.js =======
var crud_url = 'includes_pages/admin_orders_list/crud.php';
var tab_id = 'order_list';
var currentPage = 1;
var totalPages = null; // Not used by backend currently; safe to keep for future.
var selectedOrderStatusId = '';
var currentSortField = '';
var currentSortOrder = 'desc';
var currentSearchQuery = '';

const urlParams = new URLSearchParams(window.location.search);
const tParam = urlParams.get('t'); // "1" or "2"
const statusParam = urlParams.get('order_status_id') || '';

$(document).ready(function () {
    if (statusParam) {
        selectedOrderStatusId = statusParam;
        $('#orderStatusFilter').val(statusParam);
    }

    // Set tab title and "Add" button label/route on page load
    if (tParam === '1') {
        $('#tabTitleText').text('Quotes');
        $('#addNewLeadBtn').text('Add New Quote');
    } else {
        $('#tabTitleText').text('Orders');
        $('#addNewLeadBtn').text('Add New Order');
    }

    $('#addNewLeadBtn').off('click').on('click', function () {
        redirectTo(`p=admin_orders&t=${tParam || ''}`);
    });

    // Initial load
    Loadtab(tab_id);
});

function handleKeyPress(event) {
    if (event.key === 'Enter') {
        searchOrder();
    }
}

function searchOrder() {
    currentSearchQuery = document.getElementById('customerSearch').value.trim();
    currentPage = 1;
    totalPages = null;
    Loadtab(tab_id);
}

function filterByStatus() {
    selectedOrderStatusId = document.getElementById('orderStatusFilter').value;
    currentPage = 1;
    totalPages = null;
    Loadtab(tab_id);
}

function sortOrdersByDate(field) {
    if (currentSortField === field) {
        currentSortOrder = currentSortOrder === 'asc' ? 'desc' : 'asc';
    } else {
        currentSortField = field;
        currentSortOrder = 'desc';
    }
    currentPage = 1;
    totalPages = null;
    Loadtab(tab_id);
}

function resetSearch() {
    document.getElementById('customerSearch').value = '';
    document.getElementById('orderStatusFilter').value = '';
    selectedOrderStatusId = '';
    currentSearchQuery = '';
    currentSortField = '';
    currentSortOrder = 'desc';
    currentPage = 1;
    totalPages = null;
    Loadtab(tab_id);
}

function goToPreviousPage() {
    if (currentPage > 1) {
        currentPage--;
        Loadtab(tab_id);
    }
}

function goToNextPage() {
    if (totalPages === null || currentPage < totalPages) {
        currentPage++;
        Loadtab(tab_id);
    }
}

function Loadtab(tabId) {
    tab_id = tabId;
    var orderStatusId = selectedOrderStatusId || '';

    $.ajax({
        type: "POST",
        url: "includes_pages/admin_orders_list/content.php?t=" + (tParam || ''),
        data: {
            tab_id: tab_id,
            current_page: currentPage,
            search: currentSearchQuery,
            order_status_id: orderStatusId,
            sort_field: currentSortField,
            sort_order: currentSortOrder
        },
        success: function (response) {
            // Backend now returns only HTML; inject directly.
            $('#tab_body').html(response);
        },
        error: function (xhr, status, error) {
            console.error("Error receiving data:", error);
            $('#tab_body').html('<div class="card"><div class="card-body"><p>Error loading orders.</p></div></div>');
        }
    });
}
// ======= END AMENDED BLOCK: includes_pages/admin_orders_list/script.js =======
