<div class="sidebar">
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar-lg">
                <?php 
                $initials = '';
                if (isset($_SESSION['first_name']) && !empty($_SESSION['first_name'])) {
                    $initials = strtoupper(substr($_SESSION['first_name'], 0, 1));
                    if (isset($_SESSION['last_name']) && !empty($_SESSION['last_name'])) {
                        $initials .= strtoupper(substr($_SESSION['last_name'], 0, 1));
                    }
                } else {
                    $initials = 'U';
                }
                echo $initials;
                ?>
            </div>
            <div class="user-name">
                <?php 
                echo isset($_SESSION['first_name']) ? htmlspecialchars($_SESSION['first_name']) : 'User';
                echo ' ';
                echo isset($_SESSION['last_name']) ? htmlspecialchars($_SESSION['last_name']) : '';
                ?>
            </div>
            <div class="user-role">Job Applicant</div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="application.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'application.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            <span>My Applications</span>
            <?php 
            // Add badge if there are new applications (you can customize this logic)
            // For example, if you want to show badge when there are pending applications
            // You'll need to query the database here
            ?>
        </a>
        <a href="status.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'status.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            <span>Track Status</span>
        </a>
        <a href="apply.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'apply.php' ? 'active' : ''; ?>">
            <i class="bi bi-pencil-square"></i>
            <span>Apply for Job</span>
        </a>
        <a href="edit_profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profile</span>
        </a>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>
<head>
<style>
    /* Full-height sidebar styles */
    
    /* Sidebar Container */
    .sidebar {
        width: 280px;
        height: 100vh;
        background: rgb(88 116 244) !important;
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    /* Sidebar Header */
    .sidebar-header {
        padding: 2rem 1.5rem;
        background: rgba(255, 255, 255, 0.05) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .user-info {
        text-align: center;
    }
    
    .user-avatar-lg {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #4a6bff, #667eea);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        font-weight: 600;
        margin: 0 auto 1rem;
        border: 3px solid rgba(255, 255, 255, 0.2);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    
    .user-name {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: white;
    }
    
    .user-role {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.1);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        display: inline-block;
    }
    
    /* Sidebar Menu */
    .sidebar-menu {
        flex: 1;
        padding: 1.5rem 0;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    
    /* Custom scrollbar for sidebar */
    .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }
    
    .sidebar-menu::-webkit-scrollbar-track {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }
    
    .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    /* Menu Items */
    .menu-item {
        display: flex;
        align-items: center;
        padding: 0.875rem 1.5rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        margin: 0.125rem 0.5rem;
        border-radius: 8px;
    }
    
    .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }
    
    .menu-item.active {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        font-weight: 500;
    }
    
    .menu-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 4px;
        height: 60%;
        background: #4a6bff;
        border-radius: 0 4px 4px 0;
    }
    
    .menu-item i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        width: 24px;
        text-align: center;
    }
    
    .menu-item span {
        flex: 1;
        font-size: 0.9375rem;
    }
    
    .menu-item .badge {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
        margin-left: auto;
    }
    
    /* Sidebar Footer */
    .sidebar-footer {
        margin-top: auto;
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar-footer .menu-item {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .sidebar-footer .menu-item:hover {
        color: #ff6b6b;
        background: rgba(255, 107, 107, 0.1);
    }
    
    /* Mobile responsiveness */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: 100vh;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 1100;
        }
        
        .sidebar.show {
            transform: translateX(0);
        }
        
        /* Mobile backdrop */
        .sidebar-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
        }
        
        .sidebar-backdrop.show {
            display: block;
        }
        
        /* Mobile menu toggle button */
        .mobile-menu-toggle {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1001;
            background: #4a6bff;
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            box-shadow: 0 4px 12px rgba(74, 107, 255, 0.3);
        }
        
        .sidebar-header {
            padding: 1.5rem;
        }
        
        .user-avatar-lg {
            width: 70px;
            height: 70px;
            font-size: 1.75rem;
        }
    }
    
    /* Desktop adjustments */
    @media (min-width: 769px) {
        /* Adjust main content to account for sidebar */
        .main-content {
            margin-left: 280px;
            width: calc(100% - 280px);
        }
        
        .mobile-menu-toggle {
            display: none;
        }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .sidebar {
            background: linear-gradient(180deg, #0d1117 0%, #161b22 100%);
        }
        
        .menu-item {
            color: rgba(255, 255, 255, 0.7);
        }
        
        .menu-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: white;
        }
        
        .menu-item.active {
            background: rgba(255, 255, 255, 0.08);
        }
    }
    
    /* Animation for menu items */
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .menu-item {
        animation: slideIn 0.3s ease forwards;
        opacity: 0;
    }
    
    .menu-item:nth-child(1) { animation-delay: 0.1s; }
    .menu-item:nth-child(2) { animation-delay: 0.2s; }
    .menu-item:nth-child(3) { animation-delay: 0.3s; }
    .menu-item:nth-child(4) { animation-delay: 0.4s; }
    .menu-item:nth-child(5) { animation-delay: 0.5s; }
    .menu-item:nth-child(6) { animation-delay: 0.6s; }
    
    /* Focus states for accessibility */
    .menu-item:focus {
        outline: 2px solid #4a6bff;
        outline-offset: -2px;
    }
</style>
</head>