<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_class']) && is_instructor()) {
    $class_name = sanitize_input($_POST['class_name']);
    $class_code = sanitize_input($_POST['class_code']);
    $description = sanitize_input($_POST['description']);
    $semester = sanitize_input($_POST['semester']);
    $year = intval($_POST['year']);
    
    if (!empty($class_name) && !empty($class_code) && !empty($semester) && $year > 0) {
        $stmt = $conn->prepare("SELECT class_id FROM classes WHERE class_code = ?");
        $stmt->bind_param("s", $class_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $existing_class = $result->fetch_assoc();
            $class_id = $existing_class['class_id'];
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT instructs_id FROM instructs WHERE instructor_id = ? AND class_id = ? AND semester = ? AND year = ?");
            $stmt->bind_param("iisi", $user_id, $class_id, $semester, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "You are already teaching this class for {$semester} {$year}.";
            } else {
                $stmt = $conn->prepare("INSERT INTO instructs (instructor_id, class_id, semester, year) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisi", $user_id, $class_id, $semester, $year);
                
                if ($stmt->execute()) {
                    $success_message = "Successfully added to teach existing class: {$class_code}";
                } else {
                    $error_message = "Failed to link to class.";
                }
            }
            $stmt->close();
        } else {
            $stmt->close();
            $stmt = $conn->prepare("INSERT INTO classes (class_name, class_code, description) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $class_name, $class_code, $description);
            
            if ($stmt->execute()) {
                $class_id = $conn->insert_id;
                
                $stmt = $conn->prepare("INSERT INTO instructs (instructor_id, class_id, semester, year) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisi", $user_id, $class_id, $semester, $year);
                
                if ($stmt->execute()) {
                    $success_message = "Class created successfully!";
                } else {
                    $error_message = "Class created but failed to link to your account.";
                }
            } else {
                $error_message = "Failed to create class.";
            }
            $stmt->close();
        }
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll_class']) && is_student()) {
    $class_code = sanitize_input($_POST['enroll_class_code']);
    
    if (!empty($class_code)) {
        $stmt = $conn->prepare("SELECT class_id, class_name FROM classes WHERE class_code = ?");
        $stmt->bind_param("s", $class_code);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $class = $result->fetch_assoc();
            $class_id = $class['class_id'];
            $stmt->close();
            
            $stmt = $conn->prepare("SELECT enrollment_id FROM enrolls WHERE student_id = ? AND class_id = ?");
            $stmt->bind_param("ii", $user_id, $class_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error_message = "You are already enrolled in this class.";
            } else {
                $current_semester = "Fall";
                $current_year = date('Y');
                
                $stmt = $conn->prepare("INSERT INTO enrolls (student_id, class_id, semester, year) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iisi", $user_id, $class_id, $current_semester, $current_year);
                
                if ($stmt->execute()) {
                    $success_message = "Successfully enrolled in {$class['class_name']}!";
                } else {
                    $error_message = "Failed to enroll in class.";
                }
            }
            $stmt->close();
        } else {
            $error_message = "Class code not found. Please check and try again.";
        }
    } else {
        $error_message = "Please enter a class code.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unenroll_class']) && is_student()) {
    $class_id = intval($_POST['class_id']);
    
    $stmt = $conn->prepare("DELETE FROM enrolls WHERE student_id = ? AND class_id = ?");
    $stmt->bind_param("ii", $user_id, $class_id);
    
    if ($stmt->execute()) {
        $success_message = "Successfully unenrolled from class.";
    } else {
        $error_message = "Failed to unenroll from class.";
    }
    $stmt->close();
}

$stmt = $conn->prepare("SELECT class_id, class_name, class_code, description FROM classes ORDER BY class_name");
$stmt->execute();
$result = $stmt->get_result();
$available_classes = [];
while ($row = $result->fetch_assoc()) {
    $available_classes[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Classes - Classroom Check-in</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .card h2 {
            color: #333;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            font-family: inherit;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.4);
        }
        
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .class-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            border-left: 4px solid #667eea;
            transition: all 0.3s;
        }
        
        .class-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .class-card h3 {
            color: #333;
            font-size: 18px;
            margin-bottom: 8px;
        }
        
        .class-card .class-code {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .class-card .description {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            line-height: 1.5;
        }
        
        .enroll-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .enroll-form form {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .enroll-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }
        
        .enrolled-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1><?php echo is_instructor() ? '📚 Create & Manage Classes' : '📚 Browse & Enroll in Classes'; ?></h1>
        <button class="back-btn" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
    </div>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <?php if (is_instructor()): ?>
            <div class="card">
                <h2>Create New Class</h2>
                <form method="POST" action="" id="createClassForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="class_name">Class Name*:</label>
                            <input type="text" id="class_name" name="class_name" 
                                   placeholder="e.g., Web Development" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="class_code">Class Code*:</label>
                            <input type="text" id="class_code" name="class_code" 
                                   placeholder="e.g., CS401" required 
                                   pattern="[A-Z]{2,4}[0-9]{3,4}"
                                   title="Format: 2-4 letters followed by 3-4 numbers (e.g., CS401)">
                            <small style="color: #666; font-size: 12px;">Format: CS401, MATH101, etc.</small>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <textarea id="description" name="description" 
                                  placeholder="Brief description of the course..."></textarea>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="semester">Semester*:</label>
                            <select id="semester" name="semester" required>
                                <option value="">Select...</option>
                                <option value="Spring">Spring</option>
                                <option value="Summer">Summer</option>
                                <option value="Fall" selected>Fall</option>
                                <option value="Winter">Winter</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="year">Year*:</label>
                            <input type="number" id="year" name="year" 
                                   value="<?php echo date('Y'); ?>" 
                                   min="2024" max="2030" required>
                        </div>
                    </div>
                    
                    <button type="submit" name="create_class" class="btn">Create Class</button>
                </form>
            </div>
        <?php else: ?>
            <div class="card">
                <h2>Enroll in a Class</h2>
                <p style="color: #666; margin-bottom: 20px;">
                    Enter the class code provided by your instructor to enroll.
                </p>
                
                <div class="enroll-form">
                    <form method="POST" action="" id="enrollForm">
                        <div class="form-group">
                            <label for="enroll_class_code">Class Code:</label>
                            <input type="text" id="enroll_class_code" name="enroll_class_code" 
                                   placeholder="e.g., CS401" required
                                   style="text-transform: uppercase;">
                        </div>
                        <button type="submit" name="enroll_class" class="btn">Enroll</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2><?php echo is_instructor() ? 'All Classes' : 'Browse Available Classes'; ?></h2>
            
            <?php if (empty($available_classes)): ?>
                <p style="text-align: center; color: #999; padding: 40px;">
                    No classes available yet.
                </p>
            <?php else: ?>
                <div class="classes-grid">
                    <?php foreach ($available_classes as $class): ?>
                        <?php
                        $is_enrolled = false;
                        if (is_student()) {
                            $stmt = $conn->prepare("SELECT enrollment_id FROM enrolls WHERE student_id = ? AND class_id = ?");
                            $stmt->bind_param("ii", $user_id, $class['class_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $is_enrolled = $result->num_rows > 0;
                            $stmt->close();
                        }
                        ?>
                        <div class="class-card">
                            <?php if ($is_enrolled): ?>
                                <div class="enrolled-badge">✓ Enrolled</div>
                            <?php endif; ?>
                            
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            <div class="class-code"><?php echo htmlspecialchars($class['class_code']); ?></div>
                            <div class="description">
                                <?php echo htmlspecialchars($class['description'] ?: 'No description available'); ?>
                            </div>
                            
                            <?php if (is_student()): ?>
                                <?php if ($is_enrolled): ?>
                                    <form method="POST" action="" style="display: inline;"
                                          onsubmit="return confirm('Are you sure you want to unenroll?')">
                                        <input type="hidden" name="class_id" value="<?php echo $class['class_id']; ?>">
                                        <button type="submit" name="unenroll_class" class="btn btn-small btn-danger">
                                            Unenroll
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <form method="POST" action="" style="display: inline;">
                                        <input type="hidden" name="enroll_class_code" value="<?php echo $class['class_code']; ?>">
                                        <button type="submit" name="enroll_class" class="btn btn-small">
                                            Enroll Now
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            $('#class_code, #enroll_class_code').on('input', function() {
                $(this).val($(this).val().toUpperCase());
            });
            
            $('#createClassForm').on('submit', function(e) {
                const classCode = $('#class_code').val();
                const pattern = /^[A-Z]{2,4}[0-9]{3,4}$/;
                
                if (!pattern.test(classCode)) {
                    e.preventDefault();
                    alert('Class code must be 2-4 letters followed by 3-4 numbers (e.g., CS401)');
                    return false;
                }
            });
        });
    </script>
</body>
</html>