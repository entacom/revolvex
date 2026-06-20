<script type="text/javascript" src="includes_pages/admin_setup/scripts.js?n=<? echo date('h:i');?>"></script> 
<main id="main" class="main">
	   <div class="card border-0">
        <div class="card-body">
            <h4 class="card-title" id="company_name_text"></h4>
			
            <!-- Styled Tabs with Boxicons and Text -->
            <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="company-tab" data-bs-toggle="tab" onclick="Loadtab('company')" role="tab" aria-controls="company" aria-selected="true">
                        <i class='bx bxs-home'></i> Home
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="budget-tab" data-bs-toggle="tab" onclick="Loadtab('tab_budget')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bxs-task'></i> Budget
                    </a>
                </li>
				<li class="nav-item" role="presentation">
                    <a class="nav-link" id="banking-tab" data-bs-toggle="tab" onclick="Loadtab('tab_budget_cat')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> Budget Category
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="settings-tab" data-bs-toggle="tab" onclick="Loadtab('tab_trades')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> Trades
                    </a>
                </li>

				<li class="nav-item" role="presentation">
                    <a class="nav-link" id="accounting-tab" data-bs-toggle="tab" onclick="Loadtab('accounting')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> XXX
                    </a>
                </li>

            </ul>
        </div>
    </div>
	<div id="main_content"></div>
</main>
<div class="modal fade" id="addTradeModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Add Trade</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                     <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Trade Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="trade_name" type="text" id="trade_name" class="form-control"  placeholder="Trade Name">
                      </div>
                    </div>
					 <div class="row mb-3">
						<label for="fullName" class="col-md-4 col-lg-3 col-form-label">Trade Rate</label>
						<div class="col-md-8 col-lg-9">
							<div class="input-group mb-3">
								<span class="input-group-text" id="basic-addon1">$</span>
								<input type="number" id="trade_rate" class="form-control" placeholder="Rate" aria-label="Rate" aria-describedby="basic-addon1">
							</div>
						</div>
					</div>
	
					  <!-- Inside your modal -->
						<div  id="modal_message"></div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-outline-secondary" onclick="AddTrade() ">Add Trade</button>
                    </div>
                  </div>
                </div>
			  </div>
</div><!-- End Large Modal-->
<div class="modal fade" id="editTradeModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Add Trade</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
						<input name="edit_trade_id" type="hidden" id="edit_trade_id">
                     <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Trade Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="edit_trade_name" type="text" id="edit_trade_name" class="form-control"  placeholder="Trade Name">
                      </div>
                    </div>
					 <div class="row mb-3">
						<label for="fullName" class="col-md-4 col-lg-3 col-form-label">Trade Rate</label>
						<div class="col-md-8 col-lg-9">
							<div class="input-group mb-3">
								<span class="input-group-text" id="basic-addon1">$</span>
								<input type="number" id="edit_rate" class="form-control" placeholder="Rate" aria-label="Rate" aria-describedby="basic-addon1">
							</div>
						</div>
					</div>
	
					  <!-- Inside your modal -->
						<div  id="modal_message"></div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-outline-secondary" onclick="SaveTrade() ">Save Trade</button>
                    </div>
                  </div>
                </div>
			  </div>
</div><!-- End Large Modal-->
  <div class="modal fade" id="addTradeModalXXX" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title">Add Trade</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                     <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">First Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="fullName" type="text" id="new_firstname" class="form-control" >
                      </div>
                    </div>
					 <div class="row mb-3">
                      <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Last Name</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="fullName" type="text" id="new_lastname" class="form-control" >
                      </div>
                    </div>	
	
	
					<div class="input-group mb-3">
						 <label for="fullName" class="col-md-4 col-lg-3 col-form-label">Username</label>
                      <input type="text" class="form-control"   disabled id="new_username" aria-label="new_username">
                      <span class="input-group-text">@</span>
                      <input type="text" class="form-control" disabled id="domain_name_user" aria-label="domain_name_user">
                    </div>
						
                    <div class="row mb-3">
                      <label for="Job" class="col-md-4 col-lg-3 col-form-label">Job Description</label>
                      <div class="col-md-8 col-lg-9">
                        <input name="job" type="text" class="form-control" id="new_job_position">
                      </div>
                    </div>
						




					  <!-- Inside your modal -->
						<div  id="modal_message"></div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                      <button type="button" class="btn btn-outline-secondary" onclick="AddUser() ">Add User</button>
                    </div>
                  </div>
                </div>
			  </div>
</div><!-- End Large Modal-->