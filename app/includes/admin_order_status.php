<script type="text/javascript" src="includes_pages/admin_order_status/script.js?n=<?php echo date('h:i');?>"></script>
<main id="main" class="main">
    <div class="card border-0">
        <div class="card-body">
            <h4 class="card-title" id="company_name_text">Order Status</h4>
            <div id="body_content"></div>
        </div>
    </div>
</main>

<!-- Add Unit Modal -->
<div class="modal fade" id="add_status_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="description" class="form-label">Status Name</label>
                    <div class="input-group">
                        <input name="description" type="text" id="description" class="form-control" placeholder="Enter unit name">
                        <button type="button" class="btn btn-outline-secondary" onclick="AddStatus()">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="edit_status_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_status_id">
                <div class="mb-3">
                    <label for="edit_status" class="form-label">Status Name</label>
                    <div class="input-group">
                        <input name="edit_description" type="text" id="edit_description" class="form-control" placeholder="Edit unit name">
                        <button type="button" class="btn btn-outline-secondary" onclick="EditStatus()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
