<?php
session_start();
include(__DIR__ . "/../config/config.php");

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

// Get student ID
if(!isset($_GET['id'])){
    header("Location: students.php");
    exit();
}
$id = $_GET['id'];

// Delete student
$stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='student'");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

header("Location: students.php");
exit();
?>
