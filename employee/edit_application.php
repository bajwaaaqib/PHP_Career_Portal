<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireEmployee();

$error = '';
$success = '';

// Get application ID from URL or use latest application
$application_id = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$application_id) {
    // Get user's latest application
    $stmt = $db->conn->prepare("SELECT id FROM applications WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);
    $application_id = $app['id'] ?? null;
}

if (!$application_id) {
    header("Location: application.php");
    exit();
}

// Get application details with job info
$stmt = $db->conn->prepare("
    SELECT a.*, j.title as job_title, j.company as job_company, j.location as job_location 
    FROM applications a 
    LEFT JOIN job_posts j ON a.job_id = j.id 
    WHERE a.id = ? AND a.employee_id = ?
");
$stmt->execute([$application_id, $_SESSION['user_id']]);
$application = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$application) {
    header("Location: application.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $gender = sanitize($_POST['gender']);
    $date_of_birth = sanitize($_POST['date_of_birth']);
    $nationality = sanitize($_POST['nationality']);
    $visa_status = sanitize($_POST['visa_status']);
    $job_category = sanitize($_POST['job_category']);
    $email = sanitize($_POST['email']);
    $contact_number = sanitize($_POST['contact_number']);
    $cover_letter = sanitize($_POST['cover_letter']);
    
    // Validate required fields
    $required_fields = [
        'First Name' => $first_name,
        'Last Name' => $last_name,
        'Gender' => $gender,
        'Date of Birth' => $date_of_birth,
        'Nationality' => $nationality,
        'Visa Status' => $visa_status,
        'Job Category' => $job_category,
        'Email' => $email,
        'Contact Number' => $contact_number
    ];
    
    $missing_fields = [];
    foreach ($required_fields as $field => $value) {
        if (empty($value)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        $error = "Please fill in all required fields: " . implode(', ', $missing_fields);
    } elseif (!validateEmail($email)) {
        $error = "Please enter a valid email address!";
    } else {
        try {
            $cv_filename = $application['cv_filename'];
            
            // Handle CV upload if new file is provided
            if (isset($_FILES['cv']) && $_FILES['cv']['error'] !== UPLOAD_ERR_NO_FILE) {
                $cv_errors = validateFile($_FILES['cv']);
                if (!empty($cv_errors)) {
                    $error = implode("<br>", $cv_errors);
                } else {
                    // Upload new CV and delete old one
                    $new_cv_filename = uploadFile($_FILES['cv'], '../uploads/');
                    if ($new_cv_filename) {
                        deleteOldFile($application['cv_filename'], '../uploads/');
                        $cv_filename = $new_cv_filename;
                    } else {
                        $error = "Failed to upload new CV. Please try again.";
                    }
                }
            }
            
            if (empty($error)) {
                // Update application
                $stmt = $db->conn->prepare("
                    UPDATE applications SET
                        first_name = ?, last_name = ?, gender = ?, date_of_birth = ?,
                        nationality = ?, visa_status = ?, job_category = ?, email = ?,
                        contact_number = ?, cover_letter = ?, cv_filename = ?,
                        updated_at = CURRENT_TIMESTAMP, status = 'Pending'
                    WHERE id = ? AND employee_id = ?
                ");
                
                $stmt->execute([
                    $first_name, $last_name, $gender, $date_of_birth,
                    $nationality, $visa_status, $job_category, $email,
                    $contact_number, $cover_letter, $cv_filename,
                    $application['id'], $_SESSION['user_id']
                ]);
                
                $success = "Application updated successfully!";
                
                // Refresh application data
                $stmt = $db->conn->prepare("
                    SELECT a.*, j.title as job_title, j.company as job_company 
                    FROM applications a 
                    LEFT JOIN job_posts j ON a.job_id = j.id 
                    WHERE a.id = ? AND a.employee_id = ?
                ");
                $stmt->execute([$application_id, $_SESSION['user_id']]);
                $application = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Show success message with auto-redirect
                echo '<script>
                    setTimeout(function() {
                        window.location.href = "application.php";
                    }, 2000);
                </script>';
            }
        } catch(PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Application - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .edit-header {
            background: linear-gradient(135deg, var(--warning-color), #fd7e14);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .edit-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .current-cv {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .change-note {
            background: #fff3cd;
            border: 1px solid #ffecb5;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .application-info {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }
        
        .status-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }
        
        .application-breadcrumb {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid var(--primary-color);
        }
        
        .last-updated {
            font-size: 0.875rem;
            opacity: 0.9;
        }
        
        .save-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: var(--success-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            display: none;
            z-index: 1000;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            position: relative;
        }
        
        .section-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid rgba(74, 107, 255, 0.1);
        }
        
        .section-icon {
            width: 50px;
            height: 50px;
            background: rgba(74, 107, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            color: var(--primary-color);
            font-size: 1.25rem;
        }
        
        .auto-save {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.75rem;
            color: #666;
            display: none;
        }
        
        .job-reference {
            background: #e7f3ff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
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
            <!-- Application Breadcrumb -->
            <div class="application-breadcrumb">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="application.php">My Applications</a></li>
                        <li class="breadcrumb-item active">Edit Application</li>
                    </ol>
                </nav>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Application ID: #APP-<?php echo str_pad($application['id'], 6, '0', STR_PAD_LEFT); ?>
                    </small>
                    <a href="application.php" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-arrow-left me-1"></i> Back to Applications
                    </a>
                </div>
            </div>

            <!-- Edit Header -->
            <div class="edit-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">Edit Application</h1>
                        
                        <?php if ($application['job_title']): ?>
                            <div class="application-info mb-3">
                                <h5 class="mb-1"><?php echo htmlspecialchars($application['job_title']); ?></h5>
                                <p class="mb-1">
                                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($application['job_company']); ?>
                                    <?php if ($application['job_location']): ?>
                                        <i class="bi bi-geo-alt ms-2"></i> <?php echo htmlspecialchars($application['job_location']); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <p class="mb-0">General Application</p>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <span class="status-badge">
                                <i class="bi bi-clock-history me-1"></i>
                                Current Status: <?php echo $application['status']; ?>
                            </span>
                            <span class="status-badge ms-2">
                                <i class="bi bi-calendar me-1"></i>
                                Last Updated: <?php echo date('M d, Y', strtotime($application['updated_at'])); ?>
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="badge bg-light text-dark p-2 mb-2">
                            <i class="bi bi-info-circle me-1"></i> Changes reset status to Pending
                        </div>
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

            <!-- Change Note -->
            <div class="change-note">
                <div class="d-flex align-items-center">
                    <i class="bi bi-info-circle-fill me-3" style="color: #856404; font-size: 1.5rem;"></i>
                    <div>
                        <strong>Important:</strong> Your application status will change to <span class="badge bg-warning">Pending</span> after saving changes. 
                        This allows HR to review your updated information. All changes will be logged.
                    </div>
                </div>
            </div>

            <!-- Edit Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                
                <!-- Job Reference (if job-specific application) -->
                <?php if ($application['job_title']): ?>
                <div class="job-reference">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-briefcase me-3" style="font-size: 1.5rem; color: var(--primary-color);"></i>
                        <div>
                            <h6 class="mb-0">Job Position</h6>
                            <p class="mb-0">
                                You're editing your application for: 
                                <strong><?php echo htmlspecialchars($application['job_title']); ?></strong>
                                at <?php echo htmlspecialchars($application['job_company']); ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="auto-save" id="autoSaveIndicator">
                        <i class="bi bi-check-circle text-success"></i> Auto-saved
                    </div>
                    
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Personal Information</h4>
                            <p class="text-muted mb-0">Update your personal details</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo htmlspecialchars($application['first_name']); ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter your first name.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo htmlspecialchars($application['last_name']); ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter your last name.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="Male" <?php echo $application['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo $application['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo $application['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <div class="invalid-feedback">Please select your gender.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                   value="<?php echo $application['date_of_birth']; ?>" 
                                   required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                            <div class="invalid-feedback">You must be at least 18 years old.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="nationality" class="form-label">Nationality <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nationality" name="nationality" 
                                   value="<?php echo htmlspecialchars($application['nationality']); ?>" 
                                   required placeholder="e.g., American, Indian, British">
                            <div class="invalid-feedback">Please enter your nationality.</div>
                        </div>
                    </div>
                </div>

                <!-- Job Information Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Job Information</h4>
                            <p class="text-muted mb-0">Update your job preferences</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="visa_status" class="form-label">Current Visa Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="visa_status" name="visa_status" required>
                                <option value="Visit" <?php echo $application['visa_status'] === 'Visit' ? 'selected' : ''; ?>>Visit</option>
                                <option value="Employment" <?php echo $application['visa_status'] === 'Employment' ? 'selected' : ''; ?>>Employment</option>
                            </select>
                            <div class="invalid-feedback">Please select your visa status.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="job_category" class="form-label">Job Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="job_category" name="job_category" required>
                                <option value="IT & Software" <?php echo $application['job_category'] === 'IT & Software' ? 'selected' : ''; ?>>IT & Software</option>
                                <option value="Marketing" <?php echo $application['job_category'] === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Sales" <?php echo $application['job_category'] === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                <option value="Finance" <?php echo $application['job_category'] === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                <option value="HR" <?php echo $application['job_category'] === 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Operations" <?php echo $application['job_category'] === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                <option value="Customer Service" <?php echo $application['job_category'] === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                            </select>
                            <div class="invalid-feedback">Please select a job category.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($application['email']); ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                   value="<?php echo htmlspecialchars($application['contact_number']); ?>" 
                                   required placeholder="e.g., +1 (123) 456-7890">
                            <div class="invalid-feedback">Please enter your contact number.</div>
                        </div>
                    </div>
                </div>

                <!-- Cover Letter Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-chat-text"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Cover Letter</h4>
                            <p class="text-muted mb-0">Update your cover letter</p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter (Optional)</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" 
                                  rows="6" placeholder="Write your cover letter here..."><?php echo htmlspecialchars($application['cover_letter']); ?></textarea>
                        <div class="character-count">
                            <span id="charCount"><?php echo strlen($application['cover_letter'] ?? ''); ?></span> / 2000 characters
                        </div>
                    </div>
                </div>

                <!-- CV Upload Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">CV / Resume</h4>
                            <p class="text-muted mb-0">Update or keep your current CV</p>
                        </div>
                    </div>
                    
                    <!-- Current CV -->
                    <div class="current-cv">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6>Current CV</h6>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-file-earmark-pdf me-3" style="font-size: 2rem; color: var(--primary-color);"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($application['cv_filename']); ?></div>
                                        <small class="text-muted">Uploaded on <?php echo date('M d, Y', strtotime($application['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <div class="d-flex gap-2 justify-content-md-end">
                                    <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                                       class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-eye"></i> View
                                    </a>
                                    <a href="../uploads/<?php echo $application['cv_filename']; ?>" 
                                       class="btn btn-sm btn-outline-success" download>
                                        <i class="bi bi-download"></i> Download
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- New CV Upload -->
                    <div class="mb-3">
                        <label class="form-label">Upload New CV (Optional)</label>
                        <div class="file-upload-area" id="fileUploadArea" style="padding: 2rem;">
                            <i class="bi bi-cloud-arrow-up" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <h6 class="mt-2">Drag & drop your new CV here</h6>
                            <p class="text-muted small mb-2">or click to browse</p>
                            <input type="file" class="d-none" id="cv" name="cv" accept=".pdf,.doc,.docx">
                            <small class="text-muted">Maximum file size: 5MB • Allowed formats: PDF, DOC, DOCX</small>
                        </div>
                        
                        <div class="file-preview" id="filePreview" style="display: none;">
                            <div class="file-info">
                                <div class="file-icon">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 id="fileName">No file selected</h6>
                                    <small id="fileSize" class="text-muted">0 KB</small>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-danger" id="removeFile">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> 
                        <strong>Note:</strong> If you don't select a new file, your current CV will be kept.
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex justify-content-between mt-4">
                    <div>
                        <a href="application.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Applications
                        </a>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-warning" id="previewBtn">
                            <i class="bi bi-eye"></i> Preview Changes
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Save Indicator -->
    <div class="save-indicator" id="saveIndicator">
        <i class="bi bi-check-circle me-2"></i> Changes saved successfully!
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('editForm');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('cv');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const removeFileBtn = document.getElementById('removeFile');
            const coverLetter = document.getElementById('cover_letter');
            const charCount = document.getElementById('charCount');
            const autoSaveIndicator = document.getElementById('autoSaveIndicator');
            const saveIndicator = document.getElementById('saveIndicator');
            const previewBtn = document.getElementById('previewBtn');
            
            let autoSaveTimeout;
            let hasChanges = false;
            let originalData = {};
            
            // Store original form data
            const formInputs = form.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                originalData[input.name] = input.value;
            });
            
            // Character count for cover letter
            coverLetter.addEventListener('input', function() {
                const length = this.value.length;
                charCount.textContent = length;
                
                if (length > 2000) {
                    charCount.style.color = '#dc3545';
                } else if (length > 1500) {
                    charCount.style.color = '#ffc107';
                } else {
                    charCount.style.color = '#666';
                }
                
                checkForChanges();
                scheduleAutoSave();
            });
            
            // File upload handling
            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    displayFileInfo(file);
                    checkForChanges();
                    scheduleAutoSave();
                }
            });
            
            // Drag and drop functionality
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = 'var(--primary-color)';
                this.style.backgroundColor = 'rgba(74, 107, 255, 0.05)';
            });
            
            fileUploadArea.addEventListener('dragleave', function() {
                this.style.borderColor = '';
                this.style.backgroundColor = '';
            });
            
            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '';
                this.style.backgroundColor = '';
                
                if (e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    fileInput.files = e.dataTransfer.files;
                    displayFileInfo(file);
                    checkForChanges();
                    scheduleAutoSave();
                }
            });
            
            function displayFileInfo(file) {
                // Validate file
                const maxSize = 5 * 1024 * 1024; // 5MB
                const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                
                if (!allowedTypes.includes(file.type)) {
                    alert('Please upload only PDF, DOC, or DOCX files.');
                    return;
                }
                
                if (file.size > maxSize) {
                    alert('File size must be less than 5MB.');
                    return;
                }
                
                // Display file info
                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                
                // Update file icon based on type
                const fileIcon = filePreview.querySelector('.file-icon i');
                if (file.type === 'application/pdf') {
                    fileIcon.className = 'bi bi-file-earmark-pdf';
                } else {
                    fileIcon.className = 'bi bi-file-earmark-word';
                }
                
                filePreview.style.display = 'block';
            }
            
            function formatFileSize(bytes) {
                if (bytes === 0) return '0 Bytes';
                const k = 1024;
                const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }
            
            removeFileBtn.addEventListener('click', function() {
                fileInput.value = '';
                filePreview.style.display = 'none';
                checkForChanges();
                scheduleAutoSave();
            });
            
            // Check for changes
            function checkForChanges() {
                hasChanges = false;
                
                formInputs.forEach(input => {
                    if (input.type === 'file') {
                        if (input.files.length > 0) {
                            hasChanges = true;
                        }
                    } else if (originalData[input.name] !== input.value) {
                        hasChanges = true;
                    }
                });
                
                if (hasChanges) {
                    document.title = '✎ Edit Application - Career System';
                } else {
                    document.title = 'Edit Application - Career System';
                }
            }
            
            // Auto-save functionality
            function scheduleAutoSave() {
                clearTimeout(autoSaveTimeout);
                autoSaveTimeout = setTimeout(saveToLocalStorage, 1000);
            }
            
            function saveToLocalStorage() {
                if (!hasChanges) return;
                
                const formData = {};
                formInputs.forEach(input => {
                    if (input.type === 'file') {
                        // Files can't be stored in localStorage
                        return;
                    }
                    formData[input.name] = input.value;
                });
                
                localStorage.setItem('applicationDraft_' + <?php echo $application['id']; ?>, JSON.stringify(formData));
                
                // Show auto-save indicator
                autoSaveIndicator.style.display = 'block';
                setTimeout(() => {
                    autoSaveIndicator.style.display = 'none';
                }, 2000);
            }
            
            // Load auto-saved data
            function loadFromLocalStorage() {
                const savedData = localStorage.getItem('applicationDraft_' + <?php echo $application['id']; ?>);
                if (savedData) {
                    const formData = JSON.parse(savedData);
                    
                    formInputs.forEach(input => {
                        if (input.type !== 'file' && formData[input.name] !== undefined) {
                            input.value = formData[input.name];
                        }
                    });
                    
                    // Update character count
                    if (coverLetter.value) {
                        charCount.textContent = coverLetter.value.length;
                    }
                    
                    // Show restore notification
                    const restoreAlert = document.createElement('div');
                    restoreAlert.className = 'alert alert-info alert-dismissible fade show';
                    restoreAlert.innerHTML = `
                        <i class="bi bi-arrow-clockwise me-2"></i>
                        <strong>Auto-saved data found!</strong> Your previous changes have been restored.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    form.prepend(restoreAlert);
                }
            }
            
            // Monitor form changes
            formInputs.forEach(input => {
                if (input.type !== 'file') {
                    input.addEventListener('input', function() {
                        checkForChanges();
                        scheduleAutoSave();
                    });
                    
                    input.addEventListener('change', function() {
                        checkForChanges();
                        scheduleAutoSave();
                    });
                }
            });
            
            // Form submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    highlightInvalidFields();
                } else {
                    // Clear auto-saved data
                    localStorage.removeItem('applicationDraft_' + <?php echo $application['id']; ?>);
                    
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                    submitBtn.disabled = true;
                }
            });
            
            function validateForm() {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                // Validate email
                const emailField = document.getElementById('email');
                if (emailField.value && !isValidEmail(emailField.value)) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                }
                
                // Validate date of birth (must be at least 18)
                const dobField = document.getElementById('date_of_birth');
                if (dobField.value) {
                    const dob = new Date(dobField.value);
                    const today = new Date();
                    const age = today.getFullYear() - dob.getFullYear();
                    const monthDiff = today.getMonth() - dob.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    
                    if (age < 18) {
                        isValid = false;
                        dobField.classList.add('is-invalid');
                    }
                }
                
                return isValid;
            }
            
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            function highlightInvalidFields() {
                const invalidFields = form.querySelectorAll('.is-invalid');
                if (invalidFields.length > 0) {
                    invalidFields[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Show error alert
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger mt-3';
                    alertDiv.innerHTML = '<i class="bi bi-exclamation-circle"></i> Please correct the errors in the form before submitting.';
                    form.prepend(alertDiv);
                    
                    setTimeout(() => alertDiv.remove(), 5000);
                }
            }
            
            // Preview functionality
            previewBtn.addEventListener('click', function() {
                if (!hasChanges) {
                    alert('No changes to preview.');
                    return;
                }
                
                // Collect changed data
                const changes = [];
                formInputs.forEach(input => {
                    if (input.type === 'file' && input.files.length > 0) {
                        changes.push({
                            field: input.name,
                            old: 'Current CV',
                            new: input.files[0].name
                        });
                    } else if (originalData[input.name] !== input.value) {
                        changes.push({
                            field: input.name,
                            old: originalData[input.name] || '(empty)',
                            new: input.value
                        });
                    }
                });
                
                // Show preview modal
                const modalHtml = `
                    <div class="modal fade" id="previewModal" tabindex="-1">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Preview Changes</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-warning">
                                        <i class="bi bi-exclamation-triangle"></i> 
                                        <strong>Warning:</strong> Saving these changes will reset your application status to <span class="badge bg-warning">Pending</span>.
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Field</th>
                                                    <th>Current Value</th>
                                                    <th>New Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${changes.map(change => `
                                                    <tr>
                                                        <td><strong>${getFieldLabel(change.field)}</strong></td>
                                                        <td><span class="text-muted">${change.old}</span></td>
                                                        <td><span class="text-success">${change.new}</span></td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="button" class="btn btn-primary" onclick="document.getElementById('editForm').submit()">
                                        <i class="bi bi-check-circle"></i> Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Add modal to document
                const modalContainer = document.createElement('div');
                modalContainer.innerHTML = modalHtml;
                document.body.appendChild(modalContainer);
                
                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('previewModal'));
                modal.show();
                
                // Remove modal after closing
                document.getElementById('previewModal').addEventListener('hidden.bs.modal', function() {
                    modalContainer.remove();
                });
            });
            
            function getFieldLabel(fieldName) {
                const labels = {
                    'first_name': 'First Name',
                    'last_name': 'Last Name',
                    'gender': 'Gender',
                    'date_of_birth': 'Date of Birth',
                    'nationality': 'Nationality',
                    'visa_status': 'Visa Status',
                    'job_category': 'Job Category',
                    'email': 'Email',
                    'contact_number': 'Contact Number',
                    'cover_letter': 'Cover Letter',
                    'cv': 'CV/Resume'
                };
                return labels[fieldName] || fieldName;
            }
            
            // Auto-format phone number
            const phoneInput = document.getElementById('contact_number');
            phoneInput.addEventListener('input', function() {
                let value = this.value.replace(/\D/g, '');
                
                if (value.length > 0) {
                    value = '+' + value;
                    if (value.length > 4) {
                        value = value.slice(0, 4) + ' (' + value.slice(4);
                    }
                    if (value.length > 9) {
                        value = value.slice(0, 9) + ') ' + value.slice(9);
                    }
                    if (value.length > 14) {
                        value = value.slice(0, 14) + '-' + value.slice(14);
                    }
                    if (value.length > 19) {
                        value = value.slice(0, 19);
                    }
                }
                
                this.value = value;
                checkForChanges();
                scheduleAutoSave();
            });
            
            // Before unload warning
            window.addEventListener('beforeunload', function(e) {
                if (hasChanges) {
                    e.preventDefault();
                    e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
                    return e.returnValue;
                }
            });
            
            // Load saved data on page load
            loadFromLocalStorage();
            checkForChanges();
        });
    </script>
</body>
</html>