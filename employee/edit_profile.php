<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';


// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch current user from employees table
try {
    $stmt = $db->conn->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header('Location: ../auth/login.php');
        exit();
    }

} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$errors = [];
$success = false;

// Handle update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $first_name      = trim($_POST['first_name'] ?? '');
    $last_name       = trim($_POST['last_name'] ?? '');
    $email           = trim($_POST['email'] ?? '');
    $contact_number  = trim($_POST['contact_number'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Required fields
    if ($first_name === '') $errors['first_name'] = 'First name is required';
    if ($last_name === '')  $errors['last_name']  = 'Last name is required';

    if ($email === '') {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }

    // Check if email already exists (other user)
    if ($email !== $user['email']) {
        $stmt = $db->conn->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            $errors['email'] = 'Email already in use';
        }
    }

    // Handle password change
    $password_changed = false;
    if ($current_password || $new_password || $confirm_password) {

        if (empty($current_password)) {
            $errors['current_password'] = 'Current password is required';
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors['current_password'] = 'Current password is wrong';
        }

        if (empty($new_password)) {
            $errors['new_password'] = 'New password required';
        } elseif (strlen($new_password) < 6) {
            $errors['new_password'] = 'Password must be at least 6 characters';
        }

        if ($new_password !== $confirm_password) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (empty($errors)) {
            $password_changed = true;
        }
    }

    // If no errors → Update profile
    if (empty($errors)) {

        try {
            $db->conn->beginTransaction();

            $update_fields = [
                'first_name'     => $first_name,
                'last_name'      => $last_name,
                'email'          => $email,
                'contact_number' => $contact_number
            ];

            if ($password_changed) {
                $update_fields['password'] = password_hash($new_password, PASSWORD_DEFAULT);
            }

            // Build update SQL
            $set_clause = [];
            $params = [];

            foreach ($update_fields as $column => $value) {
                $set_clause[] = "$column = ?";
                $params[] = $value;
            }

            $params[] = $user_id;

            $sql = "UPDATE employees SET " . implode(', ', $set_clause) . " WHERE id = ?";
            $stmt = $db->conn->prepare($sql);
            $stmt->execute($params);

            if ($stmt->rowCount() === 0) {
                throw new Exception("No rows updated");
            }

            $db->conn->commit();

            // Update session values
            $_SESSION['first_name'] = $first_name;
            $_SESSION['last_name']  = $last_name;
            $_SESSION['email']      = $email;

            // Refresh user data
            $stmt = $db->conn->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $success = true;
            $success_message = "Profile updated successfully!";

        } catch (Exception $e) {
            $db->conn->rollBack();
            $errors['general'] = "Update failed. Please try again.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* Profile Page Styles */
        .profile-header {
            background: linear-gradient(135deg, #4a6bff, #667eea);
            color: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #4a6bff;
            margin: 0 auto 1rem;
            border: 4px solid rgba(255, 255, 255, 0.3);
        }
        
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid #e9ecef;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #4a6bff;
            box-shadow: 0 0 0 0.25rem rgba(74, 107, 255, 0.25);
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            z-index: 5;
        }
        
        .password-toggle:hover {
            color: #4a6bff;
        }
        
        .password-input-group {
            position: relative;
        }
        
        /* Mobile Responsiveness */
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem;
                margin: 0 -1rem 1.5rem -1rem;
                width: calc(100% + 2rem);
                border-radius: 0;
            }
            
            .profile-card {
                padding: 1.5rem;
                margin: 0 -0.5rem 1rem -0.5rem;
                width: calc(100% + 1rem);
                border-radius: 0;
                box-shadow: none;
                border: none;
                border-bottom: 1px solid #e9ecef;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            
            .btn-lg {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
        
        @media (max-width: 576px) {
            .profile-header {
                padding: 1.25rem;
            }
            
            .profile-card {
                padding: 1.25rem;
            }
            
            h1 {
                font-size: 1.5rem;
            }
            
            h2 {
                font-size: 1.25rem;
            }
        }
        
        /* Status badges for roles */
        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .role-employee {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
        
        .role-employer {
            background: rgba(0, 123, 255, 0.1);
            color: #007bff;
            border: 1px solid rgba(0, 123, 255, 0.2);
        }
        
        .role-admin {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }
        
        /* Loading spinner */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Validation styles */
        .is-invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + 0.75rem);
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23dc3545' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right calc(0.375em + 0.1875rem) center;
            background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
        }
        
        .invalid-feedback {
            display: block;
            width: 100%;
            margin-top: 0.25rem;
            font-size: 0.875rem;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="dashboard-container">
        <!-- Sidebar -->
        <div class="sidebar d-none d-md-block">
            <?php include 'sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <!-- Success Message -->
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?php echo $errors['general']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php endif; ?>

            <!-- Profile Information Form -->
            <div class="profile-card">
                <h2 class="mb-4">Personal Information</h2>
                <form method="POST" action="" id="profileForm" novalidate>
                    <div class="row">
                        <!-- First Name -->
                        <div class="col-md-6 mb-3">
                            <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['first_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['first_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Last Name -->
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" 
                                   class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['last_name'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['last_name']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Email -->
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" 
                                   class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>" 
                                   id="email" 
                                   name="email" 
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" 
                                   required>
                            <?php if (isset($errors['email'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['email']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Contact Number -->
                        <div class="col-md-6 mb-3">
    <label for="contact_number" class="form-label">Contact Number (UAE)</label>
    <input type="tel" 
           class="form-control <?php echo isset($errors['contact_number']) ? 'is-invalid' : ''; ?>" 
           id="contact_number" 
           name="contact_number" 
           value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>"
           pattern="^(\+971\s?(50|52|54|55|56|58)\s?\d{3}\s?\d{4}|0(50|52|54|55|56|58)\d{7})$"
           placeholder="971 50 123 4567"
           required>
           
    <?php if (isset($errors['contact_number'])): ?>
        <div class="invalid-feedback"><?php echo $errors['contact_number']; ?></div>
    <?php endif; ?>
    
    <div class="form-text">Enter valid UAE mobile number (e.g., +971 50 123 4567)</div>
</div>

                        
                        <!-- Role (Display only) -->
                        <div class="col-12 mb-3">
                            <label class="form-label">Account Type</label>
                            <div>
                                <span class="role-badge role-<?php echo strtolower($user['role'] ?? 'employee'); ?>">
                                    <i class="bi bi-person-badge me-1"></i>
                                    <?php echo ucfirst($user['role'] ?? 'Employee'); ?>
                                </span>
                            </div>
                            <div class="form-text">Account type cannot be changed.</div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Password Change Section -->
                    <h3 class="mb-4">Change Password</h3>
                    <p class="text-muted mb-4">Leave blank if you don't want to change your password</p>
                    
                    <div class="row">
                        <!-- Current Password -->
                        <div class="col-md-6 mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="password-input-group">
                                <input type="password" 
                                       class="form-control <?php echo isset($errors['current_password']) ? 'is-invalid' : ''; ?>" 
                                       id="current_password" 
                                       name="current_password">
                                <button type="button" class="password-toggle" data-target="current_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['current_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['current_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- New Password -->
                        <div class="col-md-6 mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="password-input-group">
                                <input type="password" 
                                       class="form-control <?php echo isset($errors['new_password']) ? 'is-invalid' : ''; ?>" 
                                       id="new_password" 
                                       name="new_password">
                                <button type="button" class="password-toggle" data-target="new_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['new_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['new_password']; ?></div>
                            <?php endif; ?>
                            <div class="form-text">At least 6 characters</div>
                        </div>
                        
                        <!-- Confirm Password -->
                        <div class="col-md-6 mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <div class="password-input-group">
                                <input type="password" 
                                       class="form-control <?php echo isset($errors['confirm_password']) ? 'is-invalid' : ''; ?>" 
                                       id="confirm_password" 
                                       name="confirm_password">
                                <button type="button" class="password-toggle" data-target="confirm_password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?php echo $errors['confirm_password']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Password Strength</label>
                            <div class="progress mb-2" style="height: 6px;">
                                <div id="passwordStrengthBar" class="progress-bar bg-danger" role="progressbar" style="width: 0%"></div>
                            </div>
                            <div id="passwordStrengthText" class="form-text small"></div>
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <!-- Form Actions -->
                    <div class="d-flex justify-content-between">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Account Information Card -->
            <div class="profile-card">
                <h2 class="mb-4">Account Information</h2>
                <div class="row">
                    
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Account Created</label>
                        <div class="form-control bg-light">
                            <?php echo date('F j, Y, g:i a', strtotime($user['created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Last Updated</label>
                        <div class="form-control bg-light">
                            <?php echo date('F j, Y, g:i a', strtotime($user['updated_at'] ?? $user['created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Account Status</label>
                        <div>
                            <span class="badge bg-success">
                                <i class="bi bi-check-circle me-1"></i> Active
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading" style="display: none;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const passwordToggles = document.querySelectorAll('.password-toggle');
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (targetInput.type === 'password') {
                        targetInput.type = 'text';
                        icon.classList.remove('bi-eye');
                        icon.classList.add('bi-eye-slash');
                    } else {
                        targetInput.type = 'password';
                        icon.classList.remove('bi-eye-slash');
                        icon.classList.add('bi-eye');
                    }
                });
            });
            
            // Password strength indicator
            const newPasswordInput = document.getElementById('new_password');
            const passwordStrengthBar = document.getElementById('passwordStrengthBar');
            const passwordStrengthText = document.getElementById('passwordStrengthText');
            
            if (newPasswordInput) {
                newPasswordInput.addEventListener('input', function() {
                    const password = this.value;
                    let strength = 0;
                    let text = '';
                    let color = '';
                    let width = 0;
                    
                    // Check password length
                    if (password.length >= 6) strength++;
                    if (password.length >= 8) strength++;
                    
                    // Check for uppercase letters
                    if (/[A-Z]/.test(password)) strength++;
                    
                    // Check for lowercase letters
                    if (/[a-z]/.test(password)) strength++;
                    
                    // Check for numbers
                    if (/[0-9]/.test(password)) strength++;
                    
                    // Check for special characters
                    if (/[^A-Za-z0-9]/.test(password)) strength++;
                    
                    // Determine strength level
                    switch (strength) {
                        case 0:
                        case 1:
                            text = 'Very Weak';
                            color = 'bg-danger';
                            width = 20;
                            break;
                        case 2:
                            text = 'Weak';
                            color = 'bg-warning';
                            width = 40;
                            break;
                        case 3:
                            text = 'Fair';
                            color = 'bg-info';
                            width = 60;
                            break;
                        case 4:
                            text = 'Good';
                            color = 'bg-primary';
                            width = 80;
                            break;
                        case 5:
                        case 6:
                            text = 'Strong';
                            color = 'bg-success';
                            width = 100;
                            break;
                    }
                    
                    // Update display
                    passwordStrengthBar.className = `progress-bar ${color}`;
                    passwordStrengthBar.style.width = `${width}%`;
                    passwordStrengthText.textContent = text;
                });
            }
            
            // Form validation
            const profileForm = document.getElementById('profileForm');
            if (profileForm) {
                profileForm.addEventListener('submit', function(e) {
                    // Show loading overlay
                    document.getElementById('loadingOverlay').style.display = 'flex';
                    
                    // Basic validation
                    const firstName = document.getElementById('first_name');
                    const lastName = document.getElementById('last_name');
                    const email = document.getElementById('email');
                    const newPassword = document.getElementById('new_password');
                    const confirmPassword = document.getElementById('confirm_password');
                    
                    let isValid = true;
                    
                    // Reset previous error states
                    [firstName, lastName, email].forEach(input => {
                        input.classList.remove('is-invalid');
                    });
                    
                    // Check required fields
                    if (!firstName.value.trim()) {
                        firstName.classList.add('is-invalid');
                        isValid = false;
                    }
                    
                    if (!lastName.value.trim()) {
                        lastName.classList.add('is-invalid');
                        isValid = false;
                    }
                    
                    if (!email.value.trim() || !isValidEmail(email.value)) {
                        email.classList.add('is-invalid');
                        isValid = false;
                    }
                    
                    // Check password match if new password is entered
                    if (newPassword.value.trim()) {
                        if (newPassword.value !== confirmPassword.value) {
                            confirmPassword.classList.add('is-invalid');
                            isValid = false;
                        }
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                        document.getElementById('loadingOverlay').style.display = 'none';
                        
                        // Scroll to first error
                        const firstError = profileForm.querySelector('.is-invalid');
                        if (firstError) {
                            firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            firstError.focus();
                        }
                    }
                });
            }
            
            // Email validation function
            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }
            
            // UAE Phone Number Auto Formatting
const phoneInput = document.getElementById('contact_number');

if (phoneInput) {
    phoneInput.addEventListener('input', function (e) {
        let value = e.target.value.replace(/\D/g, ''); // Only digits

        // If starts with 971 (international format)
        if (value.startsWith("971")) {
            value = value.replace(/^971/, ""); // remove 971 to format separately

            // Format: +971 50 123 4567
            if (value.length <= 2) {
                value = `+971 ${value}`;
            } else if (value.length <= 5) {
                value = `+971 ${value.slice(0, 2)} ${value.slice(2)}`;
            } else if (value.length <= 8) {
                value = `+971 ${value.slice(0, 2)} ${value.slice(2, 5)} ${value.slice(5)}`;
            } else {
                value = `+971 ${value.slice(0, 2)} ${value.slice(2, 5)} ${value.slice(5, 9)}`;
            }

        } 
        // If starts with 05 (local format)
        else if (value.startsWith("05")) {

            // Format: 050 123 4567
            if (value.length <= 3) {
                value = `${value}`;
            } else if (value.length <= 6) {
                value = `${value.slice(0, 3)} ${value.slice(3)}`;
            } else {
                value = `${value.slice(0, 3)} ${value.slice(3, 6)} ${value.slice(6, 10)}`;
            }

        } 
        // Otherwise — allow typing but no formatting yet
        else {
            value = value.slice(0, 12); // Limit digits
        }

        e.target.value = value.trim();
    });
}

            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                // Ctrl + S to save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    profileForm.querySelector('button[type="submit"]').click();
                }
                
                // Escape to cancel
                if (e.key === 'Escape') {
                    window.location.href = 'dashboard.php';
                }
            });
        });
    </script>
</body>
</html>