<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your Profile</title>
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

<div class="navbar">
    <div style="color:white; font-weight:bold;">Classroom Check-In</div>

    <div>
        <a href="main.php">Home</a>
        <a href="profile.php">Profile</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="container">
    <h2>Your Profile</h2>

    <p><strong>Name:</strong> <?php echo $_SESSION['user_name']; ?></p>
    <p><strong>Email:</strong> <?php echo $_SESSION['user_email']; ?></p>

    <form action="process/update_profile.php" method="POST">
        <label>Change Name</label>
        <input type="text" name="new_name" required>

        <label>Change Password</label>
        <input type="password" name="new_password">

        <button class="btn" type="submit">Update Profile</button>
    </form>
</div>

</body>
</html>