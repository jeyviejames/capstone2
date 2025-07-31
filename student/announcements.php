<?php
require_once '../includes/auth.php';
requireStudentLogin();

$student_info = getStudentInfo($_SESSION['student_id']);

// Get all published announcements
try {
    $stmt = $pdo->query("SELECT a.*, ad.username as created_by_name 
                        FROM announcements a 
                        JOIN admins ad ON a.created_by = ad.id 
                        WHERE a.status = 'published' 
                        ORDER BY a.created_at DESC");
    $announcements = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Announcements error: " . $e->getMessage());
    $announcements = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - Dormitory Management System</title>
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
                    <a href="announcements.php" class="nav-link active">
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
                <h1 class="header-title">Announcements</h1>
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
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Dormitory Announcements</h2>
                        <p class="text-muted mb-0">Stay updated with the latest news and information</p>
                    </div>
                    <div>
                        <button class="btn btn-success" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>

                <!-- Search -->
                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <input type="text" class="form-control" placeholder="Search announcements..." id="searchAnnouncements">
                            </div>
                            <div class="col-md-4">
                                <select class="form-control" id="sortAnnouncements">
                                    <option value="newest">Newest First</option>
                                    <option value="oldest">Oldest First</option>
                                    <option value="title">By Title</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Announcements List -->
                <div id="announcementsList">
                    <?php if (empty($announcements)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                <h4 class="text-muted">No Announcements Available</h4>
                                <p class="text-muted">Check back later for updates and announcements from the administration.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="card mb-4 announcement-item">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <h5 class="card-title mb-0">
                                                <i class="fas fa-bullhorn me-2 text-warning"></i>
                                                <?php echo htmlspecialchars($announcement['title']); ?>
                                            </h5>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo formatDateTime($announcement['created_at']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="announcement-content">
                                        <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            Posted by: <?php echo htmlspecialchars($announcement['created_by_name']); ?>
                                        </small>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?php echo getTimeAgo($announcement['created_at']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Load More Button (if needed for pagination) -->
                <?php if (count($announcements) >= 10): ?>
                    <div class="text-center">
                        <button class="btn btn-outline-primary" id="loadMoreBtn">
                            <i class="fas fa-plus me-2"></i>Load More Announcements
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
    
    <script>
        // Search functionality
        document.getElementById('searchAnnouncements').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const announcements = document.querySelectorAll('.announcement-item');
            
            announcements.forEach(item => {
                const title = item.querySelector('.card-title').textContent.toLowerCase();
                const content = item.querySelector('.announcement-content').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || content.includes(searchTerm)) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });

        // Sort functionality
        document.getElementById('sortAnnouncements').addEventListener('change', function() {
            const sortBy = this.value;
            const container = document.getElementById('announcementsList');
            const announcements = Array.from(container.querySelectorAll('.announcement-item'));
            
            announcements.sort((a, b) => {
                switch(sortBy) {
                    case 'newest':
                        const dateA = new Date(a.querySelector('.text-muted').textContent.split('Posted by:')[0].trim());
                        const dateB = new Date(b.querySelector('.text-muted').textContent.split('Posted by:')[0].trim());
                        return dateB - dateA;
                    case 'oldest':
                        const dateC = new Date(a.querySelector('.text-muted').textContent.split('Posted by:')[0].trim());
                        const dateD = new Date(b.querySelector('.text-muted').textContent.split('Posted by:')[0].trim());
                        return dateC - dateD;
                    case 'title':
                        const titleA = a.querySelector('.card-title').textContent.toLowerCase();
                        const titleB = b.querySelector('.card-title').textContent.toLowerCase();
                        return titleA.localeCompare(titleB);
                    default:
                        return 0;
                }
            });
            
            // Re-append sorted elements
            announcements.forEach(item => container.appendChild(item));
        });

        // Mark announcements as read (optional feature)
        function markAsRead(announcementId) {
            // Could implement read status tracking here
            console.log('Marked announcement ' + announcementId + ' as read');
        }

        // Auto-refresh every 5 minutes for new announcements
        setInterval(() => {
            // Could implement AJAX refresh here
            console.log('Checking for new announcements...');
        }, 300000);
    </script>
</body>
</html>