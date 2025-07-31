<?php
require_once '../includes/auth.php';
requireAdminLogin();

$success = false;
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_building'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description']);
        $address = sanitizeInput($_POST['address']);
        
        if ($name) {
            try {
                $stmt = $pdo->prepare("INSERT INTO buildings (name, description, address) VALUES (?, ?, ?)");
                if ($stmt->execute([$name, $description, $address])) {
                    logActivity($_SESSION['admin_id'], 'admin', 'add_building', "Added building: $name");
                    $success = true;
                } else {
                    $error = 'Failed to add building.';
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['add_room'])) {
        $building_id = sanitizeInput($_POST['building_id']);
        $room_number = sanitizeInput($_POST['room_number']);
        $capacity = sanitizeInput($_POST['capacity']) ?: 4; // Default 4 bedspaces
        $description = sanitizeInput($_POST['description']);
        
        if ($building_id && $room_number) {
            try {
                // Check if room number already exists in this building
                $stmt = $pdo->prepare("SELECT id FROM rooms WHERE building_id = ? AND room_number = ?");
                $stmt->execute([$building_id, $room_number]);
                if ($stmt->fetch()) {
                    $error = 'Room number already exists in this building.';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO rooms (building_id, room_number, capacity, occupied_beds, description) VALUES (?, ?, ?, 0, ?)");
                    if ($stmt->execute([$building_id, $room_number, $capacity, $description])) {
                        logActivity($_SESSION['admin_id'], 'admin', 'add_room', "Added room $room_number to building ID: $building_id");
                        $success = true;
                    } else {
                        $error = 'Failed to add room.';
                    }
                }
            } catch (PDOException $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
    
    if (isset($_POST['bulk_add_rooms'])) {
        $building_id = sanitizeInput($_POST['bulk_building_id']);
        $floor_number = sanitizeInput($_POST['floor_number']);
        $rooms_per_floor = sanitizeInput($_POST['rooms_per_floor']);
        $room_capacity = sanitizeInput($_POST['room_capacity']) ?: 4;
        
        if ($building_id && $floor_number && $rooms_per_floor) {
            try {
                $pdo->beginTransaction();
                $added_rooms = 0;
                
                for ($i = 1; $i <= $rooms_per_floor; $i++) {
                    $room_number = $floor_number . str_pad($i, 2, '0', STR_PAD_LEFT);
                    
                    // Check if room already exists
                    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE building_id = ? AND room_number = ?");
                    $stmt->execute([$building_id, $room_number]);
                    if (!$stmt->fetch()) {
                        $stmt = $pdo->prepare("INSERT INTO rooms (building_id, room_number, capacity, occupied_beds, description) VALUES (?, ?, ?, 0, ?)");
                        if ($stmt->execute([$building_id, $room_number, $room_capacity, "Floor $floor_number Room"])) {
                            $added_rooms++;
                        }
                    }
                }
                
                $pdo->commit();
                logActivity($_SESSION['admin_id'], 'admin', 'bulk_add_rooms', "Added $added_rooms rooms to building ID: $building_id");
                $success = true;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $error = 'Database error: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}

// Get buildings
try {
    $stmt = $pdo->prepare("
        SELECT b.*,
               COUNT(r.id) as total_rooms,
               SUM(r.capacity) as total_capacity,
               SUM(r.occupied_beds) as total_occupied
        FROM buildings b
        LEFT JOIN rooms r ON b.id = r.building_id
        GROUP BY b.id
        ORDER BY b.name
    ");
    $stmt->execute();
    $buildings = $stmt->fetchAll();
} catch (PDOException $e) {
    $buildings = [];
}

// Get recent rooms
try {
    $stmt = $pdo->prepare("
        SELECT r.*, b.name as building_name
        FROM rooms r
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY r.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recent_rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $recent_rooms = [];
}

$stats = [
    'total_buildings' => count($buildings),
    'total_rooms' => array_sum(array_column($buildings, 'total_rooms')),
    'total_capacity' => array_sum(array_column($buildings, 'total_capacity')),
    'available_capacity' => array_sum(array_column($buildings, 'total_capacity')) - array_sum(array_column($buildings, 'total_occupied'))
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Rooms & Buildings - Admin Panel</title>
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
                <li><a href="add_rooms.php" class="active"><i class="fas fa-plus-square"></i>Add Rooms & Buildings</a></li>
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
                        <h1 class="content-title">Add Rooms & Buildings</h1>
                        <p class="content-subtitle">Create buildings, add rooms and manage dormitory expansion</p>
                    </div>
                    <a href="room_management.php" class="btn btn-secondary">
                        <i class="fas fa-chart-bar me-2"></i>View Room Management
                    </a>
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
                        <div class="stat-icon bg-primary"><i class="fas fa-building"></i></div>
                        <div class="stat-number"><?php echo $stats['total_buildings']; ?></div>
                        <div class="stat-label">Total Buildings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-info"><i class="fas fa-door-open"></i></div>
                        <div class="stat-number"><?php echo $stats['total_rooms']; ?></div>
                        <div class="stat-label">Total Rooms</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-bed"></i></div>
                        <div class="stat-number"><?php echo $stats['total_capacity']; ?></div>
                        <div class="stat-label">Total Bed Capacity</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-plus"></i></div>
                        <div class="stat-number"><?php echo $stats['available_capacity']; ?></div>
                        <div class="stat-label">Available Beds</div>
                    </div>
                </div>

                <div class="row">
                    <!-- Add Building -->
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-building me-2"></i>Add New Building</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label for="name" class="form-label">Building Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., Building A, North Hall">
                                    </div>
                                    <div class="form-group">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of the building..."></textarea>
                                    </div>
                                    <div class="form-group">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="2" placeholder="Building address or location..."></textarea>
                                    </div>
                                    <button type="submit" name="add_building" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Add Building
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Add Individual Room -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-door-open me-2"></i>Add Individual Room</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label for="building_id" class="form-label">Building *</label>
                                        <select class="form-control" id="building_id" name="building_id" required>
                                            <option value="">Select Building</option>
                                            <?php foreach ($buildings as $building): ?>
                                                <option value="<?php echo $building['id']; ?>">
                                                    <?php echo htmlspecialchars($building['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="room_number" class="form-label">Room Number *</label>
                                                <input type="text" class="form-control" id="room_number" name="room_number" required placeholder="e.g., 101, A-205">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="capacity" class="form-label">Bed Capacity</label>
                                                <input type="number" class="form-control" id="capacity" name="capacity" min="1" max="10" value="4" placeholder="Default: 4 beds">
                                                <small class="form-text text-muted">Default is 4 bedspaces per room</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="room_description" class="form-label">Room Description</label>
                                        <textarea class="form-control" id="room_description" name="description" rows="2" placeholder="Optional room description..."></textarea>
                                    </div>
                                    <button type="submit" name="add_room" class="btn btn-success">
                                        <i class="fas fa-plus me-2"></i>Add Room
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Bulk Add Rooms & Current Buildings -->
                    <div class="col-md-6">
                        <!-- Bulk Add Rooms -->
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-plus-square me-2"></i>Bulk Add Rooms (By Floor)</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="form-group">
                                        <label for="bulk_building_id" class="form-label">Building *</label>
                                        <select class="form-control" id="bulk_building_id" name="bulk_building_id" required>
                                            <option value="">Select Building</option>
                                            <?php foreach ($buildings as $building): ?>
                                                <option value="<?php echo $building['id']; ?>">
                                                    <?php echo htmlspecialchars($building['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="floor_number" class="form-label">Floor Number *</label>
                                                <input type="number" class="form-control" id="floor_number" name="floor_number" min="1" max="20" required placeholder="e.g., 2">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="rooms_per_floor" class="form-label">Rooms per Floor *</label>
                                                <input type="number" class="form-control" id="rooms_per_floor" name="rooms_per_floor" min="1" max="50" required placeholder="e.g., 10">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="room_capacity" class="form-label">Bed Capacity</label>
                                                <input type="number" class="form-control" id="room_capacity" name="room_capacity" min="1" max="10" value="4" placeholder="Default: 4">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        This will create rooms with numbers like: <strong>201, 202, 203...</strong> (Floor + Room Number)
                                    </div>
                                    <button type="submit" name="bulk_add_rooms" class="btn btn-warning">
                                        <i class="fas fa-layer-group me-2"></i>Bulk Add Rooms
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Current Buildings -->
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-list me-2"></i>Current Buildings</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($buildings)): ?>
                                    <div class="text-center py-3">
                                        <i class="fas fa-building fa-2x text-muted mb-2"></i>
                                        <p class="text-muted">No buildings created yet</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($buildings as $building): ?>
                                        <div class="card mb-2">
                                            <div class="card-body py-2">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h6 class="mb-1"><?php echo htmlspecialchars($building['name']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php echo htmlspecialchars($building['description'] ?: 'No description'); ?>
                                                        </small>
                                                    </div>
                                                    <div class="col-md-4 text-end">
                                                        <div class="row text-center">
                                                            <div class="col-4">
                                                                <small class="text-muted">Rooms</small>
                                                                <div class="fw-bold"><?php echo $building['total_rooms']; ?></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-muted">Capacity</small>
                                                                <div class="fw-bold"><?php echo $building['total_capacity']; ?></div>
                                                            </div>
                                                            <div class="col-4">
                                                                <small class="text-muted">Occupied</small>
                                                                <div class="fw-bold"><?php echo $building['total_occupied']; ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Rooms -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-clock me-2"></i>Recently Added Rooms</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_rooms)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Rooms Added</h5>
                                <p class="text-muted">No rooms have been created yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Building</th>
                                            <th>Room Number</th>
                                            <th>Capacity</th>
                                            <th>Occupied</th>
                                            <th>Available</th>
                                            <th>Added Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_rooms as $room): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($room['building_name']); ?></td>
                                                <td><strong><?php echo $room['room_number']; ?></strong></td>
                                                <td><?php echo $room['capacity']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $room['occupied_beds'] > 0 ? 'success' : 'secondary'; ?>">
                                                        <?php echo $room['occupied_beds']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php echo ($room['capacity'] - $room['occupied_beds']) > 0 ? 'warning' : 'danger'; ?>">
                                                        <?php echo ($room['capacity'] - $room['occupied_beds']); ?>
                                                    </span>
                                                </td>
                                                <td><small><?php echo formatDateTime($room['created_at']); ?></small></td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    <script>
        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString();
        }
    </script>
</body>
</html>