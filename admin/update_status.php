<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
requireAdmin();

if (!isset($_GET['id'])) {
    header("Location: applicants.php");
    exit();
}

$id = intval($_GET['id']);

// Get current application
$stmt = $db->conn->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header("Location: applicants.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = sanitize($_POST['status']);
    $interview_location = sanitize($_POST['interview_location']);
    $contact_person_number = sanitize($_POST['contact_person_number']);
    
    try {
        $stmt = $db->conn->prepare("
            UPDATE applications SET 
                status = ?, 
                interview_location = ?, 
                contact_person_number = ?,
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        $stmt->execute([
            $status, 
            $status === 'Interview' ? $interview_location : null,
            $status === 'Interview' ? $contact_person_number : null,
            $id
        ]);
        
        $success = "Status updated successfully!";
        // Refresh application data
        $stmt = $db->conn->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Status - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-update-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        
        .applicant-summary {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .status-option {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .status-option:hover {
            border-color: var(--primary-color);
            background: rgba(74, 107, 255, 0.05);
        }
        
        .status-option.selected {
            border-color: var(--primary-color);
            background: rgba(74, 107, 255, 0.1);
        }
        
        .status-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: white;
            font-size: 1.25rem;
        }
        
        .status-icon.pending { background: #ffc107; }
        .status-icon.reviewed { background: #17a2b8; }
        .status-icon.interview { background: #007bff; }
        .status-icon.selected { background: #28a745; }
        .status-icon.rejected { background: #dc3545; }
        
        .timeline-history {
            position: relative;
            padding-left: 2rem;
        }
        
        .timeline-history::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e9ecef;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            left: -1.5rem;
            top: 0;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            z-index: 2;
        }
        
        .timeline-content {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .interview-details {
            background: #f0f8ff;
            border-radius: 8px;
            padding: 1.5rem;
            border-left: 4px solid #007bff;
        }
        
        .confirmation-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1050;
        }
        
        .confirmation-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @media (max-width: 768px) {
            .status-update-card {
                padding: 1.5rem;
            }
            
            .status-option {
                flex-direction: column;
                text-align: center;
            }
            
            .status-icon {
                margin-right: 0;
                margin-bottom: 0.5rem;
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
                    <h2 class="page-title">Update Application Status</h2>
                    <p class="text-muted mb-0">Update the status and track changes for applicant</p>
                </div>
                <div>
                    <a href="applicant_detail.php?id=<?php echo $id; ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Back to Details
                    </a>
                </div>
            </div>

            <!-- Applicant Summary -->
            <div class="applicant-summary">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h5 class="mb-2"><?php echo htmlspecialchars($application['first_name'] . ' ' . $application['last_name']); ?></h5>
                        <div class="d-flex flex-wrap gap-3">
                            <span><i class="bi bi-briefcase"></i> <?php echo htmlspecialchars($application['job_category']); ?></span>
                            <span><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($application['email']); ?></span>
                            <span><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($application['contact_number']); ?></span>
                            <span class="badge badge-<?php echo strtolower($application['status']); ?>">
                                Current: <?php echo $application['status']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <small class="text-muted">Application ID: #APP-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?></small>
                    </div>
                </div>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i> <?php echo $success; ?>
                    <div class="progress mt-2" style="height: 3px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%; animation: progress 2s linear;"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Status Update Form -->
            <form method="POST" action="" id="statusForm">
                <div class="row">
                    <div class="col-lg-8">
                        <div class="status-update-card">
                            <h5 class="mb-4">Select New Status</h5>
                            
                            <div class="row">
                                <?php
                                $status_options = [
                                    'Pending' => ['icon' => 'bi-clock', 'color' => 'warning', 'desc' => 'Application is under initial review'],
                                    'Reviewed' => ['icon' => 'bi-eye', 'color' => 'info', 'desc' => 'Application has been reviewed'],
                                    'Interview' => ['icon' => 'bi-calendar-check', 'color' => 'primary', 'desc' => 'Schedule interview with applicant'],
                                    'Selected' => ['icon' => 'bi-check-circle', 'color' => 'success', 'desc' => 'Accept application and send offer'],
                                    'Rejected' => ['icon' => 'bi-x-circle', 'color' => 'danger', 'desc' => 'Reject application with feedback']
                                ];
                                
                                foreach ($status_options as $status_key => $status_data):
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="status-option <?php echo $application['status'] === $status_key ? 'selected' : ''; ?>" 
                                         data-status="<?php echo $status_key; ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="status-icon <?php echo strtolower($status_key); ?>">
                                                <i class="bi <?php echo $status_data['icon']; ?>"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1"><?php echo $status_key; ?></h6>
                                                <p class="mb-0 text-muted small"><?php echo $status_data['desc']; ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <input type="hidden" name="status" id="selectedStatus" value="<?php echo $application['status']; ?>">

                            <!-- Interview Details (Show only when Interview is selected) -->
                            <div id="interviewDetails" style="display: <?php echo $application['status'] === 'Interview' ? 'block' : 'none'; ?>;">
                                <div class="interview-details mt-4">
                                    <h6 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Interview Details</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="interview_location" class="form-label">Interview Location / Link</label>
                                            <input type="text" class="form-control" id="interview_location" 
                                                   name="interview_location" 
                                                   value="<?php echo htmlspecialchars($application['interview_location'] ?? ''); ?>"
                                                   placeholder="e.g., Conference Room A or Zoom Link">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="contact_person_number" class="form-label">Contact Person & Number</label>
                                            <input type="text" class="form-control" id="contact_person_number" 
                                                   name="contact_person_number" 
                                                   value="<?php echo htmlspecialchars($application['contact_person_number'] ?? ''); ?>"
                                                   placeholder="e.g., John Doe - (123) 456-7890">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Suggested Date</label>
                                            <input type="date" class="form-control" id="suggested_date" 
                                                   min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Suggested Time</label>
                                            <input type="time" class="form-control" id="suggested_time">
                                        </div>
                                    </div>
                                </div>
                            </div>

                           

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between mt-4">
                                <a href="applicant_detail.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary">
                                    <i class="bi bi-x-circle"></i> Cancel
                                </a>
                                <button type="button" class="btn btn-primary" id="updateStatusBtn">
                                    <i class="bi bi-check-circle"></i> Update Status
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-4">
                        <!-- Status History -->
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Status History</h6>
                            </div>
                            <div class="card-body">
                                <?php if (empty($status_history)): ?>
                                    <div class="text-center py-3">
                                        <i class="bi bi-clock-history" style="font-size: 2rem; color: #dee2e6;"></i>
                                        <p class="text-muted mt-2">No status history available</p>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline-history">
                                        <?php foreach ($status_history as $history): ?>
                                        <div class="timeline-item">
                                            <div class="timeline-dot"></div>
                                            <div class="timeline-content">
                                                <div class="d-flex justify-content-between">
                                                    <span class="badge badge-<?php echo strtolower($history['new_status']); ?>">
                                                        <?php echo $history['new_status']; ?>
                                                    </span>
                                                    <small class="text-muted">
                                                        <?php echo date('M d, h:i A', strtotime($history['created_at'])); ?>
                                                    </small>
                                                </div>
                                                <?php if ($history['notes']): ?>
                                                    <p class="mt-2 mb-0 small"><?php echo htmlspecialchars($history['notes']); ?></p>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    Changed by: Admin
                                                </small>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status Guidelines -->
                        <div class="card mt-3">
                            <div class="card-header">
                                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Status Guidelines</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2">
                                        <i class="bi bi-check-circle text-success me-2"></i>
                                        <small><strong>Selected:</strong> Send offer letter and welcome email</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-calendar-check text-primary me-2"></i>
                                        <small><strong>Interview:</strong> Schedule and send interview details</small>
                                    </li>
                                    <li class="mb-2">
                                        <i class="bi bi-x-circle text-danger me-2"></i>
                                        <small><strong>Rejected:</strong> Send polite rejection email</small>
                                    </li>
                                    <li>
                                        <i class="bi bi-clock text-warning me-2"></i>
                                        <small><strong>Pending:</strong> Under review, no action needed</small>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmationModal" class="confirmation-modal" style="display: none;">
        <div class="confirmation-content">
            <h5 class="mb-3">Confirm Status Update</h5>
            <p>You are about to change the status from 
                <span class="badge badge-<?php echo strtolower($application['status']); ?>"><?php echo $application['status']; ?></span>
                to <span id="newStatusBadge" class="badge"></span>
            </p>
            <p id="confirmationMessage" class="text-muted"></p>
            <div class="alert alert-info" id="emailNotification">
                <i class="bi  bi-exclamation-triangle"></i>Notification will be sent to the applicant.
            </div>
            <div class="d-flex justify-content-end gap-2 mt-4">
                <button type="button" class="btn btn-outline-secondary" id="cancelConfirm">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmUpdate">Yes, Update Status</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusOptions = document.querySelectorAll('.status-option');
            const selectedStatusInput = document.getElementById('selectedStatus');
            const interviewDetails = document.getElementById('interviewDetails');
            const updateStatusBtn = document.getElementById('updateStatusBtn');
            const confirmationModal = document.getElementById('confirmationModal');
            const newStatusBadge = document.getElementById('newStatusBadge');
            const confirmationMessage = document.getElementById('confirmationMessage');
            const emailNotification = document.getElementById('emailNotification');
            const cancelConfirm = document.getElementById('cancelConfirm');
            const confirmUpdate = document.getElementById('confirmUpdate');
            
            // Status option selection
            statusOptions.forEach(option => {
                option.addEventListener('click', function() {
                    statusOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    
                    const newStatus = this.dataset.status;
                    selectedStatusInput.value = newStatus;
                    
                    // Show/hide interview details
                    if (newStatus === 'Interview') {
                        interviewDetails.style.display = 'block';
                        interviewDetails.style.animation = 'slideDown 0.3s ease';
                    } else {
                        interviewDetails.style.display = 'none';
                    }
                });
            });
            
            // Update status button click
            updateStatusBtn.addEventListener('click', function() {
                const currentStatus = '<?php echo $application['status']; ?>';
                const newStatus = selectedStatusInput.value;
                
                if (currentStatus === newStatus) {
                    alert('Please select a different status from the current one.');
                    return;
                }
                
                // Set confirmation modal content
                newStatusBadge.className = `badge badge-${newStatus.toLowerCase()}`;
                newStatusBadge.textContent = newStatus;
                
                const messages = {
                    'Pending': 'Application will be marked as pending review.',
                    'Reviewed': 'Application will be marked as reviewed.',
                    'Interview': 'Please provide interview details.',
                    'Selected': 'Congratulations! Applicant will be notified of selection.',
                    'Rejected': 'Applicant will be notified of rejection.'
                };
                
                confirmationMessage.textContent = messages[newStatus];
                
                // Show/hide email notification
                if (newStatus === 'Pending') {
                    emailNotification.style.display = 'none';
                } else {
                    emailNotification.style.display = 'block';
                }
                
                // Show confirmation modal
                confirmationModal.style.display = 'flex';
            });
            
            // Cancel confirmation
            cancelConfirm.addEventListener('click', function() {
                confirmationModal.style.display = 'none';
            });
            
            // Confirm update
            confirmUpdate.addEventListener('click', function() {
                // Validate interview details if interview is selected
                if (selectedStatusInput.value === 'Interview') {
                    const location = document.getElementById('interview_location').value;
                    const contact = document.getElementById('contact_person_number').value;
                    
                    if (!location.trim() || !contact.trim()) {
                        alert('Please fill in interview location and contact details.');
                        return;
                    }
                }
                
                // Submit the form
                document.getElementById('statusForm').submit();
                
                // Show loading state
                updateStatusBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Updating...';
                updateStatusBtn.disabled = true;
            });
            
            // Close modal when clicking outside
            confirmationModal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.style.display = 'none';
                }
            });
            
            // Auto-focus first field when interview details shown
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.target.style.display === 'block') {
                        const firstInput = interviewDetails.querySelector('input');
                        if (firstInput) firstInput.focus();
                    }
                });
            });
            
            observer.observe(interviewDetails, { attributes: true, attributeFilter: ['style'] });
        });
    </script>
</body>
</html>