
<script type="text/javascript" src="includes_pages/super_admin_plans/scripts.js?n=<? echo date('h:i');?>"></script> 
  
<main id="main" class="main">
	
	    <div class="card border-0">
        <div class="card-body">
            <h5 class="card-title"><div id="site_address_full"></div></h5>

            <!-- Styled Tabs with Boxicons and Text -->
            <ul class="nav nav-tabs nav-underline custom-tabs" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" id="plans-tab" data-bs-toggle="tab" onclick="Loadtab('plans')" role="tab" aria-controls="plans" aria-selected="true">
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
  
