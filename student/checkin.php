<?php
require '../db.php';
require_login();

$user = current_user();
if ($user['role'] !== 'student') {
    header('Location: ../instructor/dashboard.php');
    exit;
}

$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

$stmt = $pdo->prepare("
    SELECT s.*, c.code, c.title, c.id AS course_id
    FROM class_sessions s
    JOIN courses c ON s.course_id = c.id
    WHERE s.id = ?
");
$stmt->execute([$session_id]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$session) {
    die('Session not found.');
}

$enrolled = $pdo->prepare("SELECT 1 FROM enrollments WHERE student_id = ? AND course_id = ?");
$enrolled->execute([$user['id'], $session['course_id']]);
if (!$enrolled->fetch()) {
    die('You are not enrolled in this course.');
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Check In – Classroom Check-In</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .session-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 8px 24px;
            font-size: 14px;
            margin-bottom: 18px;
        }
        .session-meta div span.label {
            display:block;
            font-size:12px;
            text-transform:uppercase;
            letter-spacing:.04em;
            color:#9ca3af;
        }
        .session-meta div span.value {
            font-weight:600;
            color:#111827;
        }
        .pill {
            display:inline-flex;
            align-items:center;
            gap:6px;
            padding:4px 10px;
            border-radius:999px;
            font-size:11px;
            background:#eef2ff;
            color:#3730a3;
            margin-top:4px;
        }
        #result.ok { color:#047857; }
        #result.err { color:#b91c1c; }
    </style>
</head>
<body>
<header>
    <div class="left">Classroom Check-In</div>
    <div class="right">
        <span><?php echo htmlspecialchars($user['name']); ?> (student)</span>
        <a href="courses.php">My Courses</a>
        <a href="../logout.php">Logout</a>
    </div>
</header>
<main>
    <div class="card">
        <div style="font-size:13px;margin-bottom:8px;">
            ← <a href="courses.php">Back to my courses</a>
        </div>
        <h2>Check In for Class</h2>
        <p class="small">
            Confirm you’re in the classroom, enter a recognizable location, and tap <strong>Check In</strong>.
        </p>

        <div class="session-meta">
            <div>
                <span class="label">Course</span>
                <span class="value"><?php echo htmlspecialchars($session['code'].' – '.$session['title']); ?></span>
            </div>
            <div>
                <span class="label">Date</span>
                <span class="value"><?php echo htmlspecialchars($session['session_date']); ?></span>
            </div>
            <div>
                <span class="label">Time</span>
                <span class="value">
                    <?php echo htmlspecialchars(substr($session['start_time'],0,5)); ?>
                    – <?php echo htmlspecialchars(substr($session['end_time'],0,5)); ?>
                </span>
            </div>
            <div>
                <span class="label">Classroom</span>
                <span class="value"><?php echo htmlspecialchars($session['classroom']); ?></span>
            </div>
        </div>

        <label for="locationNote">Where are you sitting?</label>
        <input
            type="text"
            id="locationNote"
            placeholder="e.g., <?php echo htmlspecialchars($session['classroom']); ?> – front row"
        >
        <div class="small">
            Include the room and an area (e.g., “<?php echo htmlspecialchars($session['classroom']); ?> – back row”).
            Your location will be verified against the assigned classroom.
        </div>

        <div style="margin-top:12px;display:flex;align-items:center;gap:12px;">
            <button id="checkinBtn">Check In</button>
            <div class="pill">
                 This will mark you as <strong>Present</strong> for this session.
            </div>
        </div>

        <div id="result" style="margin-top:10px;font-size:13px;"></div>
    </div>
</main>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
const btn = document.getElementById('checkinBtn');
const noteInput = document.getElementById('locationNote');
const resultEl = document.getElementById('result');

btn.addEventListener('click', function() {
    const note = noteInput.value.trim();
    resultEl.textContent = '';
    resultEl.className = '';

    if (!note) {
        resultEl.textContent = 'Please enter a location note.';
        resultEl.className = 'err';
        return;
    }

    btn.disabled = true;

    $.post('../ajax/ajax_checkin.php', {
        session_id: <?php echo $session_id; ?>,
        location_note: note
    }).done(function(data) {
        let msg = (typeof data === 'string') ? data.trim() : '';
        if (msg.startsWith('OK:')) {
            resultEl.className = 'ok';
            resultEl.textContent = msg.substring(3).trim() || 'Check-in successful.';
        } else if (msg.startsWith('ERROR:')) {
            resultEl.className = 'err';
            resultEl.textContent = msg.substring(6).trim() || 'Check-in failed.';
        } else {
            resultEl.className = 'err';
            resultEl.textContent = 'Unexpected server response.';
        }
    }).fail(function() {
        resultEl.className = 'err';
        resultEl.textContent = 'Could not reach server.';
    }).always(function() {
        btn.disabled = false;
    });
});
</script>
</body>
</html>
