<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: applicants.php");
    exit();
}

$id = intval($_GET['id']);

// Get application details
$stmt = $db->conn->prepare("
    SELECT a.*, e.email as emp_email, e.created_at as emp_created 
    FROM applications a 
    LEFT JOIN employees e ON a.employee_id = e.id 
    WHERE a.id = ?
");
$stmt->execute([$id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header("Location: applicants.php");
    exit();
}

$status_colors = [
    'Pending' => 'warning',
    'Reviewed' => 'info',
    'Interview' => 'primary',
    'Selected' => 'success',
    'Rejected' => 'danger'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Details - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .detail-header {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .detail-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .applicant-avatar-lg {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 2rem;
            margin-bottom: 1rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .info-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 1.5rem;
            height: 100%;
        }
        
        .info-card h6 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(74, 107, 255, 0.1);
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #666;
            font-weight: 500;
        }
        
        .info-value {
            color: #333;
            font-weight: 500;
            text-align: right;
        }
        
        .cover-letter-box {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            max-height: 300px;
            overflow-y: auto;
            border-left: 4px solid var(--primary-color);
        }
        
        .cv-preview {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .cv-preview:hover {
            border-color: var(--primary-color);
            background: rgba(74, 107, 255, 0.05);
        }
        
        .timeline-status {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            position: relative;
        }
        
        .timeline-status::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background: #e9ecef;
            z-index: 1;
        }
        
        .status-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
        }
        
        .status-dot {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: white;
            border: 2px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .status-step.active .status-dot {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .status-step.completed .status-dot {
            background: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .status-label {
            font-size: 0.75rem;
            color: #666;
            text-align: center;
            max-width: 80px;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .detail-header {
                text-align: center;
                padding: 1.5rem;
            }
            
            .action-buttons {
                justify-content: center;
            }
            
            .timeline-status {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .status-step {
                flex: 1;
                min-width: 100px;
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
            <!-- Detail Header -->
            <div class="detail-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="applicant-avatar-lg">
                                <?php echo strtoupper(substr($application['first_name'], 0, 1)); ?>
                            </div>
                            <div class="ms-3">
                                <h2 class="mb-1"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h2>
                                <p class="mb-2"><?php echo htmlspecialchars($application['job_category']); ?> • Applied on <?php echo date('F d, Y', strtotime($application['created_at'])); ?></p>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" style="background: rgba(255, 255, 255, 0.2); font-size: 0.875rem;">
                                        #APP-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?>
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($application['email']); ?>
                                    </span>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi bi-telephone"></i> <?php echo htmlspecialchars($application['contact_number']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Status Timeline -->
                <div class="timeline-status">
                    <?php
                    $statuses = ['Pending', 'Reviewed', 'Interview', 'Selected'];
                    $current_status = $application['status'];
                    $current_index = array_search($current_status, $statuses);
                    
                    foreach ($statuses as $index => $status):
                        $is_active = $status === $current_status;
                        $is_completed = $index < $current_index;
                    ?>
                    <div class="status-step <?php echo $is_active ? 'active' : ($is_completed ? 'completed' : ''); ?>">
                        <div class="status-dot">
                            <?php if ($is_completed): ?>
                                <i class="bi bi-check"></i>
                            <?php else: ?>
                                <span><?php echo $index + 1; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="status-label"><?php echo $status; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Current Status Alert -->
            <div class="alert alert-<?php echo $status_colors[$application['status']]; ?> d-flex align-items-center justify-content-between">
                <div>
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Current Status:</strong> <?php echo $application['status']; ?>
                    <?php if ($application['status'] === 'Interview' && $application['interview_location']): ?>
                        • <strong>Interview:</strong> <?php echo htmlspecialchars($application['interview_location']); ?>
                        <?php if ($application['contact_person_number']): ?>
                            • <strong>Contact:</strong> <?php echo htmlspecialchars($application['contact_person_number']); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div>
                    <small class="text-muted">Last updated: <?php echo date('M d, Y H:i', strtotime($application['updated_at'])); ?></small>
                </div>
            </div>

            <!-- Information Cards -->
            <div class="row">
                <div class="col-lg-4">
                    <div class="info-card">
                        <h6><i class="bi bi-person me-2"></i>Personal Information</h6>
                        <div class="info-item">
                            <span class="info-label">Full Name</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Gender</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['gender']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Date of Birth</span>
                            <span class="info-value"><?php echo date('F d, Y', strtotime($application['date_of_birth'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Nationality</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['nationality']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Visa Status</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['visa_status']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="info-card">
                        <h6><i class="bi bi-briefcase me-2"></i>Job Information</h6>
                        <div class="info-item">
                            <span class="info-label">Job Category</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['job_category']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Application Date</span>
                            <span class="info-value"><?php echo date('F d, Y H:i', strtotime($application['created_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Last Updated</span>
                            <span class="info-value"><?php echo date('F d, Y H:i', strtotime($application['updated_at'])); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Application ID</span>
                            <span class="info-value">#APP-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Account Email</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['emp_email']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="info-card">
                        <h6><i class="bi bi-telephone me-2"></i>Contact Information</h6>
                        <div class="info-item">
                            <span class="info-label">Email Address</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['email']); ?></span>
                        </div>
                        <div class="info-item">
                            <span class="info-label">Phone Number</span>
                            <span class="info-value"><?php echo htmlspecialchars($application['contact_number']); ?></span>
                        </div>
                        <?php if ($application['status'] === 'Interview'): ?>
                            <div class="info-item">
                                <span class="info-label">Interview Location</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['interview_location']); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Contact Person</span>
                                <span class="info-value"><?php echo htmlspecialchars($application['contact_person_number']); ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="info-item">
                            <span class="info-label">Application Status</span>
                            <span class="info-value">
                                <span class="badge badge-<?php echo strtolower($application['status']); ?>">
                                    <?php echo $application['status']; ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cover Letter & CV -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="info-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6><i class="bi bi-chat-text me-2"></i>Cover Letter</h6>
                            <small class="text-muted"><?php echo str_word_count($application['cover_letter'] ?? ''); ?> words</small>
                        </div>
                        <?php if ($application['cover_letter']): ?>
                            <div class="cover-letter-box">
                                <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> No cover letter provided by the applicant.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="info-card">
                        <h6><i class="bi bi-file-earmark-pdf me-2"></i>CV / Resume</h6>
                        <div class="cv-preview">
                            <i class="bi bi-file-earmark-pdf" style="font-size: 4rem; color: var(--primary-color);"></i>
                            <h5 class="mt-3"><?php echo htmlspecialchars($application['cv_filename']); ?></h5>
                            <p class="text-muted">Uploaded on <?php echo date('M d, Y', strtotime($application['created_at'])); ?></p>
                            <div class="d-grid gap-2 mt-3">
                                <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                                   class="btn btn-primary" target="_blank">
                                    <i class="bi bi-eye"></i> View CV
                                </a>
                                <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                                   class="btn btn-outline-primary" download>
                                    <i class="bi bi-download"></i> Download CV
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card mt-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Quick Actions</h6>
                            <p class="text-muted mb-3">Manage this application</p>
                        </div>
                        <div class="col-md-6">
                            <div class="action-buttons justify-content-end">
                                <a href="update_status.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                    <i class="bi bi-pencil"></i> Update Status
                                </a>
                                <a href="applicants.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left"></i> Back
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Send Email Modal -->
    <div class="modal fade" id="sendEmailModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Send Email to Applicant</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">To</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($application['email']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" class="form-control" value="Regarding your application for <?php echo htmlspecialchars($application['job_category']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="6" placeholder="Type your message here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Template</label>
                            <select class="form-select">
                                <option value="">Custom Message</option>
                                <option value="interview">Interview Invitation</option>
                                <option value="selected">Job Offer</option>
                                <option value="rejected">Application Rejection</option>
                                <option value="info">Information Request</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Send Email</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Note Modal -->
    <div class="modal fade" id="addNoteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Internal Note</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label class="form-label">Note Type</label>
                            <select class="form-select">
                                <option value="general">General Note</option>
                                <option value="interview">Interview Feedback</option>
                                <option value="assessment">Assessment</option>
                                <option value="followup">Follow-up Required</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Note</label>
                            <textarea class="form-control" rows="4" placeholder="Add your internal note here..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Visibility</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility" checked>
                                <label class="form-check-label">Private (Only Admins)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="visibility">
                                <label class="form-check-label">Shared (All HR Team)</label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Note</button>
                </div>
            </div>
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

            // Email template selection
            const emailTemplate = document.querySelector('#sendEmailModal select');
            const emailSubject = document.querySelector('#sendEmailModal input[type="text"]');
            const emailMessage = document.querySelector('#sendEmailModal textarea');
            
            if (emailTemplate) {
                emailTemplate.addEventListener('change', function() {
                    const templates = {
                        'interview': {
                            subject: 'Interview Invitation - <?php echo htmlspecialchars($application["job_category"]); ?>',
                            message: 'Dear <?php echo htmlspecialchars($application["first_name"]); ?>, \n\nWe were impressed with your application for the <?php echo htmlspecialchars($application["job_category"]); ?> position and would like to invite you for an interview.\n\nPlease let us know your availability.\n\nBest regards,\nHR Team'
                        },
                        'selected': {
                            subject: 'Job Offer - <?php echo htmlspecialchars($application["job_category"]); ?>',
                            message: 'Dear <?php echo htmlspecialchars($application["first_name"]); ?>, \n\nCongratulations! We are pleased to offer you the position of <?php echo htmlspecialchars($application["job_category"]); ?>.\n\nPlease review the attached offer letter and let us know if you have any questions.\n\nBest regards,\nHR Team'
                        },
                        'rejected': {
                            subject: 'Update on Your Application',
                            message: 'Dear <?php echo htmlspecialchars($application["first_name"]); ?>, \n\nThank you for applying for the <?php echo htmlspecialchars($application["job_category"]); ?> position. We appreciate your interest in our company.\n\nAfter careful consideration, we have decided to move forward with other candidates. We will keep your resume on file for future opportunities.\n\nBest regards,\nHR Team'
                        },
                        'info': {
                            subject: 'Additional Information Required',
                            message: 'Dear <?php echo htmlspecialchars($application["first_name"]); ?>, \n\nThank you for your application for the <?php echo htmlspecialchars($application["job_category"]); ?> position.\n\nWe require some additional information to process your application. Please provide the following:\n\n1. \n2. \n\nBest regards,\nHR Team'
                        }
                    };
                    
                    if (this.value && templates[this.value]) {
                        emailSubject.value = templates[this.value].subject;
                        emailMessage.value = templates[this.value].message;
                    }
                });
            }

            // CV preview hover effect
            const cvPreview = document.querySelector('.cv-preview');
            if (cvPreview) {
                cvPreview.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.02)';
                });
                
                cvPreview.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            }
        });
    </script>
</body>
</html>