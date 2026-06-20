<script type="text/javascript" src="includes_pages/admin_item_units/script.js?n=<?php echo date('h:i');?>"></script>
<main id="main" class="main">
    <div class="card border-0">
        <div class="card-body">
            <h4 class="card-title" id="company_name_text">Inventory Item Units</h4>
            <div id="body_content"></div>
        </div>
    </div>
</main>

<!-- Add Unit Modal -->
<div class="modal fade" id="add_unit_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="item_units" class="form-label">Units Name</label>
                    <div class="row mb-0 align-items-center">
                                <label for="inputText" class="col-sm-3 col-form-label">Divisible</label>
                                <div class="col-sm-9 d-flex align-items-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="divisible">
                                    </div>
                                </div>
                            </div>
                    <div class="input-group">
                        <input name="units" type="text" id="item_units" class="form-control" placeholder="Enter unit name">
                        <button type="button" class="btn btn-outline-secondary" onclick="AddUnit()">Add</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Unit Modal -->
<div class="modal fade" id="edit_unit_modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <input type="hidden" id="edit_unit_id">
                <div class="mb-3">
                    <label for="edit_item_units" class="form-label">Units Name</label>
                    <div class="row mb-0 align-items-center">
                                <label for="inputText" class="col-sm-3 col-form-label">Divisible</label>
                                <div class="col-sm-9 d-flex align-items-center">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="edit_divisible">
                                    </div>
                                </div>
                            </div>
                    <div class="input-group">
                        <input name="units" type="text" id="edit_item_units" class="form-control" placeholder="Edit unit name">
                        <button type="button" class="btn btn-outline-secondary" onclick="EditUnit()">Save</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

