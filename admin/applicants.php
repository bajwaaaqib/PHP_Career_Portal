<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$job_category = $_GET['job_category'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$sql = "SELECT a.*, e.first_name as emp_first, e.last_name as emp_last FROM applications a LEFT JOIN employees e ON a.employee_id = e.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (a.first_name LIKE ? OR a.last_name LIKE ? OR a.email LIKE ? OR e.email LIKE ? OR a.contact_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param, $search_param]);
}

if ($status) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}

if ($job_category) {
    $sql .= " AND a.job_category = ?";
    $params[] = $job_category;
}

if ($date_from) {
    $sql .= " AND DATE(a.created_at) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $sql .= " AND DATE(a.created_at) <= ?";
    $params[] = $date_to;
}

$sql .= " ORDER BY a.created_at DESC";

$stmt = $db->conn->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for status tabs
$status_counts = [];
$statuses = ['Pending', 'Reviewed', 'Interview', 'Selected', 'Rejected'];
foreach ($statuses as $stat) {
    $stmt = $db->conn->prepare("SELECT COUNT(*) as count FROM applications WHERE status = ?");
    $stmt->execute([$stat]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $status_counts[$stat] = $result['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Applicants - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .status-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .status-tab {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            background: #f8f9fa;
            border: 2px solid transparent;
            text-decoration: none;
            color: #666;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-tab:hover {
            background: #e9ecef;
            transform: translateY(-2px);
            color: #333;
        }
        
        .status-tab.active {
            background: white;
            border-color: var(--primary-color);
            color: var(--primary-color);
            box-shadow: 0 4px 15px rgba(74, 107, 255, 0.1);
        }
        
        .status-tab .count {
            background: rgba(0, 0, 0, 0.1);
            padding: 0.125rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-tab.active .count {
            background: var(--primary-color);
            color: white;
        }
        
        .applicant-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            border: 1px solid #eee;
        }
        
        .applicant-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
            border-color: var(--primary-color);
        }
        
        .applicant-avatar {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.25rem;
            margin-right: 1rem;
        }
        
        .applicant-info h6 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }
        
        .applicant-info .email {
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }
        
        .applicant-meta {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: #666;
        }
        
        .applicant-meta span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .applicant-card {
                flex-direction: column;
                text-align: center;
            }
            
            .applicant-avatar {
                margin-right: 0;
                margin-bottom: 1rem;
            }
            
            .applicant-meta {
                justify-content: center;
                flex-wrap: wrap;
            }
            
            .action-buttons {
                justify-content: center;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content fade-in">
            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2 class="page-title">Applicant Management</h2>
                    <p class="text-muted mb-0">View and manage all job applications</p>
                </div>
                
            </div>

            <!-- Status Tabs -->
            <div class="status-tabs">
                <a href="applicants.php" class="status-tab <?php echo !$status ? 'active' : ''; ?>">
                    <i class="bi bi-grid"></i>
                    <span>All Applications</span>
                    <span class="count"><?php echo array_sum($status_counts); ?></span>
                </a>
                <?php foreach ($statuses as $stat): 
                    $status_colors = [
                        'Pending' => 'warning',
                        'Reviewed' => 'info',
                        'Interview' => 'primary',
                        'Selected' => 'success',
                        'Rejected' => 'danger'
                    ];
                ?>
                <a href="applicants.php?status=<?php echo $stat; ?>" 
                   class="status-tab <?php echo $status === $stat ? 'active' : ''; ?>">
                    <i class="bi bi-<?php echo $stat === 'Pending' ? 'clock' : ($stat === 'Interview' ? 'calendar-check' : ($stat === 'Selected' ? 'check-circle' : 'x-circle')); ?>"></i>
                    <span><?php echo $stat; ?></span>
                    <span class="count"><?php echo $status_counts[$stat]; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Filter Card -->
            <div class="filter-card">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="search" 
                               placeholder="Search name, email, phone..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="status">
                            <option value="">All Status</option>
                            <?php foreach ($statuses as $stat): ?>
                            <option value="<?php echo $stat; ?>" <?php echo $status === $stat ? 'selected' : ''; ?>>
                                <?php echo $stat; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" name="job_category">
                            <option value="">All Categories</option>
                            <option value="IT & Software" <?php echo $job_category === 'IT & Software' ? 'selected' : ''; ?>>IT & Software</option>
                            <option value="Marketing" <?php echo $job_category === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                            <option value="Sales" <?php echo $job_category === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                            <option value="Finance" <?php echo $job_category === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                            <option value="HR" <?php echo $job_category === 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                            <option value="Operations" <?php echo $job_category === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                            <option value="Customer Service" <?php echo $job_category === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_from" 
                               value="<?php echo htmlspecialchars($date_from); ?>"
                               placeholder="From Date">
                    </div>
                    <div class="col-md-2">
                        <input type="date" class="form-control" name="date_to" 
                               value="<?php echo htmlspecialchars($date_to); ?>"
                               placeholder="To Date">
                    </div>
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-filter"></i>
                        </button>
                    </div>
                </form>
                
                <?php if ($search || $status || $job_category || $date_from || $date_to): ?>
                <div class="mt-3">
                    <small class="text-muted">
                        Filtered results: <?php echo count($applications); ?> applications found
                        <?php if ($search): ?> • Search: "<?php echo htmlspecialchars($search); ?>"<?php endif; ?>
                        <?php if ($status): ?> • Status: <?php echo $status; ?><?php endif; ?>
                        <?php if ($job_category): ?> • Category: <?php echo $job_category; ?><?php endif; ?>
                        <a href="applicants.php" class="text-danger ms-2">
                            <i class="bi bi-x-circle"></i> Clear filters
                        </a>
                    </small>
                </div>
                <?php endif; ?>
            </div>

            <!-- Applications List -->
            <div class="row">
                <div class="col-12">
                    <?php if (empty($applications)): ?>
                        <div class="empty-state">
                            <i class="bi bi-people"></i>
                            <h4>No applications found</h4>
                            <p class="text-muted mb-4"><?php echo $search || $status || $job_category ? 'Try adjusting your filters' : 'No applications have been submitted yet.'; ?></p>
                            <?php if ($search || $status || $job_category): ?>
                                <a href="applicants.php" class="btn btn-outline-primary">Clear Filters</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($applications as $app): 
                                $status_class = strtolower($app['status']);
                                $status_colors = [
                                    'pending' => 'warning',
                                    'reviewed' => 'info',
                                    'interview' => 'primary',
                                    'selected' => 'success',
                                    'rejected' => 'danger'
                                ];
                            ?>
                            <div class="col-lg-6 col-xl-4 mb-3">
                                <div class="applicant-card">
                                    <div class="d-flex align-items-start">
                                        <div class="applicant-avatar">
                                            <?php echo strtoupper(substr($app['first_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="applicant-info">
                                                    <h6><?php echo htmlspecialchars($app['first_name'] . ' ' . $app['last_name']); ?></h6>
                                                    <div class="email"><?php echo htmlspecialchars($app['email']); ?></div>
                                                    <div class="applicant-meta">
                                                        <span><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($app['job_category']); ?></span>
                                                        <span><i class="bi bi-clock"></i> <?php echo date('M d, Y', strtotime($app['created_at'])); ?></span>
                                                    </div>
                                                </div>
                                                <div>
                                                    <span class="badge badge-<?php echo $status_class; ?>">
                                                        <?php echo $app['status']; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex justify-content-between align-items-center mt-3">
                                                
                                                <div class="action-buttons">
                                                    <a href="applicant_detail.php?id=<?php echo $app['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" 
                                                       data-bs-toggle="tooltip" title="View Details">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="update_status.php?id=<?php echo $app['id']; ?>" 
                                                       class="btn btn-sm btn-outline-warning"
                                                       data-bs-toggle="tooltip" title="Update Status">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="../uploads/<?php echo $app['cv_filename']; ?>" 
                                                       class="btn btn-sm btn-outline-success"
                                                       data-bs-toggle="tooltip" title="Download CV" download>
                                                        <i class="bi bi-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination (Optional) -->
            <?php if (count($applications) > 12): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item disabled">
                        <a class="page-link" href="#" tabindex="-1">Previous</a>
                    </li>
                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item">
                        <a class="page-link" href="#">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Date range validation
            const dateFrom = document.querySelector('input[name="date_from"]');
            const dateTo = document.querySelector('input[name="date_to"]');
            
            if (dateFrom && dateTo) {
                dateFrom.addEventListener('change', function() {
                    if (dateTo.value && this.value > dateTo.value) {
                        dateTo.value = this.value;
                    }
                });
                
                dateTo.addEventListener('change', function() {
                    if (dateFrom.value && this.value < dateFrom.value) {
                        dateFrom.value = this.value;
                    }
                });
            }

            // Filter form submit with loading state
            const filterForm = document.querySelector('form');
            if (filterForm) {
                filterForm.addEventListener('submit', function() {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Filtering...';
                    submitBtn.disabled = true;
                });
            }

            // Status tab click animation
            const statusTabs = document.querySelectorAll('.status-tab');
            statusTabs.forEach(tab => {
                tab.addEventListener('click', function(e) {
                    statusTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            // Applicant card hover effect
            const applicantCards = document.querySelectorAll('.applicant-card');
            applicantCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>