<script type="text/javascript" src="includes_pages/super_admin_profile/scripts.js?n=<? echo date('h:i');?>"></script> 
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
                    <a class="nav-link" id="profile-tab" data-bs-toggle="tab" onclick="Loadtab('edit_profile')" role="tab" aria-controls="profile" aria-selected="false">
                        <i class='bx bxs-task'></i> Edit
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" id="settings-tab" data-bs-toggle="tab" onclick="Loadtab('settings')" role="tab" aria-controls="budget" aria-selected="false">
                        <i class='bx bx-history'></i> Settings
                    </a>
                </li>
				 <li class="nav-item" role="presentation">
                    <a class="nav-link" id="users_list-tab" data-bs-toggle="tab" onclick="Loadtab('users_list')" role="tab" aria-controls="activity" aria-selected="false">
                        <i class='bx bx-history'></i> Users
                    </a>
                </li>


            </ul>
            <div id="tab_body"></div>
        </div>
    </div>
	<div id="company_data"></div>
</main>
  