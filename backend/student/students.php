<?php
session_start();
include(__DIR__ . "/../config/config.php");

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

// Handle form submission
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $full_name = $_POST['name'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $age = $_POST['age'];
    $phone = $_POST['phone'];
    $city = $_POST['city'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (full_name, username, email, age, phone, city, password, role) VALUES (?, ?, ?, ?, ?, ?, ?, 'student')");
    $stmt->bind_param("sssisss", $full_name, $username, $email, $age, $phone, $city, $password);
    $stmt->execute();
    $stmt->close();
}

// Fetch students
$result = $conn->query("SELECT * FROM users WHERE role='student'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students - College Management</title>
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
    padd	ing: 25px;
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

<div class="header">
    <h1>Students Management</h1>
</div>

<!-- Add Student Form -->
<div class="add-student-form">
    <h2>Add Student</h2>
    <form action="" method="POST">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="text" name="username" placeholder="Username" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="number" name="age" placeholder="Age" required>
        <input type="text" name="phone" placeholder="Phone" required>
        <input type="text" name="city" placeholder="City" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Add Student</button>
    </form>
</div>

<!-- Students Table -->
<div class="table-wrapper">
<table class="students-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Full Name</th>
            <th>Username</th>
            <th>Email</th>
            <th>Age</th>
            <th>Phone</th>
            <th>City</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id']; ?></td>
            <td><?= $row['full_name']; ?></td>
            <td><?= $row['username']; ?></td>
            <td><?= $row['email']; ?></td>
            <td><?= $row['age']; ?></td>
            <td><?= $row['phone']; ?></td>
            <td><?= $row['city']; ?></td>
            <td>
                <div class="btn-group">
                    <a href="edit_student.php?id=<?= $row['id']; ?>"><button class="action-btn edit-btn">Edit</button></a>
                    <a href="delete_student.php?id=<?= $row['id']; ?>" onclick="return confirm('Are you sure?')"><button class="action-btn delete-btn">Delete</button></a>
                </div>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>

</body>
</html>
