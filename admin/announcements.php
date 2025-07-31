<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_announcement'])) {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $priority = sanitizeInput($_POST['priority']);
        $publish_date = sanitizeInput($_POST['publish_date']);
        $expiry_date = sanitizeInput($_POST['expiry_date']) ?: null;
        
        if ($title && $content && $priority) {
            try {
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, priority, publish_date, expiry_date, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'published')");
                if ($stmt->execute([$title, $content, $priority, $publish_date, $expiry_date, $_SESSION['admin_id']])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'add_announcement', "Created announcement: $title");
                    $success = true;
                } else {
                    $error = 'Failed to create announcement.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['edit_announcement'])) {
        $announcement_id = sanitizeInput($_POST['announcement_id']);
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $priority = sanitizeInput($_POST['priority']);
        $publish_date = sanitizeInput($_POST['publish_date']);
        $expiry_date = sanitizeInput($_POST['expiry_date']) ?: null;
        
        if ($announcement_id && $title && $content && $priority) {
            try {
                $stmt = $pdo->prepare("UPDATE announcements SET title=?, content=?, priority=?, publish_date=?, expiry_date=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                if ($stmt->execute([$title, $content, $priority, $publish_date, $expiry_date, $announcement_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'edit_announcement', "Updated announcement ID: $announcement_id");
                    $success = true;
                } else {
                    $error = 'Failed to update announcement.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['delete_announcement'])) {
        $announcement_id = sanitizeInput($_POST['announcement_id']);
        
        if ($announcement_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id=?");
                if ($stmt->execute([$announcement_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'delete_announcement', "Deleted announcement ID: $announcement_id");
                    $success = true;
                } else {
                    $error = 'Failed to delete announcement.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['toggle_status'])) {
        $announcement_id = sanitizeInput($_POST['announcement_id']);
        $new_status = sanitizeInput($_POST['new_status']);
        
        if ($announcement_id && in_array($new_status, ['published', 'draft', 'archived'])) {
            try {
                $stmt = $pdo->prepare("UPDATE announcements SET status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                if ($stmt->execute([$new_status, $announcement_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'toggle_announcement_status', "Changed announcement ID $announcement_id status to: $new_status");
                    $success = true;
                } else {
                    $error = 'Failed to update announcement status.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Get announcements with creator info
try {
    $stmt = $pdo->prepare("
        SELECT a.*, 
               CONCAT(ad.username) as created_by_name
        FROM announcements a
        LEFT JOIN admins ad ON a.created_by = ad.id
        ORDER BY a.priority DESC, a.publish_date DESC, a.created_at DESC
    ");
    $stmt->execute();
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    $announcements = [];
    $error = 'Failed to fetch announcements.';
}

// Get statistics
$stats = [
    'total_announcements' => count($announcements),
    'published' => count(array_filter($announcements, function($a) { return $a['status'] === 'published'; })),
    'draft' => count(array_filter($announcements, function($a) { return $a['status'] === 'draft'; })),
    'archived' => count(array_filter($announcements, function($a) { return $a['status'] === 'archived'; }))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Admin Panel</title>
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
                <li><a href="announcements.php" class="active"><i class="fas fa-bullhorn"></i>Announcements</a></li>
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
                        <h1 class="content-title">Announcements Management</h1>
                        <p class="content-subtitle">Create and manage announcements for students</p>
                    </div>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                        <i class="fas fa-plus me-2"></i>Create Announcement
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
                        <div class="stat-icon bg-primary"><i class="fas fa-bullhorn"></i></div>
                        <div class="stat-number"><?php echo $stats['total_announcements']; ?></div>
                        <div class="stat-label">Total Announcements</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-eye"></i></div>
                        <div class="stat-number"><?php echo $stats['published']; ?></div>
                        <div class="stat-label">Published</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-edit"></i></div>
                        <div class="stat-number"><?php echo $stats['draft']; ?></div>
                        <div class="stat-label">Draft</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-secondary"><i class="fas fa-archive"></i></div>
                        <div class="stat-number"><?php echo $stats['archived']; ?></div>
                        <div class="stat-label">Archived</div>
                    </div>
                </div>

                <!-- Announcements List -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>All Announcements</h5>
                            <div class="card-tools">
                                <select class="form-control" id="statusFilter" onchange="filterAnnouncements()">
                                    <option value="">All Status</option>
                                    <option value="published">Published</option>
                                    <option value="draft">Draft</option>
                                    <option value="archived">Archived</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Announcements</h4>
                                <p class="text-muted">No announcements have been created yet.</p>
                            </div>
                        <?php else: ?>
                            <div id="announcementsList">
                                <?php foreach ($announcements as $announcement): ?>
                                    <div class="card mb-3 announcement-item" data-status="<?php echo $announcement['status']; ?>">
                                        <div class="card-header">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h6 class="card-title mb-0">
                                                        <span class="badge badge-<?php 
                                                            switch($announcement['priority']) {
                                                                case 'high': echo 'danger'; break;
                                                                case 'medium': echo 'warning'; break;
                                                                case 'low': echo 'info'; break;
                                                                default: echo 'secondary';
                                                            }
                                                        ?> me-2">
                                                            <?php echo ucfirst($announcement['priority']); ?>
                                                        </span>
                                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                                    </h6>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <span class="badge badge-<?php 
                                                        switch($announcement['status']) {
                                                            case 'published': echo 'success'; break;
                                                            case 'draft': echo 'warning'; break;
                                                            case 'archived': echo 'secondary'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?> me-2">
                                                        <?php echo ucfirst($announcement['status']); ?>
                                                    </span>
                                                    <div class="btn-group" role="group">
                                                        <button type="button" class="btn btn-sm btn-info" onclick="viewAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-warning" onclick="editAnnouncement(<?php echo htmlspecialchars(json_encode($announcement)); ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                                <i class="fas fa-cog"></i>
                                                            </button>
                                                            <ul class="dropdown-menu">
                                                                <?php if ($announcement['status'] !== 'published'): ?>
                                                                    <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?php echo $announcement['id']; ?>, 'published')">
                                                                        <i class="fas fa-eye me-2"></i>Publish
                                                                    </a></li>
                                                                <?php endif; ?>
                                                                <?php if ($announcement['status'] !== 'draft'): ?>
                                                                    <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?php echo $announcement['id']; ?>, 'draft')">
                                                                        <i class="fas fa-edit me-2"></i>Move to Draft
                                                                    </a></li>
                                                                <?php endif; ?>
                                                                <?php if ($announcement['status'] !== 'archived'): ?>
                                                                    <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?php echo $announcement['id']; ?>, 'archived')">
                                                                        <i class="fas fa-archive me-2"></i>Archive
                                                                    </a></li>
                                                                <?php endif; ?>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item text-danger" href="#" onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>)">
                                                                    <i class="fas fa-trash me-2"></i>Delete
                                                                </a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            <div class="announcement-content">
                                                <?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 200))); ?>
                                                <?php if (strlen($announcement['content']) > 200): ?>
                                                    <span class="text-muted">...</span>
                                                <?php endif; ?>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>
                                                        By: <?php echo htmlspecialchars($announcement['created_by_name']); ?>
                                                    </small>
                                                </div>
                                                <div class="col-md-6 text-end">
                                                    <small class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        Publish: <?php echo formatDateTime($announcement['publish_date']); ?>
                                                        <?php if ($announcement['expiry_date']): ?>
                                                            <br><i class="fas fa-clock me-1"></i>
                                                            Expires: <?php echo formatDateTime($announcement['expiry_date']); ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Add Announcement Modal -->
    <div class="modal fade" id="addAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Enter announcement title">
                        </div>
                        <div class="form-group">
                            <label for="content" class="form-label">Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="6" required placeholder="Enter announcement content..."></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="priority" class="form-label">Priority *</label>
                                    <select class="form-control" id="priority" name="priority" required>
                                        <option value="">Select Priority</option>
                                        <option value="high">High - Critical</option>
                                        <option value="medium">Medium - Important</option>
                                        <option value="low">Low - General</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="publish_date" class="form-label">Publish Date *</label>
                                    <input type="datetime-local" class="form-control" id="publish_date" name="publish_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="expiry_date" class="form-label">Expiry Date (Optional)</label>
                                    <input type="datetime-local" class="form-control" id="expiry_date" name="expiry_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_announcement" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create & Publish
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Announcement Modal -->
    <div class="modal fade" id="editAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_announcement_id" name="announcement_id">
                        <div class="form-group">
                            <label for="edit_title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_content" class="form-label">Content *</label>
                            <textarea class="form-control" id="edit_content" name="content" rows="6" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_priority" class="form-label">Priority *</label>
                                    <select class="form-control" id="edit_priority" name="priority" required>
                                        <option value="">Select Priority</option>
                                        <option value="high">High - Critical</option>
                                        <option value="medium">Medium - Important</option>
                                        <option value="low">Low - General</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_publish_date" class="form-label">Publish Date *</label>
                                    <input type="datetime-local" class="form-control" id="edit_publish_date" name="publish_date" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_expiry_date" class="form-label">Expiry Date (Optional)</label>
                                    <input type="datetime-local" class="form-control" id="edit_expiry_date" name="expiry_date">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_announcement" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Announcement
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Announcement Modal -->
    <div class="modal fade" id="viewAnnouncementModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i><span id="view_title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <span id="view_priority_badge"></span>
                        <span id="view_status_badge"></span>
                    </div>
                    <div id="view_content" class="mb-4"></div>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                Created by: <span id="view_creator"></span>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Publish: <span id="view_publish_date"></span>
                                <div id="view_expiry_date"></div>
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

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteAnnouncementModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Announcement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this announcement? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" id="delete_announcement_id" name="announcement_id">
                        <button type="submit" name="delete_announcement" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Toggle Form (Hidden) -->
    <form id="statusToggleForm" method="POST" action="" style="display: none;">
        <input type="hidden" id="toggle_announcement_id" name="announcement_id">
        <input type="hidden" id="toggle_new_status" name="new_status">
        <input type="hidden" name="toggle_status" value="1">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        // Set default publish date to current date/time
        document.getElementById('publish_date').value = new Date().toISOString().slice(0, 16);

        function viewAnnouncement(announcement) {
            document.getElementById('view_title').textContent = announcement.title;
            document.getElementById('view_content').innerHTML = announcement.content.replace(/\n/g, '<br>');
            document.getElementById('view_creator').textContent = announcement.created_by_name || 'Unknown';
            document.getElementById('view_publish_date').textContent = formatDateTime(announcement.publish_date);
            
            // Priority badge
            const priorityColors = {high: 'danger', medium: 'warning', low: 'info'};
            document.getElementById('view_priority_badge').innerHTML = 
                `<span class="badge badge-${priorityColors[announcement.priority] || 'secondary'} me-2">${announcement.priority.charAt(0).toUpperCase() + announcement.priority.slice(1)}</span>`;
            
            // Status badge
            const statusColors = {published: 'success', draft: 'warning', archived: 'secondary'};
            document.getElementById('view_status_badge').innerHTML = 
                `<span class="badge badge-${statusColors[announcement.status] || 'secondary'}">${announcement.status.charAt(0).toUpperCase() + announcement.status.slice(1)}</span>`;
            
            // Expiry date
            if (announcement.expiry_date) {
                document.getElementById('view_expiry_date').innerHTML = 
                    `<br><i class="fas fa-clock me-1"></i>Expires: ${formatDateTime(announcement.expiry_date)}`;
            } else {
                document.getElementById('view_expiry_date').innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('viewAnnouncementModal')).show();
        }

        function editAnnouncement(announcement) {
            document.getElementById('edit_announcement_id').value = announcement.id;
            document.getElementById('edit_title').value = announcement.title;
            document.getElementById('edit_content').value = announcement.content;
            document.getElementById('edit_priority').value = announcement.priority;
            
            // Format dates for datetime-local input
            const publishDate = new Date(announcement.publish_date);
            document.getElementById('edit_publish_date').value = publishDate.toISOString().slice(0, 16);
            
            if (announcement.expiry_date) {
                const expiryDate = new Date(announcement.expiry_date);
                document.getElementById('edit_expiry_date').value = expiryDate.toISOString().slice(0, 16);
            } else {
                document.getElementById('edit_expiry_date').value = '';
            }
            
            new bootstrap.Modal(document.getElementById('editAnnouncementModal')).show();
        }

        function deleteAnnouncement(announcementId) {
            document.getElementById('delete_announcement_id').value = announcementId;
            new bootstrap.Modal(document.getElementById('deleteAnnouncementModal')).show();
        }

        function toggleStatus(announcementId, newStatus) {
            document.getElementById('toggle_announcement_id').value = announcementId;
            document.getElementById('toggle_new_status').value = newStatus;
            document.getElementById('statusToggleForm').submit();
        }

        function filterAnnouncements() {
            const statusFilter = document.getElementById('statusFilter').value;
            const announcements = document.querySelectorAll('.announcement-item');
            
            announcements.forEach(item => {
                const status = item.getAttribute('data-status');
                if (statusFilter === '' || status === statusFilter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
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