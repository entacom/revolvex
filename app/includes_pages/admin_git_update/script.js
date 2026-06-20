var gitUpdateCrudUrl = 'includes_pages/admin_git_update/crud.php';

function escapeGitText(value) {
    return $('<div>').text(value || '').html();
}

function LoadGitUpdateStatus() {
    $.ajax({
        url: gitUpdateCrudUrl,
        type: 'POST',
        dataType: 'json',
        data: { action: 'status' },
        success: function(response) {
            if (!response || !response.success) {
                $('#git_update_message').html('<div class="alert alert-warning">Could not load Git status.</div>');
                return;
            }

            $('#git_current_version').text(response.current.short_hash || 'Unknown');
            $('#git_current_meta').text(response.current.message || '');
            $('#git_remote_status').text(response.remote.status || 'Unknown');
            $('#git_remote_meta').text(response.remote.detail || '');
            $('#git_last_deploy').text(response.last_deploy.title || 'No deploy recorded');
            $('#git_last_deploy_meta').text(response.last_deploy.detail || '');

            var historyHtml = '';
            (response.history || []).forEach(function(item) {
                historyHtml += '<div class="git-history-row">';
                historyHtml += '<div class="git-history-hash">' + escapeGitText(item.hash) + '</div>';
                historyHtml += '<div class="git-history-body">';
                historyHtml += '<div class="git-history-message">' + escapeGitText(item.message) + '</div>';
                historyHtml += '<div class="git-history-meta">' + escapeGitText(item.date + ' by ' + item.author) + '</div>';
                historyHtml += '</div></div>';
            });
            $('#git_commit_history').html(historyHtml || '<div class="git-update-muted">No commits found.</div>');
        },
        error: function(xhr) {
            $('#git_update_message').html('<div class="alert alert-danger">' + escapeGitText(xhr.responseText || 'Git status failed.') + '</div>');
        }
    });
}

function RunGitUpdate() {
    if (!confirm('Pull latest GitHub changes and deploy to public_html?')) {
        return;
    }

    $('#git_update_button').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
    $('#git_update_message').html('<div class="alert alert-info">Git update running. Please wait...</div>');

    $.ajax({
        url: gitUpdateCrudUrl,
        type: 'POST',
        dataType: 'json',
        data: { action: 'deploy' },
        success: function(response) {
            if (response && response.success) {
                $('#git_update_message').html('<div class="alert alert-success">' + escapeGitText(response.message) + '</div>');
            } else {
                $('#git_update_message').html('<div class="alert alert-danger">' + escapeGitText((response && response.message) ? response.message : 'Git update failed.') + '</div>');
            }

            $('#git_deploy_output').text(response && response.output ? response.output : 'No output returned.');
            LoadGitUpdateStatus();
        },
        error: function(xhr) {
            $('#git_update_message').html('<div class="alert alert-danger">' + escapeGitText(xhr.responseText || 'Git update failed.') + '</div>');
        },
        complete: function() {
            $('#git_update_button').prop('disabled', false).html('<i class="bx bx-cloud-download"></i> Pull & Deploy');
        }
    });
}

$(document).ready(function() {
    LoadGitUpdateStatus();
});
