<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: admin/dashboard.php');
    exit();
}

$error = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    
    if (adminLogin($username, $password)) {
        header('Location: admin/dashboard.php');
        exit();
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-logo">
                <i class="fas fa-building"></i>
            </div>
            <h2 class="login-title">Dormitory Management System</h2>
            <p class="text-center mb-4 text-muted">Admin Portal</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user me-2"></i>Username
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           required 
                           placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock me-2"></i>Password
                    </label>
                    <div class="position-relative">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Enter your password">
                        <button type="button" 
                                class="btn position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 bg-transparent"
                                onclick="togglePassword('password')"
                                style="z-index: 10;">
                            <i class="fas fa-eye text-muted" id="password-toggle"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-2">
                    <small class="text-muted">Student? Login here:</small>
                </p>
                <a href="student_login.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-user-graduate me-2"></i>Student Portal
                </a>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    Default credentials: <strong>Bsit_batch_22</strong> / <strong>Bsit_batch_22</strong>
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggle = document.getElementById(fieldId + '-toggle');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>