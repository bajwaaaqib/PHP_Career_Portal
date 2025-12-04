<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

// Get statistics
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

// Recent applications
$stmt = $db->conn->query("
    SELECT a.*, e.first_name as emp_first, e.last_name as emp_last 
    FROM applications a 
    LEFT JOIN employees e ON a.employee_id = e.id 
    ORDER BY a.created_at DESC LIMIT 3
");
$recent_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent job posts
$stmt = $db->conn->query("SELECT * FROM job_posts ORDER BY created_at DESC LIMIT 3");
$recent_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .quick-action-btn {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }
        
        .quick-action-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            color: white;
            text-decoration: none;
        }
        
        .job-card-mini {
            border-left: 4px solid var(--primary-color);
            padding: 1rem;
            background: white;
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>
<body>

<?php include '../includes/header.php'; ?>
    <!-- Dashboard Layout -->
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>
        

        <!-- Main Content -->
        <div class="main-content fade-in">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <h1>Welcome back, <?php echo $_SESSION['first_name']; ?></h1>
                <p class="mb-0">Here's what's happening with your applications today.</p>
                
                <div class="quick-actions">
                    <a href="job_posts.php" class="quick-action-btn">
                        <i class="bi bi-plus-circle-fill"></i>
                        <span>Create Job Post</span>
                    </a>
                    <a href="applicants.php" class="quick-action-btn">
                        <i class="bi bi-people-fill"></i>
                        <span>View Applicants</span>
                    </a>
                    <a href="applicants.php?status=Pending" class="quick-action-btn">
                        <i class="bi bi-clock-fill"></i>
                        <span>Review Pending</span>
                    </a>
                    <a href="../index.php" class="quick-action-btn" target="_blank">
                        <i class="bi bi-eye"></i>
                        <span>Job Posts</span>
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="page-header">
                <h2 class="page-title">Overview</h2>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-briefcase"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['jobs']; ?></div>
                    <div class="stat-label">Active Job Posts</div>
                    <div class="stat-change up">
                        <i class="bi bi-arrow-up"></i>
                        <span>Manage job listings</span>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Applications</div>
                    <div class="stat-change up">
                        <i class="bi bi-arrow-up"></i>
                        <span>12% from last week</span>
                    </div>
                </div>

                <div class="stat-card pending">
                    <div class="stat-icon">
                        <i class="bi bi-clock"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['pending']; ?></div>
                    <div class="stat-label">Pending Review</div>
                    <div class="stat-change down">
                        <i class="bi bi-arrow-down"></i>
                        <span>3% from last week</span>
                    </div>
                </div>

                <div class="stat-card selected">
                    <div class="stat-icon">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-value"><?php echo $stats['selected']; ?></div>
                    <div class="stat-label">Selected</div>
                    <div class="stat-change up">
                        <i class="bi bi-arrow-up"></i>
                        <span>5% from last week</span>
                    </div>
                </div>
            </div>

            <!-- Recent Jobs & Applications -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Job Posts</h5>
                            <a href="job_posts.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_jobs)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-file-earmark-text" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p class="text-muted mt-2">No job posts yet</p>
                                    <a href="job_posts.php?action=create" class="btn btn-sm btn-primary">Create First Job Post</a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_jobs as $job): ?>
                                <div class="job-card-mini">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h6>
                                            <p class="text-muted small mb-1">
                                                <i class="bi bi-building"></i> <?php echo htmlspecialchars($job['company']); ?> • 
                                                <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                            </p>
                                            <span class="badge bg-light text-dark"><?php echo $job['job_type']; ?></span>
                                        </div>
                                        <div class="text-end">
                                            <small class="text-muted d-block">
                                                <?php echo date('M d', strtotime($job['created_at'])); ?>
                                            </small>
                                            <a href="job_posts.php?action=edit&id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary mt-2">Edit</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Applications</h5>
                            <a href="applicants.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_applications)): ?>
                                <div class="text-center py-4">
                                    <i class="bi bi-people" style="font-size: 3rem; color: #dee2e6;"></i>
                                    <p class="text-muted mt-2">No applications yet</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Applicant</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_applications as $app): 
                                                $status_class = strtolower($app['status']);
                                            ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-avatar me-2">
                                                            <?php echo strtoupper(substr($app['first_name'], 0, 1)); ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></div>
                                                            <small class="text-muted"><?php echo $app['job_category']; ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                        <?php echo $app['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d', strtotime($app['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Stats</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 text-center">
                            <div class="display-6 fw-bold text-primary"><?php echo $stats['jobs']; ?></div>
                            <div class="text-muted">Active Jobs</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="display-6 fw-bold text-success"><?php echo $stats['selected']; ?></div>
                            <div class="text-muted">Selected Candidates</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="display-6 fw-bold text-warning"><?php echo $stats['pending']; ?></div>
                            <div class="text-muted">Pending Review</div>
                        </div>
                        <div class="col-md-3 text-center">
                            <div class="display-6 fw-bold text-info"><?php echo $stats['interview']; ?></div>
                            <div class="text-muted">Interviews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Simple animation for stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
    </script>
</body>
</html>