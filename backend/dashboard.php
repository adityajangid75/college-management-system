<?php
session_start();
include(__DIR__ . "/../config/config.php");

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: auth/login.php");
    exit();
}

// Fetch total counts from DB
$students_count = $conn->query("SELECT COUNT(*) as total FROM students")->fetch_assoc()['total'];
$faculty_count  = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='faculty'")->fetch_assoc()['total'];
$courses_count  = $conn->query("SELECT COUNT(*) as total FROM courses")->fetch_assoc()['total'] ?? 0;
//$sessions_count = $conn->query("SELECT COUNT(*) as total FROM sessions")->fetch_assoc()['total'] ?? 0;
$sessions_count = 0; // Temporary
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard - College Management</title>
<style>
/* =======================
   Desktop Styles (Main)
========================== */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
body { background-color: #f4f6f8; }

/* Sticky Navbar */
.navbar {
    position: sticky;
    top: 0;
    background-color: #4CAF50;
    padding: 20px 40px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    z-index: 1000;
}
.navbar h1 { font-size: 28px; }
.navbar .user-info { font-size: 18px; }

/* Container */
.container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }

/* Stats Cards */
.cards { display: flex; flex-wrap: wrap; gap: 25px; margin-bottom: 30px; }
.card { background-color: #fff; flex: 1; min-width: 220px; padding: 30px; border-radius: 12px; box-shadow: 0 6px 20px rgba(0,0,0,0.1); transition: transform 0.3s ease; }
.card:hover { transform: translateY(-5px); }
.card h3 { color: #333; margin-bottom: 15px; font-size: 20px; }
.card p { font-size: 28px; font-weight: bold; color: #4CAF50; }

/* Quick Links */
.quick-links { display: flex; flex-wrap: wrap; gap: 20px; }
.quick-link { background-color: #4CAF50;text-decoration: none; color: #fff; padding: 18px; border-radius: 12px; text-align: center; flex: 1; min-width: 160px; transition: background 0.3s ease; cursor: pointer; font-size: 16px; }
.quick-link:hover { background-color: #45a049; }

/* =======================
   Laptop (992px - 1199px)
========================== */
@media (max-width: 1199px) and (min-width: 992px) {
    .navbar { padding: 18px 35px; }
    .navbar h1 { font-size: 26px; }
    .navbar .user-info { font-size: 16px; }
    .card { padding: 25px; min-width: 200px; }
    .card h3 { font-size: 18px; }
    .card p { font-size: 26px; }
    .quick-link { padding: 16px; font-size: 15px; min-width: 150px; }
}

/* =======================
   Tablet Landscape (768px - 991px)
========================== */
@media (max-width: 991px) and (min-width: 768px) {
    .navbar { padding: 15px 25px; flex-direction: column; align-items: flex-start; gap: 10px; }
    .navbar h1 { font-size: 22px; }
    .navbar .user-info { font-size: 14px; }
    .cards { flex-direction: column; gap: 20px; }
    .card { padding: 22px; }
    .card h3 { font-size: 17px; }
    .card p { font-size: 24px; }
    .quick-links { flex-direction: column; gap: 15px; }
    .quick-link { padding: 14px; font-size: 14px; }
}

/* =======================
   Tablet Portrait (600px - 767px)
========================== */
@media (max-width: 767px) and (min-width: 600px) {
    .navbar h1 { font-size: 20px; }
    .navbar .user-info { font-size: 13px; }
    .card h3 { font-size: 16px; }
    .card p { font-size: 22px; }
    .quick-link { padding: 12px; font-size: 13px; }
}

/* =======================
   Mobile (<600px)
========================== */
@media (max-width: 599px) {
    .navbar { padding: 12px 15px; flex-direction: column; align-items: flex-start; gap: 8px; }
    .navbar h1 { font-size: 18px; }
    .navbar .user-info { font-size: 12px; }
    .cards { flex-direction: column; gap: 15px; }
    .card { padding: 18px; }
    .card h3 { font-size: 15px; }
    .card p { font-size: 20px; }
    .quick-links { flex-direction: column; gap: 12px; }
    .quick-link { padding: 10px; font-size: 12px; }
}
</style>
</head>
<body>
    <div class="navbar">
        <h1>College Management Dashboard</h1>
        <div class="user-info">Welcome, <?php echo $_SESSION['username'] . " (" . $_SESSION['role'] . ")"; ?></div>
    </div>

    <div class="container">
        <!-- Stats Cards -->
        <div class="cards">
            <div class="card">
                <h3>Total Students</h3>
                <p><?php echo $students_count; ?></p>
            </div>
            <div class="card">
                <h3>Total Faculty</h3>
                <p><?php echo $faculty_count; ?></p>
            </div>
            <div class="card">
                <h3>Total Courses</h3>
                <p><?php echo $courses_count; ?></p>
            </div>
            <div class="card">
                <h3>Active Sessions</h3>
                <p><?php echo $sessions_count; ?></p>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="quick-links">
            <a href="students.php" class="quick-link">Manage Students</a>
            <div class="quick-link">Add Faculty</div>
            <div class="quick-link">Manage Courses</div>
            <div class="quick-link">View Reports</div>
        </div>
    </div>
</body>
</html>
