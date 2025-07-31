<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_policy'])) {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $category = sanitizeInput($_POST['category']);
        $effective_date = sanitizeInput($_POST['effective_date']);
        
        if ($title && $content && $category) {
            try {
                $stmt = $pdo->prepare("INSERT INTO policies (title, content, category, effective_date, created_by, status) VALUES (?, ?, ?, ?, ?, 'active')");
                if ($stmt->execute([$title, $content, $category, $effective_date, $_SESSION['admin_id']])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'add_policy', "Added policy: $title");
                    $success = true;
                } else {
                    $error = 'Failed to add policy.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['edit_policy'])) {
        $policy_id = sanitizeInput($_POST['policy_id']);
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $category = sanitizeInput($_POST['category']);
        $effective_date = sanitizeInput($_POST['effective_date']);
        $status = sanitizeInput($_POST['status']);
        
        if ($policy_id && $title && $content && $category) {
            try {
                $stmt = $pdo->prepare("UPDATE policies SET title=?, content=?, category=?, effective_date=?, status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
                if ($stmt->execute([$title, $content, $category, $effective_date, $status, $policy_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'edit_policy', "Updated policy ID: $policy_id");
                    $success = true;
                } else {
                    $error = 'Failed to update policy.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['delete_policy'])) {
        $policy_id = sanitizeInput($_POST['policy_id']);
        
        if ($policy_id) {
            try {
                $stmt = $pdo->prepare("DELETE FROM policies WHERE id=?");
                if ($stmt->execute([$policy_id])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'delete_policy', "Deleted policy ID: $policy_id");
                    $success = true;
                } else {
                    $error = 'Failed to delete policy.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['upload_policy_file'])) {
        $title = sanitizeInput($_POST['file_title']);
        $category = sanitizeInput($_POST['file_category']);
        $description = sanitizeInput($_POST['file_description']);
        
        if ($title && $category && isset($_FILES['policy_file'])) {
            $uploadResult = uploadFile($_FILES['policy_file'], '../uploads/policies/', ['pdf', 'doc', 'docx'], 5242880); // 5MB
            
            if ($uploadResult['success']) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO policies (title, content, category, file_path, original_filename, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'active')");
                    if ($stmt->execute([$title, $description, $category, $uploadResult['filename'], $_FILES['policy_file']['name'], $_SESSION['admin_id']])) {
                        logActivity($_SESSION['admin_id'], 'admin', 'upload_policy_file', "Uploaded policy file: $title");
                        $success = true;
                    } else {
                        $error = 'Failed to save policy file information.';
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
}

// Get policies
try {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               a.username as created_by_name
        FROM policies p
        LEFT JOIN admins a ON p.created_by = a.id
        ORDER BY p.category, p.created_at DESC
    ");
    $stmt->execute();
    $policies = $stmt->fetchAll();
} catch (PDOException $e) {
    $policies = [];
    $error = 'Failed to fetch policies.';
}

// Create uploads directory if it doesn't exist
if (!file_exists('../uploads/policies/')) {
    mkdir('../uploads/policies/', 0755, true);
}

// Group policies by category
$policies_by_category = [];
foreach ($policies as $policy) {
    $policies_by_category[$policy['category']][] = $policy;
}

$stats = [
    'total_policies' => count($policies),
    'active_policies' => count(array_filter($policies, function($p) { return $p['status'] === 'active'; })),
    'draft_policies' => count(array_filter($policies, function($p) { return $p['status'] === 'draft'; })),
    'categories' => count($policies_by_category)
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Policies Management - Admin Panel</title>
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
                <li><a href="policies.php" class="active"><i class="fas fa-file-contract"></i>Policies</a></li>
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
                        <h1 class="content-title">Policies Management</h1>
                        <p class="content-subtitle">Upload and manage dormitory rules, policies and offense descriptions</p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#uploadFileModal">
                            <i class="fas fa-upload me-2"></i>Upload Policy File
                        </button>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
                            <i class="fas fa-plus me-2"></i>Add Policy
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

                <!-- Statistics -->
                <div class="stats-grid mb-4">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary"><i class="fas fa-file-contract"></i></div>
                        <div class="stat-number"><?php echo $stats['total_policies']; ?></div>
                        <div class="stat-label">Total Policies</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-number"><?php echo $stats['active_policies']; ?></div>
                        <div class="stat-label">Active Policies</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-edit"></i></div>
                        <div class="stat-number"><?php echo $stats['draft_policies']; ?></div>
                        <div class="stat-label">Draft Policies</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-tags"></i></div>
                        <div class="stat-number"><?php echo $stats['categories']; ?></div>
                        <div class="stat-label">Categories</div>
                    </div>
                </div>

                <!-- Policies by Category -->
                <?php if (empty($policies)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-file-contract fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Policies Found</h4>
                            <p class="text-muted">No dormitory policies have been created yet.</p>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPolicyModal">
                                <i class="fas fa-plus me-2"></i>Add First Policy
                            </button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($policies_by_category as $category => $category_policies): ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-folder me-2"></i>
                                    <?php echo ucfirst(str_replace('_', ' ', $category)); ?> Policies
                                    <span class="badge badge-secondary ms-2"><?php echo count($category_policies); ?></span>
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($category_policies as $policy): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="card-title">
                                                                <?php echo htmlspecialchars($policy['title']); ?>
                                                                <span class="badge badge-<?php echo $policy['status'] === 'active' ? 'success' : 'warning'; ?> ms-2">
                                                                    <?php echo ucfirst($policy['status']); ?>
                                                                </span>
                                                            </h6>
                                                            <p class="card-text text-muted">
                                                                <?php echo htmlspecialchars(substr($policy['content'], 0, 100)); ?>
                                                                <?php if (strlen($policy['content']) > 100): ?>...<?php endif; ?>
                                                            </p>
                                                            <small class="text-muted">
                                                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($policy['created_by_name'] ?: 'Unknown'); ?>
                                                                <i class="fas fa-calendar ms-3 me-1"></i><?php echo formatDateTime($policy['created_at']); ?>
                                                                <?php if ($policy['effective_date']): ?>
                                                                    <br><i class="fas fa-clock me-1"></i>Effective: <?php echo formatDateTime($policy['effective_date']); ?>
                                                                <?php endif; ?>
                                                            </small>
                                                        </div>
                                                        <div class="btn-group ms-2" role="group">
                                                            <button type="button" class="btn btn-sm btn-info" onclick="viewPolicy(<?php echo htmlspecialchars(json_encode($policy)); ?>)">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-warning" onclick="editPolicy(<?php echo htmlspecialchars(json_encode($policy)); ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($policy['file_path']): ?>
                                                                <a href="../uploads/policies/<?php echo $policy['file_path']; ?>" target="_blank" class="btn btn-sm btn-secondary">
                                                                    <i class="fas fa-download"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                            <button type="button" class="btn btn-sm btn-danger" onclick="deletePolicy(<?php echo $policy['id']; ?>, '<?php echo htmlspecialchars($policy['title']); ?>')">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Policy Modal -->
    <div class="modal fade" id="addPolicyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Add New Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="title" class="form-label">Policy Title *</label>
                            <input type="text" class="form-control" id="title" name="title" required placeholder="Enter policy title">
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="category" class="form-label">Category *</label>
                                    <select class="form-control" id="category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="general_rules">General Rules</option>
                                        <option value="room_policies">Room Policies</option>
                                        <option value="visitor_policies">Visitor Policies</option>
                                        <option value="conduct_rules">Conduct Rules</option>
                                        <option value="safety_guidelines">Safety Guidelines</option>
                                        <option value="offense_descriptions">Offense Descriptions</option>
                                        <option value="disciplinary_actions">Disciplinary Actions</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="effective_date" class="form-label">Effective Date</label>
                                    <input type="date" class="form-control" id="effective_date" name="effective_date">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="content" class="form-label">Policy Content *</label>
                            <textarea class="form-control" id="content" name="content" rows="8" required placeholder="Enter the detailed policy content..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_policy" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Add Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Policy File Modal -->
    <div class="modal fade" id="uploadFileModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Upload Policy File</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="file_title" class="form-label">File Title *</label>
                            <input type="text" class="form-control" id="file_title" name="file_title" required placeholder="Enter file title">
                        </div>
                        <div class="form-group">
                            <label for="file_category" class="form-label">Category *</label>
                            <select class="form-control" id="file_category" name="file_category" required>
                                <option value="">Select Category</option>
                                <option value="general_rules">General Rules</option>
                                <option value="room_policies">Room Policies</option>
                                <option value="visitor_policies">Visitor Policies</option>
                                <option value="conduct_rules">Conduct Rules</option>
                                <option value="safety_guidelines">Safety Guidelines</option>
                                <option value="offense_descriptions">Offense Descriptions</option>
                                <option value="disciplinary_actions">Disciplinary Actions</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="file_description" class="form-label">Description</label>
                            <textarea class="form-control" id="file_description" name="file_description" rows="3" placeholder="Brief description of the policy file..."></textarea>
                        </div>
                        <div class="form-group">
                            <label for="policy_file" class="form-label">Policy File *</label>
                            <input type="file" class="form-control" id="policy_file" name="policy_file" required accept=".pdf,.doc,.docx">
                            <small class="form-text text-muted">Supported formats: PDF, DOC, DOCX (Max: 5MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="upload_policy_file" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload File
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Policy Modal -->
    <div class="modal fade" id="viewPolicyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-eye me-2"></i><span id="view_policy_title"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <span id="view_policy_category_badge"></span>
                        <span id="view_policy_status_badge"></span>
                    </div>
                    <div id="view_policy_content" class="mb-4"></div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                Created by: <span id="view_policy_creator"></span>
                            </small>
                        </div>
                        <div class="col-md-6 text-end">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i>
                                Created: <span id="view_policy_date"></span>
                                <div id="view_policy_effective_date"></div>
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

    <!-- Edit Policy Modal -->
    <div class="modal fade" id="editPolicyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_policy_id" name="policy_id">
                        <div class="form-group">
                            <label for="edit_title" class="form-label">Policy Title *</label>
                            <input type="text" class="form-control" id="edit_title" name="title" required>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_category" class="form-label">Category *</label>
                                    <select class="form-control" id="edit_category" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="general_rules">General Rules</option>
                                        <option value="room_policies">Room Policies</option>
                                        <option value="visitor_policies">Visitor Policies</option>
                                        <option value="conduct_rules">Conduct Rules</option>
                                        <option value="safety_guidelines">Safety Guidelines</option>
                                        <option value="offense_descriptions">Offense Descriptions</option>
                                        <option value="disciplinary_actions">Disciplinary Actions</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_status" class="form-label">Status *</label>
                                    <select class="form-control" id="edit_status" name="status" required>
                                        <option value="active">Active</option>
                                        <option value="draft">Draft</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="edit_effective_date" class="form-label">Effective Date</label>
                                    <input type="date" class="form-control" id="edit_effective_date" name="effective_date">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_content" class="form-label">Policy Content *</label>
                            <textarea class="form-control" id="edit_content" name="content" rows="8" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_policy" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Policy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deletePolicyModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>Delete Policy</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this policy?</p>
                    <p><strong>Policy:</strong> <span id="delete_policy_name"></span></p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        This action cannot be undone. The policy will be permanently deleted.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" action="" style="display: inline;">
                        <input type="hidden" id="delete_policy_id" name="policy_id">
                        <button type="submit" name="delete_policy" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Policy
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function viewPolicy(policy) {
            document.getElementById('view_policy_title').textContent = policy.title;
            document.getElementById('view_policy_content').innerHTML = policy.content.replace(/\n/g, '<br>');
            document.getElementById('view_policy_creator').textContent = policy.created_by_name || 'Unknown';
            document.getElementById('view_policy_date').textContent = formatDateTime(policy.created_at);
            
            // Category badge
            document.getElementById('view_policy_category_badge').innerHTML = 
                `<span class="badge badge-info me-2">${policy.category.replace('_', ' ').toUpperCase()}</span>`;
            
            // Status badge
            const statusColors = {active: 'success', draft: 'warning', archived: 'secondary'};
            document.getElementById('view_policy_status_badge').innerHTML = 
                `<span class="badge badge-${statusColors[policy.status] || 'secondary'}">${policy.status.toUpperCase()}</span>`;
            
            // Effective date
            if (policy.effective_date) {
                document.getElementById('view_policy_effective_date').innerHTML = 
                    `<br><i class="fas fa-clock me-1"></i>Effective: ${formatDateTime(policy.effective_date)}`;
            } else {
                document.getElementById('view_policy_effective_date').innerHTML = '';
            }
            
            new bootstrap.Modal(document.getElementById('viewPolicyModal')).show();
        }

        function editPolicy(policy) {
            document.getElementById('edit_policy_id').value = policy.id;
            document.getElementById('edit_title').value = policy.title;
            document.getElementById('edit_content').value = policy.content;
            document.getElementById('edit_category').value = policy.category;
            document.getElementById('edit_status').value = policy.status;
            
            if (policy.effective_date) {
                document.getElementById('edit_effective_date').value = policy.effective_date.split(' ')[0];
            }
            
            new bootstrap.Modal(document.getElementById('editPolicyModal')).show();
        }

        function deletePolicy(policyId, policyTitle) {
            document.getElementById('delete_policy_id').value = policyId;
            document.getElementById('delete_policy_name').textContent = policyTitle;
            new bootstrap.Modal(document.getElementById('deletePolicyModal')).show();
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>