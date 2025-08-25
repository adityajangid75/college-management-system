<?php
session_start();

// Agar user login nahi hai to login page par bhej do
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'faculty') {
    header("Location: ../auth/login.php");
    exit();
}

$username = $_SESSION['username'];
$role     = $_SESSION['role'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Faculty Dashboard</title>
  <style>
    * {
      margin:0; padding:0; box-sizing:border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    body {
      display:flex;
      min-height:100vh;
      background:#f5f7fa;
    }

    /* ========================
       Sidebar Default Styles
    =========================== */
    .sidebar {
      width:250px;
      background:#2c3e50;
      color:white;
      display:flex;
      flex-direction:column;
      padding:20px 0;
      position:fixed;
      height:100%;
      transition: all 0.3s ease;
      z-index:1000;
    }

    .sidebar h2 {
      text-align:center;
      margin-bottom:30px;
      font-size:22px;
    }

    .sidebar a {
      text-decoration:none;
      color:white;
      padding:15px 20px;
      display:block;
      transition:background 0.2s;
      font-size:16px;
    }

    .sidebar a:hover {
      background:#34495e;
    }

    .sidebar a.logout {
      margin-top:auto;
      background:#c0392b;
    }

    .sidebar a.logout:hover {
      background:#e74c3c;
    }

    .main-content {
      margin-left:250px;
      flex:1;
      padding:20px;
      transition:margin-left 0.3s ease;
      width:100%;
    }

    .header {
      background:white;
      padding:15px 20px;
      box-shadow:0 2px 5px rgba(0,0,0,0.1);
      margin-bottom:20px;
      border-radius:8px;
      display:flex;
      justify-content:space-between;
      align-items:center;
    }

    .header h1 {
      font-size:24px;
      color:#333;
    }

    /* Hamburger icon */
    .hamburger {
      display:none;
      font-size:26px;
      cursor:pointer;
      background:none;
      border:none;
      color:#2c3e50;
    }

    .content {
      background:white;
      padding:20px;
      border-radius:8px;
      box-shadow:0 2px 6px rgba(0,0,0,0.1);
    }

    .content h2 {
      margin-bottom:10px;
      font-size:20px;
      color:#2c3e50;
    }

    .content p {
      font-size:16px;
      line-height:1.6;
      color:#555;
    }

    /* ========================
       Responsive Media Queries
    =========================== */

    /* Laptop (1024px - 1366px) */
    @media (max-width:1366px) {
      .sidebar { width:220px; }
      .main-content { margin-left:220px; }
      .header h1 { font-size:22px; }
    }

    /* Tablet Landscape (768px - 1023px) */
    @media (max-width:1023px) {
      .sidebar { width:200px; }
      .main-content { margin-left:200px; }
      .header h1 { font-size:20px; }
      .content h2 { font-size:18px; }
    }

    /* Tablet Portrait (600px - 767px) */
    @media (max-width:767px) {
      .sidebar { width:180px; }
      .main-content { margin-left:180px; }
      .sidebar a { font-size:14px; padding:12px 15px; }
      .header h1 { font-size:18px; }
      .content h2 { font-size:17px; }
      .content p { font-size:14px; }
    }

    /* Mobile (max-width:599px) */
    @media (max-width:599px){
      body { flex-direction:column; }
      .sidebar {
        position:fixed;
        left:-250px;
        top:0;
        height:100%;
        width:250px;
      }
      .sidebar.active {
        left:0;
      }
      .hamburger {
        display:block;
      }
      .main-content {
        margin-left:0;
        padding:15px;
      }
      .header h1 { font-size:16px; }
      .content h2 { font-size:16px; }
      .content p { font-size:13px; }
    }
  </style>
</head>
<body>

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <h2>Faculty Panel</h2>
    <a href="#">🧑‍🏫 Profile</a>
    <a href="#">👨‍🎓 My Students</a>
    <a href="#">📝 Attendance</a>
    <a href="#">🎯 Marks/Exams</a>
    <a href="#">📚 Subjects & Materials</a>
    <a href="#">📢 Notices</a>
    <a href="#">📊 Reports</a>
    <a href="../auth/logout.php" class="logout">🚪 Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main-content" id="main">
    <div class="header">
      <h1>Welcome, <?php echo htmlspecialchars($username); ?> (<?php echo $role; ?>)</h1>
      <button class="hamburger" onclick="toggleSidebar()">☰</button>
    </div>
    <div class="content">
      <h2>Dashboard Overview</h2>
      <p>
        Yahan pe tumhari <b>faculty related information</b> aayegi:<br>
        • Students list<br>
        • Attendance records<br>
        • Marks and exam results<br>
        • Subjects & uploaded materials<br>
        • Important notices & reports
      </p>
    </div>
  </div>

  <script>
    function toggleSidebar(){
      document.getElementById("sidebar").classList.toggle("active");
    }
  </script>

</body>
</html>
