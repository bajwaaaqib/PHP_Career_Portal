<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = sanitize($_POST['first_name']);
    $last_name = sanitize($_POST['last_name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $contact_number = sanitize($_POST['contact_number']);
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "All fields are required!";
    } elseif (!validateEmail($email)) {
        $error = "Invalid email format!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } else {
        try {
            // Check if email exists
            $stmt = $db->conn->prepare("SELECT id FROM employees WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = "Email already registered!";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->conn->prepare("
                    INSERT INTO employees (first_name, last_name, email, password, contact_number, role) 
                    VALUES (?, ?, ?, ?, ?, 'employee')
                ");
                
                if ($stmt->execute([$first_name, $last_name, $email, $hashed_password, $contact_number])) {
                    $success = "Registration successful! You can now login.";
                    // Clear form
                    $_POST = [];
                } else {
                    $error = "Registration failed. Please try again.";
                }
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
    <title>Sign Up - CareerConnect</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 2rem 0;
        }
        
        .register-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .register-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            min-height: 600px;
        }
        
        .register-left {
            background: var(--gradient);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        .register-left::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .register-left-content {
            position: relative;
            z-index: 1;
        }
        
        .register-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            font-size: 2rem;
        }
        
        .register-left h2 {
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 2.2rem;
        }
        
        .register-left p {
            opacity: 0.9;
            margin-bottom: 2rem;
            font-size: 1.1rem;
        }
        
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        
        .benefits-list li {
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            font-size: 1rem;
        }
        
        .benefits-list li i {
            width: 24px;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .register-right {
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        
        .register-header h3 {
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 1.8rem;
        }
        
        .register-header p {
            color: #666;
            font-size: 0.95rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 0.8rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 107, 255, 0.15);
        }
        
        .password-strength {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .strength-meter {
            height: 100%;
            width: 0;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .btn-register {
            background: var(--gradient);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .login-link {
            text-align: center;
            margin-top: 2rem;
            color: #666;
        }
        
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .alert {
            border-radius: 10px;
            border: none;
            padding: 1rem 1.5rem;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
        }
        
        .alert-success {
            background: #e8f7ee;
            color: #28a745;
        }
        
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .register-left,
            .register-right {
                padding: 2rem;
            }
            
            .register-left {
                padding-bottom: 3rem;
            }
            
            .register-left h2 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container register-container">
        <div class="register-card">
            <div class="row g-0">
                <!-- Left Side - Benefits -->
                <div class="col-lg-6">
                    <div class="register-left">
                        <div class="register-left-content">
                            <div class="register-icon">
                                <i class="bi bi-person-plus"></i>
                            </div>
                            <h2>Join CareerConnect Today</h2>
                            <p>Create your account and unlock amazing career opportunities</p>
                            
                            <ul class="benefits-list">
                                <li>
                                    <i class="bi bi-briefcase"></i>
                                    <span>Access to thousands of job opportunities</span>
                                </li>
                                <li>
                                    <i class="bi bi-clock"></i>
                                    <span>Track your applications in real-time</span>
                                </li>
                                <li>
                                    <i class="bi bi-bell"></i>
                                    <span>Get notified about new job matches</span>
                                </li>
                                <li>
                                    <i class="bi bi-file-text"></i>
                                    <span>Manage your profile and resume easily</span>
                                </li>
                                <li>
                                    <i class="bi bi-shield-check"></i>
                                    <span>Secure and private account protection</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Registration Form -->
                <div class="col-lg-6">
                    <div class="register-right">
                        <div class="register-header">
                            <h3>Create Your Account</h3>
                            <p>Fill in your details to get started</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger mb-4">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success mb-4">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="registerForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo $_POST['first_name'] ?? ''; ?>" 
                                           placeholder="Enter your first name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo $_POST['last_name'] ?? ''; ?>" 
                                           placeholder="Enter your last name" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo $_POST['email'] ?? ''; ?>" 
                                           placeholder="you@example.com" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-telephone"></i>
                                    </span>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" 
                                           value="<?php echo $_POST['contact_number'] ?? ''; ?>" 
                                           placeholder="Enter your phone number">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Create a password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="strength-meter" id="passwordStrength"></div>
                                </div>
                                <div class="password-requirements">
                                    <small>Must be at least 6 characters long</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                           placeholder="Confirm your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="toggleConfirmPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="mt-1"></div>
                            </div>
                            
                            <div class="d-grid mb-3">
                                <button type="submit" class="btn btn-register">
                                    <i class="bi bi-person-plus me-2"></i>Create Account
                                </button>
                            </div>
                            
                            <div class="login-link">
                                <p>Already have an account? <a href="login.php">Sign in here</a></p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Toggle confirm password visibility
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const confirmPasswordInput = document.getElementById('confirm_password');
            const icon = this.querySelector('i');
            
            if (confirmPasswordInput.type === 'password') {
                confirmPasswordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                confirmPasswordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });
        
        // Password strength meter
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthMeter = document.getElementById('passwordStrength');
            const strengthText = document.getElementById('passwordMatch');
            
            let strength = 0;
            
            if (password.length >= 6) strength += 25;
            if (/[A-Z]/.test(password)) strength += 25;
            if (/[0-9]/.test(password)) strength += 25;
            if (/[^A-Za-z0-9]/.test(password)) strength += 25;
            
            // Update strength meter
            strengthMeter.style.width = strength + '%';
            
            // Update color based on strength
            if (strength < 50) {
                strengthMeter.style.backgroundColor = '#dc3545';
            } else if (strength < 75) {
                strengthMeter.style.backgroundColor = '#ffc107';
            } else {
                strengthMeter.style.backgroundColor = '#28a745';
            }
            
            // Check password match
            const confirmPassword = document.getElementById('confirm_password').value;
            checkPasswordMatch();
        });
        
        // Check password match
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);
        
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<small class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Passwords match</small>';
            } else {
                matchDiv.innerHTML = '<small class="text-danger"><i class="bi bi-exclamation-circle-fill me-1"></i>Passwords do not match</small>';
            }
        }
        
        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Please make sure your passwords match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>
</html>