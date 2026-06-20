<?php
if (!in_array($_SESSION['session_group_id'], [11, 12, 13])) {
    echo '<main id="main" class="main"><div class="alert alert-danger">You do not have permission to access Git Update.</div></main>';
    return;
}
?>
<script type="text/javascript" src="includes_pages/admin_git_update/script.js?n=<?php echo date('h:i');?>"></script>

<main id="main" class="main git-update-page">
    <div class="git-update-shell">
        <div class="git-update-header">
            <div>
                <div class="git-update-kicker">Deployment</div>
                <h4>Git Update</h4>
                <p>Pull the latest GitHub changes into the cPanel repo and deploy the app folder to public_html.</p>
            </div>
            <button type="button" id="git_update_button" class="btn btn-success" onclick="RunGitUpdate()">
                <i class="bx bx-cloud-download"></i> Pull & Deploy
            </button>
        </div>

        <div id="git_update_message"></div>

        <div class="row g-3">
            <div class="col-lg-4">
                <div class="git-update-card">
                    <div class="git-update-card-title">Current Version</div>
                    <div id="git_current_version" class="git-update-version">Loading...</div>
                    <div id="git_current_meta" class="git-update-muted"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="git-update-card">
                    <div class="git-update-card-title">Remote Status</div>
                    <div id="git_remote_status" class="git-update-version">Loading...</div>
                    <div id="git_remote_meta" class="git-update-muted"></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="git-update-card">
                    <div class="git-update-card-title">Last App Deploy</div>
                    <div id="git_last_deploy" class="git-update-version">Loading...</div>
                    <div id="git_last_deploy_meta" class="git-update-muted"></div>
                </div>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-7">
                <div class="git-update-card">
                    <div class="git-update-card-title">Recent Changes</div>
                    <div id="git_commit_history" class="git-update-history">Loading...</div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="git-update-card">
                    <div class="git-update-card-title">Deploy Output</div>
                    <pre id="git_deploy_output" class="git-update-output">No deploy run in this browser session yet.</pre>
                </div>
            </div>
        </div>
    </div>
</main>
