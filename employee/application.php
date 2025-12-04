<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireEmployee();

// Get all applications for this user with job details
$stmt = $db->conn->prepare("
    SELECT a.*, j.title as job_title, j.company as job_company, j.location as job_location 
    FROM applications a 
    LEFT JOIN job_posts j ON a.job_id = j.id 
    WHERE a.employee_id = ? 
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_applications = count($applications);
$pending_count = 0;
$reviewed_count = 0;
$interview_count = 0;
$selected_count = 0;
$rejected_count = 0;

foreach ($applications as $app) {
    switch ($app['status']) {
        case 'Pending': $pending_count++; break;
        case 'Reviewed': $reviewed_count++; break;
        case 'Interview': $interview_count++; break;
        case 'Selected': $selected_count++; break;
        case 'Rejected': $rejected_count++; break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        /* Mobile-Responsive Styles for Applications Page */
        
        /* Applications Header */
        .applications-header {
            background: linear-gradient(135deg, #4a6bff, #667eea);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        
        
        
        /* Statistics Cards */
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            text-align: center;
            height: 100%;
            transition: all 0.3s ease;
            margin-bottom: 0.75rem;
            cursor: pointer;
            border: 1px solid #e9ecef;
        }
        
        .stat-card:hover, .stat-card:active {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            font-size: 1.25rem;
        }
        
        /* Status Colors */
        .stat-icon.total { background: rgba(74, 107, 255, 0.1); color: #4a6bff; }
        .stat-icon.pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        .stat-icon.reviewed { background: rgba(23, 162, 184, 0.1); color: #17a2b8; }
        .stat-icon.interview { background: rgba(0, 123, 255, 0.1); color: #007bff; }
        .stat-icon.selected { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.rejected { background: rgba(220, 53, 69, 0.1); color: #dc3545; }
        
        /* Application Cards */
        .application-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        
        .application-card:active {
            transform: scale(0.99);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }
        
        .application-card.pending { border-left-color: #ffc107; }
        .application-card.reviewed { border-left-color: #17a2b8; }
        .application-card.interview { border-left-color: #007bff; }
        .application-card.selected { border-left-color: #28a745; }
        .application-card.rejected { border-left-color: #dc3545; }
        
        /* Status Badge */
        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }
        
        .badge-pending { background: #ffc107; color: #000; }
        .badge-reviewed { background: #17a2b8; color: white; }
        .badge-interview { background: #007bff; color: white; }
        .badge-selected { background: #28a745; color: white; }
        .badge-rejected { background: #dc3545; color: white; }
        
        /* Application Actions */
        .application-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            min-height: 38px;
            min-width: 38px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.25rem;
        }
        
        /* Empty State */
        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 3rem 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }
        
        .empty-icon {
            font-size: 3rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        /* Mobile Optimizations */
        @media (max-width: 768px) {
            /* Header adjustments */
            .applications-header {
                padding: 1.25rem;
                margin: 0 -1rem 1.5rem -1rem;
                width: calc(100% + 2rem);
                border-radius: 0;
            }
            
            /* Statistics grid */
            .row.g-2 {
                margin-left: -0.25rem;
                margin-right: -0.25rem;
            }
            
            .row.g-2 > div {
                padding-left: 0.25rem;
                padding-right: 0.25rem;
            }
            
            .stat-card {
                padding: 0.75rem;
                margin-bottom: 0.5rem;
            }
            
            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }
            
            .stat-card h3 {
                font-size: 1.125rem;
                margin-bottom: 0.25rem;
            }
            
            .stat-card p {
                font-size: 0.75rem;
                margin-bottom: 0;
            }
            
            /* Application cards */
            .application-card {
                padding: 1rem;
                margin-bottom: 0.75rem;
            }
            
            /* Action buttons */
            .application-actions {
                gap: 0.375rem;
                justify-content: center;
            }
            
            .action-btn {
                flex: 1;
                min-width: 0;
                font-size: 0.875rem;
                padding: 0.375rem 0.5rem;
            }
            
            /* Empty state */
            .empty-state {
                padding: 2rem 1rem;
                margin: 0 -0.5rem;
                width: calc(100% + 1rem);
            }
            
            .empty-state .btn-lg {
                padding: 0.75rem 1rem;
                font-size: 1rem;
            }
            
            /* Typography adjustments */
            h1 { font-size: 1.5rem; }
            h2 { font-size: 1.25rem; }
            h3 { font-size: 1.125rem; }
            
            /* Touch-friendly improvements */
            .btn, .btn-sm, .stat-card {
                touch-action: manipulation;
            }
            
            /* Prevent zoom on input focus */
            input, select, textarea, button, .btn {
                font-size: 16px;
            }
        }
        
        @media (max-width: 576px) {
            /* Extra small devices */
            .stat-card h3 {
                font-size: 1rem;
            }
            
            .stat-icon {
                width: 35px;
                height: 35px;
                font-size: 0.875rem;
            }
            
            .application-card {
                padding: 0.875rem;
            }
            
            .application-actions {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .action-btn {
                width: 100%;
                justify-content: center;
            }
            
            .status-badge {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            .stat-card,
            .application-card,
            .empty-state {
                background: #ffffff;
                border-color: #4a5568;
                color: #000000;
            }
            
            .stat-card .text-muted,
            .application-card .text-muted {
                color: #a0aec0 !important;
            }
            
            .badge-light {
                background-color: #4a5568;
                color: #e2e8f0;
            }
        }
        
        /* Loading animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .application-card {
            animation: fadeIn 0.3s ease-out;
        }
        
        /* Improve accessibility */
        .stat-card:focus,
        .application-card:focus {
            outline: 2px solid #4a6bff;
            outline-offset: 2px;
        }
        
        /* Loading state */
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar d-none d-md-block">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Header -->
            <div class="applications-header">
                <div class="row align-items-center">
                    <div class="col-md-8 col-12">
                        <h1 class="mb-1">My Applications</h1>
                        <p class="mb-0 opacity-75">Track and manage all your job applications</p>
                        <?php if ($total_applications > 0): ?>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark me-1 mb-1"><?php echo $total_applications; ?> Total</span>
                                <span class="badge bg-light text-dark mb-1"><?php echo date('M d, Y'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 col-12 text-md-end text-center mt-2 mt-md-0">
                        <a href="apply.php" class="btn btn-light btn-lg">
                            <i class="bi bi-plus-circle me-1"></i>
                            <span class="d-none d-md-inline">New Application</span>
                            <span class="d-md-none">New</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <?php if ($total_applications > 0): ?>
            <div class="row g-2 mb-3">
                <div class="col-4 col-md-2">
                    <div class="stat-card" role="button" tabindex="0" data-filter="all">
                        <div class="stat-icon total">
                            <i class="bi bi-files"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $total_applications; ?></h3>
                        <p class="text-muted mb-0 small">Total</p>
                    </div>
                </div>
                
                <div class="col-4 col-md-2">
                    <div class="stat-card" role="button" tabindex="0" data-filter="pending">
                        <div class="stat-icon pending">
                            <i class="bi bi-clock"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $pending_count; ?></h3>
                        <p class="text-muted mb-0 small">Pending</p>
                    </div>
                </div>
                
                <div class="col-4 col-md-2">
                    <div class="stat-card" role="button" tabindex="0" data-filter="reviewed">
                        <div class="stat-icon reviewed">
                            <i class="bi bi-eye"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $reviewed_count; ?></h3>
                        <p class="text-muted mb-0 small">Reviewed</p>
                    </div>
                </div>
                
                <div class="col-4 col-md-2">
                    <div class="stat-card" role="button" tabindex="0" data-filter="interview">
                        <div class="stat-icon interview">
                            <i class="bi bi-calendar-check"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $interview_count; ?></h3>
                        <p class="text-muted mb-0 small">Interview</p>
                    </div>
                </div>
                
                <div class="col-4 col-md-2">
                    <div class="stat-card" role="button" tabindex="0" data-filter="selected">
                        <div class="stat-icon selected">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $selected_count; ?></h3>
                        <p class="text-muted mb-0 small">Selected</p>
                    </div>
                </div>
                
                <div class="col-4 col-md-2">
                    <div class="stat-card" role="button" tabindex="0" data-filter="rejected">
                        <div class="stat-icon rejected">
                            <i class="bi bi-x-circle"></i>
                        </div>
                        <h3 class="mb-0"><?php echo $rejected_count; ?></h3>
                        <p class="text-muted mb-0 small">Rejected</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Applications List Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="mb-0">All Applications</h2>
                <?php if ($total_applications > 0): ?>
                    <div class="d-none d-md-block">
                        <a href="../index.php" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-search me-1"></i> Browse Jobs
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($total_applications === 0): ?>
                <!-- Empty State -->
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-file-earmark-text"></i>
                    </div>
                    <h3 class="mb-2">No Applications Yet</h3>
                    <p class="text-muted mb-3">Start your job search journey today</p>
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                        <a href="../index.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-search me-2"></i>
                            Browse Jobs
                        </a>
                        <a href="apply.php" class="btn btn-outline-primary">
                            <i class="bi bi-plus-circle me-2"></i>
                            Quick Apply
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Browse Jobs Button (Mobile Only) -->
                <div class="d-grid d-md-none mb-3">
                    <a href="../index.php" class="btn btn-outline-primary">
                        <i class="bi bi-search me-2"></i> Browse More Jobs
                    </a>
                </div>

                <!-- Applications List -->
                <div id="applicationsList">
                    <?php foreach ($applications as $app): 
                        $status_class = strtolower($app['status']);
                        $status_badge_class = "badge-" . $status_class;
                        $status_display = $app['status'];
                        
                        // Format dates
                        $created_date = date('M d, Y', strtotime($app['created_at']));
                        $updated_date = date('M d, Y', strtotime($app['updated_at']));
                        
                        // Truncate long text for mobile
                        $job_title = htmlspecialchars($app['job_title'] ?: 'General Application');
                        $display_title = strlen($job_title) > 40 ? substr($job_title, 0, 40) . '...' : $job_title;
                    ?>
                    <div class="application-card <?php echo $status_class; ?>" data-status="<?php echo $status_class; ?>" data-id="<?php echo $app['id']; ?>">
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div class="flex-grow-1 me-2">
                                        <h5 class="mb-1" title="<?php echo htmlspecialchars($app['job_title'] ?: 'General Application'); ?>">
                                            <?php echo $display_title; ?>
                                        </h5>
                                        
                                        <?php if ($app['job_company']): ?>
                                            <p class="mb-1 text-muted small">
                                                <i class="bi bi-building me-1"></i> 
                                                <?php 
                                                $company = htmlspecialchars($app['job_company']);
                                                echo strlen($company) > 30 ? substr($company, 0, 30) . '...' : $company;
                                                ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="status-badge <?php echo $status_badge_class; ?>">
                                        <?php echo $status_display; ?>
                                    </span>
                                </div>
                                
                                <div class="d-flex flex-wrap gap-1 mb-2">
                                    <?php if (!empty($app['job_category'])): ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-briefcase me-1"></i> 
                                        <?php 
                                        $category = htmlspecialchars($app['job_category']);
                                        echo strlen($category) > 15 ? substr($category, 0, 15) . '...' : $category;
                                        ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-calendar me-1"></i> <?php echo $created_date; ?>
                                    </span>
                                    <?php if ($app['updated_at'] != $app['created_at']): ?>
                                        <span class="badge bg-light text-dark">
                                            <i class="bi bi-arrow-clockwise me-1"></i> Updated
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <?php if (!empty($app['cv_filename'])): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>
                                        <a href="../uploads/<?php echo $app['cv_filename']; ?>" class="text-decoration-none" target="_blank" title="View CV">
                                            View CV
                                        </a>
                                    </small>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <i class="bi bi-envelope me-1"></i>
                                        <?php 
                                        $email = htmlspecialchars($app['email']);
                                        echo strlen($email) > 25 ? substr($email, 0, 25) . '...' : $email;
                                        ?>
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <div class="application-actions">
                                    <a href="edit_application.php?id=<?php echo $app['id']; ?>" class="btn btn-outline-primary action-btn">
                                        <i class="bi bi-pencil d-md-none"></i>
                                        <span class="d-none d-md-inline">Edit</span>
                                    </a>
                                    <a href="status.php?id=<?php echo $app['id']; ?>" class="btn btn-outline-info action-btn">
                                        <i class="bi bi-clock-history d-md-none"></i>
                                        <span class="d-none d-md-inline">Track</span>
                                    </a>
                                    <?php if (!empty($app['cv_filename'])): ?>
                                    <a href="../uploads/<?php echo $app['cv_filename']; ?>" class="btn btn-outline-success action-btn" download>
                                        <i class="bi bi-download d-md-none"></i>
                                        <span class="d-none d-md-inline">Download CV</span>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Load More Button (if needed) -->
                <?php if ($total_applications > 10): ?>
                <div class="text-center mt-4">
                    <button class="btn btn-outline-secondary" id="loadMoreBtn">
                        <i class="bi bi-chevron-down me-2"></i> Load More Applications
                    </button>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const statCards = document.querySelectorAll('.stat-card[data-filter]');
            const applicationCards = document.querySelectorAll('.application-card');
            
            statCards.forEach(card => {
                card.addEventListener('click', function() {
                    const filter = this.getAttribute('data-filter');
                    
                    // Remove active class from all cards
                    statCards.forEach(c => c.classList.remove('active'));
                    // Add active class to clicked card
                    this.classList.add('active');
                    
                    // Show/hide applications based on filter
                    applicationCards.forEach(appCard => {
                        if (filter === 'all' || appCard.getAttribute('data-status') === filter) {
                            appCard.style.display = 'block';
                        } else {
                            appCard.style.display = 'none';
                        }
                    });
                });
                
                // Add keyboard support
                card.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        this.click();
                    }
                });
            });
            
            // Load more functionality
            const loadMoreBtn = document.getElementById('loadMoreBtn');
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...';
                    this.disabled = true;
                    
                    // In a real app, you would fetch more data from the server
                    setTimeout(() => {
                        this.style.display = 'none';
                        // Show success message
                        showToast('All applications loaded', 'success');
                    }, 1500);
                });
            }
            
            // Mobile touch improvements
            if (window.innerWidth <= 768) {
                // Add touch feedback to cards
                applicationCards.forEach(card => {
                    card.addEventListener('touchstart', function() {
                        this.style.opacity = '0.8';
                    });
                    
                    card.addEventListener('touchend', function() {
                        this.style.opacity = '1';
                    });
                });
                
                // Add swipe actions (optional)
                let touchStartX = 0;
                let touchEndX = 0;
                
                document.addEventListener('touchstart', e => {
                    touchStartX = e.changedTouches[0].screenX;
                });
                
                document.addEventListener('touchend', e => {
                    touchEndX = e.changedTouches[0].screenX;
                    handleSwipe();
                });
                
                function handleSwipe() {
                    const swipeThreshold = 50;
                    const diff = touchStartX - touchEndX;
                    
                    if (Math.abs(diff) > swipeThreshold) {
                        // Swipe detected - could be used for navigation
                    }
                }
            }
            
            // Toast notification function
            function showToast(message, type = 'info') {
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-bg-${type} border-0`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');
                toast.style.position = 'fixed';
                toast.style.bottom = '20px';
                toast.style.right = '20px';
                toast.style.zIndex = '1055';
                
                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                `;
                
                document.body.appendChild(toast);
                const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
                bsToast.show();
                
                toast.addEventListener('hidden.bs.toast', function () {
                    document.body.removeChild(toast);
                });
            }
            
            // Auto-hide scrollbars on mobile for better UX
            if (window.innerWidth <= 768) {
                setTimeout(() => {
                    document.documentElement.style.scrollBehavior = 'smooth';
                }, 100);
            }
            
            // Initialize first stat card as active
            if (statCards.length > 0) {
                statCards[0].classList.add('active');
            }
        });
    </script>
</body>
</html>