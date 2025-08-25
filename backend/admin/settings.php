<?php
session_start();

// only admins allowed
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

require __DIR__ . '/../../config/config.php'; // DB connection

$adminId = $_SESSION['user_id'];
$adminName = $_SESSION['username'] ?? 'Admin';

// Fetch current admin data
$adminData = $conn->query("SELECT * FROM users WHERE id = $adminId AND role='admin'")->fetch_assoc();

// Initialize messages
$profileMsg = '';
$passwordMsg = '';

// Handle profile update
if (isset($_POST['update_profile'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];

    // Handle profile picture
    $profile_pic = $adminData['profile_pic'];
    if (!empty($_FILES['profile_pic']['name'])) {
        $ext = pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION);
        $newName = 'admin_' . $adminId . '.' . $ext;
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], __DIR__ . '/uploads/' . $newName);
        $profile_pic = 'uploads/' . $newName;
    }

    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, full_name=?, profile_pic=? WHERE id=?");
    $stmt->bind_param("ssssi", $username, $email, $full_name, $profile_pic, $adminId);
    if ($stmt->execute()) {
        $profileMsg = "Profile updated successfully!";
        $_SESSION['username'] = $username; // update session
        $adminData = $conn->query("SELECT * FROM users WHERE id = $adminId")->fetch_assoc(); // refresh
    } else {
        $profileMsg = "Failed to update profile.";
    }
}

// Handle password change
if (isset($_POST['update_password'])) {
    $current = $_POST['current_password'];
    $new = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if (password_verify($current, $adminData['password'])) {
        if ($new === $confirm) {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->bind_param("si", $newHash, $adminId);
            if ($stmt->execute()) {
                $passwordMsg = "Password updated successfully!";
            } else {
                $passwordMsg = "Failed to update password.";
            }
        } else {
            $passwordMsg = "New password and confirm password do not match.";
        }
    } else {
        $passwordMsg = "Current password is incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Admin Settings</title>
<style>
/* ---- COPY MANAGE FACULTY CSS HERE ---- */
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
.content td:last-child { width: 150px; display: flex; gap: 5px;}
.btn { flex:1; text-align:center;}
.content table { width: 100%; border-collapse: collapse; table-layout: fixed; }
.content th, .content td { word-wrap: break-word; }
input, button { width:100%; padding:8px; margin-bottom:10px; border-radius:5px; border:1px solid #ccc; }
button { background:#4CAF50; color:white; border:none; cursor:pointer; }
button:hover { background:#45a049; }

/* Media Queries (all copied) */
@media (max-width:1366px){.sidebar{width:220px}.main-content{margin-left:220px}.header h1{font-size:22px}}
@media (max-width:1023px){.sidebar{width:200px}.main-content{margin-left:200px}.header h1{font-size:20px}.content h2{font-size:18px}}
@media (max-width:767px){.sidebar{width:180px}.main-content{margin-left:180px}.sidebar a{font-size:14px;padding:12px 15px}.header h1{font-size:18px}.content h2{font-size:17px}.content p{font-size:14px}}
@media (max-width:599px){
  body{flex-direction:column}
  .sidebar{position:fixed;left:-250px;top:0;height:100%;width:250px}
  .sidebar.active{left:0}
  .hamburger{display:block}
  .main-content{margin-left:0;padding:15px}
  .header h1{font-size:16px}
  .content h2{font-size:16px}
  .content p{font-size:13px}
}
</style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <h2>Admin Panel</h2>
    <a href="manage_users.php">👤 Manage Users</a>
    <a href="manage_faculty.php">🏫 Manage Faculty</a>
    <a href="manage_student.php">🎓 Manage Students</a>
    <a href="manage_course.php">📚 Courses</a>
    <a href="reports.php">📊 Reports</a>
    <a href="settings.php">⚙️ Settings</a>
    <a href="../auth/logout.php" class="logout">🚪 Logout</a>
</div>

<div class="main-content" id="main">
    <div class="header">
        <h1>Welcome Admin, <span style="color:#2c3e50;"><?= htmlspecialchars($adminName); ?></span> 👋</h1>
        <button class="hamburger" onclick="document.getElementById('sidebar').classList.toggle('active')">☰</button>
    </div>

    <div class="content">
        <h2>Settings</h2>
        <!-- Profile Update -->
        <h3>Update Profile</h3>
        <?php if($profileMsg) echo "<p style='color:green;'>$profileMsg</p>"; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="username" value="<?= htmlspecialchars($adminData['username'] ?? ''); ?>" required>
<input type="email" name="email" value="<?= htmlspecialchars($adminData['email'] ?? ''); ?>" required>
<input type="text" name="full_name" value="<?= htmlspecialchars($adminData['full_name'] ?? ''); ?>" required>
            <input type="file" name="profile_pic">
            <button type="submit" name="update_profile">Update Profile</button>
        </form>

        <!-- Change Password -->
        <h3>Change Password</h3>
        <?php if($passwordMsg) echo "<p style='color:green;'>$passwordMsg</p>"; ?>
        <form method="POST">
            <input type="password" name="current_password" placeholder="Current Password" required>
            <input type="password" name="new_password" placeholder="New Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
            <button type="submit" name="update_password">Change Password</button>
        </form>
    </div>
</div>

<script>
function toggleSidebar(){ document.getElementById("sidebar").classList.toggle("active"); }
</script>
</body>
</html>
