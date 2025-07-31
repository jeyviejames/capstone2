<?php
require_once '../includes/auth.php';
requireAdminLogin();

// Get buildings with room statistics
try {
    $stmt = $pdo->prepare("
        SELECT b.*,
               COUNT(r.id) as total_rooms,
               SUM(r.capacity) as total_capacity,
               SUM(r.occupied_beds) as total_occupied,
               SUM(r.capacity - r.occupied_beds) as total_available
        FROM buildings b
        LEFT JOIN rooms r ON b.id = r.building_id
        GROUP BY b.id
        ORDER BY b.name
    ");
    $stmt->execute();
    $buildings = $stmt->fetchAll();
} catch (PDOException $e) {
    $buildings = [];
    $error = 'Failed to fetch building data.';
}

// Get detailed room information
try {
    $stmt = $pdo->prepare("
        SELECT r.*,
               b.name as building_name,
               (r.capacity - r.occupied_beds) as available_beds,
               ROUND((r.occupied_beds / r.capacity) * 100, 1) as occupancy_rate
        FROM rooms r
        LEFT JOIN buildings b ON r.building_id = b.id
        ORDER BY b.name, r.room_number
    ");
    $stmt->execute();
    $rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $rooms = [];
}

// Get students in rooms
try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.student_id, s.first_name, s.last_name, s.status,
               r.room_number, b.name as building_name, r.id as room_id
        FROM students s
        LEFT JOIN rooms r ON s.room_id = r.id
        LEFT JOIN buildings b ON r.building_id = b.id
        WHERE s.status = 'approved'
        ORDER BY b.name, r.room_number, s.first_name
    ");
    $stmt->execute();
    $students_in_rooms = $stmt->fetchAll();
} catch (PDOException $e) {
    $students_in_rooms = [];
}

// Calculate overall statistics
$total_buildings = count($buildings);
$total_rooms = array_sum(array_column($buildings, 'total_rooms'));
$total_capacity = array_sum(array_column($buildings, 'total_capacity'));
$total_occupied = array_sum(array_column($buildings, 'total_occupied'));
$total_available = $total_capacity - $total_occupied;
$overall_occupancy = $total_capacity > 0 ? round(($total_occupied / $total_capacity) * 100, 1) : 0;

$stats = [
    'total_buildings' => $total_buildings,
    'total_rooms' => $total_rooms,
    'total_capacity' => $total_capacity,
    'total_occupied' => $total_occupied,
    'total_available' => $total_available,
    'occupancy_rate' => $overall_occupancy
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - Admin Panel</title>
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
                <li><a href="room_management.php" class="active"><i class="fas fa-bed"></i>Room Management</a></li>
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
                        <h1 class="content-title">Room Management & Occupancy</h1>
                        <p class="content-subtitle">Monitor capacity and track room occupancy by building</p>
                    </div>
                    <a href="add_rooms.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Add Rooms & Buildings
                    </a>
                </div>
            </div>

            <div class="content">
                <!-- Overall Statistics -->
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
                        <div class="stat-icon bg-secondary"><i class="fas fa-bed"></i></div>
                        <div class="stat-number"><?php echo $stats['total_capacity']; ?></div>
                        <div class="stat-label">Total Capacity</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-success"><i class="fas fa-user-check"></i></div>
                        <div class="stat-number"><?php echo $stats['total_occupied']; ?></div>
                        <div class="stat-label">Occupied Beds</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-warning"><i class="fas fa-bed"></i></div>
                        <div class="stat-number"><?php echo $stats['total_available']; ?></div>
                        <div class="stat-label">Available Beds</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-<?php echo $stats['occupancy_rate'] > 80 ? 'danger' : ($stats['occupancy_rate'] > 60 ? 'warning' : 'success'); ?>">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <div class="stat-number"><?php echo $stats['occupancy_rate']; ?>%</div>
                        <div class="stat-label">Occupancy Rate</div>
                    </div>
                </div>

                <div class="row">
                    <!-- Buildings Overview -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-building me-2"></i>Buildings Overview</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($buildings)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-building fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Buildings</h5>
                                        <p class="text-muted">No buildings have been added yet.</p>
                                        <a href="add_rooms.php" class="btn btn-primary">Add First Building</a>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($buildings as $building): ?>
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="row align-items-center">
                                                    <div class="col-md-8">
                                                        <h6 class="card-title mb-1"><?php echo htmlspecialchars($building['name']); ?></h6>
                                                        <p class="card-text text-muted mb-2">
                                                            <?php echo htmlspecialchars($building['description'] ?: 'No description'); ?>
                                                        </p>
                                                        <div class="row text-center">
                                                            <div class="col">
                                                                <small class="text-muted">Rooms</small>
                                                                <div class="fw-bold"><?php echo $building['total_rooms']; ?></div>
                                                            </div>
                                                            <div class="col">
                                                                <small class="text-muted">Capacity</small>
                                                                <div class="fw-bold"><?php echo $building['total_capacity']; ?></div>
                                                            </div>
                                                            <div class="col">
                                                                <small class="text-muted">Occupied</small>
                                                                <div class="fw-bold text-success"><?php echo $building['total_occupied']; ?></div>
                                                            </div>
                                                            <div class="col">
                                                                <small class="text-muted">Available</small>
                                                                <div class="fw-bold text-warning"><?php echo $building['total_available']; ?></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4 text-center">
                                                        <?php 
                                                        $building_occupancy = $building['total_capacity'] > 0 ? 
                                                            round(($building['total_occupied'] / $building['total_capacity']) * 100, 1) : 0;
                                                        ?>
                                                        <div class="progress-circle" data-percentage="<?php echo $building_occupancy; ?>">
                                                            <svg class="progress-ring" width="80" height="80">
                                                                <circle class="progress-ring-bg" cx="40" cy="40" r="35"></circle>
                                                                <circle class="progress-ring-progress" cx="40" cy="40" r="35" 
                                                                        style="stroke-dasharray: <?php echo 2 * 3.14159 * 35; ?>; 
                                                                               stroke-dashoffset: <?php echo 2 * 3.14159 * 35 * (1 - $building_occupancy/100); ?>;">
                                                                </circle>
                                                            </svg>
                                                            <div class="progress-text">
                                                                <span class="percentage"><?php echo $building_occupancy; ?>%</span>
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

                    <!-- Detailed Room Status -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-door-open me-2"></i>Room Details</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($rooms)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No Rooms</h5>
                                        <p class="text-muted">No rooms have been added yet.</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                        <table class="table table-sm">
                                            <thead class="sticky-top bg-white">
                                                <tr>
                                                    <th>Room</th>
                                                    <th>Capacity</th>
                                                    <th>Occupied</th>
                                                    <th>Available</th>
                                                    <th>Rate</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($rooms as $room): ?>
                                                    <tr onclick="viewRoomDetails(<?php echo $room['id']; ?>)" style="cursor: pointer;">
                                                        <td>
                                                            <strong><?php echo $room['building_name']; ?></strong>
                                                            <br>
                                                            <small class="text-muted">Room <?php echo $room['room_number']; ?></small>
                                                        </td>
                                                        <td><?php echo $room['capacity']; ?></td>
                                                        <td>
                                                            <span class="badge badge-<?php echo $room['occupied_beds'] > 0 ? 'success' : 'secondary'; ?>">
                                                                <?php echo $room['occupied_beds']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?php echo $room['available_beds'] > 0 ? 'warning' : 'danger'; ?>">
                                                                <?php echo $room['available_beds']; ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge badge-<?php 
                                                                if ($room['occupancy_rate'] >= 100) echo 'danger';
                                                                elseif ($room['occupancy_rate'] >= 75) echo 'warning';
                                                                elseif ($room['occupancy_rate'] > 0) echo 'success';
                                                                else echo 'secondary';
                                                            ?>">
                                                                <?php echo $room['occupancy_rate']; ?>%
                                                            </span>
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
                </div>

                <!-- Students Assignment Overview -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-users me-2"></i>Student Room Assignments</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($students_in_rooms)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No Student Assignments</h5>
                                <p class="text-muted">No students have been assigned to rooms yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Student</th>
                                            <th>Student ID</th>
                                            <th>Building</th>
                                            <th>Room</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $current_building = '';
                                        foreach ($students_in_rooms as $student): 
                                            if ($student['room_id']): // Only show students with room assignments
                                        ?>
                                            <?php if ($current_building !== $student['building_name']): ?>
                                                <?php $current_building = $student['building_name']; ?>
                                                <tr class="table-secondary">
                                                    <td colspan="5"><strong><?php echo $current_building ?: 'Unassigned'; ?></strong></td>
                                                </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <td><?php echo $student['first_name'] . ' ' . $student['last_name']; ?></td>
                                                <td><?php echo $student['student_id']; ?></td>
                                                <td><?php echo $student['building_name'] ?: 'N/A'; ?></td>
                                                <td><?php echo $student['room_number'] ?: 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-success">Active</span>
                                                </td>
                                            </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
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
        function viewRoomDetails(roomId) {
            // This could open a modal with detailed room information
            // For now, we'll just show an alert
            alert('Room details for Room ID: ' + roomId + '\n\nThis feature can be extended to show detailed room information, student list, etc.');
        }
    </script>
</body>
</html>