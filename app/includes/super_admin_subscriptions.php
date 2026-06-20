<script type="text/javascript" src="includes_pages/super_admin_subscriptions/scripts.js?n=<? echo date('h:i');?>"></script> 
<main id="main" class="main">
	
	    <div class="card border-0">
        <div class="card-body">
            <h5 class="card-title"><div id="site_address_full"></div></h5>

            <!-- Styled Tabs with Boxicons and Text -->
            <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="company-tab" data-bs-toggle="tab" onclick="Loadtab('company')" role="tab" aria-controls="company" aria-selected="true">
                        <i class='bx bxs-home'></i> Home
                    </a>
                </li>

                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="subscription-tab" data-bs-toggle="tab" onclick="Loadtab('subscribers')" role="tab" aria-controls="contract" aria-selected="false">
                        <i class='bx bx-history'></i> Subscribers
                    </a>
                </li>
            </ul>
            <div id="tab_body"></div>
        </div>
    </div>
	
	<div id="company_data"></div>
</main>
  
<div class="modal fade" id="modalEditCompany" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Subscriber</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
				<input name="edit_company_id" type="hidden" id="edit_company_id">
                <!-- Tab Navigation -->
                <ul class="nav nav-tabs custom-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#generalInfoTab">General Information</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#contactDetailsTab">Contact Details</a>
                    </li>
					<li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#subscriptionDetailsTab">Subscription</a>
                    </li>

                </ul>

                <!-- Tab Content -->
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="generalInfoTab">
                        <!-- General Information Fields -->
                        <div class="row mb-3">
                            <label for="company" class="col-md-4 col-lg-3 col-form-label">Company</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="company_name" type="text"  class="form-control" id="edit_company_name">
                            </div>
                        </div>
						 <div class="row mb-3">
                            <label for="Address" class="col-md-4 col-lg-3 col-form-label">Address</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="address" type="text" class="form-control" id="edit_company_address">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="Suburb" class="col-md-4 col-lg-3 col-form-label">Suburb</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="suburb" type="text" class="form-control" id="edit_company_suburb">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="Postcode" class="col-md-4 col-lg-3 col-form-label">Postcode</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="postcode" type="number" class="form-control" id="edit_company_postode">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="State" class="col-md-4 col-lg-3 col-form-label">State</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="state" type="text" class="form-control" id="edit_company_state">
                            </div>
                        </div>
                        
                        <!-- Add other general information fields here -->
                    </div>

                    <div class="tab-pane fade" id="contactDetailsTab">
                        <!-- Contact Details Fields -->
                       <div class="row mb-3">
                            <label for="domain_name" class="col-md-4 col-lg-3 col-form-label">Domain Name</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="domain_name"  type="text" class="form-control" id="edit_domain_name">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="company_contact" class="col-md-4 col-lg-3 col-form-label">Company Contact</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="company_contact" type="text" class="form-control" id="edit_company_contact">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="Phone" class="col-md-4 col-lg-3 col-form-label">Phone</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="phone" type="number" class="form-control" id="edit_company_phone">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="Email" class="col-md-4 col-lg-3 col-form-label">Primary Email</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="email" type="email"  class="form-control" id="edit_company_email">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="AccountsEmail" class="col-md-4 col-lg-3 col-form-label">Accounts Email</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="accounts_email" type="email" class="form-control" id="edit_company_accounts_email">
                            </div>
                        </div>
                        <!-- Add other contact details fields here -->
                    </div>
					  <div class="tab-pane fade" id="subscriptionDetailsTab">
                        <!-- Contact Details Fields -->
                        <div class="row mb-3">
                            <label for="Address" class="col-md-4 col-lg-3 col-form-label">Plan</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="edit_subscription_plan" type="text" class="form-control" id="edit_subscription_plan">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label for="Suburb" class="col-md-4 col-lg-3 col-form-label">Renew Date</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="edit_subscription_renew" type="text" class="form-control" id="edit_subscription_renew">
                            </div>
                        </div>
						<div class="row mb-3">
                            <label for="Postcode" class="col-md-4 col-lg-3 col-form-label">Renew Price</label>
                            <div class="col-md-8 col-lg-9">
                                <input name="edit_subscription_monthly_rate" type="number" class="form-control" id="edit_subscription_monthly_rate">
                            </div>
                        </div>  


                        <!-- Add other contact details fields here -->
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-outline-secondary" onclick="SaveCompany()">Save</button>
            </div>
        </div>
    </div>
</div>
