<?php
session_start();
require "db.php";

$email = $_POST['email'];
$password = md5($_POST['password']);

$sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 1) {
    $row = mysqli_fetch_assoc($result);

    $_SESSION['user_id'] = $row['id'];
    $_SESSION['fullname'] = $row['fullname'];

    echo "success";
} else {
    echo "Invalid email or password!";
}
?>