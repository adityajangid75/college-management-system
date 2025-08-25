<?php
session_start();
require __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';

// Delete user
if(isset($_GET['delete_id'])){
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php");
    exit();
}

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY id DESC");

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Admin Dashboard</title>
<style>
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
.content h2{margin-bottom:15px;font-size:20px;color:#2c3e50}
table{width:100%;border-collapse:collapse;overflow-x:auto;display:block}
th, td{padding:10px;text-align:left;border-bottom:1px solid #ccc;white-space:nowrap}
th{background:#ecf0f1}
td.actions a{margin-right:5px;text-decoration:none;padding:5px 8px;border-radius:4px;color:#fff;font-size:13px}
a.edit{background:#3498db}
a.edit:hover{background:#2980b9}
a.delete{background:#e74c3c}
a.delete:hover{background:#c0392b}
.add-btn{display:inline-block;margin-bottom:15px;padding:8px 15px;background:#2ecc71;color:#fff;border-radius:5px;text-decoration:none}
.add-btn:hover{background:#27ae60}

/* Media Queries */
/* Large screens */
@media (max-width:1366px){.sidebar{width:220px}.main-content{margin-left:220px}.header h1{font-size:22px}.content h2{font-size:18px}}
/* Medium screens */
@media (max-width:1023px){.sidebar{width:200px}.main-content{margin-left:200px}.header h1{font-size:20px}.content h2{font-size:17px}table,th,td{font-size:13px;padding:8px}}
/* Small tablets */
@media (max-width:767px){.sidebar{width:180px}.main-content{margin-left:180px}.header h1{font-size:18px}.content h2{font-size:16px}table,th,td{font-size:12px;padding:6px}}
/* Mobile */
@media (max-width:599px){body{flex-direction:column}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px}.sidebar.active{left:0}.hamburger{display:block}.main-content{margin-left:0;padding:15px}.header h1{font-size:16px}.content h2{font-size:15px}table{display:block;overflow-x:auto;font-size:11px}}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="manage_faculty.php">🏫 Manage Faculty</a>
    <a href="#">🎓 Manage Students</a>
    <a href="#">📚 Courses</a>
    <a href="#">📊 Reports</a>

<a href="settings.php">⚙️ Settings</a>
<a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
<div class="header">
<h1>Welcome Admin, <span style="color:#2c3e50;"><?php echo htmlspecialchars($adminName ?? 'Admin'); ?></span> 👋</h1>
<button class="hamburger" onclick="toggleSidebar()">☰</button>
</div>

<div class="content">
<h2>Manage Users</h2>
<a href="add_user.php" class="add-btn">➕ Add User</a>
<table>
<tr>
<th>ID</th>
<th>Name</th>
<th>Username</th>
<th>Email</th>
<th>Age</th>
<th>Phone</th>
<th>City</th>
<th>Role</th>
<th>Actions</th>
</tr>
<?php while($user = $result->fetch_assoc()): ?>
<tr>
<td><?php echo htmlspecialchars($user['id'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['full_name'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['username'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['age'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['phone'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['city'] ?? ''); ?></td>
<td><?php echo htmlspecialchars($user['role'] ?? ''); ?></td>
<td class="actions">
<a href="edit_user.php?id=<?php echo $user['id']; ?>" class="edit">Edit</a>
<a href="manage_users.php?delete_id=<?php echo $user['id']; ?>" class="delete" onclick="return confirm('Are you sure to delete this user?')">Delete</a>
</td>
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
