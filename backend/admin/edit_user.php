<?php
session_start();
require __DIR__ . '/../../config/config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

$adminName = $_SESSION['username'] ?? 'Admin';
$error = '';
$success = '';

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: manage_users.php");
    exit();
}
$user = $result->fetch_assoc();
$stmt->close();

if (isset($_POST['submit'])) {
    $name = trim($_POST['name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $age = intval($_POST['age']);
    $phone = trim($_POST['phone']);
    $city = trim($_POST['city']);
    $password = $_POST['password'];

    if (!$name || !$username || !$email || !$age || !$phone || !$city) {
        $error = "All fields except password are required.";
    } else {
        // Update query
        if ($password) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, age=?, phone=?, city=?, password=? WHERE id=?");
            $stmt2->bind_param("sssiissi", $name, $username, $email, $age, $phone, $city, $hashed, $id);
        } else {
            $stmt2 = $conn->prepare("UPDATE users SET full_name=?, username=?, email=?, age=?, phone=?, city=? WHERE id=?");
            $stmt2->bind_param("sssiisi", $name, $username, $email, $age, $phone, $city, $id);
        }

        if ($stmt2->execute()) {
            $success = "User updated successfully!";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } else {
            $error = "Failed to update user.";
        }
        $stmt2->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User - Admin Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif}
body{display:flex;min-height:100vh;background:#f5f7fa}
.sidebar{width:250px;background:#2c3e50;color:#fff;display:flex;flex-direction:column;padding:20px 0;position:fixed;height:100%;transition:.3s;z-index:1000}
.sidebar h2{text-align:center;margin-bottom:30px;font-size:22px}
.sidebar a{color:#fff;text-decoration: none;padding:15px 20px;display:block;transition:.2s;font-size:16px}
.sidebar a:hover{background:#34495e}
.sidebar a.logout{margin-top:auto;background:#c0392b}
.sidebar a.logout:hover{background:#e74c3c}
.main-content{margin-left:250px;flex:1;padding:20px;transition:margin-left .3s;width:100%}
.header{background:#fff;padding:15px 20px;box-shadow:0 2px 5px rgba(0,0,0,.1);margin-bottom:20px;border-radius:8px;display:flex;justify-content:space-between;align-items:center}
.header h1{font-size:24px;color:#333}
.hamburger { display:none; font-size:26px; cursor:pointer; background:none; border:none; color:#2c3e50; }
.content{background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,.1)}
.content h2{margin-bottom:15px;font-size:20px;color:#2c3e50}
form{display:flex;flex-direction:column;gap:12px;max-width:450px}
input, select, button{padding:10px;border:1px solid #ccc;border-radius:5px;font-size:14px;width:100%}
button{background:#3498db;color:#fff;border:none;cursor:pointer}
button:hover{background:#2980b9}
.error{color:#e74c3c;font-size:14px}
.success{color:#2ecc71;font-size:14px}

/* Media Queries */
/* Large screens */
@media (max-width:1366px){.sidebar{width:220px}.main-content{margin-left:220px}.header h1{font-size:22px}.content h2{font-size:18px}}
/* Medium screens */
@media (max-width:1023px){.sidebar{width:200px}.main-content{margin-left:200px}.header h1{font-size:20px}.content h2{font-size:17px}.input,button{font-size:13px;padding:9px}}
/* Small tablets */
@media (max-width:767px){.sidebar{width:180px}.main-content{margin-left:180px}.header h1{font-size:18px}.content h2{font-size:16px}.input,button{font-size:12px;padding:8px}}
/* Mobile */
@media (max-width:599px){body{flex-direction:column}.sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px}.sidebar.active{left:0} .hamburger { display:block; }
    .main-content { margin-left:0; padding:15px; position: relative; }  .main-content{margin-left:0;padding:15px}.header h1{font-size:16px}.content h2{font-size:15px}.input,button{font-size:11px;padding:6px}}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
<h2>Admin Panel</h2>
<a href="admin_dashboard.php">🏠 Dashboard</a>
<a href="manage_users.php">👤 Manage Users</a>
<a href="manage_faculty.php">🏫 Manage Faculty</a>
    <a href="#">🎓 Manage Students</a>
    <a href="#">📚 Courses</a>
    <a href="#">📊 Reports</a>
<a href="settings.php">⚙️ Settings</a>
<a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
<div class="header">
<h1>Welcome Admin, <span style="color:#2c3e50;"><?php echo htmlspecialchars($adminName); ?></span> 👋</h1>
<button class="hamburger" onclick="toggleSidebar()">☰</button>
</div>

<div class="content">
<h2>Edit User</h2>
<?php if($error) echo "<p class='error'>$error</p>"; ?>
<?php if($success) echo "<p class='success'>$success</p>"; ?>
<form method="post">
<input type="text" name="name" placeholder="Name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
<input type="text" name="username" placeholder="Username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
<input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
<input type="number" name="age" placeholder="Age" value="<?php echo htmlspecialchars($user['age']); ?>" required>
<input type="text" name="phone" placeholder="Phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
<input type="text" name="city" placeholder="City" value="<?php echo htmlspecialchars($user['city']); ?>" required>
<input type="password" name="password" placeholder="Password (leave blank to keep old)">
<button type="submit" name="submit">Update User</button>
</form>
</div>
</div>

  <script>
    function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("active"); }
  </script>

</body>
</html>
