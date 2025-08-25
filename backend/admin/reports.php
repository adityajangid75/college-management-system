<?php
session_start();

// only admins allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';

// DB connection
require __DIR__ . '/../../config/config.php';

// Fetch counts for reports
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'] ?? 0;
$totalFaculty = $conn->query("SELECT COUNT(*) as count FROM faculty")->fetch_assoc()['count'] ?? 0;
$totalStudents = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'] ?? 0;

// Course-wise student count
$courseData = $conn->query("
    SELECT c.name, COUNT(s.id) as student_count
    FROM courses c
    LEFT JOIN students s ON s.course = c.id
    GROUP BY c.id
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reports - Admin Dashboard</title>
  <style>
    /* === Use same CSS from your manage_faculty.php === */
    *{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
    body{display:flex;min-height:100vh;background:#f5f7fa}
    .sidebar{width:250px;background:#2c3e50;color:#fff;display:flex;flex-direction:column;padding:20px 0;position:fixed;height:100%;transition:.3s;z-index:1000}
    .sidebar h2{text-align:center;margin-bottom:30px;font-size:22px}
    .sidebar a{text-decoration:none;color:#fff;padding:15px 20px;display:block;transition:.2s;font-size:16px}
    .sidebar a:hover{background:#34495e}
    .sidebar a.logout{margin-top:auto;background:#c0392b}
    .sidebar a.logout:hover{background:#e74c3c}
    .main-content{margin-left:250px;flex:1;padding:20px;transition:margin-left .3s;width:100%}
    .header{background:#fff;padding:15px 20px;box-shadow:0 2px 5px rgba(0,0,0,.1);margin-bottom:20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
    .header h1{font-size:24px;color:#333}
    .hamburger{display:none;font-size:26px;cursor:pointer;background:none;border:none;color:#2c3e50}
    .content{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
    .content h2{margin-bottom:10px;font-size:20px;color:#2c3e50}
    .content p{font-size:16px;line-height:1.6;color:#555}
    .content table{width:100%;border-collapse:collapse;table-layout:fixed}
    .content th, .content td{padding:10px;text-align:left;border-bottom:1px solid #ddd;word-wrap:break-word}
    .content td:last-child{width:150px;display:flex;gap:5px}
    .btn{flex:1;text-align:center}
    @media (max-width:1366px){.sidebar{width:220px}.main-content{margin-left:220px}.header h1{font-size:22px}}
    @media (max-width:1023px){.sidebar{width:200px}.main-content{margin-left:200px}.header h1{font-size:20px}.content h2{font-size:18px}}
    @media (max-width:767px){.sidebar{width:180px}.main-content{margin-left:180px}.sidebar a{font-size:14px;padding:12px 15px}.header h1{font-size:18px}.content h2{font-size:17px}.content p{font-size:14px}}
    @media (max-width:599px){body{flex-direction:column}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px}.sidebar.active{left:0}.hamburger{display:block}.main-content{margin-left:0;padding:15px}.header h1{font-size:16px}.content h2{font-size:16px}.content p{font-size:13px}}
  </style>
</head>
<body>
  <div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
    <a href="manage_users.php">👤 Manage Users</a>
    <a href="manage_faculty.php">🏫 Manage Faculty</a>
    <a href="manage_student.php">🎓 Manage Students</a>
    <a href="manage_course.php">📚 Courses</a>
    <a href="settings.php">⚙️ Settings</a>
    <a href="../auth/logout.php" class="logout">🚪 Logout</a>
  </div>
  <div class="main-content" id="main">
    <div class="header">
      <h1>Welcome Admin, <span style="color:#2c3e50;"><?php echo htmlspecialchars($adminName); ?></span> 👋</h1>
      <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('active')">☰</button>
    </div>
    <div class="content">
      <h2>Reports Overview</h2>
      

      <table>
        <tr>
          <th>Report</th>
          <th>Value</th>
        </tr>
        <tr>
          <td>Total Users</td>
          <td><?php echo $totalUsers; ?></td>
        </tr>
        <tr>
          <td>Total Faculty</td>
          <td><?php echo $totalFaculty; ?></td>
        </tr>
        <tr>
          <td>Total Students</td>
          <td><?php echo $totalStudents; ?></td>
        </tr>
      </table>

      <h2 style="margin-top:20px">Course-wise Student Count</h2>
      <table>
        <tr>
          <th>Course Name</th>
          <th>Students Enrolled</th>
        </tr>
        <?php while($row = $courseData->fetch_assoc()): ?>
        <tr>
          <td><?php echo htmlspecialchars($row['name']); ?></td>
          <td><?php echo $row['student_count']; ?></td>
        </tr>
        <?php endwhile; ?>
      </table>
    </div>
  </div>

  <script>
    function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("active"); }
  </script>
</body>
</html>
