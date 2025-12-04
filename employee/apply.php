<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
requireEmployee();

$error = '';
$success = '';

// Check if job_id is provided (for specific job application)
$job_id = isset($_GET['job_id']) ? intval($_GET['job_id']) : null;
$job_title = '';
$job_company = '';

// Get job details if applying for specific job
if ($job_id) {
    $stmt = $db->conn->prepare("SELECT * FROM job_posts WHERE id = ? AND is_active = TRUE");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($job) {
        $job_title = $job['title'];
        $job_company = $job['company'];
    } else {
        $error = "Job not found or no longer available.";
        $job_id = null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $job_id = isset($_POST['job_id']) && $_POST['job_id'] ? intval($_POST['job_id']) : null;
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
    } elseif (!isset($_FILES['cv']) || $_FILES['cv']['error'] === UPLOAD_ERR_NO_FILE) {
        $error = "CV file is required!";
    } else {
        // Validate CV file
        $cv_errors = validateFile($_FILES['cv']);
        if (!empty($cv_errors)) {
            $error = implode("<br>", $cv_errors);
        } else {
            try {
                // Upload CV
                $cv_filename = uploadFile($_FILES['cv'], '../uploads/');
                
                if ($cv_filename) {
                    // Insert new application
                    $stmt = $db->conn->prepare("
                        INSERT INTO applications (
                            employee_id, job_id, first_name, last_name, gender, date_of_birth, 
                            nationality, visa_status, job_category, email, contact_number, 
                            cover_letter, cv_filename, status, created_at, updated_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW(), NOW())
                    ");
                    
                    $stmt->execute([
                        $_SESSION['user_id'], $job_id, $first_name, $last_name, $gender, $date_of_birth,
                        $nationality, $visa_status, $job_category, $email, $contact_number,
                        $cover_letter, $cv_filename
                    ]);
                    
                    $success = $job_title 
                        ? "Application for '" . htmlspecialchars($job_title) . "' submitted successfully!" 
                        : "Application submitted successfully!";
                    
                    echo '<script>
                        setTimeout(function() {
                            window.location.href = "application.php";
                        }, 2000);
                    </script>';
                } else {
                    $error = "Failed to upload CV. Please try again.";
                }
            } catch(PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $job_title ? 'Apply for ' . $job_title : 'New Job Application'; ?> - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .application-header {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .application-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            transform: rotate(30deg);
        }
        
        .form-section {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
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
        
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 3rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f8f9fa;
        }
        
        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(74, 107, 255, 0.05);
        }
        
        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: rgba(74, 107, 255, 0.1);
            transform: scale(1.02);
        }
        
        .file-preview {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
            border: 1px solid #dee2e6;
            display: none;
        }
        
        .file-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .file-icon {
            width: 50px;
            height: 50px;
            background: rgba(74, 107, 255, 0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .character-count {
            text-align: right;
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 1px solid #dee2e6;
        }
        
        .form-requirements {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .job-info-badge {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            display: inline-flex;
            align-items: center;
            margin: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .application-header {
                padding: 1.5rem;
                text-align: center;
            }
            
            .form-section {
                padding: 1.5rem;
            }
            
            .file-upload-area {
                padding: 2rem;
            }
            
            .form-navigation {
                flex-direction: column;
                gap: 1rem;
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
            <!-- Application Header -->
            <div class="application-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <?php if ($job_title): ?>
                                <i class="bi bi-briefcase me-2"></i>Apply for: <?php echo htmlspecialchars($job_title); ?>
                            <?php else: ?>
                                <i class="bi bi-file-earmark-plus me-2"></i>New Job Application
                            <?php endif; ?>
                        </h1>
                        
                        <?php if ($job_title): ?>
                            <div class="mb-3">
                                <span class="job-info-badge">
                                    <i class="bi bi-building me-1"></i> <?php echo htmlspecialchars($job_company); ?>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <p class="mb-0">Fill out the form below to submit your job application. Fields marked with <span class="text-warning">*</span> are required.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="badge bg-light text-dark p-2">
                            <i class="bi bi-clock me-1"></i> Complete all sections
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

            <!-- Application Form -->
            <form method="POST" action="" enctype="multipart/form-data" id="applicationForm">
                <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                
                <!-- Job Reference (if applying for specific job) -->
                <?php if ($job_title): ?>
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-briefcase"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Job Position</h4>
                            <p class="text-muted mb-0">You're applying for this position</p>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-1"><?php echo htmlspecialchars($job_title); ?></h5>
                                <p class="mb-1">
                                    <i class="bi bi-building"></i> <?php echo htmlspecialchars($job_company); ?>
                                </p>
                                <small class="text-muted">Your application will be reviewed for this specific position.</small>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <a href="../index.php" class="btn btn-outline-primary btn-sm mt-2">
                                    <i class="bi bi-search me-1"></i> Browse More Jobs
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Personal Information Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Personal Information</h4>
                            <p class="text-muted mb-0">Tell us about yourself</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="first_name" name="first_name" 
                                   value="<?php echo $_POST['first_name'] ?? $_SESSION['first_name']; ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter your first name.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="last_name" name="last_name" 
                                   value="<?php echo $_POST['last_name'] ?? $_SESSION['last_name']; ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter your last name.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                            <select class="form-select" id="gender" name="gender" required>
                                <option value="">Select Gender</option>
                                <option value="Male" <?php echo ($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                <option value="Female" <?php echo ($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                <option value="Other" <?php echo ($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                            <div class="invalid-feedback">Please select your gender.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                   value="<?php echo $_POST['date_of_birth'] ?? ''; ?>" 
                                   required max="<?php echo date('Y-m-d', strtotime('-18 years')); ?>">
                            <div class="invalid-feedback">You must be at least 18 years old.</div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="nationality" class="form-label">Nationality <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nationality" name="nationality" 
                                   value="<?php echo $_POST['nationality'] ?? ''; ?>" 
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
                            <p class="text-muted mb-0">Tell us about the job you're applying for</p>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="visa_status" class="form-label">Current Visa Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="visa_status" name="visa_status" required>
                                <option value="">Select Visa Status</option>
                                <option value="Visit" <?php echo ($_POST['visa_status'] ?? '') === 'Visit' ? 'selected' : ''; ?>>Visit</option>
                                <option value="Employment" <?php echo ($_POST['visa_status'] ?? '') === 'Employment' ? 'selected' : ''; ?>>Employment</option>
                            </select>
                            <div class="invalid-feedback">Please select your visa status.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="job_category" class="form-label">Job Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="job_category" name="job_category" required>
                                <option value="">Select Job Category</option>
                                <option value="IT & Software" <?php echo ($_POST['job_category'] ?? '') === 'IT & Software' ? 'selected' : ''; ?>>IT & Software</option>
                                <option value="Marketing" <?php echo ($_POST['job_category'] ?? '') === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Sales" <?php echo ($_POST['job_category'] ?? '') === 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                <option value="Finance" <?php echo ($_POST['job_category'] ?? '') === 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                <option value="HR" <?php echo ($_POST['job_category'] ?? '') === 'HR' ? 'selected' : ''; ?>>Human Resources</option>
                                <option value="Operations" <?php echo ($_POST['job_category'] ?? '') === 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                <option value="Customer Service" <?php echo ($_POST['job_category'] ?? '') === 'Customer Service' ? 'selected' : ''; ?>>Customer Service</option>
                            </select>
                            <div class="invalid-feedback">Please select a job category.</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo $_POST['email'] ?? $_SESSION['email']; ?>" 
                                   required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_number" class="form-label">Contact Number <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                   value="<?php echo $_POST['contact_number'] ?? ''; ?>" 
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
                            <p class="text-muted mb-0">Tell us why you're the right candidate</p>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="cover_letter" class="form-label">Cover Letter (Optional)</label>
                        <textarea class="form-control" id="cover_letter" name="cover_letter" 
                                  rows="6" placeholder="Write your cover letter here..."><?php echo $_POST['cover_letter'] ?? ''; ?></textarea>
                        <div class="character-count">
                            <span id="charCount">0</span> / 2000 characters
                        </div>
                    </div>
                </div>

                <!-- CV Upload Section -->
                <div class="form-section">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="bi bi-file-earmark-arrow-up"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">Upload CV / Resume</h4>
                            <p class="text-muted mb-0">Upload your CV in PDF, DOC, or DOCX format</p>
                        </div>
                    </div>
                    
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="bi bi-cloud-arrow-up" style="font-size: 3rem; color: var(--primary-color);"></i>
                        <h5 class="mt-3">Drag & drop your file here</h5>
                        <p class="text-muted">or click to browse</p>
                        <input type="file" class="d-none" id="cv" name="cv" accept=".pdf,.doc,.docx" required>
                        <div class="mt-3">
                            <small class="text-muted">Maximum file size: 5MB • Allowed formats: PDF, DOC, DOCX</small>
                        </div>
                    </div>
                    
                    <div class="file-preview" id="filePreview">
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
                    
                    <div class="form-requirements">
                        <h6><i class="bi bi-info-circle me-2"></i>CV Requirements</h6>
                        <ul class="mb-0">
                            <li>File must be in PDF, DOC, or DOCX format</li>
                            <li>Maximum file size is 5MB</li>
                            <li>Include your contact information</li>
                            <li>List your work experience and education</li>
                            <li>Include relevant skills and certifications</li>
                        </ul>
                    </div>
                </div>

                <!-- Form Navigation -->
                <div class="form-navigation">
                    <div>
                        <a href="<?php echo $job_id ? '../index.php' : 'dashboard.php'; ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-check-circle"></i> 
                            <?php echo $job_title ? 'Apply for this Job' : 'Submit Application'; ?>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('applicationForm');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInput = document.getElementById('cv');
            const filePreview = document.getElementById('filePreview');
            const fileName = document.getElementById('fileName');
            const fileSize = document.getElementById('fileSize');
            const removeFileBtn = document.getElementById('removeFile');
            const coverLetter = document.getElementById('cover_letter');
            const charCount = document.getElementById('charCount');
            
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
            });
            
            // Trigger initial count
            coverLetter.dispatchEvent(new Event('input'));
            
            // File upload handling
            fileUploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length > 0) {
                    const file = this.files[0];
                    displayFileInfo(file);
                }
            });
            
            // Drag and drop functionality
            fileUploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            fileUploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            fileUploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                if (e.dataTransfer.files.length > 0) {
                    const file = e.dataTransfer.files[0];
                    fileInput.files = e.dataTransfer.files;
                    displayFileInfo(file);
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
            });
            
            // Form validation and submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    highlightInvalidFields();
                } else {
                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';
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
                
                // Validate file upload
                if (!fileInput.files.length) {
                    isValid = false;
                    fileUploadArea.style.borderColor = '#dc3545';
                } else {
                    fileUploadArea.style.borderColor = '';
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
            
            // Real-time validation
            const inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            });
            
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
            });
        });
    </script>
</body>
</html>