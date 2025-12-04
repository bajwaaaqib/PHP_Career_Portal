<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireEmployee();

$error = '';
$success = '';

// Get application ID from URL or use latest application
$application_id = isset($_GET['id']) ? intval($_GET['id']) : null;

// If no ID provided, get the latest application
if (!$application_id) {
    $stmt = $db->conn->prepare("
        SELECT id FROM applications 
        WHERE employee_id = ? 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    $application_id = $app['id'] ?? null;
}

// If still no application, redirect to applications list
if (!$application_id) {
    header("Location: application.php");
    exit();
}

// Get application details with job info
$stmt = $db->conn->prepare("
    SELECT a.*, j.title as job_title, j.company as job_company, 
           j.location as job_location, j.description as job_description,
           j.salary_range as job_salary, j.application_deadline as job_deadline
    FROM applications a 
    LEFT JOIN job_posts j ON a.job_id = j.id 
    WHERE a.id = ? AND a.employee_id = ?
");
$stmt->execute([$application_id, $_SESSION['user_id']]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

// If application not found or doesn't belong to user, redirect
if (!$application) {
    header("Location: application.php");
    exit();
}

// Get status history
$status_history = [];
try {
    // Check if application_logs table exists
    $checkTable = $db->conn->query("SHOW TABLES LIKE 'application_logs'");
    if ($checkTable->rowCount() > 0) {
        $stmt = $db->conn->prepare("
            SELECT * FROM application_logs 
            WHERE application_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$application_id]);
        $status_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Create dummy history
        $status_history = [
            [
                'id' => 1,
                'application_id' => $application_id,
                'old_status' => null,
                'new_status' => $application['status'],
                'changed_by' => 'System',
                'notes' => 'Application submitted',
                'created_at' => $application['created_at']
            ]
        ];
        
        if ($application['created_at'] != $application['updated_at']) {
            $status_history[] = [
                'id' => 2,
                'application_id' => $application_id,
                'old_status' => 'Pending',
                'new_status' => $application['status'],
                'changed_by' => 'System',
                'notes' => 'Status updated',
                'created_at' => $application['updated_at']
            ];
        }
    }
} catch (PDOException $e) {
    // Create dummy history on error
    $status_history = [
        [
            'id' => 1,
            'application_id' => $application_id,
            'old_status' => null,
            'new_status' => $application['status'],
            'changed_by' => 'System',
            'notes' => 'Application submitted',
            'created_at' => $application['created_at']
        ]
    ];
}

// Get other applications for navigation
$stmt = $db->conn->prepare("
    SELECT a.id, a.status, j.title as job_title 
    FROM applications a 
    LEFT JOIN job_posts j ON a.job_id = j.id 
    WHERE a.employee_id = ? AND a.id != ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $application_id]);
$other_applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status arrays
$status_colors = [
    'Pending' => 'warning',
    'Reviewed' => 'info',
    'Interview' => 'primary',
    'Selected' => 'success',
    'Rejected' => 'danger'
];

$status_icons = [
    'Pending' => 'bi-clock',
    'Reviewed' => 'bi-eye',
    'Interview' => 'bi-calendar-check',
    'Selected' => 'bi-check-circle',
    'Rejected' => 'bi-x-circle'
];

$status_descriptions = [
    'Pending' => 'Your application has been received and is under review.',
    'Reviewed' => 'Your application has been reviewed by our HR team.',
    'Interview' => 'You have been shortlisted for an interview.',
    'Selected' => 'Congratulations! Your application has been selected.',
    'Rejected' => 'We appreciate your interest but have decided to move forward with other candidates.'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-header {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .status-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .application-nav {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .current-status-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            border-left: 6px solid;
            position: relative;
        }
        
        .current-status-card.pending { border-left-color: #ffc107; }
        .current-status-card.reviewed { border-left-color: #17a2b8; }
        .current-status-card.interview { border-left-color: #007bff; }
        .current-status-card.selected { border-left-color: #28a745; }
        .current-status-card.rejected { border-left-color: #dc3545; }
        
        .application-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.05);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .status-icon-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-right: 1.5rem;
            color: white;
        }
        
        .status-icon-large.pending { background: #ffc107; }
        .status-icon-large.reviewed { background: #17a2b8; }
        .status-icon-large.interview { background: #007bff; }
        .status-icon-large.selected { background: #28a745; }
        .status-icon-large.rejected { background: #dc3545; }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .info-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
        }
        
        .timeline-container {
            position: relative;
            padding-left: 3rem;
            margin: 2rem 0;
        }
        
        .timeline-container::before {
            content: '';
            position: absolute;
            left: 16px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), transparent);
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 2rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: -2.5rem;
            top: 0;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
        }
        
        .timeline-item.current .timeline-dot {
            background: var(--primary-color);
            color: white;
        }
        
        .timeline-item.completed .timeline-dot {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .application-selector {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 1px solid #dee2e6;
        }
        
        .empty-history {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-history i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        
        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-pending { background: #ffc107; color: #000; }
        .badge-reviewed { background: #17a2b8; color: white; }
        .badge-interview { background: #007bff; color: white; }
        .badge-selected { background: #28a745; color: white; }
        .badge-rejected { background: #dc3545; color: white; }
        
        @media (max-width: 768px) {
            .status-header {
                padding: 1.5rem;
            }
            
            .current-status-card {
                padding: 1.5rem;
            }
            
            .status-icon-large {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
                margin-right: 1rem;
                margin-bottom: 1rem;
            }
            
            .current-status-card .d-flex {
                flex-direction: column;
            }
            
            .timeline-container {
                padding-left: 2rem;
            }
            
            .timeline-dot {
                left: -2rem;
                width: 25px;
                height: 25px;
            }
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
            <!-- Application Navigation -->
            <div class="application-nav">
              
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        Application ID: #APP-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?>
                    </small>
                    <a href="application.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Applications
                    </a>
                </div>
            </div>

            <!-- Application Selector -->
            <?php if (!empty($other_applications)): ?>
            <div class="application-selector">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Track Another Application</h6>
                    <small class="text-muted"><?php echo count($other_applications); ?> other applications</small>
                </div>
                <select class="form-select" id="applicationSelector">
                    <option value="">Select an application...</option>
                    <?php foreach ($other_applications as $app): ?>
                    <option value="<?php echo $app['id']; ?>" <?php echo $app['id'] == $application_id ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($app['job_title'] ?: 'General Application'); ?>
                        - <?php echo $app['status']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- Status Header -->
            <div class="status-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">Application Status</h1>
                        <p class="mb-0">Track the progress of your job application</p>
                        <?php if ($application['job_title']): ?>
                            <div class="mt-2">
                                <span class="badge bg-light text-dark">
                                    <i class="bi bi-briefcase me-1"></i>
                                    <?php echo htmlspecialchars($application['job_title']); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="badge bg-light text-dark p-2">
                            <i class="bi bi-clock me-1"></i> 
                            Updated: <?php echo date('M d, Y', strtotime($application['updated_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Status Card -->
            <div class="current-status-card <?php echo strtolower($application['status']); ?>">
                <div class="application-badge">
                    #APP-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?>
                </div>
                
                <div class="d-flex align-items-center">
                    <div class="status-icon-large <?php echo strtolower($application['status']); ?>">
                        <i class="bi <?php echo $status_icons[$application['status']]; ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h3 class="mb-1">Current Status: <?php echo $application['status']; ?></h3>
                                <p class="mb-2"><?php echo $status_descriptions[$application['status']]; ?></p>
                                
                                <?php if ($application['status'] === 'Interview' && $application['interview_location']): ?>
                                    <div class="alert alert-info mt-2 mb-0">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Interview Scheduled:</strong> <?php echo htmlspecialchars($application['interview_location']); ?>
                                        <?php if ($application['contact_person_number']): ?>
                                            <br><strong>Contact:</strong> <?php echo htmlspecialchars($application['contact_person_number']); ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <span class="status-badge badge-<?php echo strtolower($application['status']); ?>">
                                    <?php echo $application['status']; ?>
                                </span>
                                <div class="mt-2">
                                    <small class="text-muted d-block">
                                        Applied: <?php echo date('M d, Y', strtotime($application['created_at'])); ?>
                                    </small>
                                    <?php if ($application['job_company']): ?>
                                        <small class="text-muted d-block">
                                            <i class="bi bi-building"></i> <?php echo htmlspecialchars($application['job_company']); ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Information Grid -->
            <div class="info-grid">
                <!-- Application Details -->
                <div class="info-card">
                    <h6><i class="bi bi-file-text"></i> Application Details</h6>
                    <div class="mb-2">
                        <small class="text-muted">Application Type</small>
                        <div class="fw-bold">
                            <?php echo $application['job_title'] ? 'Job-specific' : 'General Application'; ?>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Job Category</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($application['job_category']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Visa Status</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($application['visa_status']); ?></div>
                    </div>
                    <div>
                        <small class="text-muted">Applied Date</small>
                        <div class="fw-bold"><?php echo date('F d, Y', strtotime($application['created_at'])); ?></div>
                    </div>
                </div>

            
			 <!-- Contact -->
                <div class="info-card">
                    <h6><i class="bi bi-person-lines-fill"></i> Contact</h6>
                    <div class="mb-2">
                        <small class="text-muted">Email</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($application['email']); ?></div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Phone</small>
                        <div class="fw-bold"><?php echo htmlspecialchars($application['contact_number']); ?></div>
                    </div>
                   
                    
                </div>
			
<!-- CV Viewer -->
<div class="info-card cv-viewer-card">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <h6 class="mb-0">
            <i class="bi bi-file-earmark-person me-2"></i>Curriculum Vitae
        </h6>
        <span class="badge bg-light text-dark">
            <i class="bi bi-file-pdf me-1"></i>PDF
        </span>
    </div>
    
    <div class="cv-preview mb-3">
        <div class="cv-preview-placeholder">
            <i class="bi bi-file-earmark-pdf display-4 text-muted"></i>
            <p class="mt-2 mb-1 text-muted small"><?php echo htmlspecialchars($application['cv_filename']); ?></p>
            <div class="progress mt-2" style="height: 4px;">
                <div class="progress-bar" role="progressbar" style="width: 100%;"></div>
            </div>
            <p class="small text-success mt-2 mb-0">
                <i class="bi bi-check-circle me-1"></i>File Ready
            </p>
        </div>
    </div>
    
    <div class="btn-group w-100" role="group">
        <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
           class="btn btn-primary btn-sm d-flex align-items-center justify-content-center gap-2"
           target="_blank"
           title="Open in new tab">
            <i class="bi bi-eye-fill"></i>
            <span>Preview</span>
        </a>
        
        <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
           class="btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center gap-2"
           download
           title="Download CV">
            <i class="bi bi-cloud-download"></i>
            <span>Download</span>
        </a>
        
        <button type="button" 
                class="btn btn-outline-secondary btn-sm d-flex align-items-center justify-content-center gap-2"
                data-bs-toggle="modal" 
                data-bs-target="#cvModal"
                title="Quick View">
            <i class="bi bi-zoom-in"></i>
            <span>Quick View</span>
        </button>
    </div>
    
    <div class="mt-3">
        <div class="d-flex justify-content-between small text-muted">
            <span>Uploaded: 
                <?php 
                    if(isset($application['upload_date'])) {
                        echo date('M d, Y', strtotime($application['upload_date']));
                    } else {
                        echo 'N/A';
                    }
                ?>
            </span>
            <span>
                <?php 
                    $filepath = '../uploads/' . $application['cv_filename'];
                    if(file_exists($filepath)) {
                        $size = filesize($filepath);
                        echo round($size / 1024, 1) . ' KB';
                    }
                ?>
            </span>
        </div>
    </div>
</div>

<!-- Quick View Modal -->
<div class="modal fade" id="cvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
           
            <div class="modal-body p-0">
                <?php
                $file_extension = pathinfo($application['cv_filename'], PATHINFO_EXTENSION);
                $file_path = '../uploads/' . $application['cv_filename'];
                ?>
                
                <?php if (strtolower($file_extension) === 'pdf'): ?>
                    <!-- PDF Viewer -->
                    <div class="ratio ratio-16x9">
                        <iframe src="../uploads/<?php echo $application['cv_filename']; ?>#toolbar=0&navpanes=0"
                                class="border-0"
                                style="min-height: 600px;">
                            <div class="d-flex align-items-center justify-content-center h-100">
                                <div class="text-center">
                                    <i class="bi bi-exclamation-triangle display-4 text-warning"></i>
                                    <p class="mt-3">Your browser doesn't support PDF preview.</p>
                                    <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                                       class="btn btn-primary"
                                       target="_blank">
                                        <i class="bi bi-download me-1"></i>Download PDF
                                    </a>
                                </div>
                            </div>
                        </iframe>
                    </div>
                <?php elseif (in_array(strtolower($file_extension), ['doc', 'docx'])): ?>
                    <!-- DOC/DOCX Viewer -->
                    <div class="p-4">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Word documents cannot be previewed in the browser. Please download to view.
                        </div>
                        <div class="text-center py-4">
                            <i class="bi bi-file-earmark-word display-4 text-primary"></i>
                            <h5 class="mt-3"><?php echo htmlspecialchars($application['cv_filename']); ?></h5>
                            <p class="text-muted">Microsoft Word Document</p>
                            <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                               class="btn btn-primary mt-2"
                               download>
                                <i class="bi bi-download me-1"></i>Download Document
                            </a>
                        </div>
                    </div>
                <?php elseif (strtolower($file_extension) === 'txt'): ?>
                    <!-- Text File Viewer -->
                    <div class="p-3">
                        <div class="card">
                            <div class="card-header bg-light">
                                <i class="bi bi-file-text me-2"></i>Text Content Preview
                            </div>
                            <div class="card-body p-0">
                                <pre class="m-0 p-3" style="max-height: 500px; overflow: auto; background: #f8f9fa;">
<?php 
if (file_exists($file_path)) {
    $content = file_get_contents($file_path);
    echo htmlspecialchars(substr($content, 0, 5000));
    if (strlen($content) > 5000) {
        echo "\n\n... [Content truncated - download to view full file]";
    }
}
?>
                                </pre>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Unknown file type -->
                    <div class="p-4 text-center">
                        <i class="bi bi-question-circle display-4 text-secondary"></i>
                        <h5 class="mt-3">Unsupported File Type</h5>
                        <p class="text-muted">This file type cannot be previewed.</p>
                        <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                           class="btn btn-primary"
                           download>
                            <i class="bi bi-download me-1"></i>Download File
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                   class="btn btn-outline-primary"
                   download>
                    <i class="bi bi-download me-1"></i>Download
                </a>
                <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                   class="btn btn-primary"
                   target="_blank">
                    <i class="bi bi-box-arrow-up-right me-1"></i>Open in New Tab
                </a>
            </div>
        </div>
    </div>
</div>

               
            </div>

            <!-- Application Progress Timeline -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-diagram-3 me-2"></i>Application Progress</h5>
                </div>
                <div class="card-body">
                    <div class="timeline-container">
                        <?php
                        $status_steps = [
                            ['status' => 'Application Submitted', 'date' => $application['created_at'], 'icon' => 'bi-send'],
                            ['status' => 'Under Review', 'date' => null, 'icon' => 'bi-search'],
                            ['status' => 'Interview', 'date' => null, 'icon' => 'bi-calendar-event'],
                            ['status' => 'Decision', 'date' => null, 'icon' => 'bi-clipboard-check']
                        ];
                        
                        // Update dates based on current status
                        if (in_array($application['status'], ['Reviewed', 'Interview', 'Selected', 'Rejected'])) {
                            $status_steps[1]['date'] = $application['updated_at'];
                        }
                        if (in_array($application['status'], ['Interview', 'Selected', 'Rejected'])) {
                            $status_steps[2]['date'] = $application['updated_at'];
                        }
                        if (in_array($application['status'], ['Selected', 'Rejected'])) {
                            $status_steps[3]['date'] = $application['updated_at'];
                        }
                        
                        $current_step = 0;
                        if ($application['status'] === 'Reviewed') $current_step = 1;
                        if ($application['status'] === 'Interview') $current_step = 2;
                        if (in_array($application['status'], ['Selected', 'Rejected'])) $current_step = 3;
                        
                        foreach ($status_steps as $index => $step):
                            $is_current = $index === $current_step;
                            $is_completed = $index < $current_step;
                        ?>
                        <div class="timeline-item <?php echo $is_current ? 'current' : ($is_completed ? 'completed' : ''); ?>">
                            <div class="timeline-dot">
                                <?php if ($is_completed): ?>
                                    <i class="bi bi-check"></i>
                                <?php else: ?>
                                    <i class="bi <?php echo $step['icon']; ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo $step['status']; ?></h6>
                                        <?php if ($step['date']): ?>
                                            <div class="timeline-date">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('F d, Y - h:i A', strtotime($step['date'])); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="timeline-date text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                Pending
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <?php if ($is_current): ?>
                                            <span class="badge bg-primary">Current</span>
                                        <?php elseif ($is_completed): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Upcoming</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Status History 
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Status History</h5>
                    <small class="text-muted">Updates about your application</small>
                </div>
                <div class="card-body">
                    <?php if (empty($status_history)): ?>
                        <div class="empty-history">
                            <i class="bi bi-clock-history"></i>
                            <h6>No status updates yet</h6>
                            <p class="text-muted">Status updates will appear here as your application progresses.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Status</th>
                                        <th>Changed By</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($status_history as $history): ?>
                                    <tr>
                                        <td>
                                            <?php echo date('M d, Y h:i A', strtotime($history['created_at'])); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo strtolower($history['new_status']); ?>">
                                                <?php echo $history['new_status']; ?>
                                            </span>
                                            <?php if ($history['old_status']): ?>
                                                <small class="text-muted ms-2">
                                                    <i class="bi bi-arrow-right"></i>
                                                    from <?php echo $history['old_status']; ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($history['changed_by']); ?></td>
                                        <td><?php echo htmlspecialchars($history['notes']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div> -->

            <!-- Navigation & Actions -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6><i class="bi bi-gear me-2"></i>Application Actions</h6>
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                <a href="edit_application.php?id=<?php echo $application_id; ?>" class="btn btn-outline-primary">
                                    <i class="bi bi-pencil me-1"></i> Edit Application
                                </a>
                                <a href="application.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-list-ul me-1"></i> View All Applications
                                </a>
                                <?php if ($application['job_title']): ?>
                                    <a href="../index.php?job_id=<?php echo $application['job_id']; ?>" class="btn btn-outline-info">
                                        <i class="bi bi-eye me-1"></i> View Job
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6><i class="bi bi-question-circle me-2"></i>Need Help?</h6>
                            <p class="text-muted mb-2 small">If you have questions about your application status or need assistance, contact our HR department.</p>
                            <a href="mailto:ardperfumes2025@gmail.com" class="btn btn-outline-success">
                                <i class="bi bi-envelope me-1"></i> Contact HR
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="btn btn-primary position-fixed bottom-0 end-0 m-3" id="refreshBtn" title="Refresh Status">
        <i class="bi bi-arrow-clockwise"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Application selector
            const applicationSelector = document.getElementById('applicationSelector');
            if (applicationSelector) {
                applicationSelector.addEventListener('change', function() {
                    if (this.value) {
                        window.location.href = 'status.php?id=' + this.value;
                    }
                });
            }
            
            // Refresh button
            const refreshBtn = document.getElementById('refreshBtn');
            refreshBtn.addEventListener('click', function() {
                this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                this.disabled = true;
                
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
            
            // Auto-refresh every 60 seconds
            setInterval(() => {
                if (!document.hidden) {
                    window.location.reload();
                }
            }, 60000);
            
            // Add CSS animations
            const style = document.createElement('style');
            style.textContent = `
                .fade-in {
                    animation: fadeIn 0.5s ease-out;
                }
                
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .timeline-item {
                    animation: slideIn 0.5s ease-out;
                    animation-fill-mode: both;
                }
                
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
                
                #refreshBtn {
                    animation: pulse 2s infinite;
                    width: 50px;
                    height: 50px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
                
                @keyframes pulse {
                    0% {
                        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0.7);
                    }
                    70% {
                        box-shadow: 0 0 0 10px rgba(13, 110, 253, 0);
                    }
                    100% {
                        box-shadow: 0 0 0 0 rgba(13, 110, 253, 0);
                    }
                }
            `;
            document.head.appendChild(style);
            
            // Animate timeline items
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                item.style.animationDelay = `${index * 0.2}s`;
            });
            
            // Notification for status change
            let lastStatus = '<?php echo $application["status"]; ?>';
            
            function checkStatus() {
                fetch(`check_status.php?id=<?php echo $application_id; ?>`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.status && data.status !== lastStatus) {
                            lastStatus = data.status;
                            
                            // Show notification
                            if (Notification.permission === 'granted') {
                                new Notification('Application Status Updated', {
                                    body: `Status changed to: ${data.status}`,
                                    icon: '../assets/img/logo.png'
                                });
                            } else if (Notification.permission !== 'denied') {
                                Notification.requestPermission();
                            }
                        }
                    })
                    .catch(console.error);
            }
            
            // Check status every 30 seconds
            setInterval(checkStatus, 30000);
            
            // Initial status check
            checkStatus();
        });
    </script>
</body>
</html>