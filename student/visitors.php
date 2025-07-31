<?php
require_once '../includes/auth.php';
requireStudentLogin();

$student_info = getStudentInfo($_SESSION['student_id']);
$success = '';
$error = '';

// Handle visitor registration
if ($_POST && isset($_POST['register_visitor'])) {
    $visitor_name = sanitizeInput($_POST['visitor_name']);
    $visitor_age = (int)$_POST['visitor_age'];
    $visitor_address = sanitizeInput($_POST['visitor_address']);
    $visitor_contact = sanitizeInput($_POST['visitor_contact']);
    
    if (!$student_info['room_id']) {
        $error = 'You must be assigned to a room to register visitors';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO visitor_logs (student_id, visitor_name, visitor_age, visitor_address, visitor_contact, room_number, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$_SESSION['student_id'], $visitor_name, $visitor_age, $visitor_address, $visitor_contact, $student_info['room_number']]);
            $success = 'Visitor registered successfully';
        } catch (PDOException $e) {
            $error = 'Error registering visitor: ' . $e->getMessage();
        }
    }
}

// Handle visitor checkout
if ($_POST && isset($_POST['checkout_visitor'])) {
    $visitor_id = (int)$_POST['visitor_id'];
    
    try {
        $stmt = $pdo->prepare("UPDATE visitor_logs SET time_out = NOW(), status = 'completed' WHERE id = ? AND student_id = ?");
        $stmt->execute([$visitor_id, $_SESSION['student_id']]);
        $success = 'Visitor checked out successfully';
    } catch (PDOException $e) {
        $error = 'Error checking out visitor: ' . $e->getMessage();
    }
}

// Get student's visitor logs
try {
    $stmt = $pdo->prepare("SELECT * FROM visitor_logs WHERE student_id = ? ORDER BY time_in DESC");
    $stmt->execute([$_SESSION['student_id']]);
    $visitor_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Visitor logs error: " . $e->getMessage());
    $visitor_logs = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs - Dormitory Management System</title>
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
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3 class="sidebar-title">Student Portal</h3>
            </div>
            
            <ul class="sidebar-nav">
                <li class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        Dashboard
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
                    <a href="visitors.php" class="nav-link active">
                        <i class="nav-icon fas fa-users"></i>
                        Visitor Logs
                    </a>
                </li>
                <li class="nav-item">
                    <a href="complaints.php" class="nav-link">
                        <i class="nav-icon fas fa-comment-alt"></i>
                        Complaints
                    </a>
                </li>
                <li class="nav-item">
                    <a href="offenses.php" class="nav-link">
                        <i class="nav-icon fas fa-exclamation-triangle"></i>
                        Offense Records
                    </a>
                </li>
                <li class="nav-item">
                    <a href="policies.php" class="nav-link">
                        <i class="nav-icon fas fa-file-alt"></i>
                        Dorm Policies
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <h1 class="header-title">Visitor Logs</h1>
                <div class="header-actions">
                    <div class="user-dropdown">
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($student_info['first_name'], 0, 1)); ?>
                            </div>
                            <span><?php echo $student_info['first_name'] . ' ' . $student_info['last_name']; ?></span>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </div>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user me-2"></i>Profile
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

                <!-- Register New Visitor -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-user-plus me-2"></i>
                            Register New Visitor
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$student_info['room_id']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                You must be assigned to a room before you can register visitors.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="visitor_name" class="form-label">Visitor Name *</label>
                                            <input type="text" class="form-control" id="visitor_name" name="visitor_name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="visitor_age" class="form-label">Age *</label>
                                            <input type="number" class="form-control" id="visitor_age" name="visitor_age" min="1" max="100" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="visitor_contact" class="form-label">Contact Number *</label>
                                            <input type="tel" class="form-control" id="visitor_contact" name="visitor_contact" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="visitor_address" class="form-label">Address *</label>
                                    <textarea class="form-control" id="visitor_address" name="visitor_address" rows="2" 
                                              placeholder="Visitor's complete address..." required></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Room: <?php echo $student_info['building_name'] . ' - ' . $student_info['room_number']; ?>
                                        <br>
                                        <small>Visiting hours: 8:00 AM - 8:00 PM</small>
                                    </div>
                                    <button type="submit" name="register_visitor" class="btn btn-primary">
                                        <i class="fas fa-user-plus me-2"></i>Register Visitor
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Visitor Logs -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i>
                            My Visitor Logs
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($visitor_logs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Visitor Records</h4>
                                <p class="text-muted">You haven't registered any visitors yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Visitor Name</th>
                                            <th>Age</th>
                                            <th>Contact</th>
                                            <th>Address</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($visitor_logs as $visitor): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($visitor['visitor_name']); ?></strong>
                                                </td>
                                                <td><?php echo $visitor['visitor_age']; ?></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($visitor['visitor_contact']); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($visitor['visitor_address']); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDateTime($visitor['time_in']); ?></small>
                                                </td>
                                                <td>
                                                    <?php if ($visitor['time_out']): ?>
                                                        <small><?php echo formatDateTime($visitor['time_out']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Still visiting</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $visitor['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($visitor['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($visitor['status'] == 'active'): ?>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="checkoutVisitor(<?php echo $visitor['id']; ?>)">
                                                            <i class="fas fa-sign-out-alt"></i> Time Out
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">Completed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        function checkoutVisitor(visitorId) {
            if (confirm('Are you sure you want to check out this visitor?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="visitor_id" value="${visitorId}">
                    <input type="hidden" name="checkout_visitor" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>