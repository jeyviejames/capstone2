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
        $priority = sanitizeInput($_POST['priority']);
        $assigned_to = sanitizeInput($_POST['assigned_to']) ?: null;
        $admin_notes = sanitizeInput($_POST['admin_notes']);
        
        if ($request_id && $status) {
            try {
                $stmt = $pdo->prepare("UPDATE maintenance_requests SET status=?, priority=?, assigned_to=?, admin_notes=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                if ($stmt->execute([$status, $priority, $assigned_to, $admin_notes, $request_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'update_maintenance_request', "Updated maintenance request ID: $request_id");
                    $success = true;
                } else {
                    $error = 'Failed to update maintenance request.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['bulk_update'])) {
        $request_ids = $_POST['request_ids'] ?? [];
        $bulk_status = sanitizeInput($_POST['bulk_status']);
        $bulk_assigned_to = sanitizeInput($_POST['bulk_assigned_to']) ?: null;
        
        if (!empty($request_ids) && $bulk_status) {
            try {
                $placeholders = str_repeat('?,', count($request_ids) - 1) . '?';
                $stmt = $pdo->prepare("UPDATE maintenance_requests SET status=?, assigned_to=?, updated_at=CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
                $params = array_merge([$bulk_status, $bulk_assigned_to], $request_ids);
                if ($stmt->execute($params)) {
                    logActivity($_SESSION['admin_id'], 'admin', 'bulk_update_maintenance', "Bulk updated " . count($request_ids) . " maintenance requests");
                    $success = true;
                } else {
                    $error = 'Failed to update maintenance requests.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please select requests and status for bulk update.';
        }
    }
}

// Get maintenance requests with student and room info
try {
    $stmt = $pdo->prepare("
        SELECT mr.*,
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               s.student_id as student_number,
               CONCAT(b.name, ' - ', r.room_number) as room_info,
               mr.assigned_to as assigned_staff
        FROM maintenance_requests mr
        LEFT JOIN students s ON mr.student_id = s.id
        LEFT JOIN rooms r ON mr.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY 
            CASE mr.priority 
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            mr.created_at DESC
    ");
    $stmt->execute();
    $maintenance_requests = $stmt->fetchAll();
} catch (PDOException $e) {
    $maintenance_requests = [];
    $error = 'Failed to fetch maintenance requests.';
}

// Get statistics
$stats = [
    'total_requests' => count($maintenance_requests),
    'pending' => count(array_filter($maintenance_requests, function($r) { return $r['status'] === 'pending'; })),
    'in_progress' => count(array_filter($maintenance_requests, function($r) { return $r['status'] === 'in_progress'; })),
    'completed' => count(array_filter($maintenance_requests, function($r) { return $r['status'] === 'completed'; })),
    'urgent' => count(array_filter($maintenance_requests, function($r) { return $r['priority'] === 'urgent'; }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Requests - Admin Panel</title>
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
                <li><a href="maintenance.php" class="active"><i class="fas fa-tools"></i>Maintenance Requests</a></li>
                <li><a href="room_requests.php"><i class="fas fa-door-open"></i>Room Requests</a></li>
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
                        <h1 class="content-title">Maintenance Requests Management</h1>
                        <p class="content-subtitle">View, assign, and track maintenance requests</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#bulkUpdateModal">
                            <i class="fas fa-tasks me-2"></i>Bulk Update
                        </button>
                        <button type="button" class="btn btn-primary" onclick="exportRequests()">
                            <i class="fas fa-download me-2"></i>Export Data
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
                        <div class="stat-icon bg-primary"><i class="fas fa-tools"></i></div>
                        <div class="stat-number"><?php echo $stats['total_requests']; ?></div>
                        <div class="stat-label">Total Requests</div>
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
                        <div class="stat-number"><?php echo $stats['completed']; ?></div>
                        <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-danger"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['urgent']; ?></div>
                        <div class="stat-label">Urgent</div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <select class="form-control" id="statusFilter" onchange="filterRequests()">
                                    <option value="">All Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="priorityFilter" onchange="filterRequests()">
                                    <option value="">All Priority</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-control" id="typeFilter" onchange="filterRequests()">
                                    <option value="">All Types</option>
                                    <option value="Plumbing">Plumbing</option>
                                    <option value="Electrical">Electrical</option>
                                    <option value="Cleaning">Cleaning</option>
                                    <option value="Repair">Repair</option>
                                    <option value="HVAC">HVAC</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search requests..." onkeyup="filterRequests()">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Maintenance Requests Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Maintenance Requests</h5>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                                <label class="form-check-label" for="selectAll">Select All</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($maintenance_requests)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Maintenance Requests</h4>
                                <p class="text-muted">No maintenance requests have been submitted yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table data-table" id="maintenanceTable">
                                    <thead>
                                        <tr>
                                            <th width="50"><input type="checkbox" id="selectAllTable" onchange="toggleSelectAll()"></th>
                                            <th>Student</th>
                                            <th>Request Type</th>
                                            <th>Description</th>
                                            <th>Priority</th>
                                            <th>Status</th>
                                            <th>Assigned To</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($maintenance_requests as $request): ?>
                                            <tr data-status="<?php echo $request['status']; ?>" data-priority="<?php echo $request['priority']; ?>" data-type="<?php echo $request['request_type']; ?>">
                                                <td>
                                                    <input type="checkbox" class="request-checkbox" value="<?php echo $request['id']; ?>">
                                                </td>
                                                <td>
                                                    <strong><?php echo $request['student_name'] ?: 'Unknown Student'; ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        ID: <?php echo $request['student_number'] ?: 'N/A'; ?>
                                                        <?php if ($request['room_info']): ?>
                                                            <br>Room: <?php echo $request['room_info']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($request['request_type']); ?></strong></td>
                                                <td>
                                                    <small><?php echo htmlspecialchars(substr($request['description'], 0, 100)); ?>
                                                    <?php if (strlen($request['description']) > 100): ?>...<?php endif; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        switch($request['priority']) {
                                                            case 'urgent': echo 'danger'; break;
                                                            case 'high': echo 'warning'; break;
                                                            case 'medium': echo 'info'; break;
                                                            case 'low': echo 'secondary'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($request['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        switch($request['status']) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'in_progress': echo 'info'; break;
                                                            case 'completed': echo 'success'; break;
                                                            case 'cancelled': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($request['assigned_staff'] ?: 'Unassigned'); ?></small>
                                                </td>
                                                <td><small><?php echo formatDateTime($request['created_at']); ?></small></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-info" onclick="viewRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="editRequest(<?php echo htmlspecialchars(json_encode($request)); ?>)">
                                                            <i class="fas fa-edit"></i>
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

    <!-- View Request Modal -->
    <div class="modal fade" id="viewRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>Maintenance Request Details</h5>
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
                            <h6>Request Information</h6>
                            <p><strong>Type:</strong> <span id="view_request_type"></span></p>
                            <p><strong>Priority:</strong> <span id="view_priority_badge"></span></p>
                            <p><strong>Status:</strong> <span id="view_status_badge"></span></p>
                            <p><strong>Assigned To:</strong> <span id="view_assigned_to"></span></p>
                        </div>
                    </div>
                    <hr>
                    <h6>Description</h6>
                    <p id="view_description"></p>
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

    <!-- Edit Request Modal -->
    <div class="modal fade" id="editRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Update Maintenance Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_request_id" name="request_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_status" class="form-label">Status *</label>
                                    <select class="form-control" id="edit_status" name="status" required>
                                        <option value="">Select Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_priority" class="form-label">Priority *</label>
                                    <select class="form-control" id="edit_priority" name="priority" required>
                                        <option value="">Select Priority</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="high">High</option>
                                        <option value="medium">Medium</option>
                                        <option value="low">Low</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_assigned_to" class="form-label">Assign to Staff</label>
                            <input type="text" class="form-control" id="edit_assigned_to" name="assigned_to" placeholder="Enter staff name or team">
                        </div>
                        <div class="form-group">
                            <label for="edit_admin_notes" class="form-label">Admin Notes</label>
                            <textarea class="form-control" id="edit_admin_notes" name="admin_notes" rows="4" placeholder="Add notes about the request, assigned staff, or updates..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_request" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Update Modal -->
    <div class="modal fade" id="bulkUpdateModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-tasks me-2"></i>Bulk Update Requests</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="bulk_status" class="form-label">New Status *</label>
                            <select class="form-control" id="bulk_status" name="bulk_status" required>
                                <option value="">Select Status</option>
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bulk_assigned_to" class="form-label">Assign to Staff</label>
                            <input type="text" class="form-control" id="bulk_assigned_to" name="bulk_assigned_to" placeholder="Enter staff name or team">
                        </div>
                        <div id="selected-requests-count" class="alert alert-info">
                            No requests selected. Please select requests from the table first.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="bulk_update" class="btn btn-primary" id="bulk-update-btn" disabled>
                            <i class="fas fa-save me-2"></i>Update Selected Requests
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
            document.getElementById('view_room_info').textContent = request.room_info || 'Not assigned';
            document.getElementById('view_request_type').textContent = request.request_type;
            document.getElementById('view_description').textContent = request.description;
            document.getElementById('view_assigned_to').textContent = request.assigned_staff || 'Unassigned';
            document.getElementById('view_created_at').textContent = formatDateTime(request.created_at);
            document.getElementById('view_updated_at').textContent = formatDateTime(request.updated_at);
            
            // Priority badge
            const priorityColors = {urgent: 'danger', high: 'warning', medium: 'info', low: 'secondary'};
            document.getElementById('view_priority_badge').innerHTML = 
                `<span class="badge badge-${priorityColors[request.priority] || 'secondary'}">${request.priority.charAt(0).toUpperCase() + request.priority.slice(1)}</span>`;
            
            // Status badge
            const statusColors = {pending: 'warning', in_progress: 'info', completed: 'success', cancelled: 'danger'};
            document.getElementById('view_status_badge').innerHTML = 
                `<span class="badge badge-${statusColors[request.status] || 'secondary'}">${request.status.replace('_', ' ').charAt(0).toUpperCase() + request.status.replace('_', ' ').slice(1)}</span>`;
            
            // Admin notes
            if (request.admin_notes) {
                document.getElementById('view_admin_notes').textContent = request.admin_notes;
                document.getElementById('view_admin_notes_section').style.display = 'block';
            } else {
                document.getElementById('view_admin_notes_section').style.display = 'none';
            }
            
            new bootstrap.Modal(document.getElementById('viewRequestModal')).show();
        }

        function editRequest(request) {
            document.getElementById('edit_request_id').value = request.id;
            document.getElementById('edit_status').value = request.status;
            document.getElementById('edit_priority').value = request.priority;
            document.getElementById('edit_assigned_to').value = request.assigned_staff || '';
            document.getElementById('edit_admin_notes').value = request.admin_notes || '';
            
            new bootstrap.Modal(document.getElementById('editRequestModal')).show();
        }

        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAllTable');
            const checkboxes = document.querySelectorAll('.request-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBulkUpdateButton();
        }

        function updateBulkUpdateButton() {
            const selectedCheckboxes = document.querySelectorAll('.request-checkbox:checked');
            const count = selectedCheckboxes.length;
            const countElement = document.getElementById('selected-requests-count');
            const updateBtn = document.getElementById('bulk-update-btn');
            
            if (count > 0) {
                countElement.textContent = `${count} request${count > 1 ? 's' : ''} selected for bulk update.`;
                countElement.className = 'alert alert-success';
                updateBtn.disabled = false;
                
                // Add selected IDs to form
                const form = updateBtn.closest('form');
                // Remove existing hidden inputs
                form.querySelectorAll('input[name="request_ids[]"]').forEach(input => input.remove());
                
                // Add new hidden inputs
                selectedCheckboxes.forEach(checkbox => {
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'request_ids[]';
                    hiddenInput.value = checkbox.value;
                    form.appendChild(hiddenInput);
                });
            } else {
                countElement.textContent = 'No requests selected. Please select requests from the table first.';
                countElement.className = 'alert alert-info';
                updateBtn.disabled = true;
            }
        }

        // Add event listeners to checkboxes
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.request-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateBulkUpdateButton);
            });
        });

        function filterRequests() {
            const statusFilter = document.getElementById('statusFilter').value;
            const priorityFilter = document.getElementById('priorityFilter').value;
            const typeFilter = document.getElementById('typeFilter').value;
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#maintenanceTable tbody tr');
            
            rows.forEach(row => {
                const status = row.getAttribute('data-status');
                const priority = row.getAttribute('data-priority');
                const type = row.getAttribute('data-type');
                const text = row.textContent.toLowerCase();
                
                const statusMatch = !statusFilter || status === statusFilter;
                const priorityMatch = !priorityFilter || priority === priorityFilter;
                const typeMatch = !typeFilter || type === typeFilter;
                const textMatch = !searchTerm || text.includes(searchTerm);
                
                if (statusMatch && priorityMatch && typeMatch && textMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function exportRequests() {
            // Simple CSV export functionality
            const rows = document.querySelectorAll('#maintenanceTable tbody tr:not([style*="display: none"])');
            let csv = 'Student,Student ID,Room,Request Type,Description,Priority,Status,Assigned To,Date\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length > 1) {
                    const studentName = cells[1].querySelector('strong').textContent;
                    const studentId = cells[1].textContent.match(/ID: (.+)/)?.[1] || 'N/A';
                    const room = cells[1].textContent.match(/Room: (.+)/)?.[1] || 'N/A';
                    const requestType = cells[2].textContent;
                    const description = cells[3].textContent.replace(/"/g, '""');
                    const priority = cells[4].textContent;
                    const status = cells[5].textContent;
                    const assignedTo = cells[6].textContent;
                    const date = cells[7].textContent;
                    
                    csv += `"${studentName}","${studentId}","${room}","${requestType}","${description}","${priority}","${status}","${assignedTo}","${date}"\n`;
                }
            });
            
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'maintenance_requests_' + new Date().toISOString().split('T')[0] + '.csv';
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