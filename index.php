<?php
session_start();

// Check if user is already logged in and redirect accordingly
if (isset($_SESSION['user_type'])) {
    if ($_SESSION['user_type'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } elseif ($_SESSION['user_type'] === 'student') {
        header('Location: student/dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dormitory Management System</title>
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
            <h1 class="login-title">Dormitory Management System</h1>
            <p class="text-center mb-4 text-muted">Welcome to the Dormitory Management System</p>
            
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="login.php" class="btn btn-primary w-100 py-3">
                        <i class="fas fa-user-shield d-block mb-2" style="font-size: 2rem;"></i>
                        <strong>Admin Portal</strong>
                        <br>
                        <small>Manage dormitory operations</small>
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="student_login.php" class="btn btn-success w-100 py-3">
                        <i class="fas fa-user-graduate d-block mb-2" style="font-size: 2rem;"></i>
                        <strong>Student Portal</strong>
                        <br>
                        <small>Access student services</small>
                    </a>
                </div>
            </div>
            
            <hr class="my-4">
            
            <div class="text-center">
                <p class="mb-2">
                    <small class="text-muted">New student? Register here:</small>
                </p>
                <a href="student_registration.php" class="btn btn-outline-info">
                    <i class="fas fa-user-plus me-2"></i>Student Registration
                </a>
            </div>
            
            <div class="text-center mt-4">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    For assistance, please contact the dormitory administration
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>