<script type="text/javascript" src="includes_pages/admin_item_groups/script.js?n=<?php echo date('h:i');?>"></script>
<main id="main" class="main">
    <div class="card border-0">
        <div class="card-body">
            <h4 class="card-title" id="company_name_text">Inventory Item Groups</h4>
            <div id="body_content"></div>
        </div>
    </div>
</main>

<!-- Add Unit Modal -->
<div class="modal fade" id="add_group_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="item_groups" class="form-label">Group Name</label>
                    <div class="input-group">
                        <input name="item_groups" type="text" id="item_groups" class="form-control" placeholder="Enter unit name">
                        <button type="button" class="btn btn-outline-secondary" onclick="AddGroup()">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="edit_group_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_group_id">
                
                <div class="mb-3">
                    <label for="edit_item_group" class="form-label">Group Name</label>
                    <div class="input-group">
                        <input name="edit_item_group" type="text" id="edit_item_group" class="form-control" placeholder="Edit group name">
                        <button type="button" class="btn btn-outline-secondary" onclick="EditGroup()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

