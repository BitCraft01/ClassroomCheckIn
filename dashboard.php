<?php
require_once 'config.php';

if (!is_logged_in()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$first_name = $_SESSION['first_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Classroom Check-in</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .navbar .user-name {
            font-size: 16px;
        }
        
        .navbar .badge {
            background: rgba(255,255,255,0.3);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .navbar .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .navbar .logout-btn:hover {
            background: white;
            color: #667eea;
        }
        
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .welcome-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .welcome-section h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-section p {
            color: #666;
            font-size: 16px;
        }
        
        .section-title {
            font-size: 22px;
            color: #333;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .class-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
            border-left: 4px solid #667eea;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
        }
        
        .class-card h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 8px;
        }
        
        .class-card .class-code {
            color: #667eea;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .class-card .class-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        
        .class-card .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
        }
        
        .class-card .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.4);
        }
        
        .no-classes {
            background: white;
            padding: 60px;
            border-radius: 15px;
            text-align: center;
            color: #999;
        }
        
        .no-classes .icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            text-align: center;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-card .label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
            <div class="navbar">
        <h1>📚 Classroom Check-in System</h1>
        <div class="user-info">
            <span class="user-name">Hello, <?php echo htmlspecialchars($first_name); ?>!</span>
            <span class="badge"><?php echo ucfirst($user_type); ?></span>
            <a href="manage_classes.php"><button class="logout-btn" style="background: rgba(255,255,255,0.2); margin-right: 10px;"><?php echo $user_type === 'student' ? '+ Enroll' : '+ Create Class'; ?></button></a>
            <a href="logout.php"><button class="logout-btn">Logout</button></a>
        </div>
    </div>
    
    <div class="container">
        <div class="welcome-section">
            <h2>Welcome to Your Dashboard</h2>
            <p><?php echo $user_type === 'student' ? 'View your enrolled classes and check in for attendance.' : 'Manage your classes and view attendance records.'; ?></p>
        </div>
        
        <?php if ($user_type === 'instructor'): ?>
        <div class="stats-grid" id="instructor-stats">
            <div class="stat-card">
                <div class="number" id="total-classes">0</div>
                <div class="label">Total Classes</div>
            </div>
            <div class="stat-card">
                <div class="number" id="total-students">0</div>
                <div class="label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="number" id="active-sessions">0</div>
                <div class="label">Active Sessions Today</div>
            </div>
        </div>
        <?php endif; ?>
        
        <h2 class="section-title">
            <?php echo $user_type === 'student' ? 'My Enrolled Classes' : 'My Classes'; ?>
        </h2>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Loading classes...</p>
        </div>
        
        <div class="classes-grid" id="classes-container" style="display: none;">
        </div>
    </div>
    
    <script>
        $(document).ready(function() {
            const userType = '<?php echo $user_type; ?>';
            
            loadClasses();
            
            if (userType === 'instructor') {
                loadInstructorStats();
            }
            
            function loadClasses() {
                $.ajax({
                    url: 'api/get_classes.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        $('#loading').hide();
                        
                        if (response.success && response.classes.length > 0) {
                            $('#classes-container').show();
                            displayClasses(response.classes);
                        } else {
                            $('#classes-container').html(
                                '<div class="no-classes">' +
                                '<div class="icon">📚</div>' +
                                '<h3>No Classes Found</h3>' +
                                '<p>' + (userType === 'student' ? 
                                    'You are not enrolled in any classes yet.' : 
                                    'You have not created any classes yet.') + '</p>' +
                                '</div>'
                            ).show();
                        }
                    },
                    error: function() {
                        $('#loading').hide();
                        $('#classes-container').html(
                            '<div class="no-classes">' +
                            '<p style="color: #ff4757;">Error loading classes. Please refresh the page.</p>' +
                            '</div>'
                        ).show();
                    }
                });
            }
            
            function displayClasses(classes) {
                let html = '';
                
                classes.forEach(function(cls) {
                    html += '<div class="class-card">';
                    html += '<h3>' + escapeHtml(cls.class_name) + '</h3>';
                    html += '<div class="class-code">' + escapeHtml(cls.class_code) + '</div>';
                    html += '<div class="class-info">' + escapeHtml(cls.description || 'No description') + '</div>';
                    
                    if (userType === 'student') {
                        html += '<button class="btn" onclick="viewClass(' + cls.class_id + ')">Check In</button>';
                    } else {
                        html += '<button class="btn" onclick="manageClass(' + cls.class_id + ')">View Attendance</button>';
                    }
                    
                    html += '</div>';
                });
                
                $('#classes-container').html(html);
            }
            
            function loadInstructorStats() {
                $.ajax({
                    url: 'api/get_instructor_stats.php',
                    method: 'GET',
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#total-classes').text(response.stats.total_classes);
                            $('#total-students').text(response.stats.total_students);
                            $('#active-sessions').text(response.stats.active_sessions);
                        }
                    }
                });
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
        
        function viewClass(classId) {
            window.location.href = 'checkin.php?class_id=' + classId;
        }
        
        function manageClass(classId) {
            window.location.href = 'attendance.php?class_id=' + classId;
        }
    </script>
</body>
</html>