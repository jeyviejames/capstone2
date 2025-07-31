<?php
require_once '../includes/auth.php';
requireStudentLogin();

$student_info = getStudentInfo($_SESSION['student_id']);

// Get student statistics
try {
    // Get announcements count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM announcements WHERE status = 'published'");
    $announcements_count = $stmt->fetch()['count'];
    
    // Get maintenance requests count for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM maintenance_requests WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $maintenance_count = $stmt->fetch()['count'];
    
    // Get room requests count for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM room_requests WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $room_requests_count = $stmt->fetch()['count'];
    
    // Get offense records count for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM offense_logs WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $offenses_count = $stmt->fetch()['count'];
    
    // Get visitor logs count for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM visitor_logs WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $visitors_count = $stmt->fetch()['count'];
    
    // Get complaints count for this student
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ?");
    $stmt->execute([$_SESSION['student_id']]);
    $complaints_count = $stmt->fetch()['count'];
    
    // Get recent announcements
    $stmt = $pdo->query("SELECT * FROM announcements WHERE status = 'published' ORDER BY created_at DESC LIMIT 3");
    $recent_announcements = $stmt->fetchAll();
    
    // Get recent maintenance requests
    $stmt = $pdo->prepare("SELECT mr.*, r.room_number FROM maintenance_requests mr 
                          LEFT JOIN rooms r ON mr.room_id = r.id 
                          WHERE mr.student_id = ? ORDER BY mr.created_at DESC LIMIT 3");
    $stmt->execute([$_SESSION['student_id']]);
    $recent_maintenance = $stmt->fetchAll();
    
    // Get pending room requests
    $stmt = $pdo->prepare("SELECT rr.*, r.room_number, b.name as building_name 
                          FROM room_requests rr 
                          LEFT JOIN rooms r ON rr.requested_room_id = r.id 
                          LEFT JOIN buildings b ON r.building_id = b.id 
                          WHERE rr.student_id = ? AND rr.status = 'pending' 
                          ORDER BY rr.created_at DESC LIMIT 3");
    $stmt->execute([$_SESSION['student_id']]);
    $pending_room_requests = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Student dashboard error: " . $e->getMessage());
    $announcements_count = 0;
    $maintenance_count = 0;
    $room_requests_count = 0;
    $offenses_count = 0;
    $visitors_count = 0;
    $complaints_count = 0;
    $recent_announcements = [];
    $recent_maintenance = [];
    $pending_room_requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Dormitory Management System</title>
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
                    <a href="dashboard.php" class="nav-link active">
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
                    <a href="visitors.php" class="nav-link">
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
                <h1 class="header-title">Student Dashboard</h1>
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
                <!-- Welcome Message -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="mb-2">Welcome back, <?php echo $student_info['first_name']; ?>!</h4>
                                <p class="text-muted mb-0">
                                    Student ID: <?php echo $student_info['student_id']; ?> | 
                                    LRN: <?php echo $student_info['lrn']; ?>
                                    <?php if ($student_info['room_number']): ?>
                                        | Room: <?php echo $student_info['building_name'] . ' - ' . $student_info['room_number']; ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge badge-success fs-6">Status: Active</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="stat-number"><?php echo $announcements_count; ?></div>
                        <div class="stat-label">Active Announcements</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-number"><?php echo $maintenance_count; ?></div>
                        <div class="stat-label">Maintenance Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $room_requests_count; ?></div>
                        <div class="stat-label">Room Requests</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number"><?php echo $visitors_count; ?></div>
                        <div class="stat-label">Visitor Logs</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-comment-alt"></i>
                        </div>
                        <div class="stat-number"><?php echo $complaints_count; ?></div>
                        <div class="stat-label">Complaints Filed</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-number"><?php echo $offenses_count; ?></div>
                        <div class="stat-label">Offense Records</div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <!-- Recent Announcements -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-bullhorn me-2"></i>
                                    Recent Announcements
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_announcements)): ?>
                                    <p class="text-muted text-center py-3">No announcements available</p>
                                <?php else: ?>
                                    <?php foreach ($recent_announcements as $announcement): ?>
                                        <div class="py-2 border-bottom">
                                            <div class="fw-bold"><?php echo htmlspecialchars($announcement['title']); ?></div>
                                            <p class="mb-1 text-muted small"><?php echo substr(htmlspecialchars($announcement['content']), 0, 100) . '...'; ?></p>
                                            <small class="text-muted"><?php echo getTimeAgo($announcement['created_at']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="announcements.php" class="btn btn-sm btn-primary">View All</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Maintenance Requests -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-tools me-2"></i>
                                    My Maintenance Requests
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_maintenance)): ?>
                                    <p class="text-muted text-center py-3">No maintenance requests</p>
                                <?php else: ?>
                                    <?php foreach ($recent_maintenance as $request): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($request['request_type']); ?></div>
                                                <small class="text-muted">Room <?php echo $request['room_number']; ?></small>
                                            </div>
                                            <span class="badge badge-<?php echo $request['status'] == 'completed' ? 'success' : ($request['status'] == 'pending' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($request['status']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="maintenance.php" class="btn btn-sm btn-primary">View All</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Room Requests -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    Pending Room Requests
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_room_requests)): ?>
                                    <p class="text-muted text-center py-3">No pending requests</p>
                                <?php else: ?>
                                    <?php foreach ($pending_room_requests as $request): ?>
                                        <div class="py-2 border-bottom">
                                            <div class="fw-bold">Room Change Request</div>
                                            <small class="text-muted">
                                                To: <?php echo $request['building_name'] . ' - ' . $request['room_number']; ?><br>
                                                Requested: <?php echo getTimeAgo($request['created_at']); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="room_requests.php" class="btn btn-sm btn-primary">View All</a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <a href="maintenance.php" class="btn btn-warning w-100">
                                    <i class="fas fa-tools d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Request Maintenance
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="visitors.php" class="btn btn-info w-100">
                                    <i class="fas fa-user-plus d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Register Visitor
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="room_requests.php" class="btn btn-success w-100">
                                    <i class="fas fa-exchange-alt d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Request Room Change
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="complaints.php" class="btn btn-danger w-100">
                                    <i class="fas fa-comment-alt d-block mb-2" style="font-size: 1.5rem;"></i>
                                    File Complaint
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>