<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_offense'])) {
        $student_id = sanitizeInput($_POST['student_id']);
        $offense_type = sanitizeInput($_POST['offense_type']);
        $description = sanitizeInput($_POST['description']);
        $severity = sanitizeInput($_POST['severity']);
        $action_taken = sanitizeInput($_POST['action_taken']);
        $offense_date = sanitizeInput($_POST['offense_date']);
        
        if ($student_id && $offense_type && $description && $severity) {
            try {
                $stmt = $pdo->prepare("INSERT INTO offense_logs (student_id, offense_type, description, severity, action_taken, offense_date, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$student_id, $offense_type, $description, $severity, $action_taken, $offense_date, $_SESSION['admin_id']])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'add_offense', "Added offense record for student ID: $student_id");
                    $success = true;
                } else {
                    $error = 'Failed to add offense record.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['edit_offense'])) {
        $offense_id = sanitizeInput($_POST['offense_id']);
        $offense_type = sanitizeInput($_POST['offense_type']);
        $description = sanitizeInput($_POST['description']);
        $severity = sanitizeInput($_POST['severity']);
        $action_taken = sanitizeInput($_POST['action_taken']);
        $offense_date = sanitizeInput($_POST['offense_date']);
        
        if ($offense_id && $offense_type && $description && $severity) {
            try {
                $stmt = $pdo->prepare("UPDATE offense_logs SET offense_type=?, description=?, severity=?, action_taken=?, offense_date=? WHERE id=?");
                if ($stmt->execute([$offense_type, $description, $severity, $action_taken, $offense_date, $offense_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'edit_offense', "Updated offense record ID: $offense_id");
                    $success = true;
                } else {
                    $error = 'Failed to update offense record.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['delete_offense'])) {
        $offense_id = sanitizeInput($_POST['offense_id']);
        
        if ($offense_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM offense_logs WHERE id=?");
                if ($stmt->execute([$offense_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'delete_offense', "Deleted offense record ID: $offense_id");
                    $success = true;
                } else {
                    $error = 'Failed to delete offense record.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get offense logs with student info
try {
    $stmt = $pdo->prepare("
        SELECT ol.*, 
               CONCAT(s.first_name, ' ', s.last_name) as student_name,
               s.student_id as student_number,
               CONCAT(b.name, ' - ', r.room_number) as room_info
        FROM offense_logs ol
        LEFT JOIN students s ON ol.student_id = s.id
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY ol.offense_date DESC, ol.created_at DESC
    ");
    $stmt->execute();
    $offense_logs = $stmt->fetchAll();
} catch (PDOException $e) {
    $offense_logs = [];
    $error = 'Failed to fetch offense logs.';
}

// Get students for dropdown
try {
    $stmt = $pdo->prepare("SELECT id, student_id, first_name, last_name FROM students WHERE status = 'approved' ORDER BY first_name, last_name");
    $stmt->execute();
    $students = $stmt->fetchAll();
} catch (PDOException $e) {
    $students = [];
}

// Get statistics
$stats = [
    'total_offenses' => count($offense_logs),
    'high_severity' => count(array_filter($offense_logs, function($log) { return $log['severity'] === 'high'; })),
    'medium_severity' => count(array_filter($offense_logs, function($log) { return $log['severity'] === 'medium'; })),
    'low_severity' => count(array_filter($offense_logs, function($log) { return $log['severity'] === 'low'; }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offense Logs - Admin Panel</title>
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
                <li><a href="offense_logs.php" class="active"><i class="fas fa-exclamation-triangle"></i>Offense Logs</a></li>
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
                        <h1 class="content-title">Offense Logs Management</h1>
                        <p class="content-subtitle">Monitor and manage student violation records</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOffenseModal">
                        <i class="fas fa-plus me-2"></i>Add Offense Record
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

                <!-- Statistics Cards -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="stat-number"><?php echo $stats['total_offenses']; ?></div>
                        <div class="stat-label">Total Offenses</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-danger"><i class="fas fa-exclamation-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['high_severity']; ?></div>
                        <div class="stat-label">High Severity</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-triangle-exclamation"></i></div>
                        <div class="stat-number"><?php echo $stats['medium_severity']; ?></div>
                        <div class="stat-label">Medium Severity</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-info-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['low_severity']; ?></div>
                        <div class="stat-label">Low Severity</div>
                    </div>
                </div>

                <!-- Offense Logs Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Offense Records</h5>
                            <div class="card-tools">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search offense records...">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($offense_logs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Offense Records</h4>
                                <p class="text-muted">No violations have been recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table data-table" id="offenseTable">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Offense Type</th>
                                            <th>Description</th>
                                            <th>Severity</th>
                                            <th>Action Taken</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($offense_logs as $log): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $log['student_name'] ?: 'Unknown Student'; ?></strong>
                                                    <br>
                                                    <small class="text-muted">
                                                        ID: <?php echo $log['student_number'] ?: 'N/A'; ?>
                                                        <?php if ($log['room_info']): ?>
                                                            <br>Room: <?php echo $log['room_info']; ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </td>
                                                <td><strong><?php echo htmlspecialchars($log['offense_type']); ?></strong></td>
                                                <td><small><?php echo htmlspecialchars($log['description']); ?></small></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        switch($log['severity']) {
                                                            case 'high': echo 'danger'; break;
                                                            case 'medium': echo 'warning'; break;
                                                            case 'low': echo 'info'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($log['severity']); ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo htmlspecialchars($log['action_taken'] ?: 'None'); ?></small></td>
                                                <td><small><?php echo formatDateTime($log['offense_date']); ?></small></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="editOffense(<?php echo htmlspecialchars(json_encode($log)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteOffense(<?php echo $log['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
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

    <!-- Add Offense Modal -->
    <div class="modal fade" id="addOffenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add Offense Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="student_id" class="form-label">Student *</label>
                                    <select class="form-control" id="student_id" name="student_id" required>
                                        <option value="">Select Student</option>
                                        <?php foreach ($students as $student): ?>
                                            <option value="<?php echo $student['id']; ?>">
                                                <?php echo $student['first_name'] . ' ' . $student['last_name']; ?> (<?php echo $student['student_id']; ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="offense_type" class="form-label">Offense Type *</label>
                                    <select class="form-control" id="offense_type" name="offense_type" required>
                                        <option value="">Select Offense Type</option>
                                        <option value="Curfew Violation">Curfew Violation</option>
                                        <option value="Property Damage">Property Damage</option>
                                        <option value="Noise Violation">Noise Violation</option>
                                        <option value="Unauthorized Guest">Unauthorized Guest</option>
                                        <option value="Smoking">Smoking</option>
                                        <option value="Alcohol/Drugs">Alcohol/Drugs</option>
                                        <option value="Fighting">Fighting</option>
                                        <option value="Theft">Theft</option>
                                        <option value="Disrespect to Staff">Disrespect to Staff</option>
                                        <option value="Unauthorized Entry">Unauthorized Entry</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="severity" class="form-label">Severity Level *</label>
                                    <select class="form-control" id="severity" name="severity" required>
                                        <option value="">Select Severity</option>
                                        <option value="low">Low - Minor violation</option>
                                        <option value="medium">Medium - Moderate violation</option>
                                        <option value="high">High - Serious violation</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="offense_date" class="form-label">Offense Date *</label>
                                    <input type="datetime-local" class="form-control" id="offense_date" name="offense_date" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Detailed description of the offense..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="action_taken" class="form-label">Action Taken</label>
                            <textarea class="form-control" id="action_taken" name="action_taken" rows="2" placeholder="Disciplinary action or measures taken..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_offense" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Offense Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Offense Modal -->
    <div class="modal fade" id="editOffenseModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Offense Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_offense_id" name="offense_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_offense_type" class="form-label">Offense Type *</label>
                                    <select class="form-control" id="edit_offense_type" name="offense_type" required>
                                        <option value="">Select Offense Type</option>
                                        <option value="Curfew Violation">Curfew Violation</option>
                                        <option value="Property Damage">Property Damage</option>
                                        <option value="Noise Violation">Noise Violation</option>
                                        <option value="Unauthorized Guest">Unauthorized Guest</option>
                                        <option value="Smoking">Smoking</option>
                                        <option value="Alcohol/Drugs">Alcohol/Drugs</option>
                                        <option value="Fighting">Fighting</option>
                                        <option value="Theft">Theft</option>
                                        <option value="Disrespect to Staff">Disrespect to Staff</option>
                                        <option value="Unauthorized Entry">Unauthorized Entry</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="edit_severity" class="form-label">Severity Level *</label>
                                    <select class="form-control" id="edit_severity" name="severity" required>
                                        <option value="">Select Severity</option>
                                        <option value="low">Low - Minor violation</option>
                                        <option value="medium">Medium - Moderate violation</option>
                                        <option value="high">High - Serious violation</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_offense_date" class="form-label">Offense Date *</label>
                            <input type="datetime-local" class="form-control" id="edit_offense_date" name="offense_date" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_description" class="form-label">Description *</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit_action_taken" class="form-label">Action Taken</label>
                            <textarea class="form-control" id="edit_action_taken" name="action_taken" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_offense" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Record
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteOffenseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Offense Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this offense record? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" id="delete_offense_id" name="offense_id">
                        <button type="submit" name="delete_offense" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Record
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Set default offense date to current date/time
        document.getElementById('offense_date').value = new Date().toISOString().slice(0, 16);

        function editOffense(offense) {
            document.getElementById('edit_offense_id').value = offense.id;
            document.getElementById('edit_offense_type').value = offense.offense_type;
            document.getElementById('edit_severity').value = offense.severity;
            document.getElementById('edit_description').value = offense.description;
            document.getElementById('edit_action_taken').value = offense.action_taken || '';
            
            // Format date for datetime-local input
            const offenseDate = new Date(offense.offense_date);
            document.getElementById('edit_offense_date').value = offenseDate.toISOString().slice(0, 16);
            
            new bootstrap.Modal(document.getElementById('editOffenseModal')).show();
        }

        function deleteOffense(offenseId) {
            document.getElementById('delete_offense_id').value = offenseId;
            new bootstrap.Modal(document.getElementById('deleteOffenseModal')).show();
        }

        // Search functionality
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('#offenseTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>
</html>