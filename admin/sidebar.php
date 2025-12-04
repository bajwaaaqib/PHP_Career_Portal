<?php
// Make sure database connection is available
require_once '../config/database.php';

// Get stats for badges - ADMIN VERSION
$stats = [];
$stmt = $db->conn->query("SELECT COUNT(*) as total FROM applications");
$stats['total'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->conn->query("SELECT COUNT(*) as pending FROM applications WHERE status = 'Pending'");
$stats['pending'] = $stmt->fetch(PDO::FETCH_ASSOC)['pending'];

$stmt = $db->conn->query("SELECT COUNT(*) as interview FROM applications WHERE status = 'Interview'");
$stats['interview'] = $stmt->fetch(PDO::FETCH_ASSOC)['interview'];

$stmt = $db->conn->query("SELECT COUNT(*) as selected FROM applications WHERE status = 'Selected'");
$stats['selected'] = $stmt->fetch(PDO::FETCH_ASSOC)['selected'];

$stmt = $db->conn->query("SELECT COUNT(*) as jobs FROM job_posts WHERE is_active = TRUE");
$stats['jobs'] = $stmt->fetch(PDO::FETCH_ASSOC)['jobs'];
?>

<!-- Desktop Sidebar - Hidden on ALL mobile and tablet views -->
<div class="sidebar d-none d-xl-block">
    <div class="sidebar-header">
        <div class="user-info">
            <div class="user-avatar-lg">
                <?php 
                $initials = strtoupper(substr($_SESSION['first_name'], 0, 1));
                if (isset($_SESSION['last_name']) && !empty($_SESSION['last_name'])) {
                    $initials .= strtoupper(substr($_SESSION['last_name'], 0, 1));
                }
                echo $initials;
                ?>
            </div>
            <div class="user-name">
                <?php echo htmlspecialchars($_SESSION['first_name']); ?> 
                <?php if (isset($_SESSION['last_name']) && !empty($_SESSION['last_name'])): ?>
                    <?php echo htmlspecialchars($_SESSION['last_name']); ?>
                <?php endif; ?>
            </div>
            <div class="user-role">Administrator</div>
        </div>
    </div>
    
    <div class="sidebar-menu">
        <a href="dashboard.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="job_posts.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'job_posts.php' ? 'active' : ''; ?>">
            <i class="bi bi-file-earmark-text"></i>
            <span>Job Posts</span>
            <?php if ($stats['jobs'] > 0): ?>
                <span class="badge bg-primary"><?php echo $stats['jobs']; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="applicants.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) === 'applicants.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            <span>Applicants</span>
            <?php if ($stats['total'] > 0): ?>
                <span class="badge bg-primary"><?php echo $stats['total']; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="applicants.php?status=Pending" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'applicants.php' && ($_GET['status'] ?? '') === 'Pending') ? 'active' : ''; ?>">
            <i class="bi bi-clock"></i>
            <span>Pending</span>
            <?php if ($stats['pending'] > 0): ?>
                <span class="badge bg-warning"><?php echo $stats['pending']; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="applicants.php?status=Interview" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'applicants.php' && ($_GET['status'] ?? '') === 'Interview') ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i>
            <span>Interview</span>
            <?php if ($stats['interview'] > 0): ?>
                <span class="badge bg-info"><?php echo $stats['interview']; ?></span>
            <?php endif; ?>
        </a>
        
        <a href="applicants.php?status=Selected" class="menu-item <?php echo (basename($_SERVER['PHP_SELF']) === 'applicants.php' && ($_GET['status'] ?? '') === 'Selected') ? 'active' : ''; ?>">
            <i class="bi bi-check-circle"></i>
            <span>Selected</span>
            <?php if ($stats['selected'] > 0): ?>
                <span class="badge bg-success"><?php echo $stats['selected']; ?></span>
            <?php endif; ?>
        </a>
        <a href="edit_profile.php" class="menu-item <?php echo basename($_SERVER['PHP_SELF']) == 'edit_profile.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profile</span>
        </a>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</div>

<!-- Add SCOPED CSS for the sidebar only -->
<style>
    /* SIDEBAR-SPECIFIC STYLES ONLY */
    /* These styles only affect elements inside .sidebar */
    
    /* Desktop Sidebar Container */
    .sidebar.d-none.d-xl-block {
        width: 280px;
        height: 100vh;
        background: linear-gradient(180deg, #1a237e 0%, #283593 100%);
        color: white;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        display: none;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }
    
    @media (min-width: 1200px) {
        .sidebar.d-none.d-xl-block {
            display: flex;
        }
    }
    
    /* Sidebar Header - only affects .sidebar .sidebar-header */
    .sidebar .sidebar-header {
        padding: 2rem 1.5rem;
        background: rgba(255, 255, 255, 0.05);
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar .user-info {
        text-align: center;
    }
    
    .sidebar .user-avatar-lg {
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
    
    .sidebar .user-name {
        font-size: 1.125rem;
        font-weight: 600;
        margin-bottom: 0.25rem;
        color: white;
    }
    
    .sidebar .user-role {
        font-size: 0.875rem;
        color: rgba(255, 255, 255, 0.7);
        background: rgba(255, 255, 255, 0.1);
        padding: 0.25rem 0.75rem;
        border-radius: 20px;
        display: inline-block;
    }
    
    /* Sidebar Menu - only affects .sidebar .sidebar-menu */
    .sidebar .sidebar-menu {
        flex: 1;
        padding: 1.5rem 0;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
    }
    
    /* Custom scrollbar only for sidebar menu */
    .sidebar .sidebar-menu::-webkit-scrollbar {
        width: 4px;
    }
    
    .sidebar .sidebar-menu::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .sidebar .sidebar-menu::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.2);
        border-radius: 4px;
    }
    
    .sidebar .sidebar-menu::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.3);
    }
    
    /* Menu Items - only affects .sidebar .menu-item */
    .sidebar .menu-item {
        display: flex;
        align-items: center;
        padding: 0.875rem 1.5rem;
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: all 0.3s ease;
        position: relative;
        margin: 0.125rem 0.5rem;
        border-radius: 8px;
        outline: none;
    }
    
    .sidebar .menu-item:hover {
        background: rgba(255, 255, 255, 0.1);
        color: white;
        transform: translateX(5px);
    }
    
    .sidebar .menu-item:focus-visible {
        outline: 2px solid #4a6bff;
        outline-offset: -2px;
    }
    
    .sidebar .menu-item.active {
        background: rgba(255, 255, 255, 0.15);
        color: white;
        font-weight: 500;
    }
    
    .sidebar .menu-item.active::before {
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
    
    .sidebar .menu-item i {
        font-size: 1.25rem;
        margin-right: 0.75rem;
        width: 24px;
        text-align: center;
    }
    
    .sidebar .menu-item span {
        flex: 1;
        font-size: 0.9375rem;
    }
    
    .sidebar .menu-item .badge {
        font-size: 0.625rem;
        padding: 0.25rem 0.5rem;
        margin-left: auto;
        min-width: 20px;
        text-align: center;
    }
    
    /* Sidebar Footer - only affects .sidebar .sidebar-footer */
    .sidebar .sidebar-footer {
        margin-top: auto;
        padding: 1rem 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .sidebar .sidebar-footer .menu-item {
        color: rgba(255, 255, 255, 0.6);
    }
    
    .sidebar .sidebar-footer .menu-item:hover {
        color: #ff6b6b;
        background: rgba(255, 107, 107, 0.1);
    }
    
    /* Remove conflicting styles that affect other pages */
    /* REMOVED: .mobile-bottom-nav, .mobile-menu-toggle, .sidebar-backdrop styles */
    /* REMOVED: Global media queries affecting .main-content */
    /* REMOVED: Global animation styles */
    
    /* Only animation for sidebar menu items */
    @keyframes sidebarSlideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }
    
    .sidebar .menu-item {
        animation: sidebarSlideIn 0.3s ease forwards;
        opacity: 0;
    }
    
    .sidebar .menu-item:nth-child(1) { animation-delay: 0.1s; }
    .sidebar .menu-item:nth-child(2) { animation-delay: 0.2s; }
    .sidebar .menu-item:nth-child(3) { animation-delay: 0.3s; }
    .sidebar .menu-item:nth-child(4) { animation-delay: 0.4s; }
    .sidebar .menu-item:nth-child(5) { animation-delay: 0.5s; }
    .sidebar .menu-item:nth-child(6) { animation-delay: 0.6s; }
    .sidebar .menu-item:nth-child(7) { animation-delay: 0.7s; }
    
    /* Dark mode support only for sidebar */
    @media (prefers-color-scheme: dark) {
        .sidebar.d-none.d-xl-block {
            background: linear-gradient(180deg, #0d1117 0%, #161b22 100%);
        }
    }
</style>