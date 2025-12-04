<?php
session_start();

// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "career";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Get active job posts
$stmt = $db->query("SELECT * FROM job_posts WHERE is_active = TRUE ORDER BY created_at DESC LIMIT 6");
$job_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total job count
$stmt = $db->query("SELECT COUNT(*) as total FROM job_posts WHERE is_active = TRUE");
$total_jobs = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ARD | Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4a6bff;
            --secondary-color: #667eea;
            --gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --light-bg: #f8f9ff;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--light-bg);
            color: #333;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        /* Header Styles */
        .header {
            background: white;
            box-shadow: 0 2px 30px rgba(0, 0, 0, 0.08);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: #0d6efd;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }
        
        .btn-login {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        /* Job Cards - Better Spacing */
        .job-card {
            background: white;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            margin-bottom: 2.5rem;
            height: 100%;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .job-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
        }
        
        .job-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: #0d6efd;
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .job-card:hover::before {
            transform: scaleX(1);
        }
        
        .job-type-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .job-type-fulltime { background: #0d6efd; color: white; }
        .job-type-parttime { background: #0d6efd; color: white; }
        .job-type-contract { background: #0d6efd; color: white; }
        .job-type-internship { background: #0d6efd; color: white; }
        
        .company-icon {
            width: 50px;
            height: 50px;
            background: #0d6efd;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 1rem;
        }
        
        .job-details-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .job-details-list li {
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .job-details-list li i {
            width: 20px;
            color: var(--primary-color);
        }
        
        .view-more {
            color: var(--primary-color);
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }
        
        .view-more:hover {
            gap: 1rem;
        }
        
        /* Better Grid Spacing */
        .job-grid {
            margin-top: 3rem;
        }
        
        /* Job Details Modal */
        .job-details-modal .modal-dialog {
            max-width: 800px;
        }
        
        .job-details-modal .modal-content {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.2);
        }
        
        .job-header {
            background: var(--gradient);
            color: white;
            padding: 2.5rem;
        }
        
        .job-company {
            font-size: 1.2rem;
            opacity: 0.9;
        }
        
        .job-location {
            font-size: 1rem;
            opacity: 0.8;
        }
        
        .job-body {
            padding: 2.5rem;
        }
        
        .job-section {
            margin-bottom: 2rem;
        }
        
        .job-section h6 {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .job-info-badge {
            background: rgba(74, 107, 255, 0.1);
            color: var(--primary-color);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .header {
                padding: 0.8rem 0;
            }
            
            .logo {
                font-size: 1.5rem;
            }
            
            .job-grid {
                margin-top: 2rem;
            }
            
            .job-card {
                margin-bottom: 1.5rem;
            }
            
            .job-header {
                padding: 1.5rem;
            }
            
            .job-body {
                padding: 1.5rem;
            }
        }
        
        @media (min-width: 1200px) {
            .job-grid .col-lg-4 {
                flex: 0 0 33.333333%;
                max-width: 33.333333%;
            }
        }
        
        /* Prevent buttons from triggering card click */
        .no-card-click {
            pointer-events: auto;
        }
        
        /* Loading spinner */
        .loading-spinner {
            display: none;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <a href="#" class="logo">
                    <i class=" me-2"></i>CAREER
                </a>
                
                <div class="d-flex align-items-center">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="dropdown">
                            <button class="btn btn-login dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle me-2"></i>
                                <?php echo $_SESSION['username'] ?? 'User'; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($_SESSION['role'] === 'employee'): ?>
                                    <li><a class="dropdown-item" href="employee/dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a></li>
                                    <li><a class="dropdown-item" href="employee/application.php"><i class="bi bi-file-text me-2"></i>My Applications</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i>Login
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Page Title -->
            <div class="row mb-4">
                <div class="col-12">
                    <h1 class="display-6 fw-bold mb-3 text-center">Available Job Opportunities</h1>
                    <p class="text-muted mb-0 text-center">Browse through our latest job openings and find your perfect match</p>
                </div>
            </div>
            
            <!-- Job Grid with Better Spacing -->
            <div class="job-grid">
                <?php if (empty($job_posts)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4">
                            <i class="bi bi-briefcase" style="font-size: 4rem; color: #dee2e6;"></i>
                        </div>
                        <h4 class="mb-3">No Job Openings Available</h4>
                        <p class="text-muted mb-4">Check back later for new opportunities <br /> OR <br /> Apply for future opportunities</p>
                        <?php if (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-primary">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login to Get Notified
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($job_posts as $index => $job): ?>
                        <div class="col-lg-4 col-md-6 animate-fade-in-up" style="animation-delay: <?php echo $index * 0.1; ?>s">
                            <div class="job-card h-100" onclick="viewJobDetails(<?php echo $job['id']; ?>, this)">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="position-relative">
                                        <div class="company-icon">
                                            <?php echo strtoupper(substr($job['company'], 0, 2)); ?>
                                        </div>
                                        <span class="job-type-badge job-type-<?php echo strtolower($job['job_type']); ?>">
                                            <?php echo $job['job_type']; ?>
                                        </span>
                                    </div>
                                    
                                    <h5 class="card-title mb-3"><?php echo htmlspecialchars($job['title']); ?></h5>
                                    
                                    <ul class="job-details-list mb-4">
                                        <li>
                                            <i class="bi bi-building"></i>
                                            <?php echo htmlspecialchars($job['company']); ?>
                                        </li>
                                        <li>
                                            <i class="bi bi-geo-alt"></i>
                                            <?php echo htmlspecialchars($job['location']); ?>
                                        </li>
                                        <?php if ($job['salary_range']): ?>
                                        <li>
                                            <i class="bi bi-cash"></i>
                                            <?php echo htmlspecialchars($job['salary_range']); ?>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($job['application_deadline']): ?>
                                        <li>
                                            <i class="bi bi-calendar"></i>
                                            Apply by <?php echo date('M d, Y', strtotime($job['application_deadline'])); ?>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                    
                                    <p class="text-muted small mb-4 flex-grow-1">
                                        <?php echo substr(strip_tags($job['description']), 0, 120); ?>...
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="bi bi-clock me-1"></i>
                                                <?php echo date('M d', strtotime($job['created_at'])); ?>
                                            </small>
                                        </div>
                                        
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'employee'): ?>
                                            <div class="d-grid mt-3">
                                                <a href="employee/apply.php?job_id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-primary btn-sm no-card-click" 
                                                   onclick="event.stopPropagation()">
                                                    <i class="bi bi-send me-2"></i>Apply Now
                                                </a>
                                            </div>
                                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                                            <div class="d-grid mt-3">
                                                <a href="login.php?job_id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-primary btn-sm no-card-click" 
                                                   onclick="event.stopPropagation()">
                                                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to Apply
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Job Details Modal -->
    <div class="modal fade job-details-modal" id="jobDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="job-header">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h4 class="mb-2" id="jobModalTitle">Job Title</h4>
                            <p class="job-company mb-1" id="jobModalCompany">Company Name</p>
                            <p class="job-location mb-0" id="jobModalLocation">Location</p>
                        </div>
                        <span class="job-type-badge" id="jobModalType">Full Time</span>
                    </div>
                </div>
                <div class="job-body">
                    <div class="loading-spinner" id="loadingSpinner"></div>
                    <div id="jobModalContent" style="display: none;">
                        <div class="row mb-4">
                            <div class="col-md-6 mb-2">
                                <div class="job-info-badge">
                                    <i class="bi bi-cash"></i>
                                    <span id="jobModalSalary">Salary not specified</span>
                                </div>
                            </div>
                            <div class="col-md-6 mb-2">
                                <div class="job-info-badge">
                                    <i class="bi bi-calendar"></i>
                                    <span id="jobModalDeadline">No deadline</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="job-section">
                            <h6>Job Description</h6>
                            <p id="jobModalDescription">Description will appear here...</p>
                        </div>
                        
                        <div class="job-section">
                            <h6>Requirements</h6>
                            <p id="jobModalRequirements">Requirements will appear here...</p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                            <small class="text-muted" id="jobModalPostedDate"></small>
                            <div>
                                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" id="applyJobBtn">
                                    <i class="bi bi-send me-2"></i>Apply Now
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Store job data in JavaScript -->
    <script>
        // Store all job data for quick access
        const jobDataStore = {
            <?php foreach ($job_posts as $job): ?>
                <?php echo $job['id']; ?>: {
                    id: <?php echo $job['id']; ?>,
                    title: "<?php echo addslashes($job['title']); ?>",
                    company: "<?php echo addslashes($job['company']); ?>",
                    location: "<?php echo addslashes($job['location']); ?>",
                    job_type: "<?php echo addslashes($job['job_type']); ?>",
                    salary_range: "<?php echo addslashes($job['salary_range'] ?? ''); ?>",
                    application_deadline: "<?php echo $job['application_deadline'] ?? ''; ?>",
                    description: `<?php echo addslashes($job['description']); ?>`,
                    requirements: `<?php echo addslashes($job['requirements'] ?? 'No specific requirements listed.'); ?>`,
                    created_at: "<?php echo $job['created_at']; ?>"
                },
            <?php endforeach; ?>
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // View Job Details Function
        function viewJobDetails(jobId, cardElement) {
            // Show loading spinner
            document.getElementById('loadingSpinner').style.display = 'block';
            document.getElementById('jobModalContent').style.display = 'none';
            
            // Get job data from our stored data
            const jobData = jobDataStore[jobId];
            
            if (!jobData) {
                // If data not in store, try to extract from card
                try {
                    const card = cardElement || event.target.closest('.job-card');
                    const cardBody = card.querySelector('.card-body');
                    
                    jobData = {
                        id: jobId,
                        title: card.querySelector('.card-title').textContent.trim(),
                        company: card.querySelector('.bi-building').parentNode.textContent.trim(),
                        location: card.querySelector('.bi-geo-alt').parentNode.textContent.trim(),
                        job_type: card.querySelector('.job-type-badge').textContent.trim(),
                        salary_range: card.querySelector('.bi-cash') ? 
                            card.querySelector('.bi-cash').parentNode.textContent.trim() : 'Salary not specified',
                        application_deadline: card.querySelector('.bi-calendar') ? 
                            card.querySelector('.bi-calendar').parentNode.textContent.replace('Apply by ', '') : 'No deadline',
                        description: card.querySelector('.text-muted.small').textContent.replace('...', '') + 
                            ' This is a detailed job description including responsibilities and requirements.',
                        requirements: 'Requirements would appear here. This typically includes education, experience, skills, and certifications needed for the position.',
                        created_at: card.querySelector('.bi-clock').parentNode.textContent.trim()
                    };
                } catch (e) {
                    console.error('Error extracting job data:', e);
                    alert('Error loading job details. Please try again.');
                    return;
                }
            }
            
            // Populate modal with job data
            document.getElementById('jobModalTitle').textContent = jobData.title;
            document.getElementById('jobModalCompany').textContent = jobData.company;
            document.getElementById('jobModalLocation').textContent = jobData.location;
            document.getElementById('jobModalType').textContent = jobData.job_type;
            document.getElementById('jobModalType').className = 'job-type-badge job-type-' + jobData.job_type.toLowerCase().replace(' ', '');
            
            document.getElementById('jobModalSalary').textContent = jobData.salary_range || 'Salary not specified';
            
            if (jobData.application_deadline && jobData.application_deadline !== 'No deadline') {
                try {
                    const deadlineDate = new Date(jobData.application_deadline);
                    if (!isNaN(deadlineDate.getTime())) {
                        document.getElementById('jobModalDeadline').textContent = 'Apply by ' + deadlineDate.toLocaleDateString('en-US', { 
                            month: 'short', 
                            day: 'numeric', 
                            year: 'numeric' 
                        });
                    } else {
                        document.getElementById('jobModalDeadline').textContent = jobData.application_deadline;
                    }
                } catch (e) {
                    document.getElementById('jobModalDeadline').textContent = jobData.application_deadline;
                }
            } else {
                document.getElementById('jobModalDeadline').textContent = 'No deadline';
            }
            
            document.getElementById('jobModalDescription').textContent = jobData.description;
            document.getElementById('jobModalRequirements').textContent = jobData.requirements || 'No specific requirements listed.';
            
            // Format and display posted date
            try {
                const postedDate = new Date(jobData.created_at);
                if (!isNaN(postedDate.getTime())) {
                    document.getElementById('jobModalPostedDate').textContent = 'Posted ' + postedDate.toLocaleDateString('en-US', { 
                        month: 'short', 
                        day: 'numeric', 
                        year: 'numeric' 
                    });
                } else {
                    document.getElementById('jobModalPostedDate').textContent = 'Posted ' + jobData.created_at;
                }
            } catch (e) {
                document.getElementById('jobModalPostedDate').textContent = 'Posted ' + jobData.created_at;
            }
            
            // Hide loading spinner and show content
            setTimeout(() => {
                document.getElementById('loadingSpinner').style.display = 'none';
                document.getElementById('jobModalContent').style.display = 'block';
                
                // Show the modal
                const jobModal = new bootstrap.Modal(document.getElementById('jobDetailsModal'));
                jobModal.show();
                
                // Set up apply button
                const applyBtn = document.getElementById('applyJobBtn');
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'employee'): ?>
                    applyBtn.onclick = function() {
                        window.location.href = `employee/apply.php?job_id=${jobData.id}`;
                    };
                <?php else: ?>
                    applyBtn.onclick = function() {
                        jobModal.hide();
                        window.location.href = `login.php?job_id=${jobData.id}`;
                    };
                <?php endif; ?>
            }, 300);
        }
        
        // Prevent card click when clicking on buttons
        document.querySelectorAll('.btn, .no-card-click').forEach(element => {
            element.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        // Reset modal content when hidden
        document.getElementById('jobDetailsModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('loadingSpinner').style.display = 'none';
            document.getElementById('jobModalContent').style.display = 'none';
        });
    </script>
</body>
</html>