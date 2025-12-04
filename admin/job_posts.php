<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

// Check if function exists before declaring
if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }
}

// Handle actions
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create' || $action === 'edit') {
        $title = sanitize($_POST['title']);
        $company = sanitize($_POST['company']);
        $location = sanitize($_POST['location']);
        $job_type = sanitize($_POST['job_type']);
        $salary_range = sanitize($_POST['salary_range']);
        $description = sanitize($_POST['description']);
        $requirements = sanitize($_POST['requirements']);
        $benefits = sanitize($_POST['benefits']);
        $application_deadline = sanitize($_POST['application_deadline']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if ($action === 'create') {
            $stmt = $db->conn->prepare("
                INSERT INTO job_posts (title, company, location, job_type, salary_range, 
                                      description, requirements, benefits, application_deadline, 
                                      is_active, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $title, $company, $location, $job_type, $salary_range,
                $description, $requirements, $benefits, $application_deadline,
                $is_active, $_SESSION['user_id']
            ]);
            // Redirect to avoid form resubmission
            header("Location: job_posts.php?success=created");
            exit();
        } else {
            $stmt = $db->conn->prepare("
                UPDATE job_posts SET 
                    title = ?, company = ?, location = ?, job_type = ?, salary_range = ?,
                    description = ?, requirements = ?, benefits = ?, application_deadline = ?,
                    is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $title, $company, $location, $job_type, $salary_range,
                $description, $requirements, $benefits, $application_deadline,
                $is_active, $id
            ]);
            // Redirect to avoid form resubmission
            header("Location: job_posts.php?success=updated&id=" . $id);
            exit();
        }
    } elseif (isset($_POST['delete_action']) && $_POST['delete_action'] === 'delete') {
        $delete_id = $_POST['delete_id'] ?? 0;
        $stmt = $db->conn->prepare("DELETE FROM job_posts WHERE id = ?");
        $stmt->execute([$delete_id]);
        // Redirect to avoid resubmission on refresh
        header("Location: job_posts.php?success=deleted");
        exit();
    }
}

// Get job posts
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$sql = "SELECT * FROM job_posts WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (title LIKE ? OR company LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

if ($status === 'active') {
    $sql .= " AND is_active = TRUE";
} elseif ($status === 'inactive') {
    $sql .= " AND is_active = FALSE";
}

$sql .= " ORDER BY created_at DESC";

$stmt = $db->conn->prepare($sql);
$stmt->execute($params);
$job_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts
$stmt = $db->conn->query("SELECT COUNT(*) as total FROM job_posts");
$total_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$stmt = $db->conn->query("SELECT COUNT(*) as active FROM job_posts WHERE is_active = TRUE");
$active_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['active'];

// Check for success messages from redirect
$success_message = '';
if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'created':
            $success_message = "Job post created successfully!";
            break;
        case 'updated':
            $success_message = "Job post updated successfully!";
            break;
        case 'deleted':
            $success_message = "Job post deleted successfully!";
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Posts - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .job-posts-header {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .job-post-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .job-post-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        }
        
        .job-post-card.inactive {
            opacity: 0.7;
            border-left-color: #6c757d;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active { background: #28a745; color: white; }
        .status-inactive { background: #6c757d; color: white; }
        
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
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        
        .delete-form {
            display: inline;
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
            <!-- Success Messages -->
            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'create' || $action === 'edit'): ?>
                <!-- Create/Edit Form -->
                <?php
                $job = [];
                if ($action === 'edit' && $id) {
                    $stmt = $db->conn->prepare("SELECT * FROM job_posts WHERE id = ?");
                    $stmt->execute([$id]);
                    $job = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                ?>
                
                <div class="page-header mb-4">
                    <div>
                        <h2 class="page-title"><?php echo $action === 'create' ? 'Create New Job Post' : 'Edit Job Post'; ?></h2>
                        <p class="text-muted mb-0"><?php echo $action === 'create' ? 'Post a new job opening' : 'Update job post details'; ?></p>
                    </div>
                    <div>
                        <a href="job_posts.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
                
                <form method="POST" action="job_posts.php?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $id : ''; ?>" class="form-section">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="title" class="form-label">Job Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" 
                                   value="<?php echo htmlspecialchars($job['title'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="company" class="form-label">Company <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company" name="company" 
                                   value="<?php echo htmlspecialchars($job['company'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($job['location'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="job_type" class="form-label">Job Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="job_type" name="job_type" required>
                                <option value="Full-time" <?php echo ($job['job_type'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo ($job['job_type'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Contract" <?php echo ($job['job_type'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="Internship" <?php echo ($job['job_type'] ?? '') === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="salary_range" class="form-label">Salary Range</label>
                            <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                   value="<?php echo htmlspecialchars($job['salary_range'] ?? ''); ?>" 
                                   placeholder="e.g., د.إ 3,000 - د.إ 5,000">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="application_deadline" class="form-label">Application Deadline</label>
                            <input type="date" class="form-control" id="application_deadline" name="application_deadline" 
                                   value="<?php echo htmlspecialchars($job['application_deadline'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($job['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requirements" class="form-label">Requirements <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="requirements" name="requirements" rows="5" required><?php echo htmlspecialchars($job['requirements'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="benefits" class="form-label">Benefits</label>
                        <textarea class="form-control" id="benefits" name="benefits" rows="3"><?php echo htmlspecialchars($job['benefits'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo ($job['is_active'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active (Visible on website)
                            </label>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-between">
                        <a href="job_posts.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <?php echo $action === 'create' ? 'Create Job Post' : 'Update Job Post'; ?>
                        </button>
                    </div>
                </form>
                
            <?php else: ?>
                <!-- Job Posts List -->
                <div class="job-posts-header">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-2">Job Posts Management</h1>
                            <p class="mb-0">Create and manage job openings on the website</p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <a href="job_posts.php?action=create" class="btn btn-light">
                                <i class="bi bi-plus-circle me-2"></i> Create New Job
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Total Jobs</h5>
                                <h2 class="card-text"><?php echo $total_jobs; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Active</h5>
                                <h2 class="card-text"><?php echo $active_jobs; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-secondary text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">Inactive</h5>
                                <h2 class="card-text"><?php echo $total_jobs - $active_jobs; ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5 class="card-title">This Month</h5>
                                <h2 class="card-text"><?php 
                                    $stmt = $db->conn->query("SELECT COUNT(*) as count FROM job_posts WHERE MONTH(created_at) = MONTH(CURRENT_DATE())");
                                    echo $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                                ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Filter & Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Search jobs, company, location..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" name="status">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Job Posts List -->
                <div class="row">
                    <?php if (empty($job_posts)): ?>
                        <div class="col-12">
                            <div class="empty-state">
                                <i class="bi bi-file-earmark-text" style="font-size: 4rem; color: #dee2e6;"></i>
                                <h4 class="mt-3">No job posts found</h4>
                                <p class="text-muted mb-4"><?php echo $search || $status ? 'Try adjusting your filters' : 'Create your first job post to get started.'; ?></p>
                                <?php if ($search || $status): ?>
                                    <a href="job_posts.php" class="btn btn-outline-primary">Clear Filters</a>
                                <?php else: ?>
                                    <a href="job_posts.php?action=create" class="btn btn-primary">Create First Job Post</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($job_posts as $job): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="job-post-card <?php echo !$job['is_active'] ? 'inactive' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <p class="text-muted mb-1">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($job['company']); ?> • 
                                            <i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($job['location']); ?>
                                        </p>
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                    </div>
                                    <div class="text-end">
                                        <span class="status-badge status-<?php echo $job['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $job['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <?php if ($job['salary_range']): ?>
                                    <p class="mb-2"><strong>Salary:</strong> <?php echo htmlspecialchars($job['salary_range']); ?></p>
                                <?php endif; ?>
                                
                                <p class="text-muted small mb-3">
                                    <?php echo substr(strip_tags($job['description']), 0, 150); ?>...
                                </p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">
                                        Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                        <?php if ($job['application_deadline']): ?>
                                            • Deadline: <?php echo date('M d, Y', strtotime($job['application_deadline'])); ?>
                                        <?php endif; ?>
                                    </small>
                                    <div class="action-buttons">
                                        <a href="job_posts.php?action=edit&id=<?php echo $job['id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="job_posts.php" class="delete-form" 
                                              onsubmit="return confirm('Are you sure you want to delete this job post? This action cannot be undone.')">
                                            <input type="hidden" name="delete_action" value="delete">
                                            <input type="hidden" name="delete_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-expand textareas
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
            // Trigger once on load
            textarea.dispatchEvent(new Event('input'));
        });
        
        // Auto-dismiss alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>