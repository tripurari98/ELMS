<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="" />
        <meta name="author" content="" />
        <title> Employee Leave Management System</title>
        <link href="asset/css/styles.css" rel="stylesheet" />
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <link rel="stylesheet" type="text/css" href="asset/vendor/datatables/dataTables.bootstrap5.min.css"/>
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <!-- Added Google Font for better typography -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        
        <style>
            /* Custom Sidebar Styles */
            :root {
                --sidebar-bg: #2c3e50;
                --sidebar-hover-bg: #34495e;
                --sidebar-active-bg: #3498db;
                --sidebar-text: #ecf0f1;
                --sidebar-icon: #3498db;
                --sidebar-active-icon: #ffffff;
                --sidebar-category: #3498db;
                --navbar-bg: #1a252f;
                --navbar-text: #ecf0f1;
                --navbar-icon: #3498db;
                --navbar-hover: rgba(255, 255, 255, 0.1);
            }
            
            body {
                font-family: 'Poppins', sans-serif;
            }
            
            /* Navbar styles */
            .sb-topnav {
                background-color: var(--navbar-bg) !important;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
                height: 65px;
                padding: 0 1rem;
            }
            
            .sb-topnav .navbar-brand {
                font-size: 1.2rem;
                font-weight: 600;
                color: white;
                padding: 0.5rem 1rem;
                display: flex;
                align-items: center;
                height: 100%;
            }
            
            .sb-topnav .navbar-brand i {
                color: var(--navbar-icon);
                margin-right: 10px;
                font-size: 1.4rem;
            }
            
            .navbar-dark .navbar-nav .nav-link {
                color: var(--navbar-text);
                font-weight: 500;
                padding: 0.7rem 1rem;
                border-radius: 5px;
                transition: all 0.3s ease;
            }
            
            .navbar-dark .navbar-nav .nav-link:hover {
                background-color: var(--navbar-hover);
            }
            
            .navbar-dark .navbar-nav .nav-link .badge {
                position: relative;
                top: -8px;
                right: -8px;
                font-size: 0.65rem;
                padding: 0.25rem 0.5rem;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            
            /* User dropdown styles */
            .user-dropdown {
                display: flex;
                align-items: center;
                padding: 0.5rem 1rem;
                border-radius: 30px;
                background-color: rgba(255, 255, 255, 0.1);
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .user-dropdown:hover {
                background-color: rgba(255, 255, 255, 0.15);
            }
            
            .user-dropdown img {
                width: 32px;
                height: 32px;
                border-radius: 50%;
                object-fit: cover;
                margin-right: 10px;
            }
            
            .user-dropdown .user-info {
                display: none;
            }
            
            @media (min-width: 768px) {
                .user-dropdown .user-info {
                    display: block;
                }
            }
            
            .user-dropdown .user-info .name {
                font-size: 0.9rem;
                font-weight: 500;
                color: white;
                margin: 0;
                line-height: 1.2;
            }
            
            .user-dropdown .user-info .role {
                font-size: 0.7rem;
                color: rgba(255, 255, 255, 0.7);
                margin: 0;
            }
            
            .user-dropdown .dropdown-toggle::after {
                margin-left: 0.5rem;
                vertical-align: middle;
            }
            
            /* Notification dropdown styles */
            .dropdown-menu {
                border: none;
                border-radius: 10px;
                box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
                padding: 0.5rem 0;
                min-width: 280px;
                max-width: 320px;
                margin-top: 10px;
            }
            
            .dropdown-menu .dropdown-header {
                font-weight: 600;
                font-size: 0.85rem;
                color: #333;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #f1f1f1;
            }
            
            .dropdown-menu .dropdown-item {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
                color: #555;
                transition: all 0.2s ease;
                border-left: 3px solid transparent;
            }
            
            .dropdown-menu .dropdown-item:hover {
                background-color: #f8f9fa;
                border-left-color: var(--sidebar-active-bg);
            }
            
            .dropdown-menu .dropdown-item .notification-content {
                white-space: normal;
                line-height: 1.4;
            }
            
            .dropdown-menu .dropdown-item .notification-time {
                font-size: 0.75rem;
                color: #888;
                display: block;
                margin-top: 3px;
            }
            
            .dropdown-menu .dropdown-divider {
                margin: 0.25rem 0;
            }
            
            .notification-item {
                position: relative;
                padding-right: 2rem;
            }
            
            .notification-item::after {
                content: '';
                position: absolute;
                right: 1rem;
                top: 50%;
                transform: translateY(-50%);
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background-color: #e74c3c;
            }
            
            .notification-empty {
                text-align: center;
                padding: 2rem 1rem;
                color: #888;
            }
            
            .notification-empty i {
                font-size: 2rem;
                margin-bottom: 0.5rem;
                opacity: 0.3;
            }
            
            /* Separator */
            .nav-separator {
                height: 30px;
                width: 1px;
                background-color: rgba(255, 255, 255, 0.1);
                margin: 0 0.5rem;
            }
            
            /* Notification and user dropdown containers */
            .top-navbar-items {
                display: flex;
                align-items: center;
                margin-left: auto;
            }
            
            /* Improved notification indicator */
            .notification-indicator {
                position: relative;
                padding: 0.5rem;
                font-size: 1.2rem;
                color: var(--navbar-text);
                margin-right: 0.5rem;
                width: 40px;
                height: 40px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 50%;
                transition: all 0.3s ease;
            }
            
            .notification-indicator:hover {
                background-color: rgba(255, 255, 255, 0.1);
            }
            
            .notification-count {
                position: absolute;
                top: 4px;
                right: 4px;
                font-size: 0.65rem;
                padding: 0.15rem 0.4rem;
                border-radius: 10px;
            }
            
            /* Sidebar Styles */
            .sb-sidenav-dark {
                background-color: var(--sidebar-bg);
                box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            }
            
            .sb-sidenav-dark .sb-sidenav-menu .nav-link {
                color: var(--sidebar-text);
                padding: 12px 15px;
                font-weight: 500;
                border-radius: 10px;
                margin: 4px 10px;
                transition: all 0.3s ease;
            }
            
            .sb-sidenav-dark .sb-sidenav-menu .nav-link:hover {
                background-color: var(--sidebar-hover-bg);
                transform: translateX(5px);
            }
            
            .sb-sidenav-dark .sb-sidenav-menu .nav-link.active {
                background-color: var(--sidebar-active-bg);
                color: white;
            }
            
            .sb-sidenav-dark .sb-sidenav-menu .nav-link.active .sb-nav-link-icon {
                color: var(--sidebar-active-icon);
            }
            
            .sb-sidenav-dark .sb-sidenav-menu .nav-link .sb-nav-link-icon {
                color: var(--sidebar-icon);
                margin-right: 15px;
                font-size: 18px;
                width: 20px;
                text-align: center;
            }
            
            .sb-sidenav-dark .sb-sidenav-footer {
                background-color: #1a252f;
                border-top: 1px solid #455a64;
                padding: 15px;
                font-size: 0.9rem;
            }
            
            .sidebar-category {
                font-size: 12px;
                text-transform: uppercase;
                color: var(--sidebar-category);
                font-weight: 600;
                letter-spacing: 1px;
                padding: 20px 15px 5px 15px;
                display: block;
                margin-top: 5px;
            }
            
            .sb-sidenav-menu-heading {
                padding: 0;
                opacity: 0.6;
            }
            
            .sidebar-divider {
                height: 1px;
                margin: 10px 15px;
                background-color: rgba(255, 255, 255, 0.1);
            }
            
            /* Logo styling */
            .sidebar-brand {
                padding: 20px 15px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                margin-bottom: 10px;
                border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .sidebar-brand img {
                width: 50px;
                height: 50px;
                margin-bottom: 10px;
            }
            
            .sidebar-brand-text {
                font-size: 16px;
                font-weight: 700;
                color: white;
                margin: 0;
            }
            
            .sidebar-brand-subtext {
                font-size: 12px;
                color: rgba(255, 255, 255, 0.6);
                margin: 0;
            }

            /* User profile in sidebar */
            .user-profile {
                display: flex;
                align-items: center;
                padding: 15px;
                margin: 15px 10px;
                background: rgba(0,0,0,0.2);
                border-radius: 10px;
            }
            
            .user-avatar {
                width: 40px;
                height: 40px;
                border-radius: 50%;
                background: var(--sidebar-active-bg);
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-weight: bold;
                margin-right: 10px;
            }
            
            .user-info {
                flex: 1;
            }
            
            .user-name {
                font-size: 14px;
                font-weight: 500;
                color: white;
                margin: 0;
            }
            
            .user-role {
                font-size: 12px;
                color: rgba(255, 255, 255, 0.7);
                margin: 0;
            }
            
            /* Current page indicator */
            .nav-indicator {
                width: 4px;
                height: 20px;
                background-color: var(--sidebar-active-bg);
                position: absolute;
                left: 0;
                border-radius: 0 4px 4px 0;
            }
            
            /* Mark active link */
            .sb-sidenav-menu .nav-link.active {
                background-color: rgba(52, 152, 219, 0.2);
                position: relative;
            }
            
            .sb-sidenav-menu .nav-link.active::before {
                content: '';
                position: absolute;
                left: 0;
                top: 50%;
                transform: translateY(-50%);
                height: 70%;
                width: 4px;
                background-color: var(--sidebar-active-bg);
                border-radius: 0 4px 4px 0;
            }
            
            /* Logout button style */
            .logout-btn {
                background-color: rgba(231, 76, 60, 0.2);
                color: #e74c3c !important;
                font-weight: 600;
            }
            
            .logout-btn .sb-nav-link-icon {
                color: #e74c3c !important;
            }
            
            .logout-btn:hover {
                background-color: rgba(231, 76, 60, 0.3);
            }
            
            /* Responsive adjustments */
            @media (max-width: 768px) {
                .sidebar-brand-text {
                    font-size: 14px;
                }
                
                .sidebar-brand img {
                    width: 40px;
                    height: 40px;
                }
                
                .sb-sidenav-dark .sb-sidenav-menu .nav-link {
                    padding: 10px;
                    margin: 2px 5px;
                }
                
                .navbar-brand span {
                    display: none;
                }
            }
        </style>    

    </head>
    <body>
        <nav class="sb-topnav navbar navbar-expand navbar-dark"  >
            <!-- Navbar Brand with icon -->
            <a class="navbar-brand ps-3" href="index.html">
                <i class="fas fa-calendar-check"></i>
                <span>ELMS Portal</span>
            </a>
            
            <!-- Sidebar Toggle-->
            <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0" id="sidebarToggle" href="#!">
                <i class="fas fa-bars"></i>
            </button>
            
            <!-- Navbar Items (right-aligned) -->
            <div class="top-navbar-items">
                <?php
                if(isset($_SESSION['user_type'])){
                    if($_SESSION['user_type'] === 'Admin'){
                        $fetch_notifications = $pdo->prepare("
                            SELECT * 
                            FROM elms_notifications 
                            WHERE recipient_id = :admin_id AND recipient_role = 'Admin' AND notification_status = 'Unread' 
                            ORDER BY created_at DESC
                        ");
                        $fetch_notifications->execute([':admin_id' => $_SESSION['admin_id']]);
                        $notifications = $fetch_notifications->fetchAll(PDO::FETCH_ASSOC);
                    }
                    if($_SESSION['user_type'] === 'Employee'){
                        $fetch_notifications = $pdo->prepare("
                            SELECT * 
                            FROM elms_notifications 
                            WHERE recipient_id = :employee_id AND recipient_role = 'Employee' AND notification_status = 'Unread' 
                            ORDER BY created_at DESC
                        ");
                        $fetch_notifications->execute([':employee_id' => $_SESSION['employee_id']]);
                        $notifications = $fetch_notifications->fetchAll(PDO::FETCH_ASSOC);
                    }
                    if(isset($notifications)){
                ?>
                <!-- Notification Dropdown -->
                <div class="dropdown">
                    <a class="notification-indicator dropdown-toggle" href="#" role="button" id="notificationDropdown1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if(count($notifications) > 0): ?>
                        <span class="badge bg-danger notification-count"><?= count($notifications) ?></span>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown1">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                            <li>
                                <a class="dropdown-item notification-item" 
                                   href="view_leave_details.php?id=<?= $notification['leave_id'] ?>&notification_id=<?= $notification['notification_id'] ?>"
                                   data-notification-id="<?= $notification['notification_id'] ?>">
                                    <div class="notification-content">
                                        <?= htmlspecialchars($notification['notification_message']) ?>
                                        <span class="notification-time"><?= date('M d, g:i a', strtotime($notification['created_at'])) ?></span>
                                    </div>
                                </a>
                            </li>
                            <?php if(!$loop->last): ?>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <li>
                            <div class="notification-empty">
                                <i class="fas fa-bell-slash d-block mb-2"></i>
                                <p class="mb-0">No new notifications</p>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="nav-separator"></div>
                <?php
                    }
                }
                ?>
                
                <!-- User Dropdown -->
                <div class="dropdown">
                    <a class="user-dropdown dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin'): ?>
                        <div class="user-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="user-info">
                            <p class="name">Administrator</p>
                            <p class="role">Admin</p>
                        </div>
                        <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee'): ?>
                        <div class="user-avatar">
                            <?php 
                                // Display employee initials
                                if(isset($_SESSION['employee_id'])) {
                                    $stmt = $pdo->prepare("SELECT employee_first_name, employee_last_name FROM elms_employee WHERE employee_id = :id");
                                    $stmt->execute([':id' => $_SESSION['employee_id']]);
                                    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if($emp) {
                                        echo substr($emp['employee_first_name'], 0, 1) . substr($emp['employee_last_name'], 0, 1);
                                    } else {
                                        echo '<i class="fas fa-user"></i>';
                                    }
                                } else {
                                    echo '<i class="fas fa-user"></i>';
                                }
                            ?>
                        </div>
                        <div class="user-info">
                            <p class="name">
                                <?php 
                                    if(isset($emp)) {
                                        echo $emp['employee_first_name'] . ' ' . $emp['employee_last_name'];
                                    } else {
                                        echo 'Employee';
                                    }
                                ?>
                            </p>
                            <p class="role">Employee</p>
                        </div>
                        <?php else: ?>
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <p class="name">Guest</p>
                            <p class="role">User</p>
                        </div>
                        <?php endif; ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee'): ?>
                        <li><a class="dropdown-item" href="employee_profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="employee_change_password.php"><i class="fas fa-key me-2"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>
        <div id="layoutSidenav" >
            <div id="layoutSidenav_nav">
                <nav class="sb-sidenav accordion sb-sidenav-dark" id="sidenavAccordion">
                    <!-- Sidebar Brand/Logo -->
                    <div class="sidebar-brand">
                        <i class="fas fa-calendar-check fa-2x text-primary mb-2"></i>
                        <div class="sidebar-brand-text">ELMS Portal</div>
                        <div class="sidebar-brand-subtext">Leave Management System</div>
                    </div>
                    
                    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin'): ?>
                    <!-- Admin User Profile -->
                    <div class="user-profile">
                        <div class="user-avatar">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name">Administrator</div>
                            <div class="user-role">Admin Panel</div>
                        </div>
                    </div>
                    <?php elseif(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee'): ?>
                    <!-- Employee User Profile -->
                    <div class="user-profile">
                        <div class="user-avatar">
                            <?php 
                                // Get employee initials
                                if(isset($_SESSION['employee_id'])) {
                                    $stmt = $pdo->prepare("SELECT employee_first_name, employee_last_name FROM elms_employee WHERE employee_id = :id");
                                    $stmt->execute([':id' => $_SESSION['employee_id']]);
                                    $emp = $stmt->fetch(PDO::FETCH_ASSOC);
                                    if($emp) {
                                        echo substr($emp['employee_first_name'], 0, 1) . substr($emp['employee_last_name'], 0, 1);
                                    } else {
                                        echo '<i class="fas fa-user"></i>';
                                    }
                                } else {
                                    echo '<i class="fas fa-user"></i>';
                                }
                            ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name">
                                <?php 
                                    if(isset($emp)) {
                                        echo $emp['employee_first_name'] . ' ' . $emp['employee_last_name'];
                                    } else {
                                        echo 'Employee';
                                    }
                                ?>
                            </div>
                            <div class="user-role">Employee Portal</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="sb-sidenav-menu">
                        <div class="nav">                            
                            
                            <?php 
                            if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin'){
                            ?>
                            <!-- Admin Menu -->
                            <div class="sidebar-category">Core Navigation</div>
                            
                            <?php 
                                // Determine current page to mark active
                                $current_page = basename($_SERVER['PHP_SELF']);
                            ?>
                            
                            <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" href="dashboard.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            
                            <div class="sidebar-category">Management</div>
                            
                            <a class="nav-link <?php echo ($current_page == 'department.php') ? 'active' : ''; ?>" href="department.php">
                                <div class="sb-nav-link-icon"><i class="far fa-building"></i></div>
                                Department
                            </a>
                            <a class="nav-link <?php echo ($current_page == 'employee.php') ? 'active' : ''; ?>" href="employee.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                                Employee
                            </a>
                            <a class="nav-link <?php echo ($current_page == 'leave_type.php') ? 'active' : ''; ?>" href="leave_type.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-clipboard-list"></i></div>
                                Leave Type
                            </a>
                            <a class="nav-link <?php echo ($current_page == 'leave_list.php') ? 'active' : ''; ?>" href="leave_list.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                                Leave Management
                            </a>
                            
                            <div class="sidebar-category">Reporting</div>
                            
                            <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" href="reports.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-chart-bar"></i></div>
                                Reports & Analytics
                            </a>
                            
                            <?php
                            }
                            if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Employee'){
                            ?>
                            <!-- Employee Menu -->
                            <div class="sidebar-category">Core Navigation</div>
                            
                            <?php 
                                // Determine current page to mark active
                                $current_page = basename($_SERVER['PHP_SELF']);
                            ?>
                            
                            <a class="nav-link <?php echo ($current_page == 'employee_dashboard.php') ? 'active' : ''; ?>" href="employee_dashboard.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                                Dashboard
                            </a>
                            
                            <div class="sidebar-category">Leave Management</div>
                            
                            <a class="nav-link <?php echo ($current_page == 'leave_list.php') ? 'active' : ''; ?>" href="leave_list.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-calendar-alt"></i></div>
                                My Leaves
                            </a>
                            
                            <a class="nav-link <?php echo ($current_page == 'apply_leave.php') ? 'active' : ''; ?>" href="apply_leave.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-plus-circle"></i></div>
                                Apply for Leave
                            </a>
                            
                            <div class="sidebar-category">Account</div>
                            
                            <a class="nav-link <?php echo ($current_page == 'employee_profile.php') ? 'active' : ''; ?>" href="employee_profile.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-user"></i></div>
                                My Profile
                            </a>
                            
                            <a class="nav-link <?php echo ($current_page == 'employee_change_password.php') ? 'active' : ''; ?>" href="employee_change_password.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-key"></i></div>
                                Change Password
                            </a>
                            <?php
                            }
                            ?>
                            
                            <div class="sidebar-divider"></div>
                            
                            <a class="nav-link logout-btn" href="logout.php">
                                <div class="sb-nav-link-icon"><i class="fas fa-sign-out-alt"></i></div>
                                Logout
                            </a>
                        </div>
                    </div>
                    <div class="sb-sidenav-footer">
                        <div class="small"> Developed by: </div>
                         Tripurari Nath
                    </div>
                </nav>
            </div>
            <div id="layoutSidenav_content">
                <main>
                    <div class="container-fluid px-4 mb-4">

<!-- Add JavaScript to handle marking notifications as read and active menu highlighting -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the notification dropdown element
    const notificationDropdown = document.getElementById('notificationDropdown1');
    
    if (notificationDropdown) {
        // Flag to track if we've already marked notifications as read
        let notificationsMarkedAsRead = false;
        
        // Add click event listener
        notificationDropdown.addEventListener('click', function(e) {
            // Only process once per dropdown open
            if (notificationsMarkedAsRead) {
                return;
            }
            
            // Get all notification items
            const notificationItems = document.querySelectorAll('.notification-item');
            
            // If there are notifications
            if (notificationItems.length > 0) {
                // Collect all notification IDs
                const notificationIds = Array.from(notificationItems).map(item => 
                    parseInt(item.getAttribute('data-notification-id'), 10)
                ).filter(id => !isNaN(id));
                
                // If we have IDs to process
                if (notificationIds.length > 0) {
                    // Create form data for the request
                    const formData = new FormData();
                    formData.append('action', 'mark_as_read');
                    formData.append('notification_ids', JSON.stringify(notificationIds));
                    
                    // Make AJAX request to mark notifications as read
                    fetch('notification_ajax.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update the badge count
                            const badge = document.querySelector('.notification-count');
                            if (badge) {
                                badge.textContent = '0';
                                badge.classList.remove('bg-danger');
                                badge.classList.add('bg-secondary');
                            }
                            
                            // Mark as processed to avoid duplicate requests
                            notificationsMarkedAsRead = true;
                        }
                    })
                    .catch(error => {
                        console.error('Error marking notifications as read:', error);
                    });
                }
            }
        });
    }
    
    // Auto-highlight current page in sidebar
    const currentPath = window.location.pathname;
    const currentPage = currentPath.substring(currentPath.lastIndexOf('/') + 1);
    
    document.querySelectorAll('.nav-link').forEach(link => {
        const linkHref = link.getAttribute('href');
        if (linkHref === currentPage) {
            link.classList.add('active');
        }
    });
});
</script>