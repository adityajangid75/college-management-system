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

// Fetch student data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='student'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if(!$student){
    echo "Student not found!";
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $city = $_POST['city'];

    // If password is filled, hash it
    if(!empty($_POST['password'])){
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, age=?, phone=?, city=?, password=? WHERE id=?");
        $stmt->bind_param("sssisssi", $full_name, $username, $email, $age, $phone, $city, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, age=?, phone=?, city=? WHERE id=?");
        $stmt->bind_param("sssissi", $full_name, $username, $email, $age, $phone, $city, $id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: students.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Student</title>
<style>
/* =======================
   Universal Styles
========================== */
* {
    margin:0; padding:0; box-sizing:border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
body {
    background-color: #f4f6f8;
    padding: 20px;
}

/* Header */
.header {
    text-align: center;
    margin-bottom: 30px;
}
.header h1 {
    color: #4CAF50;
    font-size: 28px;
}

/* Add Student Form */
.add-student-form {
    background-color: #fff;
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}
.add-student-form h2 {
    margin-bottom: 20px;
    color: #333;
}
.add-student-form input {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.add-student-form button {
    width: 100%;
    padding: 14px;
    margin-top: 12px;
    background-color: #4CAF50;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.add-student-form button:hover {
    background-color: #45a049;
}

/* Students Table */
.table-wrapper {
    overflow-x: auto; /* Horizontal scroll for mobile */
}
.students-table {
    width: 100%;
    border-collapse: collapse;
    background-color: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 6px 20px rgba(0,0,0,0.1);
}
.students-table th, .students-table td {
    padding: 12px 15px;
    text-align: left;
}
.students-table th {
    background-color: #4CAF50;
    color: #fff;
}
.students-table tr:nth-child(even) {
    background-color: #f2f2f2;
}
.students-table tr:hover {
    background-color: #e0f7e0;
}

/* Buttons */
.action-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    cursor: pointer;
    font-size: 14px;
}
.edit-btn { background-color: #2196F3; color: #fff; }
.delete-btn { background-color: #f44336; color: #fff; }
td .btn-group {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}

/* =======================
   Media Queries
========================== */

/* Laptop 992px-1199px */
@media (max-width: 1199px) and (min-width: 992px){
    .add-student-form { padding: 20px; }
    .add-student-form h2 { font-size: 22px; }
    .students-table th, .students-table td { padding: 10px 12px; }
}

/* Tablet Landscape 768px-991px */
@media (max-width: 991px) and (min-width: 768px){
    .add-student-form { padding: 18px; }
    .add-student-form h2 { font-size: 20px; }
    .students-table th, .students-table td { padding: 9px 10px; }
}

/* Tablet Portrait 600px-767px */
@media (max-width: 767px) and (min-width: 600px){
    .add-student-form { padding: 16px; }
    .add-student-form h2 { font-size: 18px; }
    .students-table th, .students-table td { padding: 8px 9px; font-size: 14px; }
}

/* Mobile <600px */
@media (max-width: 599px){
    body { padding: 10px; }
    .header h1 { font-size: 22px; }
    .add-student-form { padding: 14px; }
    .add-student-form h2 { font-size: 16px; }
    .add-student-form input, .add-student-form button { padding: 10px; font-size: 14px; }

    /* Table responsive scroll */
    .table-wrapper { overflow-x: auto; }
    .students-table th, .students-table td { padding: 6px 8px; font-size: 12px; }
    td .btn-group { flex-wrap: wrap; gap: 4px; }
}
</style>

</head>
<body>
<div class="header"><h1>Edit Student</h1></div>

<div class="add-student-form">
    <form action="" method="POST">
    <input type="text" name="full_name" value="<?= isset($student['full_name']) ? htmlspecialchars($student['full_name']) : ''; ?>" required>
    <input type="text" name="username" value="<?= isset($student['username']) ? htmlspecialchars($student['username']) : ''; ?>" required>
    <input type="email" name="email" value="<?= isset($student['email']) ? htmlspecialchars($student['email']) : ''; ?>" required>
    <input type="number" name="age" value="<?= isset($student['age']) ? $student['age'] : ''; ?>" required>
    <input type="text" name="phone" value="<?= isset($student['phone']) ? htmlspecialchars($student['phone']) : ''; ?>" required>
    <input type="text" name="city" value="<?= isset($student['city']) ? htmlspecialchars($student['city']) : ''; ?>" required>
    <input type="password" name="password" placeholder="Leave blank to keep current password">
    <button type="submit">Update Student</button>
</form>
</div>
</body>
</html>
