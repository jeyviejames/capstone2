<?php
require_once '../includes/auth.php';
requireStudentLogin();

$student_info = getStudentInfo($_SESSION['student_id']);
$success = '';
$error = '';

// Handle form submission
if ($_POST && isset($_POST['submit_request'])) {
    $requested_room_id = (int)$_POST['requested_room_id'];
    $reason = sanitizeInput($_POST['reason']);
    
    try {
        // Check if student already has a pending request
        $stmt = $pdo->prepare("SELECT id FROM room_requests WHERE student_id = ? AND status = 'pending'");
        $stmt->execute([$_SESSION['student_id']]);
        if ($stmt->fetch()) {
            $error = 'You already have a pending room request. Please wait for it to be processed.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO room_requests (student_id, current_room_id, requested_room_id, reason, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->execute([$_SESSION['student_id'], $student_info['room_id'], $requested_room_id, $reason]);
            $success = 'Room change request submitted successfully';
        }
    } catch (PDOException $e) {
        $error = 'Error submitting request: ' . $e->getMessage();
    }
}

// Get available rooms
try {
    $stmt = $pdo->query("SELECT r.*, b.name as building_name, (r.capacity - r.occupied_beds) as available_beds
                        FROM rooms r 
                        JOIN buildings b ON r.building_id = b.id 
                        WHERE r.occupied_beds < r.capacity AND r.status = 'available'
                        ORDER BY b.name, r.room_number");
    $available_rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $available_rooms = [];
}

// Get student's room requests
try {
    $stmt = $pdo->prepare("SELECT rr.*, 
                          cr.room_number as current_room_number, cb.name as current_building_name,
                          rr2.room_number as requested_room_number, rb.name as requested_building_name
                          FROM room_requests rr 
                          LEFT JOIN rooms cr ON rr.current_room_id = cr.id 
                          LEFT JOIN buildings cb ON cr.building_id = cb.id
                          LEFT JOIN rooms rr2 ON rr.requested_room_id = rr2.id 
                          LEFT JOIN buildings rb ON rr2.building_id = rb.id
                          WHERE rr.student_id = ? 
                          ORDER BY rr.created_at DESC");
    $stmt->execute([$_SESSION['student_id']]);
    $room_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Room requests error: " . $e->getMessage());
    $room_requests = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Requests - Dormitory Management System</title>
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
                    <a href="room_requests.php" class="nav-link active">
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
                <h1 class="header-title">Room Requests</h1>
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

                <!-- Current Room Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-bed me-2"></i>
                            Current Room Assignment
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($student_info['room_id']): ?>
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h6>Room: <?php echo $student_info['building_name'] . ' - ' . $student_info['room_number']; ?></h6>
                                    <p class="text-muted mb-0">
                                        Bed Number: <?php echo $student_info['bed_number'] ?? 'Not assigned'; ?>
                                    </p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge badge-success">Currently Assigned</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                You are not currently assigned to a room. Please contact the administration.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Submit New Request -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-plus me-2"></i>
                            Request Room Change
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$student_info['room_id']): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-info-circle me-2"></i>
                                You must be assigned to a room before you can request a room change.
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="requested_room_id" class="form-label">Select New Room *</label>
                                    <select class="form-control" id="requested_room_id" name="requested_room_id" required>
                                        <option value="">Choose available room...</option>
                                        <?php foreach ($available_rooms as $room): ?>
                                            <?php if ($room['id'] != $student_info['room_id']): ?>
                                                <option value="<?php echo $room['id']; ?>">
                                                    <?php echo $room['building_name'] . ' - Room ' . $room['room_number']; ?>
                                                    (<?php echo $room['available_beds']; ?> beds available)
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="reason" class="form-label">Reason for Room Change *</label>
                                    <textarea class="form-control" id="reason" name="reason" rows="4" 
                                              placeholder="Please explain why you want to change rooms..." required></textarea>
                                    <small class="form-text text-muted">
                                        Valid reasons include: roommate conflicts, health issues, proximity to classes, etc.
                                    </small>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Room change requests are subject to approval and room availability.
                                    </div>
                                    <button type="submit" name="submit_request" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Room Requests -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            <i class="fas fa-list me-2"></i>
                            My Room Change Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($room_requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Room Requests</h4>
                                <p class="text-muted">You haven't submitted any room change requests yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>From Room</th>
                                            <th>To Room</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($room_requests as $request): ?>
                                            <tr>
                                                <td>
                                                    <?php if ($request['current_room_number']): ?>
                                                        <?php echo $request['current_building_name'] . ' - ' . $request['current_room_number']; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No room</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong>
                                                        <?php echo $request['requested_building_name'] . ' - ' . $request['requested_room_number']; ?>
                                                    </strong>
                                                </td>
                                                <td>
                                                    <div class="reason-preview">
                                                        <?php echo substr(htmlspecialchars($request['reason']), 0, 50); ?>
                                                        <?php if (strlen($request['reason']) > 50): ?>...<?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch ($request['status']) {
                                                        case 'approved': $statusClass = 'success'; break;
                                                        case 'rejected': $statusClass = 'danger'; break;
                                                        case 'pending': $statusClass = 'warning'; break;
                                                    }
                                                    ?>
                                                    <span class="badge badge-<?php echo $statusClass; ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDateTime($request['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-info" 
                                                            onclick="viewRoomRequest(<?php echo $request['id']; ?>)">
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
    <div class="modal" id="roomRequestModal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Room Request Details</h5>
                <button type="button" class="modal-close">&times;</button>
            </div>
            <div class="modal-body" id="roomRequestDetails">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        function viewRoomRequest(requestId) {
            const requests = <?php echo json_encode($room_requests); ?>;
            const request = requests.find(r => r.id == requestId);
            
            if (request) {
                const statusClass = {
                    'approved': 'success',
                    'rejected': 'danger',
                    'pending': 'warning'
                };
                
                document.getElementById('roomRequestDetails').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>From Room:</strong><br>
                                ${request.current_room_number ? 
                                    request.current_building_name + ' - ' + request.current_room_number : 
                                    'No room assigned'
                                }
                            </p>
                            <p><strong>To Room:</strong><br>
                                ${request.requested_building_name} - ${request.requested_room_number}
                            </p>
                            <p><strong>Status:</strong><br>
                                <span class="badge badge-${statusClass[request.status]}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Submitted:</strong><br>${new Date(request.created_at).toLocaleDateString()}</p>
                            <p><strong>Last Updated:</strong><br>${new Date(request.updated_at).toLocaleDateString()}</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <strong>Reason for Change:</strong>
                        <div class="mt-2 p-3 bg-light rounded">
                            ${request.reason}
                        </div>
                    </div>
                    ${request.admin_notes ? `
                        <div class="mb-3">
                            <strong>Admin Notes:</strong>
                            <div class="mt-2 p-3 bg-light rounded">
                                ${request.admin_notes}
                            </div>
                        </div>
                    ` : ''}
                `;
                
                DormitorySystem.openModal('roomRequestModal');
            }
        }
    </script>
</body>
</html>