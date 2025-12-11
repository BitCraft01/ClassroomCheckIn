<?php
session_start();
require "db.php";

$id = $_SESSION['user_id'];
$fullname = $_POST['fullname'];

$sql = "UPDATE users SET fullname='$fullname' WHERE id=$id";

if (mysqli_query($conn, $sql)) {
    echo "Profile updated!";
} else {
    echo "Error updating profile.";
}
?>