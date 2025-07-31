<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

$success = '';
$error = '';

if ($_POST) {
    // Validate and sanitize input
    $student_id = sanitizeInput($_POST['student_id']);
    $lrn = sanitizeInput($_POST['lrn']);
    $first_name = sanitizeInput($_POST['first_name']);
    $middle_name = sanitizeInput($_POST['middle_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $date_of_birth = sanitizeInput($_POST['date_of_birth']);
    $gender = sanitizeInput($_POST['gender']);
    $province = sanitizeInput($_POST['province']);
    $municipality = sanitizeInput($_POST['municipality']);
    $barangay = sanitizeInput($_POST['barangay']);
    $street_purok = sanitizeInput($_POST['street_purok']);
    $mobile_number = sanitizeInput($_POST['mobile_number']);
    $email = sanitizeInput($_POST['email']);
    $facebook_link = sanitizeInput($_POST['facebook_link']);
    $guardian_name = sanitizeInput($_POST['guardian_name']);
    $guardian_mobile = sanitizeInput($_POST['guardian_mobile']);
    $guardian_relationship = sanitizeInput($_POST['guardian_relationship']);
    
    // Validation
    if (!preg_match('/^\d{6}$/', $student_id)) {
        $error = 'Student ID must be exactly 6 digits';
    } elseif (!preg_match('/^\d{12}$/', $lrn)) {
        $error = 'LRN must be exactly 12 digits';
    } else {
        try {
            // Check if student ID or LRN already exists
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR lrn = ?");
            $stmt->execute([$student_id, $lrn]);
            if ($stmt->fetch()) {
                $error = 'Student ID or LRN already exists';
            } else {
                // Handle file upload
                $attachment_file = null;
                if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = uploadFile($_FILES['attachment'], 'uploads/student_documents', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png']);
                    if ($uploadResult['success']) {
                        $attachment_file = $uploadResult['filename'];
                    } else {
                        $error = $uploadResult['message'];
                    }
                }
                
                if (!$error) {
                    // Insert student record
                    $stmt = $pdo->prepare("INSERT INTO students (student_id, lrn, first_name, middle_name, last_name, date_of_birth, gender, province, municipality, barangay, street_purok, mobile_number, email, facebook_link, guardian_name, guardian_mobile, guardian_relationship, attachment_file, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    
                    $stmt->execute([
                        $student_id, $lrn, $first_name, $middle_name, $last_name, $date_of_birth, $gender,
                        $province, $municipality, $barangay, $street_purok, $mobile_number, $email,
                        $facebook_link, $guardian_name, $guardian_mobile, $guardian_relationship, $attachment_file
                    ]);
                    
                    $success = true;
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - Dormitory Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <?php if ($success): ?>
        <!-- Success Page -->
        <div class="login-container">
            <div class="login-card fade-in text-center">
                <div class="login-logo">
                    <i class="fas fa-check-circle text-success"></i>
                </div>
                <h2 class="login-title text-success">Registration Submitted!</h2>
                <p class="mb-4">
                    WAITING TO APPROVED YOUR APPLICATION.<br>
                    PLEASE REFRESH TO UPDATE THE WEB PAGE...
                </p>
                <div class="spinner mb-3"></div>
                <a href="student_login.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt me-2"></i>Try Login
                </a>
                <br><br>
                <a href="student_registration.php" class="btn btn-outline-secondary">
                    <i class="fas fa-plus me-2"></i>Register Another Student
                </a>
            </div>
        </div>
    <?php else: ?>
        <!-- Registration Form -->
        <div class="login-container">
            <div class="login-card fade-in" style="max-width: 800px;">
                <div class="login-logo">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h2 class="login-title">Student Registration</h2>
                <p class="text-center mb-4 text-muted">Apply for Dormitory Accommodation</p>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <!-- Personal Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-user me-2"></i>Personal Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="student_id" class="form-label">Student ID Number *</label>
                                        <input type="text" class="form-control" id="student_id" name="student_id" 
                                               required pattern="\d{6}" placeholder="6-digit student ID"
                                               value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                                        <small class="form-text text-muted">Must be exactly 6 digits</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="lrn" class="form-label">Learner Reference Number (LRN) *</label>
                                        <input type="text" class="form-control" id="lrn" name="lrn" 
                                               required pattern="\d{12}" placeholder="12-digit LRN"
                                               value="<?php echo isset($_POST['lrn']) ? htmlspecialchars($_POST['lrn']) : ''; ?>">
                                        <small class="form-text text-muted">Must be exactly 12 digits</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="first_name" class="form-label">First Name *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="middle_name" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name"
                                               value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="last_name" class="form-label">Last Name *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               required value="<?php echo isset($_POST['date_of_birth']) ? htmlspecialchars($_POST['date_of_birth']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select class="form-control" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="male" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'male') ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'female') ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo (isset($_POST['gender']) && $_POST['gender'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Address Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Home Address</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="province" class="form-label">Province *</label>
                                        <input type="text" class="form-control" id="province" name="province" 
                                               required value="<?php echo isset($_POST['province']) ? htmlspecialchars($_POST['province']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="municipality" class="form-label">Municipality *</label>
                                        <input type="text" class="form-control" id="municipality" name="municipality" 
                                               required value="<?php echo isset($_POST['municipality']) ? htmlspecialchars($_POST['municipality']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="barangay" class="form-label">Barangay *</label>
                                        <input type="text" class="form-control" id="barangay" name="barangay" 
                                               required value="<?php echo isset($_POST['barangay']) ? htmlspecialchars($_POST['barangay']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="street_purok" class="form-label">Street/Purok *</label>
                                        <input type="text" class="form-control" id="street_purok" name="street_purok" 
                                               required value="<?php echo isset($_POST['street_purok']) ? htmlspecialchars($_POST['street_purok']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-phone me-2"></i>Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="mobile_number" class="form-label">Mobile Number *</label>
                                        <input type="tel" class="form-control" id="mobile_number" name="mobile_number" 
                                               required value="<?php echo isset($_POST['mobile_number']) ? htmlspecialchars($_POST['mobile_number']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="facebook_link" class="form-label">Facebook Profile Link</label>
                                <input type="url" class="form-control" id="facebook_link" name="facebook_link" 
                                       placeholder="https://facebook.com/your-profile"
                                       value="<?php echo isset($_POST['facebook_link']) ? htmlspecialchars($_POST['facebook_link']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contact -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-users me-2"></i>Emergency Contact</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="guardian_name" class="form-label">Guardian/Parent Name *</label>
                                        <input type="text" class="form-control" id="guardian_name" name="guardian_name" 
                                               required value="<?php echo isset($_POST['guardian_name']) ? htmlspecialchars($_POST['guardian_name']) : ''; ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="guardian_mobile" class="form-label">Guardian Mobile Number *</label>
                                        <input type="tel" class="form-control" id="guardian_mobile" name="guardian_mobile" 
                                               required value="<?php echo isset($_POST['guardian_mobile']) ? htmlspecialchars($_POST['guardian_mobile']) : ''; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="guardian_relationship" class="form-label">Relationship *</label>
                                <select class="form-control" id="guardian_relationship" name="guardian_relationship" required>
                                    <option value="">Select Relationship</option>
                                    <option value="Father" <?php echo (isset($_POST['guardian_relationship']) && $_POST['guardian_relationship'] == 'Father') ? 'selected' : ''; ?>>Father</option>
                                    <option value="Mother" <?php echo (isset($_POST['guardian_relationship']) && $_POST['guardian_relationship'] == 'Mother') ? 'selected' : ''; ?>>Mother</option>
                                    <option value="Guardian" <?php echo (isset($_POST['guardian_relationship']) && $_POST['guardian_relationship'] == 'Guardian') ? 'selected' : ''; ?>>Guardian</option>
                                    <option value="Relative" <?php echo (isset($_POST['guardian_relationship']) && $_POST['guardian_relationship'] == 'Relative') ? 'selected' : ''; ?>>Relative</option>
                                    <option value="Other" <?php echo (isset($_POST['guardian_relationship']) && $_POST['guardian_relationship'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Document Upload -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-file-upload me-2"></i>Document Attachment</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="attachment" class="form-label">Upload Supporting Document</label>
                                <input type="file" class="form-control" id="attachment" name="attachment" 
                                       accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                                <small class="form-text text-muted">
                                    Accepted formats: PDF, DOC, DOCX, JPG, JPEG, PNG (Max: 5MB)
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="student_login.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane me-2"></i>Submit Application
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>