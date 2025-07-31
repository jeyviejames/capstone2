<?php
require_once '../includes/auth.php';

// Log the logout activity
if (isStudentLoggedIn()) {
    logActivity($_SESSION['student_id'], 'student', 'logout', 'Student logged out');
}

studentLogout();
?>