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

// Functions
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password!";
    } elseif (!validateEmail($email)) {
        $error = "Invalid email format!";
    } else {
        try {
            $stmt = $db->prepare("SELECT id, first_name, last_name, email, password, role FROM employees WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Check if it's admin (hard-coded credentials)
                if ($email === 'admin@example.com' && $password === 'Admin@123') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = 'admin';
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    
                    header("Location: admin/dashboard.php");
                    exit();
                }
                // Check employee password
                elseif (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    
                    if ($user['role'] === 'admin') {
                        header("Location: admin/dashboard.php");
                    } else {
                        header("Location: employee/dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid email or password!";
                }
            } else {
                $error = "Invalid email or password!";
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
    <title>Login - Career System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary-color: #4a6bff;
            --primary-dark: #3a5bff;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px;
        }
        
        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .login-left {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-right {
            padding: 3rem;
        }
        
        .login-brand {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .feature-list {
            list-style: none;
            padding: 0;
            margin: 2rem 0;
        }
        
        .feature-list li {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .feature-list i {
            background: rgba(255, 255, 255, 0.2);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(74, 107, 255, 0.1);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color), #667eea);
            color: white;
            padding: 0.75rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(74, 107, 255, 0.3);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .admin-note {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .login-left {
                padding: 2rem;
            }
            
            .login-right {
                padding: 2rem;
            }
            
            body {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-card">
            <div class="row g-0">
                <!-- Left Side - Welcome -->
                <div class="col-lg-6">
                    <div class="login-left">
                        <div class="login-brand">
                            <i class="bi bi-briefcase"></i>
                            <span>Career System</span>
                        </div>
                        <h1 class="mb-4">Welcome Back!</h1>
                        <p class="mb-4">Sign in to access your career dashboard and manage your applications.</p>
                        
                        <ul class="feature-list">
                            <li><i class="bi bi-check-circle"></i> Track application status</li>
                            <li><i class="bi bi-check-circle"></i> Apply for new positions</li>
                            <li><i class="bi bi-check-circle"></i> Manage your profile</li>
                            <li><i class="bi bi-check-circle"></i> Get career updates</li>
                        </ul>
                        
                        <div class="mt-4">
                            <small>New to our platform? <a href="signup.php" class="text-white fw-bold">Create an account</a></small>
                        </div>
                    </div>
                </div>
                
                <!-- Right Side - Login Form -->
                <div class="col-lg-6">
                    <div class="login-right">
                        <h2 class="mb-4">Sign In</h2>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="bi bi-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="bi bi-check-circle me-2"></i>
                                <?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="Enter your email" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                            
                            <button type="submit" class="btn btn-login w-100 mb-3">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
                            </button>
                            
                            
                        </form>
                        
                        <div class="login-footer">
                            <p class="mb-2">Don't have an account? <a href="signup.php">Sign up here</a></p>
                            <p class="mb-0"><a href="forgot-password.php">Forgot your password?</a></p>
                        </div>
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
                icon.className = 'bi bi-eye-slash';
            } else {
                passwordInput.type = 'password';
                icon.className = 'bi bi-eye';
            }
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            });
        }, 5000);
    </script>
</body>
</html>