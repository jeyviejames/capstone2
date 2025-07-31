<?php
session_start();
require_once '../config/database.php';

// Admin authentication functions
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ../login.php');
        exit();
    }
}

function adminLogin($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, password FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['user_type'] = 'admin';
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        return false;
    }
}

function adminLogout() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['user_type']);
    session_destroy();
    header('Location: ../login.php');
    exit();
}

function getAdminInfo($admin_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ?");
        $stmt->execute([$admin_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get admin info error: " . $e->getMessage());
        return false;
    }
}

// Student authentication functions
function isStudentLoggedIn() {
    return isset($_SESSION['student_id']) && !empty($_SESSION['student_id']);
}

function requireStudentLogin() {
    if (!isStudentLoggedIn()) {
        header('Location: ../student_login.php');
        exit();
    }
}

function studentLogin($student_id, $lrn) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ? AND lrn = ? AND status = 'approved'");
        $stmt->execute([$student_id, $lrn]);
        $student = $stmt->fetch();
        
        if ($student) {
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['student_number'] = $student['student_id'];
            $_SESSION['student_name'] = $student['first_name'] . ' ' . $student['last_name'];
            $_SESSION['user_type'] = 'student';
            return true;
        }
        return false;
    } catch (PDOException $e) {
        error_log("Student login error: " . $e->getMessage());
        return false;
    }
}

function studentLogout() {
    unset($_SESSION['student_id']);
    unset($_SESSION['student_number']);
    unset($_SESSION['student_name']);
    unset($_SESSION['user_type']);
    session_destroy();
    header('Location: ../student_login.php');
    exit();
}

function getStudentInfo($student_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT s.*, r.room_number, b.name as building_name 
                              FROM students s 
                              LEFT JOIN rooms r ON s.room_id = r.id 
                              LEFT JOIN buildings b ON r.building_id = b.id 
                              WHERE s.id = ?");
        $stmt->execute([$student_id]);
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Get student info error: " . $e->getMessage());
        return false;
    }
}

// General helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function uploadFile($file, $uploadDir, $allowedTypes = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'], $maxSize = 5242880) {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'message' => 'Invalid parameters.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['success' => false, 'message' => 'No file sent.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['success' => false, 'message' => 'Exceeded filesize limit.'];
        default:
            return ['success' => false, 'message' => 'Unknown errors.'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Exceeded filesize limit.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);

    $ext = array_search(
        $mimeType,
        [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png'
        ],
        true
    );

    if ($ext === false || !in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file format.'];
    }

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = sprintf('%s.%s', sha1_file($file['tmp_name']), $ext);
    $filepath = $uploadDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file.'];
    }

    return [
        'success' => true,
        'filename' => $filename,
        'filepath' => $filepath,
        'message' => 'File uploaded successfully.'
    ];
}

function sendJSONResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}

function getTimeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 60) {
        return 'just now';
    } elseif ($difference < 3600) {
        $minutes = floor($difference / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 86400) {
        $hours = floor($difference / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($difference < 2592000) {
        $days = floor($difference / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M d, Y', $timestamp);
    }
}

function logActivity($user_id, $user_type, $action, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, user_type, action, details, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $user_type, $action, $details]);
    } catch (PDOException $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

// Dashboard statistics functions
function getDashboardStats() {
    global $pdo;
    
    try {
        $stats = [];
        
        // Total rooms
        $stmt = $pdo->query("SELECT COUNT(*) as total_rooms FROM rooms");
        $stats['total_rooms'] = $stmt->fetch()['total_rooms'];
        
        // Available rooms
        $stmt = $pdo->query("SELECT COUNT(*) as available_rooms FROM rooms WHERE status = 'available'");
        $stats['available_rooms'] = $stmt->fetch()['available_rooms'];
        
        // Occupied rooms
        $stmt = $pdo->query("SELECT COUNT(*) as occupied_rooms FROM rooms WHERE occupied_beds > 0");
        $stats['occupied_rooms'] = $stmt->fetch()['occupied_rooms'];
        
        // Total students
        $stmt = $pdo->query("SELECT COUNT(*) as total_students FROM students WHERE status = 'approved'");
        $stats['total_students'] = $stmt->fetch()['total_students'];
        
        // Pending applications
        $stmt = $pdo->query("SELECT COUNT(*) as pending_applications FROM students WHERE status = 'pending'");
        $stats['pending_applications'] = $stmt->fetch()['pending_applications'];
        
        // Pending maintenance requests
        $stmt = $pdo->query("SELECT COUNT(*) as pending_maintenance FROM maintenance_requests WHERE status = 'pending'");
        $stats['pending_maintenance'] = $stmt->fetch()['pending_maintenance'];
        
        // Pending offenses
        $stmt = $pdo->query("SELECT COUNT(*) as pending_offenses FROM offense_logs WHERE status = 'pending'");
        $stats['pending_offenses'] = $stmt->fetch()['pending_offenses'];
        
        // Active visitors
        $stmt = $pdo->query("SELECT COUNT(*) as active_visitors FROM visitor_logs WHERE status = 'active'");
        $stats['active_visitors'] = $stmt->fetch()['active_visitors'];
        
        return $stats;
    } catch (PDOException $e) {
        error_log("Get dashboard stats error: " . $e->getMessage());
        return [];
    }
}

// Email notification function (placeholder)
function sendEmailNotification($to, $subject, $message) {
    // This would integrate with your email service
    // For now, we'll just log it
    error_log("Email notification: To: $to, Subject: $subject, Message: $message");
    return true;
}
?>