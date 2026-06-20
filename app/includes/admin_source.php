<script type="text/javascript" src="includes_pages/admin_source/script.js?n=<?php echo date('h:i');?>"></script>
<main id="main" class="main">
    <div class="card border-0">
        <div class="card-body">
            <h4 class="card-title" id="company_name_text">Client Source</h4>
            <div id="body_content"></div>
        </div>
    </div>
</main>

<!-- Add Unit Modal -->
<div class="modal fade" id="add_source_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="source" class="form-label">Source Name</label>
                    <div class="input-group">
                        <input name="source" type="text" id="source" class="form-control" placeholder="Enter source name">
                        <button type="button" class="btn btn-outline-secondary" onclick="AddSource()">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Edit Unit Modal -->
<div class="modal fade" id="edit_source_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_source_id">
                <div class="mb-3">
                    <label for="edit_source" class="form-label">Source Name</label>
                    <div class="input-group">
                        <input name="edit_source" type="text" id="edit_source" class="form-control" placeholder="Edit unit name">
                        <button type="button" class="btn btn-outline-secondary" onclick="EditSource()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

