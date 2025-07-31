<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['upload_file'])) {
        $file_name = sanitizeInput($_POST['file_name']);
        $attendance_date = sanitizeInput($_POST['attendance_date']);
        $description = sanitizeInput($_POST['description']);
        
        if ($file_name && $attendance_date && isset($_FILES['biometric_file'])) {
            $uploadResult = uploadFile($_FILES['biometric_file'], '../uploads/biometric_files/', ['pdf', 'xlsx', 'xls', 'csv', 'txt'], 10485760); // 10MB
            
            if ($uploadResult['success']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO biometric_files (file_name, original_filename, file_path, attendance_date, description, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($stmt->execute([$file_name, $_FILES['biometric_file']['name'], $uploadResult['filename'], $attendance_date, $description, $_SESSION['admin_id']])) {
                        logActivity($_SESSION['admin_id'], 'admin', 'upload_biometric_file', "Uploaded biometric file: $file_name");
                        $success = true;
                    } else {
                        $error = 'Failed to save file information to database.';
                    }
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            } else {
                $error = $uploadResult['error'];
            }
        } else {
            $error = 'Please fill in all required fields and select a file.';
        }
    }
    
    if (isset($_POST['delete_file'])) {
        $file_id = sanitizeInput($_POST['file_id']);
        
        if ($file_id) {
            try {
                // Get file info first
                $stmt = $pdo->prepare("SELECT file_path, file_name FROM biometric_files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch();
                
                if ($file) {
                    // Delete from database
                    $stmt = $pdo->prepare("DELETE FROM biometric_files WHERE id = ?");
                    if ($stmt->execute([$file_id])) {
                        // Delete physical file
                        $filePath = '../uploads/biometric_files/' . $file['file_path'];
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                        logActivity($_SESSION['admin_id'], 'admin', 'delete_biometric_file', "Deleted biometric file: " . $file['file_name']);
                        $success = true;
                    } else {
                        $error = 'Failed to delete file.';
                    }
                } else {
                    $error = 'File not found.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get biometric files
try {
    $stmt = $pdo->prepare("
        SELECT bf.*, 
               a.username as uploaded_by_name,
               (SELECT COUNT(*) FROM students WHERE status = 'approved') as total_students
        FROM biometric_files bf
        LEFT JOIN admins a ON bf.uploaded_by = a.id
        ORDER BY bf.attendance_date DESC, bf.created_at DESC
    ");
    $stmt->execute();
    $biometric_files = $stmt->fetchAll();
} catch (PDOException $e) {
    $biometric_files = [];
    $error = 'Failed to fetch biometric files.';
}

// Get statistics
$stats = [
    'total_files' => count($biometric_files),
    'this_month' => count(array_filter($biometric_files, function($f) { 
        return date('Y-m', strtotime($f['attendance_date'])) === date('Y-m');
    })),
    'this_week' => count(array_filter($biometric_files, function($f) { 
        return date('Y-W', strtotime($f['attendance_date'])) === date('Y-W');
    })),
    'total_students' => $biometric_files[0]['total_students'] ?? 0
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Biometrics Management - Admin Panel</title>
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
                <li><a href="biometrics.php" class="active"><i class="fas fa-fingerprint"></i>Biometrics</a></li>
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
                        <h1 class="content-title">Biometrics Management</h1>
                        <p class="content-subtitle">Upload and manage biometric attendance files</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                        <i class="fas fa-upload me-2"></i>Upload Biometric File
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
                        <div class="stat-icon bg-primary"><i class="fas fa-fingerprint"></i></div>
                        <div class="stat-number"><?php echo $stats['total_files']; ?></div>
                        <div class="stat-label">Total Files</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-calendar-month"></i></div>
                        <div class="stat-number"><?php echo $stats['this_month']; ?></div>
                        <div class="stat-label">This Month</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-calendar-week"></i></div>
                        <div class="stat-number"><?php echo $stats['this_week']; ?></div>
                        <div class="stat-label">This Week</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-users"></i></div>
                        <div class="stat-number"><?php echo $stats['total_students']; ?></div>
                        <div class="stat-label">Active Students</div>
                    </div>
                </div>

                <!-- Biometric Files Table -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Biometric Files</h5>
                            <div class="card-tools">
                                <input type="text" class="form-control" id="searchInput" placeholder="Search files..." onkeyup="filterFiles()">
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($biometric_files)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-fingerprint fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Biometric Files</h4>
                                <p class="text-muted">No biometric files have been uploaded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table data-table" id="biometricTable">
                                    <thead>
                                        <tr>
                                            <th>File Name</th>
                                            <th>Attendance Date</th>
                                            <th>Description</th>
                                            <th>File Size</th>
                                            <th>Uploaded By</th>
                                            <th>Upload Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($biometric_files as $file): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($file['file_name']); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($file['original_filename']); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo date('M d, Y', strtotime($file['attendance_date'])); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('l', strtotime($file['attendance_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($file['description'] ?: 'No description'); ?></small>
                                                </td>
                                                <td>
                                                    <?php
                                                    $filePath = '../uploads/biometric_files/' . $file['file_path'];
                                                    $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                                                    $fileSizeFormatted = $fileSize > 0 ? number_format($fileSize / 1024, 2) . ' KB' : 'Unknown';
                                                    ?>
                                                    <small><?php echo $fileSizeFormatted; ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($file['uploaded_by_name'] ?: 'Unknown'); ?></small>
                                                </td>
                                                <td>
                                                    <small><?php echo formatDateTime($file['created_at']); ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <a href="../uploads/biometric_files/<?php echo $file['file_path']; ?>" 
                                                           target="_blank" class="btn btn-sm btn-info" 
                                                           title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-warning" 
                                                                onclick="viewFile(<?php echo htmlspecialchars(json_encode($file)); ?>)"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars($file['file_name']); ?>')"
                                                                title="Delete">
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

    <!-- Upload File Modal -->
    <div class="modal fade" id="uploadFileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Biometric File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="file_name" class="form-label">File Name *</label>
                            <input type="text" class="form-control" id="file_name" name="file_name" required placeholder="Enter descriptive file name">
                        </div>
                        <div class="form-group">
                            <label for="attendance_date" class="form-label">Attendance Date *</label>
                            <input type="date" class="form-control" id="attendance_date" name="attendance_date" required>
                        </div>
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Optional description of the attendance data..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="biometric_file" class="form-label">Biometric File *</label>
                            <input type="file" class="form-control" id="biometric_file" name="biometric_file" required accept=".pdf,.xlsx,.xls,.csv,.txt">
                            <small class="form-text text-muted">Supported formats: PDF, Excel, CSV, TXT (Max: 10MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_file" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload File
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View File Modal -->
    <div class="modal fade" id="viewFileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i>File Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <h6>File Information</h6>
                            <p><strong>Name:</strong> <span id="view_file_name"></span></p>
                            <p><strong>Original Filename:</strong> <span id="view_original_filename"></span></p>
                            <p><strong>Attendance Date:</strong> <span id="view_attendance_date"></span></p>
                            <p><strong>Description:</strong> <span id="view_file_description"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                Uploaded by: <span id="view_uploaded_by"></span>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Upload date: <span id="view_upload_date"></span>
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="view_download_link" target="_blank" class="btn btn-primary">
                        <i class="fas fa-download me-2"></i>Download File
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteFileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Biometric File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this biometric file?</p>
                    <p><strong>File:</strong> <span id="delete_file_name"></span></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The file will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" id="delete_file_id" name="file_id">
                        <button type="submit" name="delete_file" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete File
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Set default attendance date to today
        document.getElementById('attendance_date').value = new Date().toISOString().split('T')[0];

        function viewFile(file) {
            document.getElementById('view_file_name').textContent = file.file_name;
            document.getElementById('view_original_filename').textContent = file.original_filename;
            document.getElementById('view_attendance_date').textContent = formatDate(file.attendance_date);
            document.getElementById('view_file_description').textContent = file.description || 'No description';
            document.getElementById('view_uploaded_by').textContent = file.uploaded_by_name || 'Unknown';
            document.getElementById('view_upload_date').textContent = formatDateTime(file.created_at);
            document.getElementById('view_download_link').href = `../uploads/biometric_files/${file.file_path}`;
            
            new bootstrap.Modal(document.getElementById('viewFileModal')).show();
        }

        function deleteFile(fileId, fileName) {
            document.getElementById('delete_file_id').value = fileId;
            document.getElementById('delete_file_name').textContent = fileName;
            new bootstrap.Modal(document.getElementById('deleteFileModal')).show();
        }

        function filterFiles() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#biometricTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>