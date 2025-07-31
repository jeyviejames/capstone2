<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_complaint'])) {
        $complaint_id = sanitizeInput($_POST['complaint_id']);
        $status = sanitizeInput($_POST['status']);
        $admin_response = sanitizeInput($_POST['admin_response']);
        
        if ($complaint_id && $status) {
            try {
                $stmt = $pdo->prepare("UPDATE complaints SET status=?, admin_response=?, admin_id=?, admin_responded_at=CURRENT_TIMESTAMP, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                if ($stmt->execute([$status, $admin_response, $_SESSION['admin_id'], $complaint_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'update_complaint', "Updated complaint ID: $complaint_id");
                    $success = true;
                } else {
                    $error = 'Failed to update complaint.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}

// Get complaints with student info
try {
    $stmt = $pdo->prepare("
        SELECT c.*,
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               s.student_id as student_number,
               CONCAT(b.name, ' - ', r.room_number) as room_info,
               a.username as admin_username
        FROM complaints c
        LEFT JOIN students s ON c.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        LEFT JOIN admins a ON c.admin_id = a.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute();
    $complaints = $stmt->fetchAll();
} catch (PDOException $e) {
    $complaints = [];
    $error = 'Failed to fetch complaints.';
}

// Get statistics
$stats = [
    'total_complaints' => count($complaints),
    'pending' => count(array_filter($complaints, function($c) { return $c['status'] === 'pending'; })),
    'in_progress' => count(array_filter($complaints, function($c) { return $c['status'] === 'in_progress'; })),
    'resolved' => count(array_filter($complaints, function($c) { return $c['status'] === 'resolved'; })),
    'closed' => count(array_filter($complaints, function($c) { return $c['status'] === 'closed'; }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaints Management - Admin Panel</title>
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
                <li><a href="visitor_logs.php"><i class="fas fa-users"></i>Visitor Logs</a></li>
                <li><a href="room_management.php"><i class="fas fa-bed"></i>Room Management</a></li>
                <li><a href="add_rooms.php"><i class="fas fa-plus-square"></i>Add Rooms & Buildings</a></li>
                <li><a href="reservations.php"><i class="fas fa-calendar-check"></i>Online Reservations</a></li>
                <li><a href="policies.php"><i class="fas fa-file-contract"></i>Policies</a></li>
                <li><a href="complaints.php" class="active"><i class="fas fa-comment-dots"></i>Complaints</a></li>
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
                        <h1 class="content-title">Complaints Management</h1>
                        <p class="content-subtitle">Review and address student complaints</p>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="exportComplaints()">
                        <i class="fas fa-download me-2"></i>Export Data
                    </button>
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

                <!-- Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="fas fa-comment-dots"></i></div>
                        <div class="stat-number"><?php echo $stats['total_complaints']; ?></div>
                        <div class="stat-label">Total Complaints</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-cogs"></i></div>
                        <div class="stat-number"><?php echo $stats['in_progress']; ?></div>
                        <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['resolved']; ?></div>
                        <div class="stat-label">Resolved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary"><i class="fas fa-archive"></i></div>
                        <div class="stat-number"><?php echo $stats['closed']; ?></div>
                        <div class="stat-label">Closed</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select class="form-control" id="statusFilter" onchange="filterComplaints()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="resolved">Resolved</option>
                                    <option value="closed">Closed</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="categoryFilter" onchange="filterComplaints()">
                                    <option value="">All Categories</option>
                                    <option value="room_issues">Room Issues</option>
                                    <option value="maintenance">Maintenance</option>
                                    <option value="noise_disturbance">Noise Disturbance</option>
                                    <option value="staff_behavior">Staff Behavior</option>
                                    <option value="facility_cleanliness">Facility Cleanliness</option>
                                    <option value="security_concerns">Security Concerns</option>
                                    <option value="policy_concerns">Policy Concerns</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="priorityFilter" onchange="filterComplaints()">
                                    <option value="">All Priority</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search complaints..." onkeyup="filterComplaints()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Complaints Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Student Complaints</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($complaints)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comment-dots fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Complaints</h4>
                                <p class="text-muted">No student complaints have been submitted yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table data-table" id="complaintsTable">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Category</th>
                                            <th>Subject</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($complaints as $complaint): ?>
                                            <tr data-status="<?php echo $complaint['status']; ?>" data-category="<?php echo $complaint['category']; ?>" data-priority="<?php echo $complaint['priority']; ?>">
                                                <td>
                                                    <strong><?php echo $complaint['student_name'] ?: 'Unknown Student'; ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        ID: <?php echo $complaint['student_number'] ?: 'N/A'; ?>
                                                        <?php if ($complaint['room_info']): ?>
                                                            <br>Room: <?php echo $complaint['room_info']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['category'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($complaint['subject']); ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars(substr($complaint['description'], 0, 100)); ?>
                                                        <?php if (strlen($complaint['description']) > 100): ?>...<?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        switch($complaint['priority']) {
                                                            case 'high': echo 'danger'; break;
                                                            case 'medium': echo 'warning'; break;
                                                            case 'low': echo 'info'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($complaint['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        switch($complaint['status']) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'in_progress': echo 'info'; break;
                                                            case 'resolved': echo 'success'; break;
                                                            case 'closed': echo 'secondary'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $complaint['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo formatDateTime($complaint['created_at']); ?></small></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-info" onclick="viewComplaint(<?php echo htmlspecialchars(json_encode($complaint)); ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="respondComplaint(<?php echo htmlspecialchars(json_encode($complaint)); ?>)">
                                                            <i class="fas fa-reply"></i>
                                                        </button>
                                                    </div>
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

    <!-- View Complaint Modal -->
    <div class="modal fade" id="viewComplaintModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Complaint Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Student Information</h6>
                            <p><strong>Name:</strong> <span id="view_student_name"></span></p>
                            <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
                            <p><strong>Room:</strong> <span id="view_room_info"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Complaint Information</h6>
                            <p><strong>Category:</strong> <span id="view_category_badge"></span></p>
                            <p><strong>Priority:</strong> <span id="view_priority_badge"></span></p>
                            <p><strong>Status:</strong> <span id="view_status_badge"></span></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Subject</h6>
                    <p id="view_subject"></p>
                    <h6>Description</h6>
                    <p id="view_description"></p>
                    <div id="view_admin_response_section" style="display: none;">
                        <hr>
                        <h6>Admin Response</h6>
                        <p id="view_admin_response"></p>
                        <small class="text-muted">
                            Responded by: <span id="view_admin_name"></span> on <span id="view_response_date"></span>
                        </small>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Submitted: <span id="view_created_at"></span>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Updated: <span id="view_updated_at"></span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Respond to Complaint Modal -->
    <div class="modal fade" id="respondComplaintModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-reply me-2"></i>Respond to Complaint</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="respond_complaint_id" name="complaint_id">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h6 class="card-title">Complaint: <span id="respond_subject"></span></h6>
                                <p class="card-text" id="respond_description"></p>
                                <small class="text-muted">
                                    From: <span id="respond_student_name"></span> | 
                                    Category: <span id="respond_category"></span> | 
                                    Priority: <span id="respond_priority"></span>
                                </small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">Status *</label>
                            <select class="form-control" id="status" name="status" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="admin_response" class="form-label">Admin Response *</label>
                            <textarea class="form-control" id="admin_response" name="admin_response" rows="6" required placeholder="Enter your response to the student's complaint..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_complaint" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Submit Response
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function viewComplaint(complaint) {
            document.getElementById('view_student_name').textContent = complaint.student_name || 'Unknown Student';
            document.getElementById('view_student_id').textContent = complaint.student_number || 'N/A';
            document.getElementById('view_room_info').textContent = complaint.room_info || 'Not assigned';
            document.getElementById('view_subject').textContent = complaint.subject;
            document.getElementById('view_description').textContent = complaint.description;
            document.getElementById('view_created_at').textContent = formatDateTime(complaint.created_at);
            document.getElementById('view_updated_at').textContent = formatDateTime(complaint.updated_at);
            
            // Category badge
            document.getElementById('view_category_badge').innerHTML = 
                `<span class="badge badge-info">${complaint.category.replace('_', ' ').toUpperCase()}</span>`;
            
            // Priority badge
            const priorityColors = {high: 'danger', medium: 'warning', low: 'info'};
            document.getElementById('view_priority_badge').innerHTML = 
                `<span class="badge badge-${priorityColors[complaint.priority] || 'secondary'}">${complaint.priority.toUpperCase()}</span>`;
            
            // Status badge
            const statusColors = {pending: 'warning', in_progress: 'info', resolved: 'success', closed: 'secondary'};
            document.getElementById('view_status_badge').innerHTML = 
                `<span class="badge badge-${statusColors[complaint.status] || 'secondary'}">${complaint.status.replace('_', ' ').toUpperCase()}</span>`;
            
            // Admin response
            if (complaint.admin_response) {
                document.getElementById('view_admin_response').textContent = complaint.admin_response;
                document.getElementById('view_admin_name').textContent = complaint.admin_username || 'Unknown Admin';
                document.getElementById('view_response_date').textContent = formatDateTime(complaint.admin_responded_at);
                document.getElementById('view_admin_response_section').style.display = 'block';
            } else {
                document.getElementById('view_admin_response_section').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewComplaintModal')).show();
        }

        function respondComplaint(complaint) {
            document.getElementById('respond_complaint_id').value = complaint.id;
            document.getElementById('respond_subject').textContent = complaint.subject;
            document.getElementById('respond_description').textContent = complaint.description;
            document.getElementById('respond_student_name').textContent = complaint.student_name || 'Unknown Student';
            document.getElementById('respond_category').textContent = complaint.category.replace('_', ' ');
            document.getElementById('respond_priority').textContent = complaint.priority;
            document.getElementById('status').value = complaint.status;
            document.getElementById('admin_response').value = complaint.admin_response || '';
            
            new bootstrap.Modal(document.getElementById('respondComplaintModal')).show();
        }

        function filterComplaints() {
            const statusFilter = document.getElementById('statusFilter').value;
            const categoryFilter = document.getElementById('categoryFilter').value;
            const priorityFilter = document.getElementById('priorityFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#complaintsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const category = row.getAttribute('data-category');
                const priority = row.getAttribute('data-priority');
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const categoryMatch = !categoryFilter || category === categoryFilter;
                const priorityMatch = !priorityFilter || priority === priorityFilter;
                const textMatch = !searchTerm || text.includes(searchTerm);
                
                if (statusMatch && categoryMatch && priorityMatch && textMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function exportComplaints() {
            const rows = document.querySelectorAll('#complaintsTable tbody tr:not([style*="display: none"])');
            let csv = 'Student,Student ID,Room,Category,Subject,Description,Priority,Status,Submitted Date,Admin Response\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 0) {
                    const studentName = cells[0].querySelector('strong').textContent;
                    const studentId = cells[0].textContent.match(/ID: (.+)/)?.[1] || 'N/A';
                    const room = cells[0].textContent.match(/Room: (.+)/)?.[1] || 'N/A';
                    const category = cells[1].textContent;
                    const subject = cells[2].querySelector('strong').textContent;
                    const description = cells[2].textContent.replace(subject, '').replace(/"/g, '""').trim();
                    const priority = cells[3].textContent;
                    const status = cells[4].textContent;
                    const date = cells[5].textContent;
                    
                    csv += `"${studentName}","${studentId}","${room}","${category}","${subject}","${description}","${priority}","${status}","${date}",""\n`;
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'complaints_' + new Date().toISOString().split('T')[0] + '.csv';
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