<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireEmployee();

// Get all employee's applications with job details
$stmt = $db->conn->prepare("
    SELECT a.*, j.title as job_title, j.company as job_company, j.location as job_location 
    FROM applications a 
    LEFT JOIN job_posts j ON a.job_id = j.id 
    WHERE a.employee_id = ? 
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get application statistics
$total_applications = count($applications);
$pending_applications = 0;
$active_applications = 0;

foreach ($applications as $app) {
    if ($app['status'] == 'Pending') {
        $pending_applications++;
    }
    if (in_array($app['status'], ['Pending', 'Reviewed', 'Interview'])) {
        $active_applications++;
    }
}

// Get the latest application for quick status display
$latest_application = !empty($applications) ? $applications[0] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, #4a6bff 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .status-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .status-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        
        .status-card.pending { border-left-color: #ffc107; }
        .status-card.reviewed { border-left-color: #17a2b8; }
        .status-card.interview { border-left-color: #007bff; }
        .status-card.selected { border-left-color: #28a745; }
        .status-card.rejected { border-left-color: #dc3545; }
        
        .task-list .task-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-bottom: 1px solid #eee;
            transition: all 0.3s ease;
        }
        
        .task-list .task-item:hover {
            background: rgba(74, 107, 255, 0.05);
        }
        
        .task-list .task-item.completed {
            opacity: 0.7;
        }
        
        .task-list .task-item.completed .task-text {
            text-decoration: line-through;
            color: #999;
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            height: 100%;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            text-align: center;
            height: 100%;
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .stat-icon.total { background: rgba(74, 107, 255, 0.1); color: #4a6bff; }
        .stat-icon.active { background: rgba(40, 167, 69, 0.1); color: #28a745; }
        .stat-icon.pending { background: rgba(255, 193, 7, 0.1); color: #ffc107; }
        
        .application-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #4a6bff;
            transition: all 0.3s ease;
        }
        
        .application-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }
        
        .application-card.pending { border-left-color: #ffc107; }
        .application-card.reviewed { border-left-color: #17a2b8; }
        .application-card.interview { border-left-color: #007bff; }
        .application-card.selected { border-left-color: #28a745; }
        .application-card.rejected { border-left-color: #dc3545; }
        
       
        
       
        
        .document-card {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .document-card:hover {
            border-color: #4a6bff;
            background: rgba(74, 107, 255, 0.05);
        }
        
        .timeline-step {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            position: relative;
        }
        
        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 15px;
            top: 30px;
            bottom: -25px;
            width: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .step-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            position: relative;
            z-index: 2;
        }
        
        .timeline-step.active .step-dot {
            background: #4a6bff;
            color: white;
        }
        
        .step-content {
            flex: 1;
        }
    </style>
</head>
<body>
<?php include '../includes/header.php'; ?>
    <div class="dashboard-container">
        <!-- Desktop Sidebar (hidden on mobile) -->
        <?php if (file_exists('sidebar.php')): ?>
        <div class="sidebar d-none d-md-block">
            <?php include 'sidebar.php'; ?>
        </div>
        <?php endif; ?>

        <!-- Main Content -->
        <div class="main-content fade-in">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1>Welcome, <?php echo $_SESSION['first_name']; ?></h1>
                        <p class="mb-0">Track your job applications and stay updated with the recruitment process.</p>
                        <?php if ($total_applications > 0): ?>
                            <div class="mt-3">
                                <span class="badge bg-light text-dark me-2"><?php echo $total_applications; ?> Application(s)</span>
                                <span class="badge bg-light text-dark"><?php echo $active_applications; ?> Active</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon total">
                            <i class="bi bi-files"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $total_applications; ?></h3>
                        <p class="text-muted mb-0">Total Applications</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon active">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $active_applications; ?></h3>
                        <p class="text-muted mb-0">Active Applications</p>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon pending">
                            <i class="bi bi-hourglass-split"></i>
                        </div>
                        <h3 class="mb-2"><?php echo $pending_applications; ?></h3>
                        <p class="text-muted mb-0">Pending Review</p>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="page-header">
                <h2 class="page-title">Recent Applications</h2>
                <div>
                    <?php if ($total_applications > 0): ?>
                        <a href="application.php" class="btn btn-outline-primary">View All</a>
                    <?php endif; ?>
                    <a href="../index.php" class="btn btn-primary ms-2">Browse Jobs</a>
                </div>
            </div>

            <?php if ($total_applications > 0): ?>
                <!-- Latest Application Status -->
                <?php if ($latest_application): ?>
                <?php
                $status_colors = [
                    'Pending' => 'warning',
                    'Reviewed' => 'info',
                    'Interview' => 'primary',
                    'Selected' => 'success',
                    'Rejected' => 'danger'
                ];
                ?>
                <div class="status-card <?php echo strtolower($latest_application['status']); ?> mb-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-2">
                                Latest Application: 
                                <?php if ($latest_application['job_title']): ?>
                                    <span class="text-dark"><?php echo htmlspecialchars($latest_application['job_title']); ?></span>
                                <?php else: ?>
                                    <span class="text-dark">General Application</span>
                                <?php endif; ?>
                            </h4>
                            <p class="mb-1">
                                Status: <span class="text-<?php echo $status_colors[$latest_application['status']]; ?> fw-bold"><?php echo $latest_application['status']; ?></span>
                            </p>
                            <p class="mb-0 text-muted">
                                <?php 
                                $messages = [
                                    'Pending' => 'Your application has been received and is under review.',
                                    'Reviewed' => 'Your application has been reviewed by our team.',
                                    'Interview' => 'You have been shortlisted for an interview.',
                                    'Selected' => 'Congratulations! Your application has been selected.',
                                    'Rejected' => 'We appreciate your interest but have decided to move forward with other candidates.'
                                ];
                                echo $messages[$latest_application['status']];
                                ?>
                            </p>
                            <?php if ($latest_application['status'] === 'Interview' && $latest_application['interview_location']): ?>
                                <div class="mt-3 alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Interview Scheduled:</strong> <?php echo $latest_application['interview_location']; ?>
                                    <?php if ($latest_application['contact_person_number']): ?>
                                        <br><strong>Contact:</strong> <?php echo $latest_application['contact_person_number']; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="status.php?id=<?php echo $latest_application['id']; ?>" class="btn btn-primary btn-lg">
                                <i class="bi bi-clock-history me-2"></i>
                                Track Progress
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Application List -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Your Applications</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        $display_applications = array_slice($applications, 0, 3); // Show only 3 latest
                        foreach ($display_applications as $app): 
                        ?>
                        <div class="application-card <?php echo strtolower($app['status']); ?>">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6 class="mb-1">
                                        <?php if ($app['job_title']): ?>
                                            <?php echo htmlspecialchars($app['job_title']); ?>
                                        <?php else: ?>
                                            General Application
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1 text-muted">
                                        <?php if ($app['job_company']): ?>
                                            <i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($app['job_company']); ?>
                                            <?php if ($app['job_location']): ?>
                                                <i class="bi bi-geo-alt ms-2 me-1"></i> <?php echo htmlspecialchars($app['job_location']); ?>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </p>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i> Applied: <?php echo date('M d, Y', strtotime($app['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="d-flex align-items-center justify-content-end">
                                        <span class="badge bg-<?php echo $status_colors[$app['status']]; ?> me-3">
                                            <?php echo $app['status']; ?>
                                        </span>
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a class="dropdown-item" href="edit_application.php?id=<?php echo $app['id']; ?>"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                                <li><a class="dropdown-item" href="status.php?id=<?php echo $app['id']; ?>"><i class="bi bi-clock-history me-2"></i>Track</a></li>
                                                <li><a class="dropdown-item" href="../uploads/<?php echo $app['cv_filename']; ?>" download><i class="bi bi-download me-2"></i>Download CV</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php if ($total_applications > 3): ?>
                            <div class="text-center mt-3">
                                <a href="application.php" class="btn btn-outline-primary">
                                    View All <?php echo $total_applications; ?> Applications
                                    <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Empty State -->
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #dee2e6;"></i>
                        <h4 class="mt-3">No Applications Yet</h4>
                        <p class="text-muted mb-4">You haven't submitted any job applications yet. Browse jobs and start applying!</p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="../index.php" class="btn btn-primary btn-lg">
                                <i class="bi bi-search me-2"></i>
                                Browse Jobs
                            </a>
                            <a href="application.php" class="btn btn-outline-primary btn-lg">
                                <i class="bi bi-plus-circle me-2"></i>
                                Quick Apply
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="info-card text-center">
                        <div class="mb-3">
                            <i class="bi bi-file-earmark-plus" style="font-size: 2.5rem; color: #4a6bff;"></i>
                        </div>
                        <h5>New Application</h5>
                        <p class="text-muted">Apply for a new job opportunity</p>
                        <a href="../index.php" class="btn btn-primary w-100">Browse & Apply</a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="info-card text-center">
                        <div class="mb-3">
                            <i class="bi bi-files" style="font-size: 2.5rem; color: #17a2b8;"></i>
                        </div>
                        <h5>All Applications</h5>
                        <p class="text-muted">View and manage all your applications</p>
                        <a href="application.php" class="btn btn-outline-info w-100 <?php echo $total_applications == 0 ? 'disabled' : ''; ?>">
                            <?php echo $total_applications > 0 ? 'View Applications' : 'No Applications'; ?>
                        </a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3">
                    <div class="info-card text-center">
                        <div class="mb-3">
                            <i class="bi bi-person-circle" style="font-size: 2.5rem; color: #28a745;"></i>
                        </div>
                        <h5>Profile</h5>
                        <p class="text-muted">Update your personal information</p>
                        <a href="edit_profile.php" class="btn btn-outline-success w-100">Edit Profile</a>
                    </div>
                </div>
            </div>

            <!-- Next Steps -->
            <?php if ($total_applications > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Next Steps & Tips</h5>
                </div>
                <div class="card-body">
                    <div class="task-list">
                        <?php
                        $tasks = [
                            ['icon' => 'bi-check-circle', 'text' => 'Applications submitted successfully', 'completed' => $total_applications > 0],
                            ['icon' => 'bi-bell', 'text' => 'Enable notifications for updates', 'completed' => false],
                            ['icon' => 'bi-search', 'text' => 'Browse more job opportunities', 'completed' => false],
                            ['icon' => 'bi-pencil', 'text' => 'Keep your CV updated', 'completed' => false],
                            ['icon' => 'bi-calendar-check', 'text' => 'Prepare for potential interviews', 'completed' => $active_applications > 0]
                        ];
                        
                        foreach ($tasks as $task):
                        ?>
                        <div class="task-item <?php echo $task['completed'] ? 'completed' : ''; ?>">
                            <i class="bi <?php echo $task['icon']; ?> me-3" style="color: <?php echo $task['completed'] ? '#28a745' : '#6c757d'; ?>"></i>
                            <span class="task-text"><?php echo $task['text']; ?></span>
                            <?php if ($task['completed']): ?>
                                <i class="bi bi-check-circle-fill ms-auto" style="color: #28a745;"></i>
                            <?php else: ?>
                                <i class="bi bi-clock ms-auto" style="color: #ffc107;"></i>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Career Application & Tracking System</h5>
                    <p class="mb-0">Track your job application progress in real-time</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> Career System. All rights reserved.</p>
                    <small>Need help? <a href="mailto:support@careersystem.com" class="text-light">Contact Support</a></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // Animation for progress ring
        document.addEventListener('DOMContentLoaded', function() {
           
                
                // Animate application cards
            const appCards = document.querySelectorAll('.application-card');
            appCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'fadeInUp 0.5s ease-out forwards';
                card.style.opacity = '0';
            });
            }
            
            // Task list hover effects
            const taskItems = document.querySelectorAll('.task-item');
            taskItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('completed')) {
                        this.style.transform = 'translateX(10px)';
                    }
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                });
            });
        });
        
        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes progressAnimation {
                from { transform: scale(0.8); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            
            @keyframes fadeInUp {
                from { transform: translateY(20px); opacity: 0; }
                to { transform: translateY(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>