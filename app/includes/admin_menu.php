<header id="header" class="header fixed-top d-flex align-items-center">
    <div class="d-flex align-items-center justify-content-between">
        <a href="/" class="logo d-flex align-items-center">
            <img src="<?php echo getTableField('company_image_path','tblCompany', $_SESSION['session_company_id']); ?>" alt="">
        </a>
        <i class="bx bx-menu toggle-sidebar-btn"></i>
    </div>


    <div id="site_message"></div>
    <div id="loading-indicator" style="display:none;" class="text-center">
        <div class="spinner-border text-danger" role="status"></div>
    </div>

    <nav class="header-nav ms-auto">
        <ul class="d-flex align-items-center">
            <li class="nav-item d-block d-lg-none">
                <a class="nav-link nav-icon search-bar-toggle" href="/">
                    <i class="bx bx-search"></i>
                </a>
            </li>
            <li class="nav-item dropdown pe-3">
                <a class="nav-link nav-profile d-flex align-items-center pe-0" href="#" data-bs-toggle="dropdown">
                    <span class="d-none d-md-block dropdown-toggle ps-2"><?php echo $_SESSION['session_first_lastname']; ?></span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-arrow profile">
                    <li class="dropdown-header">
                        <h6><?php echo $_SESSION['session_first_lastname']; ?></h6>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="?p=admin_users_profile">
                            <i class="bx bx-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <a class="dropdown-item d-flex align-items-center" href="?p=logout">
                            <i class="bx bx-log-out"></i>
                            <span>Sign Out</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</header>

<aside id="sidebar" class="sidebar">
    <ul class="sidebar-nav" id="sidebar-nav">
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=admin_orders_list&t=1">
                <i class="bx bx-list-ul"></i>
                <span>Quotes</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=admin_orders_list&t=2">
                <i class="bx bx-list-ul"></i>
                <span>Orders</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=admin_production">
                <i class="bx bx-cog"></i>
                <span>Production</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=admin_inventory">
                <i class="bx bx-box"></i>
                <span>Inventory</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=admin_reports">
                <i class="bx bx-line-chart"></i>
                <span>Reports</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=admin_purchasing_list">
                <i class="bx bx-shopping-bag"></i>
                <span>Purchases</span>
            </a>
        </li>
        <?php if (in_array($_SESSION['session_group_id'], [11, 12, 13])) { ?>
            <li class="nav-item">
                <a class="nav-link collapsed" data-bs-target="#components-nav" data-bs-toggle="collapse" href="?p=admin_company">
                    <i class="bx bx-buildings"></i><span>Company</span><i class="bx bx-chevron-down ms-auto"></i>
                </a>
                <ul id="components-nav" class="nav-content collapse" data-bs-parent="#sidebar-nav">
                    <li>
                        <a href="?p=admin_company">
                            <i class="bx bx-circle"></i><span>Setup</span>
                        </a>
                    </li>
                    <li>
                        <a href="?p=admin_source">
                            <i class="bx bx-circle"></i><span>Source</span>
                        </a>
                    </li>
                    <li>
                        <a href="?p=admin_item_units">
                            <i class="bx bx-circle"></i><span>Units</span>
                        </a>
                    </li>
                    <li>
                        <a href="?p=admin_item_groups">
                            <i class="bx bx-circle"></i><span>Groups</span>
                        </a>
                    </li>
                    <li>
                        <a href="?p=admin_order_status">
                            <i class="bx bx-circle"></i><span>Order Status</span>
                        </a>
                    </li>
                     <li>
                        <a href="?p=admin_purchase_status">
                            <i class="bx bx-circle"></i><span>Purchase Status</span>
                        </a>
                    </li>
                </ul>
            </li>
        <?php } ?>
        <li class="nav-item">
            <a class="nav-link collapsed" href="?p=logout">
                <i class="bx bx-log-out"></i>
                <span>Logout</span>
            </a>
        </li>
    </ul>
</aside>
