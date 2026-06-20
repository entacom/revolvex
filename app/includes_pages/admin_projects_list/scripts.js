function LoadProjectsTable() {
    $.ajax({
        type: "POST",
        url: "includes_pages/admin_projects_list/content.php?recent_projects",
        data: { }, // Assuming 'home' is the correct tabId
        success: function(data, status) {
            $("#recent_project_table").html(data);
            console.log(data + " content loaded");
        }
    });
}


$(document).ready(function() {
    LoadProjectsTable();
});
