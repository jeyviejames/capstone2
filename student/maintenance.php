<?php
require_once '../includes/auth.php';
requireStudentLogin();

$student_info = getStudentInfo($_SESSION['student_id']);
$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['submit_request'])) {
    $request_type = sanitizeInput($_POST['request_type']);
    $description = sanitizeInput($_POST['description']);
    $priority = sanitizeInput($_POST['priority']);
    
    if (!$student_info['room_id']) {
        $error = 'You must be assigned to a room to submit maintenance requests';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO maintenance_requests (student_id, room_id, request_type, description, priority, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['student_id'], $student_info['room_id'], $request_type, $description, $priority]);
            $success = 'Maintenance request submitted successfully';
        } catch (PDOException $e) {
            $error = 'Error submitting request: ' . $e->getMessage();
        }
    }
}

// Get student's maintenance requests
try {
    $stmt = $pdo->prepare("SELECT mr.*, r.room_number, b.name as building_name 
                          FROM maintenance_requests mr 
                          JOIN rooms r ON mr.room_id = r.id 
                          JOIN buildings b ON r.building_id = b.id 
                          WHERE mr.student_id = ? 
                          ORDER BY mr.created_at DESC");
    $stmt->execute([$_SESSION['student_id']]);
    $maintenance_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Maintenance requests error: " . $e->getMessage());
    $maintenance_requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Requests - Dormitory Management System</title>
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
                    <a href="maintenance.php" class="nav-link active">
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
                <h1 class="header-title">Maintenance Requests</h1>
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

                <!-- Submit New Request -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-plus me-2"></i>
                            Submit New Maintenance Request
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$student_info['room_id']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                You must be assigned to a room before you can submit maintenance requests.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="request_type" class="form-label">Request Type *</label>
                                            <select class="form-control" id="request_type" name="request_type" required>
                                                <option value="">Select Request Type</option>
                                                <option value="Plumbing">Plumbing</option>
                                                <option value="Electrical">Electrical</option>
                                                <option value="Air Conditioning">Air Conditioning</option>
                                                <option value="Furniture">Furniture</option>
                                                <option value="Lighting">Lighting</option>
                                                <option value="Cleaning">Cleaning</option>
                                                <option value="Security">Security</option>
                                                <option value="Internet/WiFi">Internet/WiFi</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="priority" class="form-label">Priority Level *</label>
                                            <select class="form-control" id="priority" name="priority" required>
                                                <option value="">Select Priority</option>
                                                <option value="low">Low - Can wait</option>
                                                <option value="medium">Medium - Normal</option>
                                                <option value="high">High - Important</option>
                                                <option value="urgent">Urgent - Immediate attention</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description" class="form-label">Description *</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" 
                                              placeholder="Please describe the issue in detail..." required></textarea>
                                    <small class="form-text text-muted">
                                        Be as specific as possible to help our maintenance team address the issue quickly.
                                    </small>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Your Room: <?php echo $student_info['building_name'] . ' - ' . $student_info['room_number']; ?>
                                    </div>
                                    <button type="submit" name="submit_request" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Maintenance Requests -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i>
                            My Maintenance Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($maintenance_requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Maintenance Requests</h4>
                                <p class="text-muted">You haven't submitted any maintenance requests yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Request Type</th>
                                            <th>Description</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Room</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenance_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['request_type']); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="description-preview">
                                                        <?php echo substr(htmlspecialchars($request['description']), 0, 100); ?>
                                                        <?php if (strlen($request['description']) > 100): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $priorityClass = '';
                                                    switch ($request['priority']) {
                                                        case 'urgent': $priorityClass = 'danger'; break;
                                                        case 'high': $priorityClass = 'warning'; break;
                                                        case 'medium': $priorityClass = 'info'; break;
                                                        case 'low': $priorityClass = 'secondary'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $priorityClass; ?>">
                                                        <?php echo ucfirst($request['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($request['status']) {
                                                        case 'completed': $statusClass = 'success'; break;
                                                        case 'in_progress': $statusClass = 'info'; break;
                                                        case 'assigned': $statusClass = 'warning'; break;
                                                        case 'pending': $statusClass = 'secondary'; break;
                                                        case 'cancelled': $statusClass = 'danger'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small>
                                                        <?php echo $request['building_name'] . ' - ' . $request['room_number']; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDateTime($request['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewRequest(<?php echo $request['id']; ?>)">
                                                        <i class="fas fa-eye"></i>
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
        </main>
    </div>

    <!-- Request Details Modal -->
    <div class="modal" id="requestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Maintenance Request Details</h5>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="requestDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        function viewRequest(requestId) {
            // Find request data
            const requests = <?php echo json_encode($maintenance_requests); ?>;
            const request = requests.find(r => r.id == requestId);
            
            if (request) {
                const priorityClass = {
                    'urgent': 'danger',
                    'high': 'warning', 
                    'medium': 'info',
                    'low': 'secondary'
                };
                
                const statusClass = {
                    'completed': 'success',
                    'in_progress': 'info',
                    'assigned': 'warning',
                    'pending': 'secondary',
                    'cancelled': 'danger'
                };
                
                document.getElementById('requestDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Request Type:</strong><br>${request.request_type}</p>
                            <p><strong>Priority:</strong><br>
                                <span class="badge badge-${priorityClass[request.priority]}">${request.priority.charAt(0).toUpperCase() + request.priority.slice(1)}</span>
                            </p>
                            <p><strong>Status:</strong><br>
                                <span class="badge badge-${statusClass[request.status]}">${request.status.replace('_', ' ').charAt(0).toUpperCase() + request.status.replace('_', ' ').slice(1)}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Room:</strong><br>${request.building_name} - ${request.room_number}</p>
                            <p><strong>Submitted:</strong><br>${new Date(request.created_at).toLocaleDateString()}</p>
                            <p><strong>Last Updated:</strong><br>${new Date(request.updated_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Description:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            ${request.description}
                        </div>
                    </div>
                    ${request.assigned_to ? `<p><strong>Assigned To:</strong> ${request.assigned_to}</p>` : ''}
                    ${request.completion_notes ? `
                        <div class="mb-3">
                            <strong>Completion Notes:</strong>
                            <div class="mt-2 p-3 bg-light rounded">
                                ${request.completion_notes}
                            </div>
                        </div>
                    ` : ''}
                `;
                
                DormitorySystem.openModal('requestModal');
            }
        }
    </script>
</body>
</html>