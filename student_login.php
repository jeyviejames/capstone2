<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (isStudentLoggedIn()) {
    header('Location: student/dashboard.php');
    exit();
}

$error = '';
$info = '';

if ($_POST) {
    $student_id = sanitizeInput($_POST['student_id']);
    $lrn = sanitizeInput($_POST['lrn']);
    
    try {
        // Check student status first
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND lrn = ?");
        $stmt->execute([$student_id, $lrn]);
        $student = $stmt->fetch();
        
        if ($student) {
            switch ($student['status']) {
                case 'pending':
                    $info = 'WAITING TO APPROVED YOUR APPLICATION. PLEASE REFRESH TO UPDATE THE WEB PAGE………';
                    break;
                case 'rejected':
                    $error = 'Thank you for your application. After careful review, we regret to inform you that it was not approved this time. We sincerely apologize for any disappointment this may cause and appreciate your interest.';
                    break;
                case 'approved':
                    if (studentLogin($student_id, $lrn)) {
                        header('Location: student/dashboard.php');
                        exit();
                    } else {
                        $error = 'Login failed. Please try again.';
                    }
                    break;
                default:
                    $error = 'Account status unknown. Please contact administration.';
            }
        } else {
            $error = 'Invalid student ID or LRN. Please check your credentials or register first.';
        }
    } catch (PDOException $e) {
        $error = 'Database error. Please try again later.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card fade-in">
            <div class="login-logo">
                <i class="fas fa-user-graduate"></i>
            </div>
            <h2 class="login-title">Student Portal</h2>
            <p class="text-center mb-4 text-muted">Dormitory Management System</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($info): ?>
                <div class="alert alert-info text-center" role="alert">
                    <i class="fas fa-clock me-2"></i>
                    <?php echo $info; ?>
                    <div class="spinner mt-3"></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="student_id" class="form-label">
                        <i class="fas fa-id-card me-2"></i>Student ID
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="student_id" 
                           name="student_id" 
                           required 
                           pattern="\d{6}"
                           placeholder="Enter your 6-digit student ID"
                           value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="lrn" class="form-label">
                        <i class="fas fa-key me-2"></i>LRN (Learner Reference Number)
                    </label>
                    <div class="position-relative">
                        <input type="text" 
                               class="form-control" 
                               id="lrn" 
                               name="lrn" 
                               required 
                               pattern="\d{12}"
                               placeholder="Enter your 12-digit LRN"
                               value="<?php echo isset($_POST['lrn']) ? htmlspecialchars($_POST['lrn']) : ''; ?>">
                    </div>
                    <small class="form-text text-muted">Use your LRN as your password</small>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-2">
                    <small class="text-muted">Don't have an account?</small>
                </p>
                <a href="student_registration.php" class="btn btn-outline-success btn-sm">
                    <i class="fas fa-user-plus me-2"></i>Register Here
                </a>
            </div>
            
            <div class="text-center mt-3">
                <p class="mb-2">
                    <small class="text-muted">Admin? Login here:</small>
                </p>
                <a href="login.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-user-shield me-2"></i>Admin Portal
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // Auto refresh every 10 seconds if showing waiting message
        <?php if ($info): ?>
        setTimeout(function() {
            location.reload();
        }, 10000);
        <?php endif; ?>
    </script>
</body>
</html>