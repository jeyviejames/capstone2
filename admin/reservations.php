<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = '';
$error = '';

// Handle actions
if ($_POST) {
    if (isset($_POST['action']) && isset($_POST['student_id'])) {
        $student_id = (int)$_POST['student_id'];
        $action = $_POST['action'];
        
        try {
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE students SET status = 'approved' WHERE id = ?");
                $stmt->execute([$student_id]);
                $success = 'Student application approved successfully';
                
                // Log activity
                logActivity($_SESSION['admin_id'], 'admin', 'approve_student', "Approved student ID: $student_id");
                
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE students SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$student_id]);
                $success = 'Student application rejected';
                
                // Log activity
                logActivity($_SESSION['admin_id'], 'admin', 'reject_student', "Rejected student ID: $student_id");
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Get all student applications
try {
    $stmt = $pdo->query("SELECT s.*, r.room_number, b.name as building_name 
                        FROM students s 
                        LEFT JOIN rooms r ON s.room_id = r.id 
                        LEFT JOIN buildings b ON r.building_id = b.id 
                        ORDER BY 
                        CASE 
                            WHEN s.status = 'pending' THEN 1 
                            WHEN s.status = 'approved' THEN 2 
                            WHEN s.status = 'rejected' THEN 3 
                        END, s.created_at DESC");
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Error loading student data: ' . $e->getMessage();
    $students = [];
}

// Get available rooms for assignment
try {
    $stmt = $pdo->query("SELECT r.*, b.name as building_name 
                        FROM rooms r 
                        JOIN buildings b ON r.building_id = b.id 
                        WHERE r.occupied_beds < r.capacity 
                        ORDER BY b.name, r.room_number");
    $available_rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $available_rooms = [];
}

$admin_info = getAdminInfo($_SESSION['admin_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Reservations - Dormitory Management System</title>
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
                    <a href="reservations.php" class="nav-link active">
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
                <h1 class="header-title">Online Reservations Management</h1>
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

                <!-- Statistics Cards -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-clock text-warning"></i>
                        </div>
                        <div class="stat-number">
                            <?php echo count(array_filter($students, function($s) { return $s['status'] == 'pending'; })); ?>
                        </div>
                        <div class="stat-label">Pending Applications</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle text-success"></i>
                        </div>
                        <div class="stat-number">
                            <?php echo count(array_filter($students, function($s) { return $s['status'] == 'approved'; })); ?>
                        </div>
                        <div class="stat-label">Approved Students</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-times-circle text-danger"></i>
                        </div>
                        <div class="stat-number">
                            <?php echo count(array_filter($students, function($s) { return $s['status'] == 'rejected'; })); ?>
                        </div>
                        <div class="stat-label">Rejected Applications</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-bed text-info"></i>
                        </div>
                        <div class="stat-number"><?php echo count($available_rooms); ?></div>
                        <div class="stat-label">Available Rooms</div>
                    </div>
                </div>

                <!-- Search and Filter -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <input type="text" class="form-control table-search" placeholder="Search students..." id="studentSearch">
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="statusFilter">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button class="btn btn-success" onclick="DormitorySystem.refreshPage()">
                                    <i class="fas fa-sync-alt me-2"></i>Refresh
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Applications Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-users me-2"></i>
                            Student Applications
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table data-table" id="studentsTable">
                                <thead>
                                    <tr>
                                        <th data-sortable>Student ID</th>
                                        <th data-sortable>Name</th>
                                        <th data-sortable>Contact</th>
                                        <th data-sortable>Address</th>
                                        <th data-sortable>Status</th>
                                        <th data-sortable>Applied Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['student_id']; ?></td>
                                            <td>
                                                <strong><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></strong>
                                                <br>
                                                <small class="text-muted">LRN: <?php echo $student['lrn']; ?></small>
                                            </td>
                                            <td>
                                                <small>
                                                    <i class="fas fa-phone me-1"></i><?php echo $student['mobile_number']; ?><br>
                                                    <i class="fas fa-envelope me-1"></i><?php echo $student['email']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small>
                                                    <?php echo $student['province'] . ', ' . $student['municipality']; ?><br>
                                                    <?php echo $student['barangay'] . ', ' . $student['street_purok']; ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php
                                                $statusClass = '';
                                                switch ($student['status']) {
                                                    case 'pending': $statusClass = 'warning'; break;
                                                    case 'approved': $statusClass = 'success'; break;
                                                    case 'rejected': $statusClass = 'danger'; break;
                                                }
                                                ?>
                                                <span class="badge badge-<?php echo $statusClass; ?>">
                                                    <?php echo ucfirst($student['status']); ?>
                                                </span>
                                                <?php if ($student['room_number']): ?>
                                                    <br><small class="text-muted">Room: <?php echo $student['building_name'] . ' - ' . $student['room_number']; ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDateTime($student['created_at']); ?></td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button type="button" class="btn btn-sm btn-info" onclick="viewStudent(<?php echo $student['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    
                                                    <?php if ($student['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-sm btn-success" onclick="approveStudent(<?php echo $student['id']; ?>)">
                                                            <i class="fas fa-check"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="rejectStudent(<?php echo $student['id']; ?>)">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($student['attachment_file']): ?>
                                                        <a href="../uploads/student_documents/<?php echo $student['attachment_file']; ?>" 
                                                           target="_blank" class="btn btn-sm btn-warning">
                                                            <i class="fas fa-file"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Student Details Modal -->
    <div class="modal" id="studentModal">
        <div class="modal-content" style="max-width: 800px;">
            <div class="modal-header">
                <h5 class="modal-title">Student Details</h5>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="studentDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Room Assignment Modal -->
    <div class="modal" id="roomModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Room</h5>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="roomAssignmentForm">
                    <input type="hidden" id="assignStudentId" name="student_id">
                    <div class="form-group">
                        <label for="room_id" class="form-label">Select Room</label>
                        <select class="form-control" id="room_id" name="room_id" required>
                            <option value="">Choose available room...</option>
                            <?php foreach ($available_rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    <?php echo $room['building_name'] . ' - Room ' . $room['room_number']; ?>
                                    (<?php echo ($room['capacity'] - $room['occupied_beds']); ?> beds available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bed_number" class="form-label">Bed Number</label>
                        <select class="form-control" id="bed_number" name="bed_number" required>
                            <option value="">Select bed...</option>
                            <option value="1">Bed 1</option>
                            <option value="2">Bed 2</option>
                            <option value="3">Bed 3</option>
                            <option value="4">Bed 4</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="DormitorySystem.closeModal('roomModal')">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="assignRoom()">Assign Room</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Search functionality
        document.getElementById('studentSearch').addEventListener('input', function() {
            filterTable(this.value, document.getElementById('statusFilter').value);
        });
        
        document.getElementById('statusFilter').addEventListener('change', function() {
            filterTable(document.getElementById('studentSearch').value, this.value);
        });
        
        function filterTable(searchTerm, statusFilter) {
            const rows = document.querySelectorAll('#studentsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const status = row.querySelector('.badge').textContent.toLowerCase();
                
                const matchesSearch = text.includes(searchTerm.toLowerCase());
                const matchesStatus = !statusFilter || status.includes(statusFilter.toLowerCase());
                
                row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
            });
        }
        
        function viewStudent(studentId) {
            // Load student details via AJAX
            fetch(`get_student_details.php?id=${studentId}`)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('studentDetails').innerHTML = data;
                    DormitorySystem.openModal('studentModal');
                })
                .catch(error => {
                    DormitorySystem.showAlert('danger', 'Error loading student details');
                });
        }
        
        function approveStudent(studentId) {
            if (confirm('Are you sure you want to approve this student application?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="action" value="approve">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectStudent(studentId) {
            if (confirm('Are you sure you want to reject this student application?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="student_id" value="${studentId}">
                    <input type="hidden" name="action" value="reject">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function assignRoomToStudent(studentId) {
            document.getElementById('assignStudentId').value = studentId;
            DormitorySystem.openModal('roomModal');
        }
        
        function assignRoom() {
            const form = document.getElementById('roomAssignmentForm');
            const formData = new FormData(form);
            
            fetch('assign_room.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    DormitorySystem.showAlert('success', data.message);
                    DormitorySystem.closeModal('roomModal');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    DormitorySystem.showAlert('danger', data.message);
                }
            })
            .catch(error => {
                DormitorySystem.showAlert('danger', 'Error assigning room');
            });
        }
    </script>
</body>
</html>