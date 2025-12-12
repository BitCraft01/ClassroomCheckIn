<?php
require_once 'config.php';

if (!is_logged_in() || !is_student()) {
    redirect('login.php');
}

$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;

if ($class_id === 0) {
    redirect('dashboard.php');
}

$student_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.*, e.enrollment_id
    FROM classes c
    INNER JOIN enrolls e ON c.class_id = e.class_id
    WHERE c.class_id = ? AND e.student_id = ?
");
$stmt->bind_param("ii", $class_id, $student_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('dashboard.php');
}

$class = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check In - <?php echo htmlspecialchars($class['class_name']); ?></title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 600px;
            margin: 40px auto;
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.3);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 20px;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .back-btn:hover {
            background: rgba(255, 255, 255, 0.5);
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        
        .class-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .class-header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .class-header .class-code {
            color: #667eea;
            font-size: 18px;
            font-weight: 600;
        }
        
        .status-section {
            text-align: center;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        
        .status-checking {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-error {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-section .icon {
            font-size: 64px;
            margin-bottom: 15px;
        }
        
        .status-section h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .status-section p {
            font-size: 16px;
            line-height: 1.6;
        }
        
        .location-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        
        .location-info h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
        }
        
        .location-info p {
            color: #666;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .session-list {
            margin-top: 20px;
        }
        
        .session-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .session-item .info {
            flex: 1;
        }
        
        .session-item .time {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }
        
        .session-item .location {
            color: #666;
            font-size: 14px;
        }
        
        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
        }
        
        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .attendance-history {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        
        .attendance-history h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .history-item {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 14px;
        }
        
        .history-item .date {
            color: #333;
            font-weight: 600;
        }
        
        .history-item .time {
            color: #666;
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <button class="back-btn" onclick="window.location.href='dashboard.php'">← Back to Dashboard</button>
        
        <div class="card">
            <div class="class-header">
                <h1><?php echo htmlspecialchars($class['class_name']); ?></h1>
                <div class="class-code"><?php echo htmlspecialchars($class['class_code']); ?></div>
            </div>
            
            <div id="status-container">
                <div class="status-section status-checking">
                    <div class="icon">📍</div>
                    <h2>Checking Location...</h2>
                    <p>Please enable location services to check in.</p>
                    <div class="loading-spinner"></div>
                </div>
            </div>
            
            <div id="sessions-container" style="display: none;">
                <h3 style="color: #333; margin-bottom: 15px;">Available Check-in Sessions</h3>
                <div class="session-list" id="session-list"></div>
            </div>
            
            <div class="attendance-history" id="history-container" style="display: none;">
                <h3>Your Attendance History</h3>
                <div id="history-list"></div>
            </div>
        </div>
    </div>
    
    <script>
        const classId = <?php echo $class_id; ?>;
        let userLocation = null;
        
        $(document).ready(function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    onLocationSuccess,
                    onLocationError,
                    { enableHighAccuracy: true, timeout: 10000 }
                );
            } else {
                showError('Geolocation is not supported by your browser.');
            }
            
            loadAttendanceHistory();
        });
        
        function onLocationSuccess(position) {
            userLocation = {
                latitude: position.coords.latitude,
                longitude: position.coords.longitude
            };
            
            loadSessions();
        }
        
        function onLocationError(error) {
            let message = 'Unable to retrieve your location. ';
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message += 'Please enable location permissions.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message += 'Location information unavailable.';
                    break;
                case error.TIMEOUT:
                    message += 'Location request timed out.';
                    break;
            }
            showError(message);
        }
        
        function loadSessions() {
            $.ajax({
                url: 'api/get_sessions.php',
                method: 'GET',
                data: { 
                    class_id: classId,
                    latitude: userLocation.latitude,
                    longitude: userLocation.longitude
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.sessions.length > 0) {
                        displaySessions(response.sessions);
                    } else {
                        showInfo('No active check-in sessions available at this time.');
                    }
                },
                error: function() {
                    showError('Error loading sessions. Please try again.');
                }
            });
        }
        
        function displaySessions(sessions) {
            let html = '';
            
            sessions.forEach(function(session) {
                const canCheckIn = session.in_range && !session.already_checked_in;
                const distance = session.distance ? Math.round(session.distance) : 0;
                
                html += '<div class="session-item">';
                html += '<div class="info">';
                html += '<div class="time">' + session.session_time + '</div>';
                html += '<div class="location">' + escapeHtml(session.approved_location) + 
                        (distance > 0 ? ' (' + distance + 'm away)' : '') + '</div>';
                html += '</div>';
                html += '<button class="btn ' + (session.already_checked_in ? 'btn-success' : '') + '" ' +
                        'onclick="checkIn(' + session.session_id + ')" ' +
                        (canCheckIn ? '' : 'disabled') + '>' +
                        (session.already_checked_in ? '✓ Checked In' : 
                         session.in_range ? 'Check In' : 'Out of Range') +
                        '</button>';
                html += '</div>';
            });
            
            $('#session-list').html(html);
            $('#sessions-container').show();
            
            const hasAvailable = sessions.some(s => s.in_range && !s.already_checked_in);
            if (hasAvailable) {
                showSuccess('You are in the approved location. You may check in.');
            } else {
                const allCheckedIn = sessions.every(s => s.already_checked_in);
                if (allCheckedIn) {
                    showSuccess('You have already checked in for all available sessions.');
                } else {
                    showError('You are not within the approved check-in location.');
                }
            }
        }
        
        function checkIn(sessionId) {
            if (!userLocation) {
                alert('Location not available. Please refresh the page.');
                return;
            }
            
            $('#status-container').html(
                '<div class="status-section status-checking">' +
                '<div class="icon">⏳</div>' +
                '<h2>Checking In...</h2>' +
                '<p>Please wait while we process your attendance.</p>' +
                '<div class="loading-spinner"></div>' +
                '</div>'
            );
            
            $.ajax({
                url: 'api/checkin.php',
                method: 'POST',
                data: {
                    session_id: sessionId,
                    latitude: userLocation.latitude,
                    longitude: userLocation.longitude
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showSuccess('Check-in successful! Your attendance has been recorded.', true);
                        setTimeout(function() {
                            loadSessions();
                            loadAttendanceHistory();
                        }, 2000);
                    } else {
                        showError(response.message || 'Check-in failed. Please try again.');
                    }
                },
                error: function() {
                    showError('Check-in failed. Please try again.');
                }
            });
        }
        
        function loadAttendanceHistory() {
            $.ajax({
                url: 'api/get_attendance_history.php',
                method: 'GET',
                data: { class_id: classId },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.history.length > 0) {
                        let html = '';
                        response.history.forEach(function(record) {
                            html += '<div class="history-item">';
                            html += '<span class="date">' + record.session_date + '</span>';
                            html += '<span class="time">' + record.check_in_time + '</span>';
                            html += '</div>';
                        });
                        $('#history-list').html(html);
                        $('#history-container').show();
                    }
                }
            });
        }
        
        function showSuccess(message, persistent = false) {
            $('#status-container').html(
                '<div class="status-section status-success">' +
                '<div class="icon">✅</div>' +
                '<h2>Success!</h2>' +
                '<p>' + message + '</p>' +
                '</div>'
            );
            
            if (!persistent) {
                setTimeout(function() {
                    $('#status-container').fadeOut();
                }, 3000);
            }
        }
        
        function showError(message) {
            $('#status-container').html(
                '<div class="status-section status-error">' +
                '<div class="icon">❌</div>' +
                '<h2>Unable to Check In</h2>' +
                '<p>' + message + '</p>' +
                '</div>'
            );
        }
        
        function showInfo(message) {
            $('#status-container').html(
                '<div class="status-section status-checking">' +
                '<div class="icon">ℹ️</div>' +
                '<h2>Information</h2>' +
                '<p>' + message + '</p>' +
                '</div>'
            );
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>