function LoadOrdersTable() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_dashboard/content.php?recent_orders",
        data: { }, // Assuming 'home' is the correct tabId
        success: function(data, status) {
            $("#recent_order_table").html(data);
        }
    });
}
function LoadRecentActivityTable() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_dashboard/content.php?recent_activity",
        data: { }, // Assuming 'home' is the correct tabId
        success: function(data, status) {
            $("#recent_activity_table").html(data);
        }
    });
}
$(document).ready(function() {
    LoadOrdersTable();
    LoadRecentActivityTable()
});

	