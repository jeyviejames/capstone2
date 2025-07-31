<?php
require_once '../includes/auth.php';
requireAdminLogin();

$admin_info = getAdminInfo($_SESSION['admin_id']);
$success = '';
$error = '';

if ($_POST) {
    if (isset($_POST['update_credentials'])) {
        $username = sanitizeInput($_POST['username']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Verify current password
        if (!password_verify($current_password, $admin_info['password'])) {
            $error = 'Current password is incorrect';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long';
        } else {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE admins SET username = ?, password = ? WHERE id = ?");
                $stmt->execute([$username, $hashed_password, $_SESSION['admin_id']]);
                
                $_SESSION['admin_username'] = $username;
                $success = 'Credentials updated successfully';
                $admin_info['username'] = $username;
                $admin_info['password'] = $hashed_password;
                
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
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
    <title>Admin Settings - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="main-container">
        <!-- Sidebar -->
        <nav class="sidebar slide-in-left">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <i class="fas fa-building"></i>
                </div>
                <h3 class="sidebar-title">Dormitory MS</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="offense_logs.php" class="nav-link">
                        <i class="nav-icon fas fa-exclamation-triangle"></i>
                        Offense Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="announcements.php" class="nav-link">
                        <i class="nav-icon fas fa-bullhorn"></i>
                        Announcements
                    </a>
                </li>
                <li class="nav-item">
                    <a href="maintenance.php" class="nav-link">
                        <i class="nav-icon fas fa-tools"></i>
                        Maintenance Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a href="room_requests.php" class="nav-link">
                        <i class="nav-icon fas fa-exchange-alt"></i>
                        Room Requests
                    </a>
                </li>
                <li class="nav-item">
                    <a href="biometrics.php" class="nav-link">
                        <i class="nav-icon fas fa-fingerprint"></i>
                        Biometrics
                    </a>
                </li>
                <li class="nav-item">
                    <a href="student_locator.php" class="nav-link">
                        <i class="nav-icon fas fa-map-marker-alt"></i>
                        Student Locator
                    </a>
                </li>
                <li class="nav-item">
                    <a href="visitor_logs.php" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        Visitor Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="room_management.php" class="nav-link">
                        <i class="nav-icon fas fa-bed"></i>
                        Room Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="add_rooms.php" class="nav-link">
                        <i class="nav-icon fas fa-plus-circle"></i>
                        Add Rooms & Buildings
                    </a>
                </li>
                <li class="nav-item">
                    <a href="reservations.php" class="nav-link">
                        <i class="nav-icon fas fa-calendar-check"></i>
                        Online Reservations
                    </a>
                </li>
                <li class="nav-item">
                    <a href="policies.php" class="nav-link">
                        <i class="nav-icon fas fa-file-alt"></i>
                        Policies Management
                    </a>
                </li>
                <li class="nav-item">
                    <a href="complaints.php" class="nav-link">
                        <i class="nav-icon fas fa-comment-alt"></i>
                        Complaints Management
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="header-title">Admin Settings</h1>
                <div class="header-actions">
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($admin_info['username'], 0, 1)); ?>
                            </div>
                            <span><?php echo $admin_info['username']; ?></span>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="settings.php" class="dropdown-item">
                                <i class="fas fa-cog me-2"></i>Settings
                            </a>
                            <a href="logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt me-2"></i>Logout
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Content -->
            <div class="content fade-in">
                <div class="row justify-content-center">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-cog me-2"></i>
                                    Admin Account Settings
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if ($success): ?>
                                    <div class="alert alert-success" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?php echo $success; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($error): ?>
                                    <div class="alert alert-danger" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="username" class="form-label">
                                                    <i class="fas fa-user me-2"></i>Username
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       id="username" 
                                                       name="username" 
                                                       value="<?php echo htmlspecialchars($admin_info['username']); ?>" 
                                                       required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="created_at" class="form-label">
                                                    <i class="fas fa-calendar me-2"></i>Account Created
                                                </label>
                                                <input type="text" 
                                                       class="form-control" 
                                                       value="<?php echo formatDateTime($admin_info['created_at']); ?>" 
                                                       readonly>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-4">
                                    
                                    <h6 class="mb-3">
                                        <i class="fas fa-lock me-2"></i>Change Password
                                    </h6>

                                    <div class="form-group">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <div class="position-relative">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="current_password" 
                                                   name="current_password" 
                                                   required 
                                                   placeholder="Enter current password">
                                            <button type="button" 
                                                    class="btn position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 bg-transparent"
                                                    onclick="togglePassword('current_password')"
                                                    style="z-index: 10;">
                                                <i class="fas fa-eye text-muted" id="current_password-toggle"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="new_password" class="form-label">New Password</label>
                                                <div class="position-relative">
                                                    <input type="password" 
                                                           class="form-control" 
                                                           id="new_password" 
                                                           name="new_password" 
                                                           required 
                                                           placeholder="Enter new password"
                                                           minlength="6">
                                                    <button type="button" 
                                                            class="btn position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 bg-transparent"
                                                            onclick="togglePassword('new_password')"
                                                            style="z-index: 10;">
                                                        <i class="fas fa-eye text-muted" id="new_password-toggle"></i>
                                                    </button>
                                                </div>
                                                <small class="form-text text-muted">Password must be at least 6 characters long</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                                <div class="position-relative">
                                                    <input type="password" 
                                                           class="form-control" 
                                                           id="confirm_password" 
                                                           name="confirm_password" 
                                                           required 
                                                           placeholder="Confirm new password"
                                                           minlength="6">
                                                    <button type="button" 
                                                            class="btn position-absolute top-50 end-0 translate-middle-y me-3 p-0 border-0 bg-transparent"
                                                            onclick="togglePassword('confirm_password')"
                                                            style="z-index: 10;">
                                                        <i class="fas fa-eye text-muted" id="confirm_password-toggle"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a href="dashboard.php" class="btn btn-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                        </a>
                                        <button type="submit" name="update_credentials" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Settings
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- System Information -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-info-circle me-2"></i>
                                    System Information
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>System Version:</strong> 1.0.0</p>
                                        <p><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></p>
                                        <p><strong>Server:</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Database:</strong> MySQL</p>
                                        <p><strong>Last Login:</strong> <?php echo formatDateTime($admin_info['updated_at']); ?></p>
                                        <p><strong>Time Zone:</strong> <?php echo date_default_timezone_get(); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
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

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>