<?php
require_once '../includes/auth.php';
requireAdminLogin();

$stats = getDashboardStats();
$admin_info = getAdminInfo($_SESSION['admin_id']);

// Get recent activities
try {
    $stmt = $pdo->query("SELECT mr.*, s.first_name, s.last_name, r.room_number 
                        FROM maintenance_requests mr 
                        JOIN students s ON mr.student_id = s.id 
                        JOIN rooms r ON mr.room_id = r.id 
                        WHERE mr.status = 'pending' 
                        ORDER BY mr.created_at DESC LIMIT 5");
    $recent_maintenance = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT ol.*, s.first_name, s.last_name 
                        FROM offense_logs ol 
                        JOIN students s ON ol.student_id = s.id 
                        WHERE ol.status = 'pending' 
                        ORDER BY ol.created_at DESC LIMIT 5");
    $recent_offenses = $stmt->fetchAll();
    
    $stmt = $pdo->query("SELECT * FROM students WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
    $pending_students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard data error: " . $e->getMessage());
    $recent_maintenance = [];
    $recent_offenses = [];
    $pending_students = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Dormitory Management System</title>
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
                    <a href="dashboard.php" class="nav-link active">
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
                <h1 class="header-title">Dashboard</h1>
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
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_rooms'] ?? 0; ?></div>
                        <div class="stat-label">Total Rooms</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['available_rooms'] ?? 0; ?></div>
                        <div class="stat-label">Available Rooms</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-user-graduate"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['total_students'] ?? 0; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['pending_applications'] ?? 0; ?></div>
                        <div class="stat-label">Pending Applications</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['pending_maintenance'] ?? 0; ?></div>
                        <div class="stat-label">Pending Maintenance</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['pending_offenses'] ?? 0; ?></div>
                        <div class="stat-label">Pending Offenses</div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="row">
                    <!-- Pending Student Applications -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-user-plus me-2"></i>
                                    Pending Applications
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($pending_students)): ?>
                                    <p class="text-muted text-center py-3">No pending applications</p>
                                <?php else: ?>
                                    <?php foreach ($pending_students as $student): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-bold"><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></div>
                                                <small class="text-muted">ID: <?php echo $student['student_id']; ?></small>
                                            </div>
                                            <small class="text-muted"><?php echo getTimeAgo($student['created_at']); ?></small>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="reservations.php" class="btn btn-sm btn-primary">View All</a>
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
                                    Recent Maintenance
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_maintenance)): ?>
                                    <p class="text-muted text-center py-3">No pending requests</p>
                                <?php else: ?>
                                    <?php foreach ($recent_maintenance as $request): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-bold"><?php echo $request['request_type']; ?></div>
                                                <small class="text-muted">Room <?php echo $request['room_number']; ?> - <?php echo $request['first_name']; ?></small>
                                            </div>
                                            <span class="badge badge-<?php echo $request['priority'] == 'urgent' ? 'danger' : ($request['priority'] == 'high' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($request['priority']); ?>
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

                    <!-- Recent Offenses -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Recent Offenses
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_offenses)): ?>
                                    <p class="text-muted text-center py-3">No pending offenses</p>
                                <?php else: ?>
                                    <?php foreach ($recent_offenses as $offense): ?>
                                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                                            <div>
                                                <div class="fw-bold"><?php echo $offense['offense_type']; ?></div>
                                                <small class="text-muted"><?php echo $offense['first_name'] . ' ' . $offense['last_name']; ?></small>
                                            </div>
                                            <span class="badge badge-<?php echo $offense['severity'] == 'critical' ? 'danger' : ($offense['severity'] == 'major' ? 'warning' : 'info'); ?>">
                                                <?php echo ucfirst($offense['severity']); ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="text-center mt-3">
                                        <a href="offense_logs.php" class="btn btn-sm btn-primary">View All</a>
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
                                <a href="reservations.php" class="btn btn-success w-100">
                                    <i class="fas fa-user-check d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Approve Applications
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="announcements.php" class="btn btn-info w-100">
                                    <i class="fas fa-bullhorn d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Create Announcement
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="add_rooms.php" class="btn btn-warning w-100">
                                    <i class="fas fa-plus-circle d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Add Rooms
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="offense_logs.php" class="btn btn-danger w-100">
                                    <i class="fas fa-clipboard-list d-block mb-2" style="font-size: 1.5rem;"></i>
                                    Log Offense
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
    <script>
        // Auto-refresh dashboard data every 30 seconds
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>