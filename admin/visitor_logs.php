<?php
require_once '../includes/auth.php';
requireAdminLogin();

// Get visitor logs with student and room info
try {
    $stmt = $pdo->prepare("
        SELECT vl.*,
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               s.student_id as student_number,
               CONCAT(b.name, ' - ', r.room_number) as room_info
        FROM visitor_logs vl
        LEFT JOIN students s ON vl.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY vl.time_in DESC
    ");
    $stmt->execute();
    $visitor_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $visitor_logs = [];
    $error = 'Failed to fetch visitor logs.';
}

// Get statistics
$stats = [
    'total_visitors' => count($visitor_logs),
    'active_visitors' => count(array_filter($visitor_logs, function($v) { return $v['status'] === 'active'; })),
    'completed_visits' => count(array_filter($visitor_logs, function($v) { return $v['status'] === 'completed'; })),
    'today_visitors' => count(array_filter($visitor_logs, function($v) { 
        return date('Y-m-d', strtotime($v['time_in'])) === date('Y-m-d'); 
    }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visitor Logs - Admin Panel</title>
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
                <li><a href="student_locator.php"><i class="fas fa-map-marker-alt"></i>Student Locator</a></li>
                <li><a href="visitor_logs.php" class="active"><i class="fas fa-users"></i>Visitor Logs</a></li>
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
                        <h1 class="content-title">Visitor Logs Management</h1>
                        <p class="content-subtitle">View and monitor all visitor records</p>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="exportVisitorLogs()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
                </div>
            </div>

            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo $stats['total_visitors']; ?></div>
                        <div class="stat-label">Total Visitors</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-user-check"></i></div>
                        <div class="stat-number"><?php echo $stats['active_visitors']; ?></div>
                        <div class="stat-label">Currently Visiting</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-sign-out-alt"></i></div>
                        <div class="stat-number"><?php echo $stats['completed_visits']; ?></div>
                        <div class="stat-label">Completed Visits</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-calendar-day"></i></div>
                        <div class="stat-number"><?php echo $stats['today_visitors']; ?></div>
                        <div class="stat-label">Today's Visitors</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select class="form-control" id="statusFilter" onchange="filterVisitors()">
                                    <option value="">All Status</option>
                                    <option value="active">Currently Visiting</option>
                                    <option value="completed">Completed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" class="form-control" id="dateFilter" onchange="filterVisitors()">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="studentFilter" placeholder="Search student..." onkeyup="filterVisitors()">
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="visitorFilter" placeholder="Search visitor..." onkeyup="filterVisitors()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Visitor Logs Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>All Visitor Records</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($visitor_logs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Visitor Records</h4>
                                <p class="text-muted">No visitors have been registered yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table data-table" id="visitorTable">
                                    <thead>
                                        <tr>
                                            <th>Visitor</th>
                                            <th>Student</th>
                                            <th>Room</th>
                                            <th>Contact</th>
                                            <th>Time In</th>
                                            <th>Time Out</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($visitor_logs as $visitor): ?>
                                            <tr data-status="<?php echo $visitor['status']; ?>" data-date="<?php echo date('Y-m-d', strtotime($visitor['time_in'])); ?>">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($visitor['visitor_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Age: <?php echo $visitor['visitor_age']; ?>
                                                        <br>
                                                        <?php echo htmlspecialchars($visitor['visitor_address']); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <strong><?php echo $visitor['student_name'] ?: 'Unknown Student'; ?></strong>
                                                    <br>
                                                    <small class="text-muted">ID: <?php echo $visitor['student_number'] ?: 'N/A'; ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo $visitor['room_info'] ?: 'Not assigned'; ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($visitor['visitor_contact']); ?></small>
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
                                                    <?php if ($visitor['time_out']): ?>
                                                        <?php 
                                                        $duration = strtotime($visitor['time_out']) - strtotime($visitor['time_in']);
                                                        $hours = floor($duration / 3600);
                                                        $minutes = floor(($duration % 3600) / 60);
                                                        ?>
                                                        <small><?php echo $hours . 'h ' . $minutes . 'm'; ?></small>
                                                    <?php else: ?>
                                                        <?php 
                                                        $duration = time() - strtotime($visitor['time_in']);
                                                        $hours = floor($duration / 3600);
                                                        $minutes = floor(($duration % 3600) / 60);
                                                        ?>
                                                        <small class="text-info"><?php echo $hours . 'h ' . $minutes . 'm'; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo $visitor['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($visitor['status']); ?>
                                                    </span>
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
        function filterVisitors() {
            const statusFilter = document.getElementById('statusFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;
            const studentFilter = document.getElementById('studentFilter').value.toLowerCase();
            const visitorFilter = document.getElementById('visitorFilter').value.toLowerCase();
            const rows = document.querySelectorAll('#visitorTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const date = row.getAttribute('data-date');
                const studentText = row.cells[1].textContent.toLowerCase();
                const visitorText = row.cells[0].textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const dateMatch = !dateFilter || date === dateFilter;
                const studentMatch = !studentFilter || studentText.includes(studentFilter);
                const visitorMatch = !visitorFilter || visitorText.includes(visitorFilter);
                
                if (statusMatch && dateMatch && studentMatch && visitorMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function exportVisitorLogs() {
            const rows = document.querySelectorAll('#visitorTable tbody tr:not([style*="display: none"])');
            let csv = 'Visitor Name,Age,Address,Contact,Student,Student ID,Room,Time In,Time Out,Duration,Status\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const visitorName = cells[0].querySelector('strong').textContent;
                    const visitorDetails = cells[0].textContent.split('\n');
                    const age = visitorDetails[1]?.trim().replace('Age: ', '') || '';
                    const address = visitorDetails[2]?.trim() || '';
                    const contact = cells[3].textContent.trim();
                    const student = cells[1].querySelector('strong').textContent;
                    const studentId = cells[1].textContent.match(/ID: (.+)/)?.[1] || '';
                    const room = cells[2].textContent.trim();
                    const timeIn = cells[4].textContent.trim();
                    const timeOut = cells[5].textContent.trim();
                    const duration = cells[6].textContent.trim();
                    const status = cells[7].textContent.trim();
                    
                    csv += `"${visitorName}","${age}","${address}","${contact}","${student}","${studentId}","${room}","${timeIn}","${timeOut}","${duration}","${status}"\n`;
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'visitor_logs_' + new Date().toISOString().split('T')[0] + '.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>