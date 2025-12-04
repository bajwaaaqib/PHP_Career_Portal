<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Initialize variables
$error = '';
$success = '';
$email = '';

// ================= SMTP CONFIGURATION =================
define('SMTP_HOST', 'mail.server');
define('SMTP_PORT', 587); // TLS
define('SMTP_USERNAME', 'example@email.com'); // Zoho email
define('SMTP_PASSWORD', ''); // Use Zoho app password if 2FA enabled
define('SMTP_FROM_EMAIL', 'example@email.com');
define('SMTP_FROM_NAME', 'Career');
define('SMTP_SECURE', 'tls'); // Use 'ssl' if port=465

// ================= FORM SUBMISSION =================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = sanitize($_POST['email']);

    if (empty($email)) {
        $error = "Please enter your email address!";
    } elseif (!validateEmail($email)) {
        $error = "Invalid email format!";
    } else {
        try {
            // Check if employee exists
            $stmt = $db->conn->prepare("SELECT id, first_name, email FROM employees WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() === 0) {
                $error = "No employee found with that email!";
            } else {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Generate reset token
                $token = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Save token in database
                $update = $db->conn->prepare("UPDATE employees SET reset_token=?, reset_token_expires=? WHERE id=?");
                if ($update->execute([$token, $expires, $user['id']])) {

                    $resetLink = "https://" . $_SERVER['HTTP_HOST'] . "/career/auth/reset-password.php?token=" . $token;

                    // Email content
                    $subject = "Password Reset Request - Ard Perfumes";
                    $body = "
                        <div style='font-family:sans-serif; line-height:1.6; color:#333'>
                            <h3>Hello {$user['first_name']},</h3>
                            <p>You requested to reset your password.</p>
                            <p style='text-align:center'>
                                <a href='{$resetLink}' style='background:#4a6bff;color:#fff;padding:10px 20px;
                                border-radius:5px;text-decoration:none;font-weight:bold;'>Reset Password</a>
                            </p>
                            <p>If the button does not work, copy and paste this link into your browser:</p>
                            <p><a href='{$resetLink}'>{$resetLink}</a></p>
                            <p><b>Note:</b> This link is valid for 1 hour.</p>
                            <p>Regards,<br>Ard Perfumes Career Team</p>
                        </div>
                    ";

                    // Send email via PHPMailer
                    $mail = new PHPMailer(true);

                    try {
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->SMTPAuth = true;
                        $mail->Username = SMTP_USERNAME;
                        $mail->Password = SMTP_PASSWORD;
                        $mail->SMTPSecure = SMTP_SECURE;
                        $mail->Port = SMTP_PORT;

                        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
                        $mail->addAddress($user['email'], $user['first_name']);

                        $mail->isHTML(true);
                        $mail->Subject = $subject;
                        $mail->Body = $body;

                        $mail->send();

                        $success = "A password reset link has been sent to your email!";
                        $email = ''; // Clear input field

                    } catch (Exception $e) {
                        $error = "Could not send email. SMTP Error: " . $mail->ErrorInfo;
                    }

                } else {
                    $error = "Failed to generate reset request. Please try again.";
                }
            }

        } catch (PDOException $e) {
            $error = "Database error occurred!";
            error_log("Database error in forgot_password: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Ard Perfumes HR System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --primary: #4a6bff;
    --primary-dark: #3a5bef;
    --primary-light: #e8f0ff;
    --secondary: #667eea;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --dark: #1e293b;
    --gray: #6b7280;
    --gray-light: #e5e7eb;
    --light: #f8f9ff;
    --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
    --shadow-lg: 0 20px 50px rgba(0, 0, 0, 0.12);
    --radius: 16px;
    --radius-sm: 12px;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #f0f4ff 0%, #f5f7ff 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    color: var(--dark);
    line-height: 1.6;
}

.forgot-container {
    width: 100%;
    max-width: 480px;
    margin: 0 auto;
}

.forgot-card {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
    border: 1px solid rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.forgot-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-lg);
}

.card-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    padding: 40px 30px 30px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.card-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at 30% 20%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(74, 107, 255, 0.2) 0%, transparent 50%);
}

.logo-container {
    position: relative;
    z-index: 2;
}

.brand-logo {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: white;
    font-size: 2.5rem;
    box-shadow: 0 10px 30px rgba(74, 107, 255, 0.3);
    border: 3px solid rgba(255, 255, 255, 0.2);
}

.brand-title {
    color: white;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 8px;
    position: relative;
    z-index: 2;
}

.brand-subtitle {
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.95rem;
    opacity: 0.9;
    position: relative;
    z-index: 2;
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

/* Form Styling */
.form-group {
    margin-bottom: 28px;
}

.form-label {
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 12px;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

.input-group {
    position: relative;
    border-radius: var(--radius-sm);
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.input-group-text {
    background: linear-gradient(135deg, var(--primary-light) 0%, #e3e9ff 100%);
    border: 2px solid var(--gray-light);
    border-right: none;
    padding: 0 20px;
    color: var(--primary);
    font-size: 1.1rem;
}

.form-control {
    border: 2px solid var(--gray-light);
    border-left: none;
    border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
    padding: 16px 20px;
    font-size: 1rem;
    font-family: 'Inter', sans-serif;
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
    font-weight: 400;
}

.input-group:focus-within .input-group-text {
    border-color: var(--primary);
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
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

/* Success State */
.success-state {
    text-align: center;
    padding: 40px 30px;
    animation: fadeIn 0.6s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--success) 0%, #34d399 100%);
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
    line-height: 1.6;
}

/* Info Box */
.info-box {
    background: linear-gradient(135deg, var(--primary-light) 0%, #e3e9ff 100%);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin-bottom: 28px;
    border-left: 4px solid var(--primary);
}

.info-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 10px;
}

.info-text {
    color: var(--dark);
    font-size: 0.95rem;
    line-height: 1.6;
}

.info-text ul {
    padding-left: 20px;
    margin: 10px 0;
}

.info-text li {
    margin-bottom: 8px;
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
    
    .forgot-container {
        max-width: 100%;
    }
    
    .forgot-card {
        border-radius: 20px;
    }
    
    .card-header {
        padding: 30px 20px;
    }
    
    .brand-logo {
        width: 70px;
        height: 70px;
        font-size: 2rem;
    }
    
    .brand-title {
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
    
    .forgot-card {
        background: #1e293b;
        color: #e2e8f0;
    }
    
    .form-control {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }
    
    .input-group-text {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }
    
    .form-label {
        color: #e2e8f0;
    }
    
    .info-box {
        background: #1e293b;
    }
    
    .info-text {
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
    
    .success-title {
        color: #e2e8f0;
    }
    
    .success-message {
        color: #94a3b8;
    }
}

/* Password Reset Instructions */
.reset-instructions {
    background: linear-gradient(135deg, #f0f7ff 0%, #e6f0ff 100%);
    border-radius: var(--radius-sm);
    padding: 20px;
    margin-bottom: 25px;
    border-left: 4px solid var(--primary);
}

.instructions-title {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.instructions-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.instructions-list li {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    margin-bottom: 10px;
    color: var(--dark);
    font-size: 0.95rem;
}

.instructions-list li:last-child {
    margin-bottom: 0;
}

.instructions-list li i {
    color: var(--primary);
    margin-top: 3px;
    flex-shrink: 0;
}
</style>
</head>
<body>
<div class="forgot-container">
    <div class="forgot-card">
        <div class="card-header">
            <div class="logo-container">
                <div class="brand-logo">
                    <i class="fas fa-lock"></i>
                </div>
                <h1 class="brand-title">Forgot Password</h1>
                <p class="brand-subtitle">Ard Perfumes HR System</p>
            </div>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle alert-icon"></i>
                    <div>
                        <strong>Unable to Process</strong><br>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success-state">
                    <div class="success-icon">
                        <i class="fas fa-paper-plane"></i>
                    </div>
                    <h2 class="success-title">Reset Link Sent!</h2>
                    <div class="success-message">
                        <?php echo $success; ?>
                        <p class="mt-3 text-muted" style="font-size: 0.9rem;">
                            <i class="fas fa-clock me-1"></i>
                            The reset link will expire soon
                        </p>
                    </div>
                    <a href="login.php" class="btn-submit">
                        <i class="fas fa-sign-in-alt"></i>
                        Return to Login
                    </a>
                </div>
            <?php else: ?>
                
                <!-- Instructions -->
                <div class="reset-instructions">
                    <h4 class="instructions-title">
                        <i class="fas fa-info-circle"></i>
                        Password Reset Instructions
                    </h4>
                    <ul class="instructions-list">
                        <li><i class="fas fa-check-circle"></i> Enter your registered email address</li>
                        <li><i class="fas fa-check-circle"></i> We'll send a reset link to your email</li>
                        <li><i class="fas fa-check-circle"></i> Click the link to set a new password</li>
                        <li><i class="fas fa-check-circle"></i> Link expires soon for security</li>
                    </ul>
                </div>
                
                <!-- Email Form -->
                <form method="POST" action="" class="form-animation" id="forgotForm">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address
                        </label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="fas fa-at"></i>
                            </span>
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   id="email"
                                   value="<?php echo htmlspecialchars($email); ?>"
                                   placeholder="your.email@ardperfumes.com"
                                   required
                                   autocomplete="email"
                                   autofocus>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-info-circle me-1"></i>
                            Enter the email associated with your employee account
                        </small>
                    </div>
                    
                    <button type="submit" 
                            class="btn-submit" 
                            id="submitBtn">
                        <i class="fas fa-paper-plane"></i>
                        <span id="btnText">Send Reset Link</span>
                    </button>
                </form>
                
                <!-- Links -->
                <div class="links">
                    <a href="login.php" class="link">
                        <i class="fas fa-arrow-left link-icon"></i>
                        Back to Login
                    </a>
                    <a href="mailto:hr@ardperfumes.com" class="link">
                        <i class="fas fa-question-circle link-icon"></i>
                        Need Help? Contact HR
                    </a>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('forgotForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const emailInput = document.getElementById('email');
    
    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            const email = emailInput.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showToast('Please enter a valid email address!', 'error');
                emailInput.focus();
                return false;
            }
            
            // Show loading state
            if (submitBtn && btnText) {
                submitBtn.disabled = true;
                btnText.innerHTML = 'Sending Link...';
                submitBtn.innerHTML = '<div class="spinner"></div> Sending Reset Link...';
            }
            
            return true;
        });
    }
    
    // Auto-focus email on mobile
    if (emailInput && window.innerWidth <= 768 && !emailInput.value) {
        emailInput.focus();
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
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
        
        document.body.appendChild(toast);
        
        // Close button
        toast.querySelector('.toast-close').addEventListener('click', () => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        });
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }
    
    // Add input validation feedback
    if (emailInput) {
        emailInput.addEventListener('blur', function() {
            const email = this.value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email && !emailRegex.test(email)) {
                this.style.borderColor = '#ef4444';
                this.style.boxShadow = '0 0 0 4px rgba(239, 68, 68, 0.15)';
            } else {
                this.style.borderColor = '';
                this.style.boxShadow = '';
            }
        });
        
        emailInput.addEventListener('input', function() {
            this.style.borderColor = '';
            this.style.boxShadow = '';
        });
    }
});
</script>
</body>

</html>
