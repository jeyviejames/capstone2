<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_location'])) {
        $student_id = sanitizeInput($_POST['student_id']);
        $location_status = sanitizeInput($_POST['location_status']);
        $notes = sanitizeInput($_POST['notes']);
        
        if ($student_id && $location_status) {
            try {
                // Insert new location log
                $stmt = $pdo->prepare("INSERT INTO student_location_logs (student_id, location_status, notes, logged_by, log_time) VALUES (?, ?, ?, ?, NOW())");
                if ($stmt->execute([$student_id, $location_status, $notes, $_SESSION['admin_id']])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'update_student_location', "Updated location for student ID: $student_id to $location_status");
                    $success = true;
                } else {
                    $error = 'Failed to update student location.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['flush_logs'])) {
        $cutoff_date = date('Y-m-d H:i:s', strtotime('-1 week'));
        
        try {
            $stmt = $pdo->prepare("DELETE FROM student_location_logs WHERE log_time < ?");
            $deletedCount = $stmt->execute([$cutoff_date]) ? $stmt->rowCount() : 0;
            logActivity($_SESSION['admin_id'], 'admin', 'flush_location_logs', "Flushed $deletedCount location logs older than 1 week");
            $success = true;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get students with current location
try {
    $stmt = $pdo->prepare("
        SELECT s.*, 
               CONCAT(s.first_name, ' ', s.last_name) as full_name,
               CONCAT(b.name, ' - ', r.room_number) as room_info,
               sll.location_status,
               sll.log_time as last_location_update,
               sll.notes as location_notes
        FROM students s
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN (
            SELECT student_id, location_status, log_time, notes,
                   ROW_NUMBER() OVER (PARTITION BY student_id ORDER BY log_time DESC) as rn
            FROM student_location_logs
        ) sll ON s.id = sll.student_id AND sll.rn = 1
        WHERE s.status = 'approved'
        ORDER BY s.first_name, s.last_name
    ");
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
    $error = 'Failed to fetch student data.';
}

// Get recent location logs
try {
    $stmt = $pdo->prepare("
        SELECT sll.*, 
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               s.student_id as student_number,
               CONCAT(b.name, ' - ', r.room_number) as room_info,
               a.username as logged_by_name
        FROM student_location_logs sll
        LEFT JOIN students s ON sll.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN admins a ON sll.logged_by = a.id
        ORDER BY sll.log_time DESC
        LIMIT 100
    ");
    $stmt->execute();
    $recent_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_logs = [];
}

// Get statistics
$stats = [
    'total_students' => count($students),
    'on_campus' => count(array_filter($students, function($s) { return $s['location_status'] === 'on_campus'; })),
    'in_class' => count(array_filter($students, function($s) { return $s['location_status'] === 'in_class'; })),
    'outside_campus' => count(array_filter($students, function($s) { return $s['location_status'] === 'outside_campus'; })),
    'unknown' => count(array_filter($students, function($s) { return empty($s['location_status']); }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Locator - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="sidebar-header">
                <h4><i class="fas fa-user-shield me-2"></i>Admin Panel</h4>
            </div>
            <ul class="sidebar-nav">
                <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i>Admin Settings</a></li>
                <li><a href="offense_logs.php"><i class="fas fa-exclamation-triangle"></i>Offense Logs</a></li>
                <li><a href="announcements.php"><i class="fas fa-bullhorn"></i>Announcements</a></li>
                <li><a href="maintenance.php"><i class="fas fa-tools"></i>Maintenance Requests</a></li>
                <li><a href="room_requests.php"><i class="fas fa-door-open"></i>Room Requests</a></li>
                <li><a href="biometrics.php"><i class="fas fa-fingerprint"></i>Biometrics</a></li>
                <li><a href="student_locator.php" class="active"><i class="fas fa-map-marker-alt"></i>Student Locator</a></li>
                <li><a href="visitor_logs.php"><i class="fas fa-users"></i>Visitor Logs</a></li>
                <li><a href="room_management.php"><i class="fas fa-bed"></i>Room Management</a></li>
                <li><a href="add_rooms.php"><i class="fas fa-plus-square"></i>Add Rooms & Buildings</a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i>Online Reservations</a></li>
                <li><a href="policies.php"><i class="fas fa-file-contract"></i>Policies</a></li>
                <li><a href="complaints.php"><i class="fas fa-comment-dots"></i>Complaints</a></li>
            </ul>
            <div class="sidebar-footer">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-2"></i><?php echo $_SESSION['admin_username']; ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <div class="content-header">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="content-title">Student Locator & Location Logs</h1>
                        <p class="content-subtitle">Track student locations and manage location logs</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#flushLogsModal">
                            <i class="fas fa-trash me-2"></i>Flush Weekly Logs
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateLocationModal">
                            <i class="fas fa-map-marker-alt me-2"></i>Update Student Location
                        </button>
                    </div>
                </div>
            </div>

            <div class="content">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>Operation completed successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Statistics Cards -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-label">Total Students</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-university"></i></div>
                        <div class="stat-number"><?php echo $stats['on_campus']; ?></div>
                        <div class="stat-label">On Campus</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-chalkboard-teacher"></i></div>
                        <div class="stat-number"><?php echo $stats['in_class']; ?></div>
                        <div class="stat-label">In Class</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-sign-out-alt"></i></div>
                        <div class="stat-number"><?php echo $stats['outside_campus']; ?></div>
                        <div class="stat-label">Outside Campus</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary"><i class="fas fa-question-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['unknown']; ?></div>
                        <div class="stat-label">Unknown Location</div>
                    </div>
                </div>

                <div class="row">
                    <!-- Student Location Status -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0"><i class="fas fa-map-marker-alt me-2"></i>Student Location Status</h5>
                                    <div class="card-tools">
                                        <input type="text" class="form-control" id="searchStudents" placeholder="Search students..." onkeyup="filterStudents()">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (empty($students)): ?>
                                    <div class="text-center py-5">
                                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                        <h4 class="text-muted">No Students Found</h4>
                                        <p class="text-muted">No approved students in the system.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table" id="studentsTable">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Room</th>
                                                    <th>Current Location</th>
                                                    <th>Last Update</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($students as $student): ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo $student['full_name']; ?></strong>
                                                            <br>
                                                            <small class="text-muted">ID: <?php echo $student['student_id']; ?></small>
                                                        </td>
                                                        <td>
                                                            <small><?php echo $student['room_info'] ?: 'Not assigned'; ?></small>
                                                        </td>
                                                        <td>
                                                            <?php if ($student['location_status']): ?>
                                                                <span class="badge badge-<?php 
                                                                    switch($student['location_status']) {
                                                                        case 'on_campus': echo 'success'; break;
                                                                        case 'in_class': echo 'info'; break;
                                                                        case 'outside_campus': echo 'warning'; break;
                                                                        default: echo 'secondary';
                                                                    }
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $student['location_status'])); ?>
                                                                </span>
                                                                <?php if ($student['location_notes']): ?>
                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($student['location_notes']); ?></small>
                                                                <?php endif; ?>
                                                            <?php else: ?>
                                                                <span class="badge badge-secondary">Unknown</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <?php if ($student['last_location_update']): ?>
                                                                <small><?php echo getTimeAgo($student['last_location_update']); ?></small>
                                                            <?php else: ?>
                                                                <small class="text-muted">Never updated</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-primary" onclick="updateStudentLocation(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')">
                                                                <i class="fas fa-map-marker-alt"></i>
                                                            </button>
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

                    <!-- Recent Location Logs -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Recent Location Updates</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_logs)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-clock fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No recent location updates</p>
                                    </div>
                                <?php else: ?>
                                    <div class="timeline">
                                        <?php foreach (array_slice($recent_logs, 0, 10) as $log): ?>
                                            <div class="timeline-item">
                                                <div class="timeline-marker">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                </div>
                                                <div class="timeline-content">
                                                    <h6 class="timeline-title"><?php echo htmlspecialchars($log['student_name'] ?: 'Unknown Student'); ?></h6>
                                                    <p class="timeline-text">
                                                        Location: <span class="badge badge-<?php 
                                                            switch($log['location_status']) {
                                                                case 'on_campus': echo 'success'; break;
                                                                case 'in_class': echo 'info'; break;
                                                                case 'outside_campus': echo 'warning'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?>"><?php echo ucfirst(str_replace('_', ' ', $log['location_status'])); ?></span>
                                                        <?php if ($log['notes']): ?>
                                                            <br><small><?php echo htmlspecialchars($log['notes']); ?></small>
                                                        <?php endif; ?>
                                                    </p>
                                                    <small class="timeline-time">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?php echo getTimeAgo($log['log_time']); ?>
                                                        <br>
                                                        <i class="fas fa-user me-1"></i>
                                                        <?php echo htmlspecialchars($log['logged_by_name'] ?: 'Unknown'); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Update Location Modal -->
    <div class="modal fade" id="updateLocationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-map-marker-alt me-2"></i>Update Student Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student *</label>
                            <select class="form-control" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo $student['full_name']; ?> (<?php echo $student['student_id']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="location_status" class="form-label">Location Status *</label>
                            <select class="form-control" id="location_status" name="location_status" required>
                                <option value="">Select Location</option>
                                <option value="on_campus">On Campus</option>
                                <option value="in_class">In Class</option>
                                <option value="outside_campus">Outside Campus</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Optional notes about the location..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_location" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Location
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Flush Logs Modal -->
    <div class="modal fade" id="flushLogsModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Flush Weekly Location Logs</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This will permanently delete all location logs older than 1 week. This action cannot be undone.
                    </div>
                    <p>Are you sure you want to flush the weekly location logs?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display: inline;">
                        <button type="submit" name="flush_logs" class="btn btn-warning">
                            <i class="fas fa-trash me-2"></i>Flush Logs
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function updateStudentLocation(studentId, studentName) {
            document.getElementById('student_id').value = studentId;
            new bootstrap.Modal(document.getElementById('updateLocationModal')).show();
        }

        function filterStudents() {
            const searchTerm = document.getElementById('searchStudents').value.toLowerCase();
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }
    </script>
</body>
</html>