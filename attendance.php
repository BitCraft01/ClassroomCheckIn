<?php
require_once 'config.php';

if (!is_logged_in() || !is_instructor()) {
    redirect('login.php');
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($class_id === 0) {
    redirect('dashboard.php');
}

$instructor_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.*
    FROM classes c
    INNER JOIN instructs i ON c.class_id = i.class_id
    WHERE c.class_id = ? AND i.instructor_id = ?
");
$stmt->bind_param("ii", $class_id, $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('dashboard.php');
}

$class = $result->fetch_assoc();
$stmt->close();

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_session'])) {
    $session_date = sanitize_input($_POST['session_date']);
    $session_time = sanitize_input($_POST['session_time']);
    $approved_location = sanitize_input($_POST['approved_location']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);
    $location_radius = intval($_POST['location_radius']);
    
    if (!empty($session_date) && !empty($session_time) && !empty($approved_location)) {
        $stmt = $conn->prepare("
            INSERT INTO class_sessions (class_id, session_date, session_time, approved_location, latitude, longitude, location_radius)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isssddi", $class_id, $session_date, $session_time, $approved_location, $latitude, $longitude, $location_radius);
        
        if ($stmt->execute()) {
            $success_message = "Session created successfully!";
        } else {
            $error_message = "Failed to create session.";
        }
        $stmt->close();
    } else {
        $error_message = "Please fill in all required fields.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_session'])) {
    $session_id = intval($_POST['session_id']);
    
    $stmt = $conn->prepare("DELETE FROM class_sessions WHERE session_id = ? AND class_id = ?");
    $stmt->bind_param("ii", $session_id, $class_id);
    
    if ($stmt->execute()) {
        $success_message = "Session deleted successfully!";
    } else {
        $error_message = "Failed to delete session.";
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_attendance'])) {
    $attendance_id = intval($_POST['attendance_id']);
    $status = sanitize_input($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE attendance SET status = ? WHERE attendance_id = ?");
    $stmt->bind_param("si", $status, $attendance_id);
    
    if ($stmt->execute()) {
        $success_message = "Attendance updated successfully!";
    } else {
        $error_message = "Failed to update attendance.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - <?php echo htmlspecialchars($class['class_name']); ?></title>
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
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .container {
            max-width: 1400px;
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
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }
        
        .tab-btn {
            flex: 1;
            padding: 15px;
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th {
            background: #f8f9fa;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        
        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
            color: #666;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-present {
            background: #d4edda;
            color: #155724;
        }
        
        .status-absent {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-late {
            background: #fff3cd;
            color: #856404;
        }
        
        .session-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .session-info h4 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .session-info p {
            color: #666;
            font-size: 14px;
        }
        
        .session-actions {
            display: flex;
            gap: 10px;
        }
        
        .filter-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-box {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-box .number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }
        
        .stat-box .label {
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 <?php echo htmlspecialchars($class['class_name']); ?> - <?php echo htmlspecialchars($class['class_code']); ?></h1>
        <button class="back-btn" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
    </div>
    
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('view-attendance')">📋 View Attendance</button>
            <button class="tab-btn" onclick="showTab('manage-sessions')">🕐 Manage Sessions</button>
            <button class="tab-btn" onclick="showTab('students')">👥 Students</button>
        </div>
        
        <div class="tab-content active" id="view-attendance">
            <div class="card">
                <h2>Attendance Overview</h2>
                
                <div class="filter-section">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="session-filter">Select Session:</label>
                            <select id="session-filter">
                                <option value="">Loading sessions...</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="stats-row" id="attendance-stats">
                    <div class="stat-box">
                        <div class="number" id="stat-present">0</div>
                        <div class="label">Present</div>
                    </div>
                    <div class="stat-box">
                        <div class="number" id="stat-absent">0</div>
                        <div class="label">Absent</div>
                    </div>
                    <div class="stat-box">
                        <div class="number" id="stat-rate">0%</div>
                        <div class="label">Attendance Rate</div>
                    </div>
                </div>
                
                <div id="attendance-table-container">
                    <p style="text-align: center; color: #999; padding: 40px;">Select a session to view attendance</p>
                </div>
            </div>
        </div>
        
        <div class="tab-content" id="manage-sessions">
            <div class="card">
                <h2>Create New Session</h2>
                
                <form method="POST" action="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="session_date">Session Date*:</label>
                            <input type="date" id="session_date" name="session_date" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="session_time">Session Time*:</label>
                            <input type="time" id="session_time" name="session_time" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="approved_location">Approved Location*:</label>
                        <input type="text" id="approved_location" name="approved_location" 
                               placeholder="e.g., Building A, Room 101" required>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="latitude">Latitude:</label>
                            <input type="number" id="latitude" name="latitude" step="0.000001" 
                                   placeholder="e.g., 40.7128" value="40.7128">
                        </div>
                        
                        <div class="form-group">
                            <label for="longitude">Longitude:</label>
                            <input type="number" id="longitude" name="longitude" step="0.000001" 
                                   placeholder="e.g., -74.0060" value="-74.0060">
                        </div>
                        
                        <div class="form-group">
                            <label for="location_radius">Check-in Radius (meters):</label>
                            <input type="number" id="location_radius" name="location_radius" 
                                   value="100" min="10" max="1000">
                        </div>
                    </div>
                    
                    <button type="submit" name="create_session" class="btn">Create Session</button>
                </form>
            </div>
            
            <div class="card">
                <h2>Existing Sessions</h2>
                <div id="sessions-list"></div>
            </div>
        </div>
        
        <div class="tab-content" id="students">
            <div class="card">
                <h2>Enrolled Students</h2>
                <div id="students-list"></div>
            </div>
        </div>
    </div>
    
    <script>
        const classId = <?php echo $class_id; ?>;
        let currentSessionId = null;
        
        $(document).ready(function() {
            loadSessions();
            loadStudents();
            
            $('#session-filter').change(function() {
                currentSessionId = $(this).val();
                if (currentSessionId) {
                    loadAttendanceForSession(currentSessionId);
                }
            });
        });
        
        function showTab(tabName) {
            $('.tab-btn').removeClass('active');
            $('.tab-content').removeClass('active');
            
            $('[onclick="showTab(\'' + tabName + '\')"]').addClass('active');
            $('#' + tabName).addClass('active');
        }
        
        function loadSessions() {
            $.ajax({
                url: 'api/get_all_sessions.php',
                method: 'GET',
                data: { class_id: classId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displaySessionsInFilter(response.sessions);
                        displaySessionsList(response.sessions);
                    }
                }
            });
        }
        
        function displaySessionsInFilter(sessions) {
            let html = '<option value="">Select a session...</option>';
            sessions.forEach(function(session) {
                html += '<option value="' + session.session_id + '">' +
                        session.session_date + ' at ' + session.session_time +
                        ' - ' + escapeHtml(session.approved_location) + '</option>';
            });
            $('#session-filter').html(html);
        }
        
        function displaySessionsList(sessions) {
            if (sessions.length === 0) {
                $('#sessions-list').html('<p style="text-align: center; color: #999;">No sessions created yet</p>');
                return;
            }
            
            let html = '';
            sessions.forEach(function(session) {
                html += '<div class="session-item">';
                html += '<div class="session-info">';
                html += '<h4>' + session.session_date + ' at ' + session.session_time + '</h4>';
                html += '<p>' + escapeHtml(session.approved_location) + '</p>';
                html += '</div>';
                html += '<div class="session-actions">';
                html += '<form method="POST" style="display: inline;" onsubmit="return confirm(\'Delete this session?\')">';
                html += '<input type="hidden" name="session_id" value="' + session.session_id + '">';
                html += '<button type="submit" name="delete_session" class="btn btn-small btn-danger">Delete</button>';
                html += '</form>';
                html += '</div>';
                html += '</div>';
            });
            $('#sessions-list').html(html);
        }
        
        function loadAttendanceForSession(sessionId) {
            $.ajax({
                url: 'api/get_attendance.php',
                method: 'GET',
                data: { session_id: sessionId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayAttendance(response.attendance, response.stats);
                    }
                }
            });
        }
        
        function displayAttendance(attendance, stats) {
            $('#stat-present').text(stats.present);
            $('#stat-absent').text(stats.absent);
            $('#stat-rate').text(stats.rate + '%');
            
            let html = '<table>';
            html += '<thead><tr>';
            html += '<th>Student Name</th>';
            html += '<th>Email</th>';
            html += '<th>Status</th>';
            html += '<th>Check-in Time</th>';
            html += '<th>Actions</th>';
            html += '</tr></thead><tbody>';
            
            attendance.forEach(function(record) {
                html += '<tr>';
                html += '<td>' + escapeHtml(record.student_name) + '</td>';
                html += '<td>' + escapeHtml(record.email) + '</td>';
                html += '<td><span class="status-badge status-' + record.status + '">' + 
                        record.status + '</span></td>';
                html += '<td>' + (record.check_in_time || 'Not checked in') + '</td>';
                html += '<td>';
                if (record.attendance_id) {
                    html += '<form method="POST" style="display: inline;">';
                    html += '<input type="hidden" name="attendance_id" value="' + record.attendance_id + '">';
                    html += '<select name="status" onchange="this.form.submit()" style="padding: 4px 8px; font-size: 12px;">';
                    html += '<option value="present"' + (record.status === 'present' ? ' selected' : '') + '>Present</option>';
                    html += '<option value="late"' + (record.status === 'late' ? ' selected' : '') + '>Late</option>';
                    html += '<option value="absent"' + (record.status === 'absent' ? ' selected' : '') + '>Absent</option>';
                    html += '</select>';
                    html += '<input type="hidden" name="update_attendance" value="1">';
                    html += '</form>';
                }
                html += '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $('#attendance-table-container').html(html);
        }
        
        function loadStudents() {
            $.ajax({
                url: 'api/get_students.php',
                method: 'GET',
                data: { class_id: classId },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        displayStudents(response.students);
                    }
                }
            });
        }
        
        function displayStudents(students) {
            if (students.length === 0) {
                $('#students-list').html('<p style="text-align: center; color: #999;">No students enrolled</p>');
                return;
            }
            
            let html = '<table>';
            html += '<thead><tr><th>Name</th><th>Email</th><th>Enrollment Date</th></tr></thead>';
            html += '<tbody>';
            
            students.forEach(function(student) {
                html += '<tr>';
                html += '<td>' + escapeHtml(student.name) + '</td>';
                html += '<td>' + escapeHtml(student.email) + '</td>';
                html += '<td>' + student.enrollment_date + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
            $('#students-list').html(html);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>