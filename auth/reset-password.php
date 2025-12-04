<?php
// Enable errors for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



// Database connection
$host = "localhost";
$username = "root";
$password = "";
$dbname = "career";

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Initialize variables
$error = '';
$success = '';
$token = '';
$show_form = false;
$user = null;

// Get token from GET or POST
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = isset($_GET['token']) ? trim($_GET['token']) : '';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = isset($_POST['token']) ? trim($_POST['token']) : '';
}

if (empty($token)) {
    $error = "Invalid or expired reset link. Please request a new password reset.";
} else {
    try {
        // Debug: show token
        // echo "Token: $token";

        // Check if employees table exists
        $tableCheck = $db->query("SHOW TABLES LIKE 'employees'");
        if ($tableCheck->rowCount() == 0) {
            throw new Exception("Table 'employees' does not exist in database!");
        }

        // Check token in employees table
        $stmt = $db->prepare("
            SELECT id, first_name, last_name, email
            FROM employees
            WHERE reset_token = ?
            AND reset_token_expires > NOW()
           
            LIMIT 1
        ");

        if (!$stmt) {
            $errorInfo = $db->errorInfo();
            throw new Exception("Prepare failed: " . implode(" | ", $errorInfo));
        }

        $stmt->execute([$token]);

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            $user['name'] = $user['first_name'] . ' ' . ($user['last_name'] ?? '');
            $show_form = true;
        } else {
            $error = "Invalid or expired reset link. Please request a new password reset.";
        }

    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        error_log("Reset Password PDO Error: " . $e->getMessage());
    } catch (Exception $e) {
        $error = "System error: " . $e->getMessage();
        error_log("Reset Password General Error: " . $e->getMessage());
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit']) && $show_form && $user) {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields!";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long!";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                UPDATE employees
                SET password = ?, reset_token = NULL, reset_token_expires = NULL
                WHERE id = ? AND reset_token = ?
            ");

            if (!$stmt) {
                $errorInfo = $db->errorInfo();
                throw new Exception("Prepare failed: " . implode(" | ", $errorInfo));
            }

            if ($stmt->execute([$hashed_password, $user['id'], $token])) {
                $success = "Password has been reset successfully! You can now <a href='../login.php' style='color:#fff;text-decoration:underline;'>login</a> with your new password.";
                $show_form = false;
            } else {
                $errorInfo = $stmt->errorInfo();
                throw new Exception("Execute failed: " . implode(" | ", $errorInfo));
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Password Update PDO Error: " . $e->getMessage());
        } catch (Exception $e) {
            $error = "System error: " . $e->getMessage();
            error_log("Password Update General Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - Career</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #4a6bff;
    --primary-dark: #3a5bef;
    --secondary: #667eea;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --light: #f8f9ff;
    --dark: #1e293b;
    --gray: #6b7280;
    --gray-light: #e5e7eb;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.12);
    --radius: 16px;
    --radius-sm: 12px;
    --radius-lg: 24px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f5f7ff 0%, #f0f4ff 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: var(--dark);
    line-height: 1.6;
}

.reset-container {
    width: 100%;
    max-width: 440px;
    margin: 0 auto;
}

.reset-card {
    background: white;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.reset-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.card-header {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    padding: 40px 30px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.card-header::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.3;
    animation: float 20s linear infinite;
}

@keyframes float {
    0% { transform: translate(0, 0) rotate(0deg); }
    100% { transform: translate(20px, 20px) rotate(360deg); }
}

.logo {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 14px 28px;
    border-radius: 100px;
    margin-bottom: 20px;
    color: white;
    font-weight: 700;
    font-size: 1.4rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.header-icon {
    width: 80px;
    height: 80px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 2.5rem;
    color: white;
    border: 3px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.header-title {
    color: white;
    font-weight: 700;
    font-size: 1.8rem;
    margin-bottom: 8px;
}

.header-subtitle {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.95rem;
    opacity: 0.9;
}

.card-body {
    padding: 40px 35px;
}

@media (max-width: 576px) {
    .card-body {
        padding: 30px 25px;
    }
    .card-header {
        padding: 30px 20px 25px;
    }
}

/* Alert Styling */
.alert {
    border-radius: var(--radius-sm);
    border: none;
    padding: 18px 20px;
    margin-bottom: 24px;
    font-size: 0.95rem;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    animation: slideDown 0.4s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert-danger {
    background: linear-gradient(135deg, #fee 0%, #fdd 100%);
    color: var(--danger);
    border-left: 4px solid var(--danger);
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: var(--success);
    border-left: 4px solid var(--success);
}

.alert-success a {
    color: var(--success);
    font-weight: 600;
    text-decoration: underline;
    transition: color 0.3s ease;
}

.alert-success a:hover {
    color: #0da271;
}

.alert-icon {
    font-size: 1.2rem;
    flex-shrink: 0;
    margin-top: 2px;
}

/* User Info */
.user-info {
    background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin-bottom: 28px;
    border-left: 4px solid var(--primary);
    display: flex;
    align-items: center;
    gap: 15px;
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.user-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.3rem;
    flex-shrink: 0;
}

.user-details {
    flex: 1;
}

.user-label {
    font-size: 0.85rem;
    color: var(--gray);
    margin-bottom: 5px;
}

.user-name {
    font-weight: 600;
    color: var(--dark);
    font-size: 1.1rem;
}

/* Form Styling */
.form-label {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 10px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-group {
    margin-bottom: 24px;
    position: relative;
}

.form-control {
    border: 2px solid var(--gray-light);
    border-radius: var(--radius-sm);
    padding: 16px 18px;
    font-size: 1rem;
    font-family: 'Poppins', sans-serif;
    transition: all 0.3s ease;
    background: white;
    width: 100%;
}

.form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(74, 107, 255, 0.15);
    outline: none;
}

.form-control::placeholder {
    color: #a0aec0;
}

.password-input-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 15px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray);
    cursor: pointer;
    padding: 8px;
    transition: color 0.3s ease;
    z-index: 2;
}

.toggle-password:hover {
    color: var(--primary);
}

.password-strength {
    margin-top: 10px;
}

.strength-meter {
    height: 4px;
    background: var(--gray-light);
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 6px;
}

.strength-fill {
    height: 100%;
    width: 0%;
    border-radius: 2px;
    transition: width 0.3s ease, background 0.3s ease;
}

.strength-text {
    font-size: 0.85rem;
    color: var(--gray);
}

/* Submit Button */
.btn-submit {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    border: none;
    padding: 18px 30px;
    border-radius: var(--radius-sm);
    font-weight: 600;
    font-size: 1.05rem;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
    position: relative;
    overflow: hidden;
}

.btn-submit::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.6s ease;
}

.btn-submit:hover::before {
    left: 100%;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 25px rgba(74, 107, 255, 0.3);
}

.btn-submit:active {
    transform: translateY(0);
}

.btn-submit:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
    box-shadow: none !important;
}

/* Loading Spinner */
.spinner {
    width: 20px;
    height: 20px;
    border: 3px solid rgba(255,255,255,0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 0.8s linear infinite;
    display: inline-block;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Links */
.links {
    margin-top: 30px;
    padding-top: 25px;
    border-top: 1px solid var(--gray-light);
    text-align: center;
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.link {
    color: var(--gray);
    text-decoration: none;
    font-size: 0.95rem;
    transition: color 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.link:hover {
    color: var(--primary);
    text-decoration: none;
}

.link-icon {
    font-size: 0.9rem;
}

/* Progress Indicator */
.progress-indicator {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 30px;
    margin-bottom: 40px;
}

.progress-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
}

.step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gray-light);
    color: var(--gray);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-bottom: 8px;
    position: relative;
    z-index: 1;
}

.step-number.active {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    box-shadow: 0 5px 15px rgba(74, 107, 255, 0.3);
}

.step-label {
    font-size: 0.85rem;
    color: var(--gray);
    font-weight: 500;
}

.step-label.active {
    color: var(--primary);
    font-weight: 600;
}

.progress-connector {
    position: absolute;
    top: 20px;
    left: 50%;
    width: 60px;
    height: 2px;
    background: var(--gray-light);
    transform: translateX(20px);
}

/* Success State */
.success-state {
    text-align: center;
    padding: 40px 30px;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 25px;
    color: white;
    font-size: 3rem;
    animation: scaleIn 0.6s ease;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

.success-title {
    font-size: 1.8rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 15px;
}

.success-message {
    color: var(--gray);
    margin-bottom: 30px;
    font-size: 1.05rem;
}

/* Animation for form */
@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.form-animation {
    animation: slideUp 0.6s ease;
}

/* Mobile Optimization */
@media (max-width: 480px) {
    body {
        padding: 15px;
        background: #f8f9ff;
    }
    
    .reset-container {
        max-width: 100%;
    }
    
    .reset-card {
        border-radius: 20px;
    }
    
    .card-header {
        padding: 30px 20px;
    }
    
    .logo {
        padding: 12px 20px;
        font-size: 1.2rem;
    }
    
    .header-icon {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
    
    .header-title {
        font-size: 1.5rem;
    }
    
    .card-body {
        padding: 30px 20px;
    }
    
    .form-control {
        padding: 14px 16px;
        font-size: 16px; /* Prevents zoom on iOS */
    }
    
    .btn-submit {
        padding: 16px;
        font-size: 1rem;
    }
    
    .links {
        flex-direction: column;
        gap: 12px;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    body {
        background: #0f172a;
    }
    
    .reset-card {
        background: #1e293b;
        color: #e2e8f0;
    }
    
    .form-control {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }
    
    .user-info {
        background: #1e293b;
        border-left-color: #4a6bff;
    }
    
    .user-name {
        color: #e2e8f0;
    }
    
    .form-label {
        color: #e2e8f0;
    }
    
    .links {
        border-top-color: #334155;
    }
    
    .link {
        color: #94a3b8;
    }
    
    .link:hover {
        color: #4a6bff;
    }
}
</style>
</head>
<body>
<div class="reset-container">
    <div class="reset-card">
        <div class="card-header">
            <div class="logo">
                <i class="fas fa-briefcase"></i>
                ARD PERFUMES
            </div>
            <div class="header-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1 class="header-title">Reset Password</h1>
            <p class="header-subtitle">Create a new secure password for your account</p>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <div>
                        <strong>Error</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-state">
                    <div class="success-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="success-title">Password Reset!</h2>
                    <div class="success-message">
                        <?php echo $success; ?>
                    </div>
                    <a href="../login.php" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        Go to Login
                    </a>
                </div>
            <?php else: ?>
                
                <!-- Progress Indicator -->
                <div class="progress-indicator">
                    <div class="progress-step">
                        <div class="step-number <?php echo $show_form ? 'active' : ''; ?>">1</div>
                        <div class="step-label <?php echo $show_form ? 'active' : ''; ?>">Verify Link</div>
                    </div>
                    <div class="progress-connector"></div>
                    <div class="progress-step">
                        <div class="step-number <?php echo $show_form ? 'active' : ''; ?>">2</div>
                        <div class="step-label <?php echo $show_form ? 'active' : ''; ?>">Set Password</div>
                    </div>
                </div>
                
                <?php if ($show_form && $user): ?>
                    <!-- User Info -->
                    <div class="user-info">
                        <div class="user-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-details">
                            <div class="user-label">Resetting password for:</div>
                            <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                        </div>
                    </div>
                    
                    <!-- Password Form -->
                    <form method="POST" action="" class="form-animation" id="resetForm">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-key"></i>
                                New Password
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" 
                                       name="password" 
                                       class="form-control" 
                                       id="password"
                                       placeholder="Enter your new password"
                                       required 
                                       minlength="6"
                                       autocomplete="new-password">
                                <button type="button" class="toggle-password" id="togglePassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="strength-meter">
                                    <div class="strength-fill" id="strengthFill"></div>
                                </div>
                                <div class="strength-text" id="strengthText">Password strength</div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-key"></i>
                                Confirm Password
                            </label>
                            <div class="password-input-wrapper">
                                <input type="password" 
                                       name="confirm_password" 
                                       class="form-control" 
                                       id="confirm_password"
                                       placeholder="Confirm your new password"
                                       required 
                                       minlength="6"
                                       autocomplete="new-password">
                                <button type="button" class="toggle-password" id="toggleConfirmPassword">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="strength-text" id="passwordMatch"></div>
                        </div>
                        
                        <button type="submit" 
                                name="submit" 
                                class="btn-submit" 
                                id="submitBtn">
                            <i class="fas fa-save"></i>
                            <span id="btnText">Reset Password</span>
                        </button>
                    </form>
                    
                <?php elseif (!$show_form && !$error): ?>
                    <!-- Loading State -->
                    <div class="text-center py-5">
                        <div class="spinner" style="width: 50px; height: 50px; border-width: 4px;"></div>
                        <p class="mt-3 text-muted">Verifying your reset link...</p>
                    </div>
                <?php endif; ?>
                
                <!-- Links -->
                <div class="links">
                    <a href="../login.php" class="link">
                        <i class="fas fa-arrow-left link-icon"></i>
                        Back to Login
                    </a>
                    <a href="../forgot-password.php" class="link">
                        <i class="fas fa-redo link-icon"></i>
                        Request New Reset Link
                    </a>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatch = document.getElementById('passwordMatch');
    const form = document.getElementById('resetForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    
    // Password visibility toggle
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    if (toggleConfirmPassword) {
        toggleConfirmPassword.addEventListener('click', function() {
            const type = confirmPasswordField.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordField.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Password strength checker
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let text = '';
            let color = '';
            
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    text = 'Very Weak';
                    color = '#ef4444';
                    break;
                case 2:
                    text = 'Weak';
                    color = '#f59e0b';
                    break;
                case 3:
                    text = 'Good';
                    color = '#3b82f6';
                    break;
                case 4:
                    text = 'Strong';
                    color = '#10b981';
                    break;
                case 5:
                    text = 'Very Strong';
                    color = '#059669';
                    break;
            }
            
            if (strengthFill) {
                strengthFill.style.width = (strength * 20) + '%';
                strengthFill.style.background = color;
            }
            
            if (strengthText) {
                strengthText.textContent = text;
                strengthText.style.color = color;
            }
        });
    }
    
    // Password match validation
    if (confirmPasswordField && passwordMatch) {
        confirmPasswordField.addEventListener('input', function() {
            if (passwordField.value !== confirmPasswordField.value) {
                passwordMatch.innerHTML = '<span style="color: #ef4444;"><i class="fas fa-times me-1"></i>Passwords do not match</span>';
            } else if (confirmPasswordField.value.length > 0) {
                passwordMatch.innerHTML = '<span style="color: #10b981;"><i class="fas fa-check me-1"></i>Passwords match</span>';
            } else {
                passwordMatch.innerHTML = '';
            }
        });
    }
    
    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 6) {
                e.preventDefault();
                showToast('Password must be at least 6 characters long!', 'error');
                return false;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showToast('Passwords do not match!', 'error');
                return false;
            }
            
            // Show loading state
            if (submitBtn && btnText) {
                submitBtn.disabled = true;
                btnText.innerHTML = 'Resetting Password...';
                submitBtn.innerHTML = '<div class="spinner"></div> Resetting Password...';
            }
            
            return true;
        });
    }
    
    // Toast notification function
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.innerHTML = `
            <div class="toast-content">
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="toast-close"><i class="fas fa-times"></i></button>
        `;
        
        // Add toast styles
        const style = document.createElement('style');
        style.textContent = `
            .toast {
                position: fixed;
                top: 20px;
                right: 20px;
                background: white;
                padding: 15px 20px;
                border-radius: 12px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
                display: flex;
                align-items: center;
                gap: 12px;
                z-index: 1000;
                animation: slideIn 0.3s ease;
                max-width: 350px;
                border-left: 4px solid #4a6bff;
            }
            .toast-error { border-left-color: #ef4444; }
            .toast-success { border-left-color: #10b981; }
            .toast-content {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 1;
            }
            .toast-close {
                background: none;
                border: none;
                color: #6b7280;
                cursor: pointer;
                padding: 5px;
            }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(toast);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.remove();
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }
    
    // Add slideOut animation
    const slideOutStyle = document.createElement('style');
    slideOutStyle.textContent = `
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(slideOutStyle);
    
    // Auto-focus password field on mobile
    if (passwordField && window.innerWidth <= 768) {
        passwordField.focus();
    }
});
</script>
</body>
</html>