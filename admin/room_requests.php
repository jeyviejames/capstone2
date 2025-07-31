<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_request'])) {
        $request_id = sanitizeInput($_POST['request_id']);
        $status = sanitizeInput($_POST['status']);
        $admin_notes = sanitizeInput($_POST['admin_notes']);
        
        if ($request_id && $status) {
            try {
                // If approving, need to update student's room assignment
                if ($status === 'approved') {
                    // Get request details
                    $stmt = $pdo->prepare("SELECT * FROM room_requests WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $request = $stmt->fetch();
                    
                    if ($request) {
                        // Start transaction
                        $pdo->beginTransaction();
                        
                        // Update room request status
                        $stmt = $pdo->prepare("UPDATE room_requests SET status=?, admin_notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                        $stmt->execute([$status, $admin_notes, $request_id]);
                        
                        // Update student's room assignment
                        $stmt = $pdo->prepare("UPDATE students SET room_id=? WHERE id=?");
                        $stmt->execute([$request['requested_room_id'], $request['student_id']]);
                        
                        // Update room occupancy
                        $stmt = $pdo->prepare("UPDATE rooms SET occupied_beds = occupied_beds + 1 WHERE id = ?");
                        $stmt->execute([$request['requested_room_id']]);
                        
                        // Decrease occupancy from old room if exists
                        if ($request['current_room_id']) {
                            $stmt = $pdo->prepare("UPDATE rooms SET occupied_beds = occupied_beds - 1 WHERE id = ? AND occupied_beds > 0");
                            $stmt->execute([$request['current_room_id']]);
                        }
                        
                        $pdo->commit();
                        logActivity($_SESSION['admin_id'], 'admin', 'approve_room_request', "Approved room request ID: $request_id");
                        $success = true;
                    }
                } else {
                    // Just update status for rejected/cancelled requests
                    $stmt = $pdo->prepare("UPDATE room_requests SET status=?, admin_notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                    if ($stmt->execute([$status, $admin_notes, $request_id])) {
                        logActivity($_SESSION['admin_id'], 'admin', 'update_room_request', "Updated room request ID: $request_id to $status");
                        $success = true;
                    } else {
                        $error = 'Failed to update room request.';
                    }
                }
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}

// Get room requests with student and room info
try {
    $stmt = $pdo->prepare("
        SELECT rr.*,
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               s.student_id as student_number,
               CONCAT(cb.name, ' - ', cr.room_number) as current_room_info,
               CONCAT(rb.name, ' - ', rr_room.room_number) as requested_room_info,
               rr_room.capacity,
               rr_room.occupied_beds,
               (rr_room.capacity - rr_room.occupied_beds) as available_beds
        FROM room_requests rr
        LEFT JOIN students s ON rr.student_id = s.id
        LEFT JOIN rooms cr ON rr.current_room_id = cr.id
        LEFT JOIN buildings cb ON cr.building_id = cb.id
        LEFT JOIN rooms rr_room ON rr.requested_room_id = rr_room.id
        LEFT JOIN buildings rb ON rr_room.building_id = rb.id
        ORDER BY rr.created_at DESC
    ");
    $stmt->execute();
    $room_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $room_requests = [];
    $error = 'Failed to fetch room requests.';
}

// Get statistics
$stats = [
    'total_requests' => count($room_requests),
    'pending' => count(array_filter($room_requests, function($r) { return $r['status'] === 'pending'; })),
    'approved' => count(array_filter($room_requests, function($r) { return $r['status'] === 'approved'; })),
    'rejected' => count(array_filter($room_requests, function($r) { return $r['status'] === 'rejected'; }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Requests - Admin Panel</title>
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
                <li><a href="room_requests.php" class="active"><i class="fas fa-door-open"></i>Room Requests</a></li>
                <li><a href="biometrics.php"><i class="fas fa-fingerprint"></i>Biometrics</a></li>
                <li><a href="student_locator.php"><i class="fas fa-map-marker-alt"></i>Student Locator</a></li>
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
                        <h1 class="content-title">Room Requests Management</h1>
                        <p class="content-subtitle">Approve or reject student room change requests</p>
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
                        <div class="stat-icon bg-primary"><i class="fas fa-door-open"></i></div>
                        <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                        <div class="stat-label">Total Requests</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div>
                        <div class="stat-number"><?php echo $stats['pending']; ?></div>
                        <div class="stat-label">Pending</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['approved']; ?></div>
                        <div class="stat-label">Approved</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-danger"><i class="fas fa-times-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['rejected']; ?></div>
                        <div class="stat-label">Rejected</div>
                    </div>
                </div>

                <!-- Room Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Room Change Requests</h5>
                            <div class="card-tools">
                                <select class="form-control" id="statusFilter" onchange="filterRequests()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($room_requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Room Requests</h4>
                                <p class="text-muted">No room change requests have been submitted yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table data-table" id="roomRequestsTable">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Current Room</th>
                                            <th>Requested Room</th>
                                            <th>Reason</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($room_requests as $request): ?>
                                            <tr data-status="<?php echo $request['status']; ?>">
                                                <td>
                                                    <strong><?php echo $request['student_name'] ?: 'Unknown Student'; ?></strong>
                                                    <br>
                                                    <small class="text-muted">ID: <?php echo $request['student_number'] ?: 'N/A'; ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo $request['current_room_info'] ?: 'Not assigned'; ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo $request['requested_room_info']; ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        Available: <?php echo $request['available_beds']; ?>/<?php echo $request['capacity']; ?> beds
                                                        <?php if ($request['available_beds'] <= 0): ?>
                                                            <span class="badge badge-danger ms-1">Full</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($request['reason'], 0, 100)); ?>
                                                    <?php if (strlen($request['reason']) > 100): ?>...<?php endif; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        switch($request['status']) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'approved': echo 'success'; break;
                                                            case 'rejected': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo formatDateTime($request['created_at']); ?></small></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-info" onclick="viewRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <?php if ($request['status'] === 'pending'): ?>
                                                            <button type="button" class="btn btn-sm btn-success" onclick="updateRequest(<?php echo $request['id']; ?>, 'approved')">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="updateRequest(<?php echo $request['id']; ?>, 'rejected')">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
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

    <!-- View Request Modal -->
    <div class="modal fade" id="viewRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Room Request Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Student Information</h6>
                            <p><strong>Name:</strong> <span id="view_student_name"></span></p>
                            <p><strong>Student ID:</strong> <span id="view_student_id"></span></p>
                            <p><strong>Current Room:</strong> <span id="view_current_room"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Request Information</h6>
                            <p><strong>Requested Room:</strong> <span id="view_requested_room"></span></p>
                            <p><strong>Room Availability:</strong> <span id="view_availability"></span></p>
                            <p><strong>Status:</strong> <span id="view_status_badge"></span></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Reason for Room Change</h6>
                    <p id="view_reason"></p>
                    <div id="view_admin_notes_section" style="display: none;">
                        <hr>
                        <h6>Admin Notes</h6>
                        <p id="view_admin_notes"></p>
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

    <!-- Update Request Modal -->
    <div class="modal fade" id="updateRequestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Room Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="update_request_id" name="request_id">
                        <input type="hidden" id="update_status" name="status">
                        <div class="form-group">
                            <label for="admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" placeholder="Add notes about your decision..."></textarea>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="update_action_text"></span>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_request" class="btn" id="update_submit_btn">
                            <i class="fas fa-save me-2"></i><span id="update_btn_text">Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function viewRequest(request) {
            document.getElementById('view_student_name').textContent = request.student_name || 'Unknown Student';
            document.getElementById('view_student_id').textContent = request.student_number || 'N/A';
            document.getElementById('view_current_room').textContent = request.current_room_info || 'Not assigned';
            document.getElementById('view_requested_room').textContent = request.requested_room_info;
            document.getElementById('view_availability').textContent = `${request.available_beds}/${request.capacity} beds available`;
            document.getElementById('view_reason').textContent = request.reason;
            document.getElementById('view_created_at').textContent = formatDateTime(request.created_at);
            document.getElementById('view_updated_at').textContent = formatDateTime(request.updated_at);
            
            // Status badge
            const statusColors = {pending: 'warning', approved: 'success', rejected: 'danger'};
            document.getElementById('view_status_badge').innerHTML = 
                `<span class="badge badge-${statusColors[request.status] || 'secondary'}">${request.status.charAt(0).toUpperCase() + request.status.slice(1)}</span>`;
            
            // Admin notes
            if (request.admin_notes) {
                document.getElementById('view_admin_notes').textContent = request.admin_notes;
                document.getElementById('view_admin_notes_section').style.display = 'block';
            } else {
                document.getElementById('view_admin_notes_section').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewRequestModal')).show();
        }

        function updateRequest(requestId, status) {
            document.getElementById('update_request_id').value = requestId;
            document.getElementById('update_status').value = status;
            
            const actionText = status === 'approved' ? 
                'You are about to approve this room change request. The student will be moved to the requested room.' :
                'You are about to reject this room change request. The student will remain in their current room.';
            
            document.getElementById('update_action_text').textContent = actionText;
            document.getElementById('update_btn_text').textContent = status === 'approved' ? 'Approve Request' : 'Reject Request';
            
            const submitBtn = document.getElementById('update_submit_btn');
            submitBtn.className = status === 'approved' ? 'btn btn-success' : 'btn btn-danger';
            
            new bootstrap.Modal(document.getElementById('updateRequestModal')).show();
        }

        function filterRequests() {
            const statusFilter = document.getElementById('statusFilter').value;
            const rows = document.querySelectorAll('#roomRequestsTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                if (!statusFilter || status === statusFilter) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>