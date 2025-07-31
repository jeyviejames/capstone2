<?php
require_once '../includes/auth.php';

// Log the logout activity
if (isAdminLoggedIn()) {
    logActivity($_SESSION['admin_id'], 'admin', 'logout', 'Admin logged out');
}

adminLogout();
?>