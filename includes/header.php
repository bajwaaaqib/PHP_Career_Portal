<?php
// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Determine if user is admin
$is_admin = false;
$current_page = basename($_SERVER['PHP_SELF']);

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    $is_admin = true;
}
?>

<!-- MOBILE + TABLET HEADER ONLY -->
<div class="mobile-bottom-nav" id="mobileBottomNav">
    <?php if ($is_admin): ?>
        <!-- Admin Navigation -->
        <a href="dashboard.php" class="mobile-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>

        <a href="applicants.php" class="mobile-nav-item <?= in_array($current_page, ['applicant.php','applicant_detail.php','applicants.php']) ? 'active' : '' ?>">
            <i class="bi bi-people"></i>
            <span>Applicants</span>
        </a>

        <a href="job_posts.php" class="mobile-nav-item <?= $current_page === 'job_posts.php' ? 'active' : '' ?>">
            <i class="bi bi-briefcase"></i>
            <span>Jobs</span>
        </a>

        <a href="edit_profile.php" class="mobile-nav-item <?= in_array($current_page, ['status.php','update_status.php','edit_profile.php']) ? 'active' : '' ?>">
            <i class="bi bi-person-circle"></i>
            <span>Profile</span>
        </a>

        <a href="../logout.php" class="mobile-nav-item logout-btn">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>

    <?php else: ?>
        <!-- Employee Navigation -->
        <a href="dashboard.php" class="mobile-nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-house-door"></i>
            <span>Home</span>
        </a>

        <a href="application.php" class="mobile-nav-item <?= $current_page === 'application.php' ? 'active' : '' ?>">
            <i class="bi bi-files"></i>
            <span>My Jobs</span>
        </a>

        <a href="apply.php" class="mobile-nav-item apply-btn <?= $current_page === 'apply.php' ? 'active' : '' ?>">
            <i class="bi bi-plus-lg"></i>
            <span>Apply</span>
        </a>

        <a href="status.php" class="mobile-nav-item <?= $current_page === 'status.php' ? 'active' : '' ?>">
            <i class="bi bi-clock-history"></i>
            <span>Status</span>
        </a>

        <a href="../logout.php" class="mobile-nav-item logout-btn">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    <?php endif; ?>
</div>

<style>
/* Hide navigation on desktops */
@media (min-width: 1025px) {
    .mobile-bottom-nav {
        display: none !important;
    }
}

/* Base Mobile Bottom Navigation */
.mobile-bottom-nav {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: rgba(255,255,255,0.95);
    border-top: 1px solid #e9ecef;
    padding: 0.5rem;
    z-index: 1000;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
    backdrop-filter: blur(10px);
}

/* Show only on Phones and Tablets */
@media (max-width: 1024px) {
    .mobile-bottom-nav {
        display: flex;
    }
    .main-content {
        padding-bottom: 70px !important;
    }
}

/* Mobile Navigation Items */
.mobile-nav-item {
    flex: 1;
    text-align: center;
    text-decoration: none;
    color: #718096;
    font-size: 0.75rem;
    padding: 0.5rem;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.mobile-nav-item i {
    font-size: 1.25rem;
}

.mobile-nav-item.active {
    color: var(--primary-color, #4a6bff);
}

.mobile-nav-item.active i {
    transform: translateY(-2px);
}

.mobile-nav-item.active::after {
    content: "";
    position: absolute;
    top: 0;
    left: 50%;
    width: 24px;
    height: 3px;
    transform: translateX(-50%);
    background: var(--primary-color, #4a6bff);
    border-radius: 0 0 3px 3px;
}

/* Apply Button */
.apply-btn {
    background: linear-gradient(135deg,#4a6bff,#667eea);
    color: #fff !important;
    border-radius: 12px;
    padding: 0.7rem 0.5rem;
    margin-top: -0.8rem;
    margin-bottom: -0.8rem;
}

.apply-btn.active {
    transform: scale(1.05);
}

/* Logout */
.logout-btn {
    color: #dc3545 !important;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const items = document.querySelectorAll(".mobile-nav-item");

    items.forEach((item) => {
        item.addEventListener("click", () => {
            items.forEach(i => i.classList.remove("active"));
            item.classList.add("active");
        });
    });
});
</script>
